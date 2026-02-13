<?php
/**
 * Payroll API Controller
 */

namespace App\Controllers\Api;

use App\Core\Controller;

class PayrollController extends Controller
{
    /**
     * List payroll periods
     */
    public function periods(): array
    {
        $params = $this->getQueryParams();
        $status = $params['status'] ?? null;

        $tenantId = $this->db->getTenantId();
        $conditions = ["tenant_id = ?"];
        $bindings = [$tenantId];

        if ($status) {
            $conditions[] = "status = ?";
            $bindings[] = $status;
        }

        $where = implode(' AND ', $conditions);

        $periods = $this->db->fetchAll(
            "SELECT * FROM payroll_periods WHERE {$where} ORDER BY period_start DESC",
            $bindings
        );

        return $this->success($periods);
    }

    /**
     * Create a payroll period
     */
    public function createPeriod(): array
    {
        $data = $this->validate([
            'period_start' => 'required',
            'period_end' => 'required',
        ]);

        $input = $this->getJsonInput();

        $periodId = $this->db->insert('payroll_periods', [
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'pay_date' => $input['pay_date'] ?? null,
            'notes' => $input['notes'] ?? null,
            'status' => 'open',
        ]);

        $period = $this->db->fetch("SELECT * FROM payroll_periods WHERE id = ?", [$periodId]);

        return $this->success($period, 'Payroll period created', 201);
    }

    /**
     * Get payroll period with records
     */
    public function showPeriod(string $id): array
    {
        $period = $this->db->fetch(
            "SELECT * FROM payroll_periods WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$period) {
            $this->error('Payroll period not found', 404);
        }

        $records = $this->db->fetchAll(
            "SELECT 
                pr.*,
                e.first_name,
                e.last_name,
                e.job_title,
                e.payment_type
             FROM payroll_records pr
             JOIN employees e ON pr.employee_id = e.id
             WHERE pr.payroll_period_id = ?
             ORDER BY e.last_name, e.first_name",
            [$id]
        );

        $period['records'] = $records;

        return $this->success($period);
    }

