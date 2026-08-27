Recommended school chart of accounts
Each school should start from an Edlink template, but be allowed to modify it.
1000 — Assets
- 1100 Cash and cash equivalents
  - 1110 Cash on Hand
  - 1120 Petty Cash
  - 1130 Main Bank Account
  - 1140 Savings Account
  - 1150 Mobile Money
- 1200 Receivables
  - 1210 Tuition Fee Receivable
  - 1220 Boarding Fee Receivable
  - 1230 Transport Fee Receivable
  - 1240 Other Student Receivables
  - 1250 Staff Receivables and Advances
  - 1290 Allowance for Doubtful Fees
- 1300 Inventory
  - 1310 Uniform Inventory
  - 1320 Textbook and Stationery Inventory
  - 1330 Food and Kitchen Supplies
- 1400 Prepayments and deposits
- 1500 Property and equipment
  - 1510 Land
  - 1520 Buildings
  - 1530 Furniture and Equipment
  - 1540 Computers and ICT Equipment
  - 1550 School Vehicles
  - 1590 Accumulated Depreciation
2000 — Liabilities
- 2100 Supplier Payables
- 2200 Payroll Liabilities
  - 2210 Salaries Payable
  - 2220 Statutory Deductions Payable
  - 2230 Staff Loan Deductions Payable
- 2300 Fees Received in Advance
- 2400 Student and Caution Deposits
- 2500 Taxes and Statutory Obligations
- 2600 Short-Term Loans
- 2700 Long-Term Loans
3000 — School Funds and Equity
For a private school, these can be equity accounts. For a nonprofit or government-supported school, they can be fund-balance accounts.
- 3100 Opening Fund Balance
- 3200 Accumulated Surplus or Deficit
- 3300 Current-Year Surplus or Deficit
- 3400 Restricted Funds
- 3500 Capital or Development Fund
4000 — Income
- 4100 Academic Fee Income
  - 4110 Tuition Fees
  - 4120 Admission and Registration Fees
  - 4130 Examination Fees
  - 4140 Computer and Laboratory Fees
  - 4150 Development Fees
- 4200 Student Service Income
  - 4210 Boarding Fees
  - 4220 Transport Fees
  - 4230 Meals Income
  - 4240 Medical Fees
  - 4250 Activity and Sports Fees
- 4300 Sales
  - 4310 Uniform Sales
  - 4320 Books and Stationery Sales
- 4400 Grants and Donations
- 4500 Rental and Facility Income
- 4600 Interest Income
- 4900 Other Income
5000 — Academic and Staff Expenses
- 5100 Teaching Salaries
- 5200 Non-Teaching Salaries
- 5300 Staff Benefits and Allowances
- 5400 Teaching Materials
- 5500 Examination Expenses
- 5600 Laboratory and Library Expenses
- 5700 Sports and Co-curricular Activities
- 5800 Staff Training
6000 — Operating Expenses
- 6100 Food and Boarding
- 6200 Transport and Vehicle Expenses
- 6300 Utilities
- 6400 Repairs and Maintenance
- 6500 Rent and Property Expenses
- 6600 Security and Cleaning
- 6700 ICT and Software
- 6800 Printing, Postage, and Communication
- 6900 Insurance
7000 — Administrative and Financial Expenses
- 7100 Office and Administration
- 7200 Professional and Audit Fees
- 7300 Bank and Mobile-Money Charges
- 7400 Interest Expense
- 7500 Depreciation
- 7600 Scholarships and Bursaries
- 7700 Bad-Debt Expense
- 7800 Losses and Adjustments
Schools should be able to create subaccounts such as:
- 1130-01 Stanbic Bank
- 1130-02 Centenary Bank
- 1150-01 MTN Mobile Money
- 1150-02 Airtel Money
How “modifiable” should work
Each school receives a default chart when the accounting module is activated. Administrators with a dedicated permission can then:
- Add accounts and subaccounts
- Change account names
- Select account type and normal balance
- Move accounts within the hierarchy
- Assign account codes
- Mark accounts as bank, cash, receivable, payable, income, expense, or control accounts
- Archive unused accounts
- Import a chart from Excel/CSV
- Export the complete chart
- Map Edlink activities to accounts
However, accounting history must remain protected:
- An account with transactions cannot be deleted.
- Its fundamental type should not be changed after posting.
- Account codes must be unique within a school.
- Parent accounts should normally be non-posting.
- System control accounts can be remapped but not casually removed.
- Changes to accounts and mappings must enter the audit log.
Avoiding an oversized chart
We should not create separate ledger accounts for every student, class, term, department, or branch. That would make the chart unmanageable.
Instead, Edlink should use accounting dimensions:
- School/branch
- Academic term
- Fund or project
- Department or cost centre
- Student
- Class
- Fee item
- Vendor
- Employee
For example, one 4110 Tuition Fee Income account can still be reported by branch, term, class, student category, or individual student.
How Edlink transactions will post
Edlink event	Debit	Credit
Student is billed	Student Fee Receivable	Relevant Fee Income
Student pays	Cash/Bank/Mobile Money	Student Fee Receivable
Fee paid in advance	Cash/Bank	Fees Received in Advance
Approved discount	Fee Discount/Contra-Income	Student Fee Receivable
Scholarship funded by school	Scholarship Expense	Student Fee Receivable
Bad debt written off	Bad-Debt Expense	Student Fee Receivable
Supplier bill recorded	Relevant Expense/Asset	Supplier Payable
Supplier is paid	Supplier Payable	Cash/Bank
Payroll approved	Salary Expense	Salaries Payable
Payroll is paid	Salaries Payable	Bank/Cash
Cash transferred to bank	Bank	Cash on Hand


