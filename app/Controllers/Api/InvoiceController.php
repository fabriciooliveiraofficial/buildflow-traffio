<?php
/**
 * Invoice API Controller
 */

namespace App\Controllers\Api;

use App\Core\Controller;

class InvoiceController extends Controller
{
    /**
     * List invoices
     */
    public function index(): array
    {
        $params = $this->getQueryParams();
        $page = (int) ($params['page'] ?? 1);
        $perPage = (int) ($params['per_page'] ?? 15);
        $status = $params['status'] ?? null;
        $clientId = $params['client_id'] ?? null;
        $projectId = $params['project_id'] ?? null;
        $overdue = $params['overdue'] ?? null;

        $tenantId = $this->db->getTenantId();
        $conditions = ["i.tenant_id = ?"];
        $bindings = [$tenantId];

        if ($status) {
            $conditions[] = "i.status = ?";
            $bindings[] = $status;
        }

        if ($clientId) {
            $conditions[] = "i.client_id = ?";
            $bindings[] = $clientId;
        }

        if ($projectId) {
            $conditions[] = "i.project_id = ?";
            $bindings[] = $projectId;
        }

        if ($overdue === 'true') {
            $conditions[] = "i.due_date < CURDATE() AND i.status NOT IN ('paid', 'cancelled')";
        }

        $where = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $countResult = $this->db->fetch(
            "SELECT COUNT(*) as total FROM invoices i WHERE {$where}",
            $bindings
        );
        $total = (int) $countResult['total'];

        $invoices = $this->db->fetchAll(
            "SELECT 
                i.*,
                c.name as client_name,
                p.name as project_name,
                DATEDIFF(CURDATE(), i.due_date) as days_overdue
             FROM invoices i
             LEFT JOIN clients c ON i.client_id = c.id
             LEFT JOIN projects p ON i.project_id = p.id
             WHERE {$where}
             ORDER BY i.issue_date DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        return $this->paginate($invoices, $total, $page, $perPage);
    }

    /**
     * Create invoice
     */
    public function store(): array
    {
        $data = $this->validate([
            'client_id' => 'required|numeric',
        ]);

        $input = $this->getJsonInput();
        $tenantId = $this->db->getTenantId();

        // Generate invoice number
        $lastInvoice = $this->db->fetch(
            "SELECT invoice_number FROM invoices WHERE tenant_id = ? ORDER BY id DESC LIMIT 1",
            [$tenantId]
        );

        $nextNumber = 1;
        if ($lastInvoice) {
            preg_match('/(\d+)$/', $lastInvoice['invoice_number'], $matches);
            $nextNumber = (int) ($matches[1] ?? 0) + 1;
        }
        $invoiceNumber = 'INV-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        // Calculate totals from items
        $items = $input['items'] ?? [];
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
        }

        $taxRate = $input['tax_rate'] ?? 0;
        $taxAmount = $subtotal * ($taxRate / 100);
        $discountAmount = $input['discount_amount'] ?? 0;
        $totalAmount = $subtotal + $taxAmount - $discountAmount;

        $issueDate = $input['issue_date'] ?? date('Y-m-d');

        // Get client payment terms
        $client = $this->db->fetch("SELECT payment_terms FROM clients WHERE id = ?", [$data['client_id']]);
        $paymentTerms = $client['payment_terms'] ?? 30;
        $dueDate = $input['due_date'] ?? date('Y-m-d', strtotime("+{$paymentTerms} days", strtotime($issueDate)));

        $invoiceId = $this->db->insert('invoices', [
            'client_id' => $data['client_id'],
            'project_id' => $input['project_id'] ?? null,
            'invoice_number' => $invoiceNumber,
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'notes' => $input['notes'] ?? null,
            'terms' => $input['terms'] ?? null,
            'status' => 'draft',
        ]);

        // Insert line items
        $order = 0;
        foreach ($items as $item) {
            $this->db->query(
                "INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, amount, sort_order) 
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $invoiceId,
                    $item['description'],
                    $item['quantity'] ?? 1,
                    $item['unit_price'] ?? 0,
                    ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0),
                    $order++,
                ]
            );
        }

        $invoice = $this->db->fetch("SELECT * FROM invoices WHERE id = ?", [$invoiceId]);

        return $this->success($invoice, 'Invoice created', 201);
    }

    /**
     * Get single invoice
     */
    public function show(string $id): array
    {
        $invoice = $this->db->fetch(
            "SELECT 
                i.*,
                c.name as client_name,
                c.email as client_email,
                c.address as client_address,
                c.city as client_city,
                c.state as client_state,
                c.zip_code as client_zip,
                p.name as project_name
             FROM invoices i
             LEFT JOIN clients c ON i.client_id = c.id
             LEFT JOIN projects p ON i.project_id = p.id
             WHERE i.id = ? AND i.tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$invoice) {
            $this->error('Invoice not found', 404);
        }

        // Get line items
        $items = $this->db->fetchAll(
            "SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY sort_order",
            [$id]
        );

        // Get payments
        $payments = $this->db->fetchAll(
            "SELECT * FROM payments WHERE invoice_id = ? ORDER BY payment_date DESC",
            [$id]
        );

        $invoice['items'] = $items;
        $invoice['payments'] = $payments;

        return $this->success($invoice);
    }

    /**
     * Update invoice
     */
    public function update(string $id): array
    {
        $invoice = $this->db->fetch(
            "SELECT * FROM invoices WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$invoice) {
            $this->error('Invoice not found', 404);
        }

        if ($invoice['status'] === 'paid') {
            $this->error('Cannot modify paid invoice', 422);
        }

        $input = $this->getJsonInput();

        // Handle item updates
        if (isset($input['items'])) {
            // Delete existing items
            $this->db->query("DELETE FROM invoice_items WHERE invoice_id = ?", [$id]);

            // Insert new items
            $subtotal = 0;
            $order = 0;
            foreach ($input['items'] as $item) {
                $amount = ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
                $subtotal += $amount;

                $this->db->query(
                    "INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, amount, sort_order) 
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [
                        $id,
                        $item['description'],
                        $item['quantity'] ?? 1,
                        $item['unit_price'] ?? 0,
                        $amount,
                        $order++,
                    ]
                );
            }

            $input['subtotal'] = $subtotal;
            $taxRate = $input['tax_rate'] ?? $invoice['tax_rate'];
            $input['tax_amount'] = $subtotal * ($taxRate / 100);
            $input['total_amount'] = $subtotal + $input['tax_amount'] - ($input['discount_amount'] ?? $invoice['discount_amount']);
        }

        $allowedFields = [
            'client_id',
            'project_id',
            'issue_date',
            'due_date',
            'subtotal',
            'tax_rate',
            'tax_amount',
            'discount_amount',
            'total_amount',
            'notes',
            'terms',
            'status'
        ];

        $updateData = [];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateData[$field] = $input[$field];
            }
        }

        if (!empty($updateData)) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('invoices', $updateData, ['id' => $id]);
        }

        return $this->show($id);
    }

    /**
     * Delete invoice
     */
    public function destroy(string $id): array
    {
        $invoice = $this->db->fetch(
            "SELECT * FROM invoices WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$invoice) {
            $this->error('Invoice not found', 404);
        }

        if ($invoice['paid_amount'] > 0) {
            $this->error('Cannot delete invoice with payments', 422);
        }

        $this->db->delete('invoices', ['id' => $id]);

        return $this->success(null, 'Invoice deleted');
    }

    /**
     * Send invoice to client
     */
    public function send(string $id): array
    {
        $invoice = $this->db->fetch(
            "SELECT i.*, c.email as client_email, c.name as client_name
             FROM invoices i
             JOIN clients c ON i.client_id = c.id
             WHERE i.id = ? AND i.tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$invoice) {
            $this->error('Invoice not found', 404);
        }

        if (!$invoice['client_email']) {
            $this->error('Client has no email address', 422);
        }

        // In production, send email here using mail service
        // For now, just update status

        $this->db->update('invoices', [
            'status' => 'sent',
            'sent_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);

        return $this->success(null, 'Invoice sent');
    }

    /**
     * Generate payment link
     */
    public function paymentLink(string $id): array
    {
        $invoice = $this->db->fetch(
            "SELECT * FROM invoices WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$invoice) {
            $this->error('Invoice not found', 404);
        }

        $amountDue = $invoice['total_amount'] - $invoice['paid_amount'];

        if ($amountDue <= 0) {
            $this->error('Invoice is already paid', 422);
        }

        // Generate unique payment token
        $paymentToken = bin2hex(random_bytes(32));

        // Store token (in production, save to database with expiration)
        $paymentUrl = APP_URL . "/pay/{$paymentToken}";

        return $this->success([
            'payment_url' => $paymentUrl,
            'amount_due' => $amountDue,
            'expires_in' => 7 * 24 * 60 * 60, // 7 days
        ]);
    }

    /**
     * Get billable time logs that haven't been invoiced
     */
    public function getBillableTime(): array
    {
        $params = $this->getQueryParams();
        $projectId = $params['project_id'] ?? null;
        $clientId = $params['client_id'] ?? null;
        $startDate = $params['start_date'] ?? null;
        $endDate = $params['end_date'] ?? null;

        $tenantId = $this->db->getTenantId();
        $conditions = ["tl.tenant_id = ?", "tl.billable = 1", "tl.invoiced = 0", "tl.status = 'approved'"];
        $bindings = [$tenantId];

        if ($projectId) {
            $conditions[] = "tl.project_id = ?";
            $bindings[] = $projectId;
        }

        if ($clientId) {
            $conditions[] = "p.client_id = ?";
            $bindings[] = $clientId;
        }

        if ($startDate) {
            $conditions[] = "tl.log_date >= ?";
            $bindings[] = $startDate;
        }

        if ($endDate) {
            $conditions[] = "tl.log_date <= ?";
            $bindings[] = $endDate;
        }

        $where = implode(' AND ', $conditions);

        $logs = $this->db->fetchAll(
            "SELECT 
                tl.id,
                tl.project_id,
                tl.employee_id,
                tl.log_date,
                tl.hours,
                tl.description,
                e.first_name,
                e.last_name,
                e.hourly_rate,
                p.name as project_name,
                p.client_id,
                c.name as client_name
             FROM time_logs tl
             JOIN employees e ON tl.employee_id = e.id
             LEFT JOIN projects p ON tl.project_id = p.id
             LEFT JOIN clients c ON p.client_id = c.id
             WHERE {$where}
             ORDER BY tl.log_date DESC, e.first_name",
            $bindings
        );

        // Calculate billable amount for each log
        foreach ($logs as &$log) {
            $rate = (float) ($log['hourly_rate'] ?? 0);
            $log['billable_amount'] = round($log['hours'] * $rate, 2);
        }

        // Group by project summary
        $byProject = [];
        foreach ($logs as $log) {
            $pid = $log['project_id'] ?? 'none';
            if (!isset($byProject[$pid])) {
                $byProject[$pid] = [
                    'project_id' => $log['project_id'],
                    'project_name' => $log['project_name'] ?? 'No Project',
                    'client_id' => $log['client_id'],
                    'client_name' => $log['client_name'],
                    'total_hours' => 0,
                    'total_amount' => 0,
                    'log_count' => 0,
                ];
            }
            $byProject[$pid]['total_hours'] += $log['hours'];
            $byProject[$pid]['total_amount'] += $log['billable_amount'];
            $byProject[$pid]['log_count']++;
        }

        return $this->success([
            'logs' => $logs,
            'by_project' => array_values($byProject),
            'totals' => [
                'hours' => array_sum(array_column($logs, 'hours')),
                'amount' => array_sum(array_column($logs, 'billable_amount')),
            ],
        ]);
    }

    /**
     * Create invoice from selected time logs
     */
    public function createFromTime(): array
    {
        $input = $this->getJsonInput();
        $timeLogIds = $input['time_log_ids'] ?? [];
        $clientId = $input['client_id'] ?? null;
        $projectId = $input['project_id'] ?? null;

        if (empty($timeLogIds)) {
            $this->error('No time logs selected', 422);
        }

        $tenantId = $this->db->getTenantId();

        // Fetch selected time logs with employee rates
        $placeholders = implode(',', array_fill(0, count($timeLogIds), '?'));
        $logs = $this->db->fetchAll(
            "SELECT 
                tl.*,
                e.first_name,
                e.last_name,
                e.hourly_rate,
                p.name as project_name,
                p.client_id as project_client_id
             FROM time_logs tl
             JOIN employees e ON tl.employee_id = e.id
             LEFT JOIN projects p ON tl.project_id = p.id
             WHERE tl.id IN ({$placeholders}) AND tl.tenant_id = ? AND tl.invoiced = 0",
            [...$timeLogIds, $tenantId]
        );

        if (empty($logs)) {
            $this->error('No valid time logs found', 422);
        }

        // Determine client if not provided
        if (!$clientId) {
            $clientId = $logs[0]['project_client_id'] ?? $input['client_id'];
        }

        if (!$clientId) {
            $this->error('Client ID is required', 422);
        }

        // Generate invoice number
        $lastInvoice = $this->db->fetch(
            "SELECT invoice_number FROM invoices WHERE tenant_id = ? ORDER BY id DESC LIMIT 1",
            [$tenantId]
        );

        $nextNumber = 1;
        if ($lastInvoice) {
            preg_match('/(\d+)$/', $lastInvoice['invoice_number'], $matches);
            $nextNumber = (int) ($matches[1] ?? 0) + 1;
        }
        $invoiceNumber = 'INV-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        // Build line items from time logs
        $items = [];
        $subtotal = 0;
        foreach ($logs as $log) {
            $rate = (float) ($log['hourly_rate'] ?? 0);
            $amount = round($log['hours'] * $rate, 2);
            $subtotal += $amount;

            $description = sprintf(
                '%s %s - %s - %s (%sh)',
                $log['first_name'],
                $log['last_name'],
                date('M d', strtotime($log['log_date'])),
                $log['description'] ?? $log['project_name'] ?? 'Time worked',
                $log['hours']
            );

            $items[] = [
                'description' => $description,
                'quantity' => $log['hours'],
                'unit_price' => $rate,
                'amount' => $amount,
                'time_log_id' => $log['id'],
            ];
        }

        // Get client payment terms
        $client = $this->db->fetch("SELECT payment_terms FROM clients WHERE id = ?", [$clientId]);
        $paymentTerms = $client['payment_terms'] ?? 30;

        $issueDate = date('Y-m-d');
        $dueDate = date('Y-m-d', strtotime("+{$paymentTerms} days"));

        // Create invoice
        $invoiceId = $this->db->insert('invoices', [
            'client_id' => $clientId,
            'project_id' => $projectId ?? $logs[0]['project_id'],
            'invoice_number' => $invoiceNumber,
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'subtotal' => $subtotal,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => $subtotal,
            'notes' => $input['notes'] ?? 'Invoice generated from billable time logs',
            'status' => 'draft',
        ]);

        // Insert line items
        $order = 0;
        foreach ($items as $item) {
            $this->db->query(
                "INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, amount, sort_order) 
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $invoiceId,
                    $item['description'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['amount'],
                    $order++,
                ]
            );
        }

        // Mark time logs as invoiced
        $this->db->query(
            "UPDATE time_logs SET invoiced = 1, invoice_id = ?, updated_at = NOW() 
             WHERE id IN ({$placeholders})",
            [$invoiceId, ...$timeLogIds]
        );

        $invoice = $this->db->fetch("SELECT * FROM invoices WHERE id = ?", [$invoiceId]);

        return $this->success($invoice, 'Invoice created from time logs', 201);
    }
}
