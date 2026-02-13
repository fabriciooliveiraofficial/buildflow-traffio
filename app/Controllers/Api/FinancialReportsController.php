<?php
/**
 * Financial Reports API Controller
 * 
 * Provides endpoints for Trial Balance, Income Statement (P&L), and Balance Sheet
 */

namespace App\Controllers\Api;

use App\Core\Controller;

class FinancialReportsController extends Controller
{
    /**
     * Trial Balance - List all accounts with their debit/credit totals
     */
    public function trialBalance(): array
    {
        $params = $this->getQueryParams();
        $asOf = $params['as_of'] ?? date('Y-m-d');

        $tenantId = $this->db->getTenantId();

        $accounts = $this->db->fetchAll(
            "SELECT 
                coa.id,
                coa.code,
                coa.name,
                coa.type,
                coa.subtype,
                COALESCE(SUM(jel.debit), 0) as total_debit,
                COALESCE(SUM(jel.credit), 0) as total_credit
             FROM chart_of_accounts coa
             LEFT JOIN journal_entry_lines jel ON coa.id = jel.account_id
             LEFT JOIN journal_entries je ON jel.journal_entry_id = je.id 
                AND je.status = 'posted' 
                AND je.entry_date <= ?
             WHERE coa.tenant_id = ?
             GROUP BY coa.id, coa.code, coa.name, coa.type, coa.subtype
             ORDER BY coa.code",
            [$asOf, $tenantId]
        );

        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as &$acc) {
            $acc['total_debit'] = (float) $acc['total_debit'];
            $acc['total_credit'] = (float) $acc['total_credit'];

            // Calculate balance based on account type
            $debitTypes = ['asset', 'expense'];
            if (in_array($acc['type'], $debitTypes)) {
                $acc['balance'] = $acc['total_debit'] - $acc['total_credit'];
            } else {
                $acc['balance'] = $acc['total_credit'] - $acc['total_debit'];
            }

            $totalDebit += $acc['total_debit'];
            $totalCredit += $acc['total_credit'];
        }

