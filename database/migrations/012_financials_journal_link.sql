-- Migration: Add journal entry linking to expenses and payments
-- Run this migration after deploying the code

-- Add journal_entry_id to expenses table
ALTER TABLE expenses ADD COLUMN journal_entry_id INT NULL AFTER status;
ALTER TABLE expenses ADD INDEX idx_expenses_journal_entry (journal_entry_id);

-- Add journal_entry_id to payments table  
ALTER TABLE payments ADD COLUMN journal_entry_id INT NULL AFTER updated_at;
ALTER TABLE payments ADD INDEX idx_payments_journal_entry (journal_entry_id);