    /**
     * Process payroll for a period
     */
    public function process(string $id): array
    {
        $period = $this->db->fetch(
            "SELECT * FROM payroll_periods WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$period) {
            $this->error('Payroll period not found', 404);
        }

        if ($period['status'] === 'completed') {
            $this->error('Payroll already processed', 422);
        }

        $tenantId = $this->db->getTenantId();
        $user = $_SESSION['user'];

        // Get all active employees
        $employees = $this->db->fetchAll(
            "SELECT * FROM employees WHERE tenant_id = ? AND status = 'active'",
            [$tenantId]
        );

        $totalGross = 0;
        $totalDeductions = 0;
        $totalNet = 0;

        $this->db->beginTransaction();

        try {
            foreach ($employees as $employee) {
                // Calculate pay based on payment type
                $payData = $this->calculateEmployeePay(
                    $employee,
                    $period['period_start'],
                    $period['period_end']
                );

                // Create or update payroll record
                $existingRecord = $this->db->fetch(
                    "SELECT id FROM payroll_records WHERE payroll_period_id = ? AND employee_id = ?",
                    [$id, $employee['id']]
                );

                if ($existingRecord) {
                    $this->db->update('payroll_records', $payData, ['id' => $existingRecord['id']]);
                } else {
                    $payData['tenant_id'] = $tenantId;
                    $payData['payroll_period_id'] = $id;
                    $payData['employee_id'] = $employee['id'];
                    $this->db->insert('payroll_records', $payData);
                }

                $totalGross += $payData['gross_pay'];
                $totalDeductions += $payData['tax_deduction'] + $payData['insurance_deduction'] + $payData['other_deductions'];
                $totalNet += $payData['net_pay'];
            }

            // Update period totals and status
            $this->db->update('payroll_periods', [
                'total_gross' => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_net' => $totalNet,
                'status' => 'processing',
                'processed_by' => $user['id'],
                'processed_at' => date('Y-m-d H:i:s'),
            ], ['id' => $id]);

            $this->db->commit();

            $updated = $this->db->fetch("SELECT * FROM payroll_periods WHERE id = ?", [$id]);

            return $this->success($updated, 'Payroll processed successfully');

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Calculate pay for an employee
     */
    private function calculateEmployeePay(array $employee, string $startDate, string $endDate): array
    {
        // Get time logs for period
        $timeLogs = $this->db->fetch(
            "SELECT 
                COALESCE(SUM(CASE WHEN is_overtime = 0 THEN hours ELSE 0 END), 0) as regular_hours,
                COALESCE(SUM(CASE WHEN is_overtime = 1 THEN hours ELSE 0 END), 0) as overtime_hours
             FROM time_logs 
             WHERE employee_id = ? AND log_date BETWEEN ? AND ?",
            [$employee['id'], $startDate, $endDate]
        );

        $regularHours = (float) $timeLogs['regular_hours'];
        $overtimeHours = (float) $timeLogs['overtime_hours'];
        $regularPay = 0;
        $overtimePay = 0;
        $commissionPay = 0;

        switch ($employee['payment_type']) {
            case 'hourly':
                $regularPay = $regularHours * ($employee['hourly_rate'] ?? 0);
                $overtimePay = $overtimeHours * ($employee['hourly_rate'] ?? 0) * ($employee['overtime_multiplier'] ?? 1.5);
                break;

            case 'daily':
                // Count unique work days
                $workDays = $this->db->fetch(
                    "SELECT COUNT(DISTINCT log_date) as days FROM time_logs 
                     WHERE employee_id = ? AND log_date BETWEEN ? AND ?",
                    [$employee['id'], $startDate, $endDate]
                );
                $regularPay = (int) $workDays['days'] * ($employee['daily_rate'] ?? 0);
                break;

            case 'salary':
                // Calculate prorated salary based on period
                $daysInPeriod = (strtotime($endDate) - strtotime($startDate)) / 86400 + 1;
                $regularPay = ($employee['salary'] ?? 0) / 30 * $daysInPeriod; // Approximate
                break;

            case 'project':
                // Project-based pay is typically set manually per project completion
                // For now, check if any projects were completed in this period
                $completedProjects = $this->db->fetchAll(
                    "SELECT budget FROM projects 
                     WHERE manager_id = (SELECT user_id FROM employees WHERE id = ?)
                     AND status = 'completed'
                     AND updated_at BETWEEN ? AND ?",
                    [$employee['id'], $startDate, $endDate]
                );
                foreach ($completedProjects as $project) {
                    $regularPay += $project['budget'] ?? 0;
                }
                break;

            case 'commission':
                $rate = ($employee['commission_rate'] ?? 0) / 100;
                // Calculate based on completed invoices
                $invoiceTotal = $this->db->fetch(
                    "SELECT COALESCE(SUM(paid_amount), 0) as total 
                     FROM invoices 
                     WHERE paid_at BETWEEN ? AND ?
                     AND tenant_id = ?",
                    [$startDate, $endDate, $this->db->getTenantId()]
                );
                $commissionPay = $invoiceTotal['total'] * $rate;
                break;
        }

        $grossPay = $regularPay + $overtimePay + $commissionPay;

        // Calculate deductions (simplified - in production these would be configurable)
        $taxRate = 0.22; // 22% tax
        $taxDeduction = $grossPay * $taxRate;
        $insuranceDeduction = 150; // Fixed insurance
        $otherDeductions = 0;

        $netPay = $grossPay - $taxDeduction - $insuranceDeduction - $otherDeductions;

        return [
            'regular_hours' => $regularHours,
            'overtime_hours' => $overtimeHours,
            'regular_pay' => round($regularPay, 2),
            'overtime_pay' => round($overtimePay, 2),
            'commission_pay' => round($commissionPay, 2),
            'bonus' => 0,
            'gross_pay' => round($grossPay, 2),
            'tax_deduction' => round($taxDeduction, 2),
            'insurance_deduction' => $insuranceDeduction,
            'other_deductions' => $otherDeductions,
            'net_pay' => round($netPay, 2),
            'status' => 'pending',
        ];
    }

    /**
     * List payroll records
     */
    public function records(): array
    {
        $params = $this->getQueryParams();
        $periodId = $params['period_id'] ?? null;
        $employeeId = $params['employee_id'] ?? null;

        $tenantId = $this->db->getTenantId();
        $conditions = ["pr.tenant_id = ?"];
        $bindings = [$tenantId];

        if ($periodId) {
            $conditions[] = "pr.payroll_period_id = ?";
            $bindings[] = $periodId;
        }

        if ($employeeId) {
            $conditions[] = "pr.employee_id = ?";
            $bindings[] = $employeeId;
        }

        $where = implode(' AND ', $conditions);

        $records = $this->db->fetchAll(
            "SELECT 
                pr.*,
                e.first_name,
                e.last_name,
                e.job_title,
                pp.period_start,
                pp.period_end
             FROM payroll_records pr
             JOIN employees e ON pr.employee_id = e.id
             JOIN payroll_periods pp ON pr.payroll_period_id = pp.id
             WHERE {$where}
             ORDER BY pp.period_start DESC, e.last_name",
            $bindings
        );

        return $this->success($records);
    }

    /**
     * Update payroll record
     */
    public function updateRecord(string $id): array
    {
        $record = $this->db->fetch(
            "SELECT pr.*, pp.status as period_status
             FROM payroll_records pr
             JOIN payroll_periods pp ON pr.payroll_period_id = pp.id
             WHERE pr.id = ? AND pr.tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$record) {
            $this->error('Payroll record not found', 404);
        }

        if ($record['period_status'] === 'completed') {
            $this->error('Cannot modify completed payroll', 422);
        }

        $input = $this->getJsonInput();
        $allowedFields = [
            'bonus',
            'other_deductions',
            'notes'
        ];

        $updateData = [];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateData[$field] = $input[$field];
            }
        }

        // Recalculate net pay if deductions changed
        if (isset($input['bonus']) || isset($input['other_deductions'])) {
            $grossPay = $record['gross_pay'] + ($updateData['bonus'] ?? $record['bonus']);
            $deductions = $record['tax_deduction'] + $record['insurance_deduction'] + ($updateData['other_deductions'] ?? $record['other_deductions']);
            $updateData['net_pay'] = $grossPay - $deductions;
        }

        if (!empty($updateData)) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('payroll_records', $updateData, ['id' => $id]);
        }