        return $this->success([
            'as_of' => $asOf,
            'accounts' => $accounts,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => abs($totalDebit - $totalCredit) < 0.01
        ]);
    }

    /**
     * Income Statement (Profit & Loss)
     */
    public function incomeStatement(): array
    {
        $params = $this->getQueryParams();
        $startDate = $params['start_date'] ?? date('Y-01-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');

        $tenantId = $this->db->getTenantId();

        // Get Income accounts
        $income = $this->db->fetchAll(
            "SELECT 
                coa.id, coa.code, coa.name, coa.subtype,
                COALESCE(SUM(jel.credit) - SUM(jel.debit), 0) as amount
             FROM chart_of_accounts coa
             LEFT JOIN journal_entry_lines jel ON coa.id = jel.account_id
             LEFT JOIN journal_entries je ON jel.journal_entry_id = je.id 
                AND je.status = 'posted' 
                AND je.entry_date BETWEEN ? AND ?
             WHERE coa.tenant_id = ? AND coa.type = 'income'
             GROUP BY coa.id, coa.code, coa.name, coa.subtype
             HAVING amount != 0
             ORDER BY coa.code",
            [$startDate, $endDate, $tenantId]
        );

        // Get Expense accounts
        $expenses = $this->db->fetchAll(
            "SELECT 
                coa.id, coa.code, coa.name, coa.subtype,
                COALESCE(SUM(jel.debit) - SUM(jel.credit), 0) as amount
             FROM chart_of_accounts coa
             LEFT JOIN journal_entry_lines jel ON coa.id = jel.account_id
             LEFT JOIN journal_entries je ON jel.journal_entry_id = je.id 
                AND je.status = 'posted' 
                AND je.entry_date BETWEEN ? AND ?
             WHERE coa.tenant_id = ? AND coa.type = 'expense'
             GROUP BY coa.id, coa.code, coa.name, coa.subtype
             HAVING amount != 0
             ORDER BY coa.code",
            [$startDate, $endDate, $tenantId]
        );

        $totalIncome = array_sum(array_column($income, 'amount'));
        $totalExpenses = array_sum(array_column($expenses, 'amount'));
        $netIncome = $totalIncome - $totalExpenses;

        return $this->success([
            'period' => ['start' => $startDate, 'end' => $endDate],
            'income' => $income,
            'expenses' => $expenses,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_income' => $netIncome
        ]);
    }

    /**
     * Balance Sheet
     */
    public function balanceSheet(): array
    {
        $params = $this->getQueryParams();
        $asOf = $params['as_of'] ?? date('Y-m-d');

        $tenantId = $this->db->getTenantId();

        // Get Assets
        $assets = $this->db->fetchAll(
            "SELECT 
                coa.id, coa.code, coa.name, coa.subtype,
                COALESCE(SUM(jel.debit) - SUM(jel.credit), 0) as amount
             FROM chart_of_accounts coa
             LEFT JOIN journal_entry_lines jel ON coa.id = jel.account_id
             LEFT JOIN journal_entries je ON jel.journal_entry_id = je.id 
                AND je.status = 'posted' 
                AND je.entry_date <= ?
             WHERE coa.tenant_id = ? AND coa.type = 'asset'
             GROUP BY coa.id, coa.code, coa.name, coa.subtype
             HAVING amount != 0
             ORDER BY coa.code",
            [$asOf, $tenantId]
        );

        // Get Liabilities
        $liabilities = $this->db->fetchAll(
            "SELECT 
                coa.id, coa.code, coa.name, coa.subtype,
                COALESCE(SUM(jel.credit) - SUM(jel.debit), 0) as amount
             FROM chart_of_accounts coa
             LEFT JOIN journal_entry_lines jel ON coa.id = jel.account_id
             LEFT JOIN journal_entries je ON jel.journal_entry_id = je.id 
                AND je.status = 'posted' 
                AND je.entry_date <= ?
             WHERE coa.tenant_id = ? AND coa.type = 'liability'
             GROUP BY coa.id, coa.code, coa.name, coa.subtype
             HAVING amount != 0
             ORDER BY coa.code",
            [$asOf, $tenantId]
        );

        // Get Equity
        $equity = $this->db->fetchAll(
            "SELECT 
                coa.id, coa.code, coa.name, coa.subtype,
                COALESCE(SUM(jel.credit) - SUM(jel.debit), 0) as amount
             FROM chart_of_accounts coa
             LEFT JOIN journal_entry_lines jel ON coa.id = jel.account_id
             LEFT JOIN journal_entries je ON jel.journal_entry_id = je.id 
                AND je.status = 'posted' 
                AND je.entry_date <= ?
             WHERE coa.tenant_id = ? AND coa.type = 'equity'
             GROUP BY coa.id, coa.code, coa.name, coa.subtype
             HAVING amount != 0
             ORDER BY coa.code",
            [$asOf, $tenantId]
        );

        // Calculate Retained Earnings (Net Income for the period)
        $retainedEarnings = $this->db->fetch(
            "SELECT 
                COALESCE(SUM(CASE WHEN coa.type = 'income' THEN jel.credit - jel.debit ELSE 0 END), 0) -
                COALESCE(SUM(CASE WHEN coa.type = 'expense' THEN jel.debit - jel.credit ELSE 0 END), 0) as amount
             FROM chart_of_accounts coa
             LEFT JOIN journal_entry_lines jel ON coa.id = jel.account_id
             LEFT JOIN journal_entries je ON jel.journal_entry_id = je.id 
                AND je.status = 'posted' 
                AND je.entry_date <= ?
             WHERE coa.tenant_id = ? AND coa.type IN ('income', 'expense')",
            [$asOf, $tenantId]
        );

        $totalAssets = array_sum(array_column($assets, 'amount'));
        $totalLiabilities = array_sum(array_column($liabilities, 'amount'));
        $totalEquity = array_sum(array_column($equity, 'amount')) + (float) $retainedEarnings['amount'];

        return $this->success([
            'as_of' => $asOf,
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'retained_earnings' => (float) $retainedEarnings['amount'],
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01
        ]);
    }
}
