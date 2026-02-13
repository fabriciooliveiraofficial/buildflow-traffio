<?php

namespace App\Models\Finance;

use App\Core\Database;
use App\Core\Tenant;
use PDO;

class Account
{
    private $db;
    private $tenantId;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $current = Tenant::current();
        $this->tenantId = $current ? $current->getId() : null;
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO chart_of_accounts 
                (tenant_id, code, name, type, subtype, description, is_system) 
                VALUES 
                (:tenant_id, :code, :name, :type, :subtype, :description, :is_system)";

        $params = [
            ':tenant_id' => $this->tenantId,
            ':code' => $data['code'],
            ':name' => $data['name'],
            ':type' => $data['type'],
            ':subtype' => $data['subtype'] ?? null,
            ':description' => $data['description'] ?? null,
            ':is_system' => $data['is_system'] ?? 0
        ];

        $this->db->query($sql, $params);
        return $this->db->getPdo()->lastInsertId();
    }

    public function getAll()
    {
        $sql = "SELECT * FROM chart_of_accounts WHERE tenant_id = :tenant_id ORDER BY code ASC";
        return $this->db->fetchAll($sql, [':tenant_id' => $this->tenantId]);
    }

    public function getByType($type)
    {
        $sql = "SELECT * FROM chart_of_accounts WHERE tenant_id = :tenant_id AND type = :type ORDER BY code ASC";
        return $this->db->fetchAll($sql, [
            ':tenant_id' => $this->tenantId,
            ':type' => $type
        ]);
    }

    /**
     * Seed default accounts for a new tenant
     */
    public function seedDefaults()
    {
        $defaults = [
            // Assets (1000-1999)
            ['1000', 'Cash on Hand', 'asset', 'current_asset', 1],
            ['1001', 'Checking Account', 'asset', 'current_asset', 0],
            ['1100', 'Accounts Receivable', 'asset', 'current_asset', 1],
            ['1200', 'Inventory Asset', 'asset', 'current_asset', 1],
            ['1500', 'Equipment & Machinery', 'asset', 'fixed_asset', 0],

            // Liabilities (2000-2999)
            ['2000', 'Accounts Payable', 'liability', 'current_liability', 1],
            ['2100', 'Credit Card Payable', 'liability', 'current_liability', 0],
            ['2200', 'Sales Tax Payable', 'liability', 'current_liability', 1],
            ['2300', 'Payroll Liabilities', 'liability', 'current_liability', 1],

            // Equity (3000-3999)
            ['3000', 'Owner Equity', 'equity', 'equity', 1],
            ['3900', 'Retained Earnings', 'equity', 'equity', 1],

            // Income (4000-4999)
            ['4000', 'Construction Revenue', 'income', 'operating_revenue', 1],
            ['4100', 'Service Revenue', 'income', 'operating_revenue', 0],
            ['4200', 'Other Income', 'income', 'other_income', 0],

            // COGS (5000-5999)
            ['5000', 'Cost of Goods Sold - Labor', 'expense', 'cost_of_goods_sold', 1],
            ['5100', 'Cost of Goods Sold - Materials', 'expense', 'cost_of_goods_sold', 1],
            ['5200', 'Subcontractor Expense', 'expense', 'cost_of_goods_sold', 1],

            // Expenses (6000-7999)
            ['6000', 'Advertising & Marketing', 'expense', 'operating_expense', 0],
            ['6100', 'insurance', 'expense', 'operating_expense', 0],
            ['6200', 'Office Supplies', 'expense', 'operating_expense', 0],
            ['6300', 'Rent & Lease', 'expense', 'operating_expense', 0],
            ['6400', 'Utilities', 'expense', 'operating_expense', 0],
        ];

        foreach ($defaults as $acc) {
            // Check if exists
            $exists = $this->db->fetch("SELECT id FROM chart_of_accounts WHERE tenant_id = :tid AND code = :code", [
                ':tid' => $this->tenantId,
                ':code' => $acc[0]
            ]);

            if (!$exists) {
                $this->create([
                    'code' => $acc[0],
                    'name' => $acc[1],
                    'type' => $acc[2],
                    'subtype' => $acc[3],
                    'is_system' => $acc[4]
                ]);
            }
        }
    }
}