The existing approval workflow can remain: operational transactions create draft journals, and approval posts them to the general ledger.
Proposed module structure
The Accounting module should contain:
1. Accounting dashboard
2. Chart of accounts
3. Accounting mappings
4. Journal entries
5. General ledger
6. Student receivables control
7. Supplier payables
8. Cash and bank accounts
9. Bank reconciliation
10. Budgets and budget-versus-actual reports
11. Trial balance
12. Income and expenditure statement
13. Balance sheet
14. Cash-flow statement
15. Fund and project reports
16. Financial periods and year-end closing
17. Audit trail
How I would add it technically
We should introduce separate accounting records rather than forcing double-entry behavior into the existing single-sided ledger.
The central structure would be:
- accounts — the configurable chart of accounts
- accounting_periods — months and financial years
- journals — transaction headers
- journal_lines — debit and credit lines
- account_mappings — links Edlink events to accounts
- cost_centres
- funds
- vendors
- bills and bill_payments
- budgets and budget_lines
Every journal line would carry school_id, account, amount, debit/credit, date, and optional branch, term, student, employee, vendor, fund, or cost-centre references.
The current financial_accounts can remain as bank/cash registers, but each one must point to an account in the chart of accounts. The existing finance_ledger_entries can either become an operational approval register or be gradually migrated into the new journals.
Recommended construction order
The safest order is:
1. Build the configurable chart of accounts and school template.
2. Build balanced journals and journal lines.
3. Add account mappings for fees, payments, expenses, payroll, and transfers.
4. Migrate existing approved finance history into opening or historical journals.
5. Add trial balance, general ledger, income statement, and balance sheet.
6. Add vendors, payables, budgeting, and deeper financial reporting.
7. Add year-end closing and accountant exports.


how to post expenses
Use the Journals tab inside Accounting:

  1. Open Accounting → Accounting Workspace.
  2. Select Journals in the header.
  3. Under Manual journal entry, enter:
      - Journal date
      - Reference
      - Description
      - Debit account and amount
      - Credit account and amount

  4. Ensure total debits equal total credits.
  5. Click Save balanced draft.
  6. Submit the journal for approval.
  7. A different authorized user approves it.
  8. The authorized poster posts it to the ledger.

  Example: paying UGX 500,000 rent from the bank:

   Account                         Debit     Credit
  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━  ━━━━━━━━━  ━━━━━━━━━
   Rent and Property Expenses    500,000          0
  ────────────────────────────  ─────────  ─────────
   Main Bank Account                   0    500,000

  The workflow is:

  Draft → Submitted → Approved → Posted

  Posted journals cannot be edited or deleted. Corrections are made using Reverse.

  Ordinary fee payments, approved expenses, and payroll should automatically create their corresponding accounting journals. Manual journals
  are mainly for adjustments, opening entries, depreciation, accruals, and other accountant-controlled transactions.


 ### When a student pays fees

  A fee payment does not affect an expense account.

  The posting is:

   Account                                     Debit                                Credit
  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━  ━━━━━━━━━━━━━━━━  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   Cash/Bank/Mobile Money account     Payment amount                                     —
  ─────────────────────────────────  ────────────────  ────────────────────────────────────
   1210 – Student Fee Receivables                  —    Amount applied to outstanding fees
  ─────────────────────────────────  ────────────────  ────────────────────────────────────
   2130 – Fees Received in Advance                 —                       Any overpayment

  Example: a student owes UGX 1,000,000 and pays UGX 1,200,000:

  - Debit Cash/Bank: UGX 1,200,000
  - Credit Student Fee Receivables: UGX 1,000,000
  - Credit Fees Received in Advance: UGX 200,000

  The cash-side account depends on the payment destination:

  - Cash → 1110 Cash on Hand
  - Petty cash → 1120 Petty Cash
  - Bank → 1130 Main Bank Account
  - Mobile money → 1150 Mobile Money

  These mappings can be changed in Accounting Settings. The fee-payment logic is in app/Services/AccountingPostingService.php:46.

  Fee income is recognized earlier when fees are assessed:

  - Debit 1210 Student Fee Receivables
  - Credit the configured fee-income account, normally 4100 Tuition Fees

  Therefore, receiving payment settles the receivable; it does not recognize the income again.

  ### When an expense is recorded

  For an expense paid immediately:

   Account                                             Debit            Credit
  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━  ━━━━━━━━━━━━━━━━  ━━━━━━━━━━━━━━━━
   Selected expense account                   Expense amount                 —
  ─────────────────────────────────────────  ────────────────  ────────────────
   Selected Cash/Bank/Mobile Money account                 —    Expense amount

  The expense screen lets the user select the actual expense ledger account. Its initial default is:

  - 5500 Office and Administration

  Other available accounts include Utilities 5400, Repairs and Maintenance 5410, Transport 5310, Teaching Materials 5200, and several others.

  For an expense bought on credit:

  - Debit the selected expense account
  - Credit 2100 Supplier Payables

  No cash account is affected at that stage. The expense rules are in app/Services/AccountingPostingService.php:69.

  ### How posting happens

  1. A payment or expense is entered.
  2. The system creates a pending finance-ledger entry.
  3. A different authorized user must approve it—users cannot approve their own entries.
  4. Approval generates a balanced accounting journal.
  5. The journal goes through draft → submitted → approved → posted.
  6. Only then is the finance entry marked posted and the cash pool updated.
  7. Posted transactions cannot be deleted; they must be reversed.

  This approval process is implemented in app/Services/FinanceLedgerService.php:26, while journal validation and final posting are handled by app/Services/DoubleEntryService.php:32.

  One important detail: if there is no posted fee assessment for the student, their receivable balance is zero. In that case, the entire payment is credited to 2130 Fees Received in
  Advance, rather than fee income.

─────────────────────