        $updated = $this->db->fetch("SELECT * FROM payroll_records WHERE id = ?", [$id]);

        return $this->success($updated, 'Payroll record updated');
    }

    /**
     * Mark a payroll record as paid
     */
    public function payRecord(string $id): array
    {
        $record = $this->db->fetch(
            "SELECT * FROM payroll_records WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$record) {
            $this->error('Payroll record not found', 404);
        }

        if ($record['status'] === 'paid') {
            $this->error('Record already paid', 422);
        }

        $input = $this->getJsonInput();

        $this->db->update('payroll_records', [
            'status' => 'paid',
            'payment_method' => $input['payment_method'] ?? 'direct_deposit',
            'payment_reference' => $input['reference'] ?? null,
            'paid_at' => $input['payment_date'] ?? date('Y-m-d'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);

        // Check if all records in the period are paid
        $periodId = $record['payroll_period_id'];
        $unpaid = $this->db->fetch(
            "SELECT COUNT(*) as count FROM payroll_records WHERE payroll_period_id = ? AND status != 'paid'",
            [$periodId]
        );

        if ((int) $unpaid['count'] === 0) {
            $this->db->update('payroll_periods', [
                'status' => 'completed',
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => $periodId]);
        }

        return $this->success(null, 'Payment recorded');
    }

    /**
     * Complete/finalize a payroll period
     */
    public function completePeriod(string $id): array
    {
        $period = $this->db->fetch(
            "SELECT * FROM payroll_periods WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$period) {
            $this->error('Payroll period not found', 404);
        }

        $this->db->update('payroll_periods', [
            'status' => 'completed',
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);

        return $this->success(null, 'Payroll period completed');
    }
}
