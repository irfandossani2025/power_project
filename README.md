# PowerBooks

PowerBooks is an Oman-ready accounting, inventory, quotation, invoicing, purchasing, and reporting application for a single business. It uses Omani rial (OMR) formatting, supports VAT on commercial documents, and has a controlled CSV migration centre for QuickBooks data.

## What the application provides

- Clear operational dashboard: month-to-date invoiced sales, receivables, payables, low-stock alerts, quotation pipeline, and recent activity.
- Customer and supplier directories with contacts, VAT numbers, payment terms, and active/inactive controls.
- Products and services, including SKU, cost, sales price, VAT rate, and reorder level.
- Quotations with line discounts and VAT; only accepted quotations can be converted to a sales invoice.
- Printable quotations, invoices, and supplier bills through the browser print dialog / Save as PDF.
- Sales invoices created from accepted quotations, customer payment recording, receivables, sales VAT, stock issues, and ledger postings.
- Supplier bills, stock receipts for product lines, supplier payment recording, payables, purchase VAT, and ledger postings.
- Inventory movements for opening stock, receipts, returns, and adjustments; stock-on-hand and reorder watchlist.
- Operating expenses with VAT treatment and automatic accounting postings.
- Chart of accounts, balanced manual journals, trial balance, profit and loss, balance sheet, VAT summary, and operational reports.
- Admin-managed team accounts and roles: admin, accountant, sales, inventory, and staff.
- Username-based login, active-user protection, profile/password management, and the footer credits requested for PowerBooks.

## Accounting behaviour

Operational records create traceable double-entry journal postings:

| Workflow | Core posting |
| --- | --- |
| Sales invoice | Accounts receivable / sales revenue / output VAT; cost of sales and inventory for stocked products |
| Customer payment | Cash and bank / accounts receivable |
| Supplier bill | Inventory or direct cost plus input VAT / accounts payable |
| Supplier payment | Accounts payable / cash and bank |
| Expense | Operating expense plus input VAT / cash and bank |

All journal entries are company-scoped and balanced. A quotation must be accepted before invoice conversion, and converted quotations are locked to preserve the invoice audit trail.

## QuickBooks migration process

The application does not connect directly to a QuickBooks account. It supports a safe, reviewable **CSV migration** from QuickBooks exports.

1. Back up/export the QuickBooks company data and agree a PowerBooks cut-over date.
2. Export customers, suppliers, products/services, the opening trial balance, historical sales invoices, and historical supplier bills as required.
3. In PowerBooks, open **Import data** and download the matching template for each data type.
4. Copy the exported data into the template headings, validate a small sample first, then import in this order:
   1. Customers
   2. Suppliers
   3. Items and services
   4. Opening trial balance
   5. Historical sales invoices, if historical document visibility is required
   6. Historical supplier bills, if historical document visibility is required
5. Reconcile customers, suppliers, opening journal totals, invoice/bill totals, payment balances, VAT, and stock quantities against QuickBooks before the cut-over is approved.

### Import safeguards

- Every import creates an audit batch showing the original filename, date, imported count, skipped count, and row-level errors.
- Opening balances must be a single balanced trial balance and may only be imported once per business.
- Historical invoices and bills are processed one document at a time in a database transaction. A malformed document is skipped entirely, with no partial customer, document, payment, or journal records left behind.
- Historical document templates require only their documented mandatory columns. Due dates, email addresses, and payment columns are optional.
- Historical invoices/bills are ledger and receivable/payable imports; they deliberately do not fabricate past stock movements. Establish opening stock through the Inventory screen or a reconciled stock-opening process.

Do not upload live client exports until the client has approved the export scope and the cut-over reconciliation plan.

## Local verification

Requirements: PHP and Composer compatible with the committed Laravel dependencies. Node.js is only needed when rebuilding frontend assets; the hosting environment is constrained to Node 17.9, so do not upgrade the server runtime without a separate compatibility review.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan test --compact
```

The test suite covers authentication, quotations, sales invoices, purchase bills, payments, inventory, expenses, reporting, ledger posting, company settings, team access, and QuickBooks-ready CSV imports.

## Production deployment (Plesk without SSH)

1. Push the verified release to the `main` branch of the GitHub repository.
2. In Plesk, open the existing PowerBooks domain and its configured Git/Laravel deployment entry.
3. Use the existing **Pull/Deploy** action. Do not use **Install Application** against a live site.
4. Run pending Laravel migrations through the existing Plesk Laravel tooling only when a release contains a migration.
5. Verify `/login`, `/dashboard`, `/quotations`, `/invoices`, `/purchases`, `/inventory`, `/reports`, and `/imports` in the live application.
6. Do not submit client CSV data during technical verification.

## Production checklist before client handover

- Confirm the company legal name, VAT registration number, address, and contact details in **Business settings**.
- Confirm administrator username/password handling and create only required team accounts.
- Confirm the VAT rate(s), chart of accounts, payment terms, and document wording with the accountant.
- Import and reconcile approved QuickBooks exports.
- Create a test quotation, accept it, convert it to an invoice, record a test payment, and confirm the ledger/report results before client go-live.
- Configure email delivery separately if email sending is required; it is not assumed configured merely because documents are printable.
