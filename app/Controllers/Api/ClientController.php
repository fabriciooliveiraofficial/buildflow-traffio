<?php
/**
 * Client API Controller
 */

namespace App\Controllers\Api;

use App\Core\Controller;

class ClientController extends Controller
{
    /**
     * List all clients
     */
    public function index(): array
    {
        $params = $this->getQueryParams();
        $page = (int) ($params['page'] ?? 1);
        $perPage = (int) ($params['per_page'] ?? 15);
        $status = $params['status'] ?? null;
        $search = $params['search'] ?? null;

        $tenantId = $this->db->getTenantId();
        $conditions = ["tenant_id = ?"];
        $bindings = [$tenantId];

        if ($status) {
            $conditions[] = "status = ?";
            $bindings[] = $status;
        }

        if ($search) {
            $conditions[] = "(name LIKE ? OR email LIKE ? OR contact_person LIKE ?)";
            $bindings[] = "%{$search}%";
            $bindings[] = "%{$search}%";
            $bindings[] = "%{$search}%";
        }

        $where = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $countResult = $this->db->fetch(
            "SELECT COUNT(*) as total FROM clients WHERE {$where}",
            $bindings
        );
        $total = (int) $countResult['total'];

        $clients = $this->db->fetchAll(
            "SELECT 
                c.*,
                (SELECT COUNT(*) FROM projects WHERE client_id = c.id) as project_count,
                (SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE client_id = c.id AND status != 'cancelled') as total_invoiced,
                (SELECT COALESCE(SUM(total_amount - paid_amount), 0) FROM invoices WHERE client_id = c.id AND status NOT IN ('paid', 'cancelled')) as outstanding_balance
             FROM clients c
             WHERE {$where}
             ORDER BY c.name
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        return $this->paginate($clients, $total, $page, $perPage);
    }

    /**
     * Create client
     */
    public function store(): array
    {
        $data = $this->validate([
            'name' => 'required',
        ]);

        $input = $this->getJsonInput();

        $clientId = $this->db->insert('clients', [
            'name' => $data['name'],
            'contact_person' => $input['contact_person'] ?? null,
            'email' => $input['email'] ?? null,
            'phone' => $input['phone'] ?? null,
            'address' => $input['address'] ?? null,
            'city' => $input['city'] ?? null,
            'state' => $input['state'] ?? null,
            'zip_code' => $input['zip_code'] ?? null,
            'country' => $input['country'] ?? 'USA',
            'industry' => $input['industry'] ?? null,
            'payment_terms' => $input['payment_terms'] ?? 30,
            'credit_limit' => $input['credit_limit'] ?? null,
            'tax_id' => $input['tax_id'] ?? null,
            'notes' => $input['notes'] ?? null,
        ]);

        $client = $this->db->fetch("SELECT * FROM clients WHERE id = ?", [$clientId]);

        return $this->success($client, 'Client created', 201);
    }

    /**
     * Get single client
     */
    public function show(string $id): array
    {
        $client = $this->db->fetch(
            "SELECT * FROM clients WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$client) {
            $this->error('Client not found', 404);
        }

        return $this->success($client);
    }

    /**
     * Update client
     */
    public function update(string $id): array
    {
        $client = $this->db->fetch(
            "SELECT * FROM clients WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$client) {
            $this->error('Client not found', 404);
        }

        $input = $this->getJsonInput();
        $allowedFields = [
            'name',
            'contact_person',
            'email',
            'phone',
            'address',
            'city',
            'state',
            'zip_code',
            'country',
            'industry',
            'payment_terms',
            'credit_limit',
            'tax_id',
            'notes',
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
            $this->db->update('clients', $updateData, ['id' => $id]);
        }

        $updated = $this->db->fetch("SELECT * FROM clients WHERE id = ?", [$id]);

        return $this->success($updated, 'Client updated');
    }

    /**
     * Delete client
     */
    public function destroy(string $id): array
    {
        $client = $this->db->fetch(
            "SELECT * FROM clients WHERE id = ? AND tenant_id = ?",
            [$id, $this->db->getTenantId()]
        );

        if (!$client) {
            $this->error('Client not found', 404);
        }

        // Check for projects
        $projectCount = $this->db->fetch(
            "SELECT COUNT(*) as count FROM projects WHERE client_id = ?",
            [$id]
        );

        if ($projectCount['count'] > 0) {
            $this->error('Cannot delete client with existing projects', 422);
        }

        $this->db->delete('clients', ['id' => $id]);

        return $this->success(null, 'Client deleted');
    }

    /**
     * Get client projects
     */
    public function projects(string $id): array
    {
        $projects = $this->db->fetchAll(
            "SELECT 
                p.*,
                (SELECT COALESCE(SUM(budgeted_amount), 0) FROM budgets WHERE project_id = p.id) as total_budget,
                (SELECT COALESCE(SUM(spent_amount), 0) FROM budgets WHERE project_id = p.id) as total_spent
             FROM projects p
             WHERE p.client_id = ? AND p.tenant_id = ?
             ORDER BY p.created_at DESC",
            [$id, $this->db->getTenantId()]
        );

        return $this->success($projects);
    }

    /**
     * Get client financials
     */
    public function financials(string $id): array
    {
        $tenantId = $this->db->getTenantId();

        // Invoice summary
        $invoices = $this->db->fetch(
            "SELECT 
                COUNT(*) as total_invoices,
                COALESCE(SUM(total_amount), 0) as total_invoiced,
                COALESCE(SUM(paid_amount), 0) as total_paid,
                COALESCE(SUM(total_amount - paid_amount), 0) as outstanding
             FROM invoices 
             WHERE client_id = ? AND tenant_id = ? AND status != 'cancelled'",
            [$id, $tenantId]
        );

        // Recent invoices
        $recentInvoices = $this->db->fetchAll(
            "SELECT * FROM invoices 
             WHERE client_id = ? AND tenant_id = ?
             ORDER BY issue_date DESC
             LIMIT 10",
            [$id, $tenantId]
        );

        // Payments
        $payments = $this->db->fetchAll(
            "SELECT p.*, i.invoice_number
             FROM payments p
             JOIN invoices i ON p.invoice_id = i.id
             WHERE i.client_id = ? AND p.tenant_id = ?
             ORDER BY p.payment_date DESC
             LIMIT 10",
            [$id, $tenantId]
        );

        // Project budgets
        $projectBudgets = $this->db->fetchAll(
            "SELECT 
                pr.id,
                pr.name,
                COALESCE(SUM(b.budgeted_amount), 0) as budget,
                COALESCE(SUM(b.spent_amount), 0) as spent
             FROM projects pr
             LEFT JOIN budgets b ON b.project_id = pr.id
             WHERE pr.client_id = ? AND pr.tenant_id = ?
             GROUP BY pr.id, pr.name",
            [$id, $tenantId]
        );

        return $this->success([
            'summary' => $invoices,
            'recent_invoices' => $recentInvoices,
            'recent_payments' => $payments,
            'project_budgets' => $projectBudgets,
        ]);
    }
}
