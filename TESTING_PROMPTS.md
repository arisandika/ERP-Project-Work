# TESTING PROMPTS - NEXICON ERP SYSTEM
## Panduan Testing Komprehensif Per Modul

---

## 📌 MODULE 1: HR (HUMAN RESOURCES)

### A. DEPARTMENT TESTING

#### Unit Test - Department Creation
```gherkin
Scenario: Membuat Department Baru
  Given User memiliki role HR Manager
  When Saya membuat department dengan:
    | Field      | Value           |
    | Nama       | Finance         |
    | Deskripsi  | Div Keuangan    |
    | Status     | Active          |
  Then Department tersimpan di database
  And Department list menampilkan Finance
  And Activity log mencatat create action

Scenario: Validasi Nama Department Unik
  Given Department "Finance" sudah ada
  When Saya membuat department dengan nama "Finance"
  Then Error: "Department name sudah terdaftar"
  And Form tidak ter-submit

Scenario: Edit Department
  Given Department "Finance" sudah ada
  When Saya mengubah nama menjadi "Accounting"
  And Saya update status ke Inactive
  Then Perubahan tersimpan
  And Employee yang sudah assign ke Finance tetap terkait
  And Activity log menunjukkan change history
```

#### Feature Test - Department & Employee Relationship
```gherkin
Scenario: Transfer Employee ke Department Lain
  Given Employee John ada di Department IT
  When HR Manager move John ke Department Finance
  Then Employee record update department
  And Leave balance per department ter-reset
  And Salary component yang department-specific ter-update

Scenario: Delete Department dengan Employee
  Given Department Finance memiliki 5 employees
  When User mencoba delete Department Finance
  Then Warning: "Department masih memiliki 5 employees"
  And Delete butuh reassign semua employees dulu
```

---

### B. EMPLOYEE TESTING

#### Unit Test - Employee Data
```gherkin
Scenario: Registrasi Employee Baru
  Given Form registrasi employee kosong
  When Saya isi:
    | Field           | Value              |
    | Nama            | Budi Santoso       |
    | Email           | budi@nexicon.com   |
    | NIK             | 1234567890123456   |
    | Department      | IT                 |
    | Join Date       | 01-06-2024         |
    | Status          | Active             |
  Then Employee tersimpan
  And User account auto-created
  And Default password dikirim via email
  And Employee list menampilkan Budi

Scenario: Validasi NIK Unik
  Given Employee dengan NIK 1234567890123456 sudah ada
  When Saya buat employee baru dengan NIK sama
  Then Error: "NIK sudah terdaftar"

Scenario: Validasi Email Format & Unik
  When Saya input email "invalid-email"
  Then Error: "Format email tidak valid"
  When Saya input email "existing@nexicon.com"
  Then Error: "Email sudah digunakan"

Scenario: Employee Status Workflow
  Given Employee baru dengan status "Active"
  When Employee resign (status = "Inactive")
  Then Employee tidak muncul di active list
  And Employee masih terlihat di history
  And All credentials should be revoked
```

#### Feature Test - Employee Lifecycle
```gherkin
Scenario: Employee Onboarding Process
  Given Employee baru "Andi" telah di-register
  When HR Manager assign ke Department IT
  And Set supervisor ke "Manager IT"
  And Assign shift "9AM-5PM"
  Then Employee dapat login
  And Employee melihat dashboard
  And Supervisor dapat monitor Andi

Scenario: Employee Promotion
  Given Budi adalah Senior Developer di IT
  When HR Manager promote Budi menjadi Lead Developer
  And Update salary dari 10M menjadi 15M
  Then All historical data tetap intact
  And New salary applicable from date tertentu
  And Promotion record tersimpan di employee history
```

---

### C. SHIFT MANAGEMENT

#### Unit Test - Shift Configuration
```gherkin
Scenario: Buat Shift Baru
  Given Form create shift
  When Saya input:
    | Field       | Value     |
    | Nama Shift  | Morning   |
    | Jam Masuk   | 07:00     |
    | Jam Keluar  | 15:30     |
    | Working Day | Mon-Fri   |
  Then Shift tersimpan
  And Shift dapat di-assign ke employee

Scenario: Validasi Jam Kerja
  When Saya buat shift dengan:
    | Jam Masuk | 08:00 |
    | Jam Keluar| 07:30 |
  Then Error: "Jam keluar harus setelah jam masuk"

Scenario: Multiple Shift per Hari
  Given Sudah ada shift Morning (7:00-15:30)
  When Saya buat shift Evening (15:00-23:00)
  Then Kedua shift dapat co-exist
  And Ada overlap warning: "15:00-15:30"
```

#### Feature Test - Shift Assignment
```gherkin
Scenario: Assign Shift ke Employee
  Given Shift Morning sudah ada
  When HR assign shift ke employee Budi
  Then Budi's attendance harus follow shift schedule
  And Presensi tidak valid jika di luar jam shift

Scenario: Change Employee Shift
  Given Budi di shift Morning (7:00-15:30)
  When Change ke Evening (15:00-23:00) effective tanggal 01-07-2024
  Then:
    - Attendance sebelum 01-07 check Morning shift
    - Attendance dari 01-07 check Evening shift
    - History mencatat shift change
```

---

### D. ATTENDANCE TRACKING

#### Unit Test - Attendance Recording
```gherkin
Scenario: Clock In - Clock Out
  Given Employee Budi shift Morning (7:00-15:30)
  When Budi clock in pada 07:15
  Then Status: "Masuk"
  And Check in time: 07:15
  And Late status: Yes (15 min late)

  When Budi clock out pada 15:45
  Then Status: "Pulang"
  And Check out time: 15:45
  And Overtime: 15 minutes

Scenario: Tanpa Clock Out
  Given Budi clock in 07:10
  When Hari berakhir tanpa clock out
  Then Status: "Incomplete"
  And Warning: "Missing clock out"
  And Admin dapat manual input clock out

Scenario: Multiple Clock In/Out (Tidak Valid)
  Given Employee sudah clock in hari ini
  When Budi clock in lagi pada jam 09:00
  Then Error: "Sudah clock in hari ini"
  And Button clock in disabled sampai clock out

Scenario: Overtime Calculation
  Given Shift 7:00-15:30 (8.5 jam)
  When Employee clock out 17:00
  Then Overtime: 1.5 hours
  And OT pay calculated correctly
```

#### Feature Test - Attendance Validation & Reports
```gherkin
Scenario: Late Attendance Detection
  Given Shift starts 08:00
  When Employee clock in 08:30
  Then Status: "Late"
  And Late duration: 30 minutes
  And Auto-log di attendance history

Scenario: Absence Detection
  Given Today adalah working day, employee shift active
  When Employee tidak clock in sampai end of day
  Then Status: "Absent"
  And Absence notification sent ke manager
  And Can be marked as sick leave by manager

Scenario: Monthly Attendance Report
  When User generate attendance report for month
  Then Report show:
    - Total working days
    - Total present
    - Total absent
    - Total late
    - Total overtime hours
    - Average working hours
```

---

### E. LEAVE MANAGEMENT

#### Unit Test - Leave Configuration
```gherkin
Scenario: Set Leave Balance per Employee
  Given Employee Budi
  When HR setup:
    | Leave Type    | Balance | Unit   |
    | Annual Leave  | 12      | Days   |
    | Sick Leave    | 10      | Days   |
    | Cuti Bersama  | 5       | Days   |
  Then Balances tersimpan
  And Employee dapat request sesuai balance

Scenario: Leave Year Configuration
  Given Leave year setup: 01-Jan to 31-Dec
  When Year berakhir
  Then Unused leave bisa carry forward (max 5 days)
  And Remaining balance reset untuk tahun baru
```

#### Feature Test - Leave Request Workflow
```gherkin
Scenario: Create Leave Request
  Given Budi memiliki 12 Annual Leave days
  When Budi request leave:
    | Field       | Value           |
    | Leave Type  | Annual Leave    |
    | Start Date  | 15-06-2024      |
    | End Date    | 19-06-2024      |
    | Reason      | Liburan Keluarga|
    | Days        | 5               |
  Then Request created dengan status "Pending"
  And Notification sent ke supervisor
  And Leave balance still 12 (pending)

Scenario: Supervisor Approval
  Given Leave request pending
  When Supervisor approve request
  Then Status = "Approved"
  And Leave balance Budi = 7 (12-5)
  And Notification sent ke employee
  And Calendar updated menunjukkan leave days

Scenario: Supervisor Rejection
  Given Leave request pending
  When Supervisor reject dengan alasan "Project deadline"
  Then Status = "Rejected"
  And Leave balance tetap 12
  And Notification with rejection reason sent ke Budi

Scenario: Overlapping Leave Request
  Given Budi sudah approve leave 15-19 Juni
  When Budi request leave 18-22 Juni
  Then Warning: "Overlapping dengan approved leave"
  And System suggest alternative dates

Scenario: Bulk Leave (Cuti Bersama)
  Given Cuti Bersama 5 hari setup untuk tim
  When HR create bulk leave approval
  Then Semua employee auto-marked leave
  And System deduct dari Cuti Bersama balance
  And Calendar show company-wide holiday
```

#### Feature Test - Leave Approval Matrix
```gherkin
Scenario: Multi-Level Approval
  Given Budi (employee) → Andi (supervisor) → Manager
  When Budi submit leave request
  Then Andi terima approval task
  When Andi approve
  Then Task escalate ke Manager
  When Manager approve
  Then Leave fully approved

Scenario: Reject at Any Level
  When Manager reject approval dari Andi
  Then Status = "Rejected"
  And Revert ke Budi dengan reason
  And Budi dapat re-submit atau adjust
```

---

### F. HOLIDAY SETUP

#### Unit Test - Holiday Configuration
```gherkin
Scenario: Create Company Holiday
  Given Form create holiday
  When Input:
    | Field       | Value             |
    | Name        | Lebaran           |
    | Date        | 10-06-2024        |
    | Type        | National Holiday  |
    | Days        | 1                 |
  Then Holiday tersimpan
  And Tidak bisa overlap

Scenario: Multi-Day Holiday
  When Create holiday 4-day Lebaran (8-11 Juni)
  Then Holiday cover 4 days
  And Attendance validation use holiday settings

Scenario: Regional Holiday
  When Setup holiday khusus region Jawa Timur
  Then Holiday only apply ke employees di region tsb
  And Employees di region lain tetap working
```

#### Feature Test - Holiday Impact
```gherkin
Scenario: Holiday tidak hitung sebagai working day
  Given Tanggal 01 Juli adalah holiday
  When Generate monthly report
  Then Total working days exclude 01 Juli
  And Absence pada holiday tidak dicatat

Scenario: Holiday Override untuk shift tertentu
  Given Tanggal 01 Juli holiday
  When Security team must work pada tanggal ini
  Then Shift marked as "Holiday Assignment"
  And OT calculated 2x rate
```

---

## 📦 MODULE 2: INVENTORY MANAGEMENT

### A. PRODUCT MASTER DATA

#### Unit Test - Product Creation
```gherkin
Scenario: Create Product Baru
  Given Form create product kosong
  When Input:
    | Field              | Value           |
    | SKU                | PRD-001         |
    | Nama Product       | Laptop Dell     |
    | Kategori           | Electronics     |
    | Unit               | Pieces          |
    | Harga Beli         | 8,000,000       |
    | Harga Jual         | 10,000,000      |
    | Stock Min          | 5               |
    | Stock Max          | 50              |
    | Deskripsi          | Dell Inspiron   |
  Then Product tersimpan
  And SKU unique check passed
  And Product appear di master list

Scenario: Validasi SKU Unik
  Given Product SKU PRD-001 sudah ada
  When Buat product dengan SKU PRD-001
  Then Error: "SKU sudah terdaftar"

Scenario: Validasi Harga
  When Input Harga Jual < Harga Beli
  Then Error: "Harga jual harus lebih besar dari harga beli"

Scenario: Batch/Lot Tracking (Untuk Pharmaceutical/Food)
  When Create product dengan enable batch tracking
  Then:
    - Serial Number field active
    - Expiry date field active
    - Batch management page available
```

#### Feature Test - Product Hierarchy
```gherkin
Scenario: Product dengan Multiple Unit
  Given Product Laptop dapat dijual dalam:
    | Unit        | Konversi |
    | Pieces      | 1        |
    | Box (10pcs) | 10       |
    | Pallet      | 100      |
  When Customer order 1 box
  Then System correctly convert ke 10 pieces
  And Inventory deduct 10 pieces
```

---

### B. WAREHOUSE MANAGEMENT

#### Unit Test - Warehouse Setup
```gherkin
Scenario: Create Warehouse
  Given Form create warehouse
  When Input:
    | Field           | Value        |
    | Nama Warehouse  | Jakarta Main |
    | Lokasi          | Jl Sudirman  |
    | Kapasitas       | 10,000 units |
    | Tipe            | Main         |
  Then Warehouse tersimpan
  And Warehouse menjadi default untuk stock tracking

Scenario: Multiple Warehouse
  Given Sudah ada Jakarta Main warehouse
  When Create Surabaya Branch warehouse
  Then Kedua warehouse independent
  And Transfer stock possible antar warehouse
```

#### Feature Test - Stock Management Per Warehouse
```gherkin
Scenario: Stock Monitoring Per Warehouse
  Given Product A:
    | Warehouse      | Stock |
    | Jakarta Main   | 100   |
    | Surabaya Branch| 50    |
  When Generate stock report
  Then Show:
    - Total stock: 150
    - Per warehouse detail
    - Stock location by warehouse

Scenario: Warehouse Transfer
  Given Product A (100 units) di Jakarta Main
  When Create transfer 30 units ke Surabaya
  Then:
    - Jakarta Main: 70 units
    - Surabaya: 80 units
    - Transfer history recorded
```

---

### C. STOCK TRANSACTION & MOVEMENT

#### Unit Test - Stock Transaction
```gherkin
Scenario: Inbound Stock (Goods Receipt)
  Given PO dari supplier untuk 100 units Product A
  When Create Goods Receipt:
    | Field           | Value      |
    | Received Qty    | 100        |
    | Warehouse       | Jakarta    |
    | Batch Number    | BATCH-2024 |
    | Expiry Date     | 01-06-2025 |
  Then Transaction recorded
  And Stock updated: 100 units
  And Transaction type: "Inbound"
  And Batch/Lot info stored

Scenario: Outbound Stock (Sales)
  Given Product A stock 100 units
  When SO created & completed:
    | SO No       | Product A Qty |
    | SO-001      | 30            |
  Then Stock deduct: 30 units
  And Transaction type: "Outbound - Sales"
  And FIFO/LIFO method applied

Scenario: Stock Adjustment
  Given Stock record 100 units, tapi fisik cuma 95
  When Create adjustment dengan qty 95
  Then Stock updated 95
  And Variance recorded: 5 units loss
  And Reason dapat di-note
```

#### Feature Test - Low Stock Alert
```gherkin
Scenario: Automatic Low Stock Detection
  Given Product A:
    | Stock Min | Current Stock |
    | 10        | 8             |
  When Stock check daily
  Then Alert: "Product A low stock"
  And Notification sent ke Purchase Manager
  And Auto-generate PO recommendation

Scenario: Stock Out
  Given Product X stock = 0
  When Customer request SO
  Then Back Order option available
  And Can fulfill when stock available
```

---

### D. PACKAGE & BUNDLE PRODUCTS

#### Unit Test - Package Configuration
```gherkin
Scenario: Create Product Bundle/Package
  Given Create package "Office Starter Kit"
  When Add items:
    | Item                | Qty |
    | Laptop              | 1   |
    | Mouse               | 2   |
    | Keyboard            | 1   |
    | Monitor 24"         | 1   |
  Then Package created
  And Total package price = sum of items
  And Discount can be applied

Scenario: Package Stock Management
  When Package stock reserved
  Then Component stock automatically reserved
  And If component stock insufficient, package not available
```

#### Feature Test - Bundle Sales
```gherkin
Scenario: Sell Package/Bundle
  Given Customer order "Office Starter Kit"
  When SO line item is package
  Then:
    - All components deducted from stock
    - Package discount applied
    - Single invoice line for package
    - Fulfillment shows component items
```

---

### E. SERIAL NUMBER TRACKING (For High-Value Items)

#### Unit Test - Serial Number Management
```gherkin
Scenario: Generate Serial Numbers
  Given Product Laptop dengan serial tracking enabled
  When Goods Receipt 10 units
  Then System auto-generate 10 unique serial numbers
  And Serial format: PRD-001-2024-00001 to 00010

Scenario: Assign Serial to Sales
  Given Customer order 1 Laptop
  When SO fulfilled
  Then Serial number assigned to customer
  And Warranty tracking linked to serial
```

---

## 💰 MODULE 3: SALES & INVOICING

### A. QUOTATION (SALES QUOTE)

#### Unit Test - Quotation Creation
```gherkin
Scenario: Create Sales Quotation
  Given Customer "PT ABC Jaya" sudah ada
  When Create quotation:
    | Field              | Value         |
    | Quote No           | QT-2024-0001  |
    | Customer           | PT ABC Jaya   |
    | Quote Date         | 01-06-2024    |
    | Valid Until        | 15-06-2024    |
    | PIC                | Budi Santoso  |
    | Notes              | Project X     |
  Then Quotation created
  And Status = "Draft"
  And Can add line items

Scenario: Add Line Item to Quotation
  When Add line item:
    | Field       | Value      |
    | Product     | Laptop     |
    | Qty         | 5          |
    | Unit Price  | 10,000,000 |
    | Discount %  | 10         |
  Then Line item calculated:
    - Subtotal: 50,000,000
    - After discount: 45,000,000
  And Total quotation updated

Scenario: Calculate Quotation Total
  Given 3 line items:
    | Item     | Subtotal      |
    | Laptop   | 45,000,000    |
    | Mouse    | 2,500,000     |
    | Software | 5,000,000     |
  And Tax: 10%
  When Calculate total
  Then:
    - Subtotal: 52,500,000
    - Tax (10%): 5,250,000
    - Grand Total: 57,750,000
```

#### Feature Test - Quotation Workflow
```gherkin
Scenario: Send Quotation to Customer
  Given Quotation in Draft status
  When Send quotation
  Then:
    - Status: "Sent"
    - PDF generated
    - Email sent ke customer
    - Customer dapat review via link
    - Quote expiry date set

Scenario: Convert Quotation to Sales Order
  Given Customer accept quotation
  When Click "Convert to SO"
  Then:
    - New SO created dari quotation items
    - SO reference quotation
    - Quotation status: "Converted"
    - Line items copy dengan same pricing

Scenario: Quotation Expiry
  Given Quote valid until 15-06-2024
  When Date passes 15-06-2024
  Then Quote status: "Expired"
  And Cannot convert ke SO
  And Notification sent ke sales person
```

---

### B. SALES ORDER (SO)

#### Unit Test - Sales Order
```gherkin
Scenario: Create Sales Order
  Given Customer "PT ABC Jaya" exist
  When Create SO:
    | Field           | Value          |
    | SO No           | SO-2024-00001  |
    | Customer        | PT ABC Jaya    |
    | SO Date         | 01-06-2024     |
    | Delivery Date   | 10-06-2024     |
    | Delivery Address| Jl Sudirman... |
  Then SO created
  And Status: "Draft"
  And Ready untuk add line items

Scenario: SO Item Pricing
  Given Product Laptop:
    | Master Price | 10,000,000 |
    | Customer Price| 9,500,000  |
  When Create SO for customer
  Then System auto-use customer price: 9,500,000

Scenario: Payment Terms
  When Add payment terms:
    | Field        | Value |
    | Terms        | 30 DP |
    | Due Days     | 30    |
  Then:
    - DP Amount = 30% x grand total
    - Due date = SO date + 30 days
```

#### Feature Test - SO to Delivery to Invoice
```gherkin
Scenario: SO Approval Flow
  Given SO created by sales person
  When Sales Manager review SO
  Then Can approve/reject
  When Approved
  Then Status: "Approved"
  And Ready untuk fulfillment

Scenario: Confirm SO (Ready for Delivery)
  Given Approved SO
  When Sales confirm SO
  Then:
    - Status: "Confirmed"
    - Inventory reserved
    - Picking list generated
    - Ready untuk delivery

Scenario: Create Delivery Order from SO
  Given Confirmed SO
  When Warehouse create delivery
  Then:
    - DO created linked to SO
    - Items ready untuk packing
    - Status: "Ready to Ship"

Scenario: Complete Delivery
  When Delivery completed
  Then:
    - DO status: "Delivered"
    - Inventory updated (deducted)
    - Auto-create invoice
    - Invoice referencing SO & DO

Scenario: Partial Delivery
  Given SO 100 units, Delivered 60 units
  When Create DO for remaining 40 units
  Then:
    - First DO: 60 units (complete)
    - Second DO: 40 units (pending)
    - SO status: "Partially Delivered"
```

---

### C. INVOICE & PAYMENT

#### Unit Test - Invoice Creation
```gherkin
Scenario: Auto-Create Invoice from Delivery
  Given Delivery completed (DO-001)
  When System auto-create invoice
  Then:
    - Invoice No: INV-2024-00001
    - Reference: SO & DO
    - Items: Dari DO line items
    - Status: "Draft"
    - Can edit before finalize

Scenario: Manual Invoice Creation
  Given SO but no delivery
  When Create invoice manually
  Then Can create invoice based on SO
  And Can partial invoice (partial delivery scenario)

Scenario: Invoice Line Items
  Given SO items:
    | Product      | Qty | Unit Price | Discount |
    | Laptop       | 5   | 10M        | 10%      |
    | Mouse        | 10  | 500K       | 5%       |
  When Create invoice
  Then Invoice calculated correctly

Scenario: Tax Calculation
  Given Subtotal: 50,000,000
  And Tax rate: 10%
  When Generate invoice
  Then:
    - Tax amount: 5,000,000
    - Grand total: 55,000,000
```

#### Feature Test - Invoice Workflow
```gherkin
Scenario: Finalize Invoice
  Given Invoice in draft
  When Click "Finalize"
  Then:
    - Status: "Finalized"
    - Due date set
    - Cannot edit line items
    - Can send to customer

Scenario: Send Invoice to Customer
  Given Finalized invoice
  When Send invoice
  Then:
    - PDF generated
    - Email sent
    - Status: "Sent"
    - Customer dapat access via link

Scenario: Record Payment - Full Payment
  Given Invoice 55,000,000
  When Record payment:
    | Amount       | 55,000,000 |
    | Payment Date | 10-06-2024 |
    | Method       | Bank TT    |
    | Reference    | TT-001     |
  Then:
    - Payment recorded
    - Unpaid amount: 0
    - Invoice status: "Paid"
    - Accounting entry: Debit Bank, Credit AR

Scenario: Record Payment - Partial Payment
  Given Invoice 55,000,000
  When Record payment 30,000,000
  Then:
    - Payment recorded
    - Remaining: 25,000,000
    - Status: "Partially Paid"
    - Due date still active

Scenario: Overdue Invoice Reminder
  Given Invoice due date: 10-06-2024
  When Current date: 15-06-2024
  Then:
    - Status: "Overdue"
    - Auto-generate reminder
    - Send reminder email ke customer

Scenario: Invoice Write-Off (Bad Debt)
  Given Overdue invoice, customer unable to pay
  When Record as bad debt
  Then:
    - Status: "Written Off"
    - Accounting entry: Bad debt expense
    - Remove dari AR
```

---

### D. DELIVERY ORDER (DO)

#### Unit Test - Delivery Order
```gherkin
Scenario: Create Delivery Order
  Given Confirmed SO with items
  When Create DO:
    | Field              | Value       |
    | DO No              | DO-2024-001 |
    | Delivery Date      | 10-06-2024  |
    | Driver             | Sopir 1     |
    | Vehicle            | Truck 001   |
  Then DO created
  And Status: "Draft"

Scenario: DO Packing List
  When Generate packing list
  Then Show:
    - All items dengan qty
    - Barcode untuk setiap item
    - Customer info
    - Delivery address
```

#### Feature Test - DO Tracking
```gherkin
Scenario: Mark DO as Shipped
  Given DO ready untuk delivery
  When Mark as shipped
  Then:
    - Status: "In Transit"
    - Driver dapat track via mobile app
    - Customer dapat track via link

Scenario: Confirm Delivery
  When Driver confirm delivery
  Then:
    - Status: "Delivered"
    - Timestamp recorded
    - Proof of delivery (foto/signature) stored
    - Inventory deducted from warehouse

Scenario: Delivery Exception
  When Delivery failed (customer unavailable)
  Then:
    - Status: "Failed"
    - Reason recorded
    - Reschedule option available
```

---

## 🏪 MODULE 4: ACCOUNTING / FINANCIAL

### A. ACCOUNT PAYABLE (AP) - HUTANG

#### Unit Test - AP Recording
```gherkin
Scenario: Record Supplier Invoice (Auto dari PO)
  Given PO-001 untuk 100 units @ 100K = 10M
  When Goods received & GR created
  Then:
    - AP record auto-created
    - Vendor: Supplier ABC
    - Amount: 10,000,000
    - Status: "Unpaid"
    - Accounting entry:
      * Debit: Inventory/Expense (10M)
      * Credit: AP (10M)

Scenario: Manual AP Entry
  When Receive supplier invoice without PO
  Then Can create AP manually:
    - Supplier
    - Invoice no
    - Amount
    - Due date

Scenario: AP Aging Analysis
  When Generate AP aging report
  Then Show:
    - Current (not due): 5M
    - 1-30 days overdue: 2M
    - 31-60 days overdue: 1M
    - >60 days overdue: 0.5M
```

#### Feature Test - AP Payment
```gherkin
Scenario: Record AP Payment
  Given AP 10,000,000
  When Record payment:
    | Amount        | 10,000,000 |
    | Payment Date  | 15-06-2024 |
    | Method        | Bank TT    |
  Then:
    - Payment recorded
    - AP status: "Paid"
    - Accounting entry:
      * Debit: AP (10M)
      * Credit: Bank (10M)

Scenario: Partial AP Payment
  Given AP 10,000,000
  When Pay 6,000,000
  Then:
    - Payment recorded
    - Remaining: 4,000,000
    - AP status: "Partially Paid"
```

---

### B. ACCOUNT RECEIVABLE (AR) - PIUTANG

#### Unit Test - AR Recording
```gherkin
Scenario: Record Customer Invoice (Auto dari SO/DO)
  Given Invoice 55,000,000 created
  Then:
    - AR record auto-created
    - Customer: PT ABC Jaya
    - Amount: 55,000,000
    - Status: "Unpaid"
    - Accounting entry:
      * Debit: AR (55M)
      * Credit: Sales Revenue (55M)

Scenario: AR Aging Report
  When Generate AR aging report
  Then Show:
    - Current: 30M
    - 1-30 days: 15M
    - 31-60 days: 8M
    - >60 days: 2M
```

#### Feature Test - AR Collection
```gherkin
Scenario: Record Customer Payment (Update AR)
  Given AR 55,000,000
  When Record payment 55,000,000
  Then:
    - Payment recorded
    - AR status: "Paid"
    - Accounting entry:
      * Debit: Bank (55M)
      * Credit: AR (55M)

Scenario: Collection Reminder
  Given Invoice overdue 10 days
  When Generate collection report
  Then Show:
    - Overdue amount
    - Contact info
    - Payment history
```

---

### C. FINANCIAL RECONCILIATION

#### Unit Test - Bank Reconciliation
```gherkin
Scenario: Bank Statement Reconciliation
  Given:
    | Bank balance (system) | 100,000,000 |
    | Bank statement       | 98,000,000  |
    | Outstanding checks   | 2,000,000   |
  When Reconcile
  Then Difference: 0 (balanced)

Scenario: Reconciliation Variance
  Given:
    | System balance | 100,000,000 |
    | Bank statement | 97,000,000  |
  When Reconcile
  Then Variance: 3,000,000
  And Flag untuk investigation
```

---

## 👥 MODULE 5: CRM (CUSTOMER RELATIONSHIP MANAGEMENT)

### A. CUSTOMER MASTER

#### Unit Test - Customer Creation
```gherkin
Scenario: Create New Customer
  Given Form create customer
  When Input:
    | Field               | Value           |
    | Tipe Pelanggan      | Company         |
    | Nama Perusahaan     | PT ABC Jaya     |
    | PIC Nama            | Budi Santoso    |
    | Email               | budi@abc.com    |
    | Telepon             | 021-1234567     |
    | Alamat              | Jl Sudirman ... |
    | Kota                | Jakarta         |
    | NPWP/NIK            | 12.345.678.0-123|
    | Tipe Industri       | Manufacturing   |
    | Status              | Active          |
  Then Customer created
  And Customer ID: CUST-2024-001
  And Can save multiple contact persons

Scenario: Customer Validation
  When Input invalid email
  Then Error: "Email format tidak valid"
  When Input duplicate NPWP
  Then Error: "NPWP sudah terdaftar"

Scenario: Multiple Contact Person per Customer
  Given Customer PT ABC Jaya
  When Add contact persons:
    | Nama            | Title      | Email        |
    | Budi Santoso    | Direktur   | budi@abc.com |
    | Andi Wijaya     | Acc Mgr    | andi@abc.com |
  Then Multiple contacts stored
  And Can set default contact
```

#### Feature Test - Customer Segmentation
```gherkin
Scenario: Customer Segment Assignment
  Given Customers:
    | Customer    | Annual Revenue |
    | PT ABC Jaya | 50,000,000     |
    | CV XYZ      | 5,000,000      |
  When Run segmentation
  Then:
    - PT ABC Jaya: "Premium"
    - CV XYZ: "Regular"
  And Segment determines:
    - Pricing tier
    - Payment terms
    - Service level

Scenario: Customer Credit Limit
  When Set customer credit limit: 100,000,000
  And Customer has outstanding AR: 80,000,000
  When Create new SO: 30,000,000
  Then Warning: "Exceed credit limit"
  And Require approval
```

---

### B. LEAD MANAGEMENT

#### Unit Test - Lead Tracking
```gherkin
Scenario: Create Lead
  Given Prospective customer info
  When Create lead:
    | Field           | Value          |
    | Lead Name       | PT Maju Jaya   |
    | Source          | Referral       |
    | Contact Person  | Edi Supartono  |
    | Phone           | 085-1234567    |
    | Budget          | 500,000,000    |
    | Timeline        | 2 bulan        |
    | Assigned To     | Sales Person A |
  Then Lead created
  And Status: "New"

Scenario: Lead Scoring
  When Lead scored based on:
    - Budget size
    - Timeline urgency
    - Industry match
  Then Score calculated
  And High-score leads prioritized
```

#### Feature Test - Lead to Deal Conversion
```gherkin
Scenario: Convert Lead to Deal
  Given Lead with high score
  When Sales person convert to deal
  Then:
    - Deal created
    - Deal stage: "Qualification"
    - Deal value: Per lead budget
    - Lead status: "Converted"

Scenario: Deal Progress Tracking
  Given Deal created
  When Move through stages:
    | Stage           | Action            |
    | Qualification   | Discovery call    |
    | Proposal Sent   | Send quotation    |
    | Negotiation     | Price discussion  |
    | Closed Won      | Create SO         |
    | Closed Lost     | Document reason   |
  Then Track each stage transition
  And Timeline for each stage recorded
```

---

### C. DEAL MANAGEMENT

#### Unit Test - Deal Lifecycle
```gherkin
Scenario: Create Deal from Lead/Scratch
  When Create deal:
    | Field           | Value             |
    | Deal Name       | Project X Deal    |
    | Customer        | PT ABC Jaya       |
    | Deal Value      | 100,000,000       |
    | Stage           | Qualification     |
    | Expected Close  | 30-06-2024        |
    | Probability     | 50%               |
  Then Deal created
  And Revenue forecast = 100M x 50% = 50M

Scenario: Deal Stage Progression
  Given Deal in "Qualification" stage
  When Move to next stage
  Then:
    - Record what was done
    - Update probability
    - Update expected close date
    - Log stage history

Scenario: Lost Deal Analysis
  When Mark deal as "Closed Lost"
  Then:
    - Require reason for loss
    - Competitive info documented
    - Can reopen if needed
    - Learning untuk future
```

#### Feature Test - Sales Pipeline
```gherkin
Scenario: Sales Pipeline Report
  When Generate pipeline report
  Then Show:
    | Stage             | Count | Total Value | Probability | Forecast |
    | Qualification     | 5     | 250M        | 20%         | 50M      |
    | Proposal          | 3     | 300M        | 50%         | 150M     |
    | Negotiation       | 2     | 200M        | 75%         | 150M     |
    | Closed Won (MTD)  | 4     | 400M        | 100%        | 400M     |
    | Total Forecast    |       |             |             | 750M     |

Scenario: Deal Activity Tracking
  When Log call/meeting/email for deal
  Then:
    - Activity recorded
    - Timeline shown
    - Auto-update last contact date
    - Can attach documents/notes
```

---

## 📦 MODULE 6: PROCUREMENT (PURCHASE)

### A. PURCHASE REQUISITION (PR)

#### Unit Test - PR Creation
```gherkin
Scenario: Create Purchase Requisition
  Given Department IT needs supplies
  When Create PR:
    | Field              | Value        |
    | PR No              | PR-2024-001  |
    | Requesting Dept    | IT           |
    | Approver           | Dept Manager |
    | Required Date      | 10-06-2024   |
    | Priority           | High         |
  And Add items:
    | Product        | Qty | Reason              |
    | Laptop         | 2   | Replacement old PC  |
    | Monitor 24"    | 2   | Additional monitor  |
  Then PR created
  And Status: "Draft"

Scenario: PR Approval
  When Department Manager approve PR
  Then:
    - Status: "Approved"
    - Sent to Procurement
    - Approval date recorded
```

---

### B. PURCHASE ORDER (PO)

#### Unit Test - PO Management
```gherkin
Scenario: Create PO from Approved PR
  Given Approved PR
  When Procurement officer create PO
  Then:
    - PO No: PO-2024-001
    - Link ke PR
    - Supplier selected
    - Payment terms: 30 days
    - Delivery date: 10-06-2024

Scenario: PO Line Item Details
  Given Add PO line:
    | Field        | Value      |
    | Product      | Laptop     |
    | Qty          | 2          |
    | Unit Price   | 9,000,000  |
    | Total        | 18,000,000 |
    | Delivery     | 10-06-2024 |
  Then Calculated correctly
  And Total PO: 18M

Scenario: PO Approval Flow
  When PO ready untuk approval
  Then:
    - CFO review if > threshold
    - Approval recorded
    - Status: "Approved"
    - Ready untuk send to supplier
```

#### Feature Test - PO to Receipt
```gherkin
Scenario: Send PO to Supplier
  When Send PO to supplier
  Then:
    - Status: "Sent"
    - Email sent dengan PDF
    - Reference saved
    - Expected delivery tracked

Scenario: Goods Receipt (GR)
  Given PO approved, goods arrived
  When Warehouse create GR:
    | Field           | Value                |
    | PO No           | PO-2024-001          |
    | Qty Received    | 2 Laptop             |
    | Batch Number    | BATCH-2024-001       |
    | Expiry (if any) | 31-12-2025           |
  Then:
    - GR created
    - Inventory updated: +2 Laptop
    - AP record created
    - PO status: "Received"

Scenario: GR Variance
  Given PO qty 2, received 1
  When Create GR for 1 unit
  Then:
    - GR qty: 1
    - PO balance: 1 unit pending
    - Can receive remaining later
```

---

### C. SUPPLIER MANAGEMENT

#### Unit Test - Supplier Master
```gherkin
Scenario: Create Supplier
  When Create supplier:
    | Field           | Value          |
    | Supplier Name   | Supplier ABC   |
    | Alamat          | Jl Gatot Subroto |
    | NPWP            | 12.345.678.0-123 |
    | PIC             | Andi Wijaya    |
    | Email           | andi@supplier.com |
    | Telepon         | 021-9876543    |
    | Payment Terms   | Net 30         |
    | Bank Account    | BCA xxxxxx     |
  Then Supplier created
  And Status: "Active"

Scenario: Supplier Rating
  When Evaluate supplier based on:
    - Quality
    - On-time delivery
    - Price competitiveness
  Then Rating calculated
  And Display in supplier profile
```

---

## 🎯 MODULE 7: PROJECT MANAGEMENT

### A. PROJECT & TICKET SETUP

#### Unit Test - Project Creation
```gherkin
Scenario: Create Project
  When Create project:
    | Field           | Value          |
    | Project Name    | Project X      |
    | Client          | PT ABC Jaya    |
    | Start Date      | 01-06-2024     |
    | End Date        | 31-12-2024     |
    | Project Manager | Rina Wijaya    |
    | Budget          | 500,000,000    |
    | Status          | Planning       |
  Then Project created
  And Project workspace ready

Scenario: Create Ticket/Task
  Given Project created
  When Create ticket:
    | Field           | Value                |
    | Ticket No       | TKT-001              |
    | Title           | Design UI Dashboard  |
    | Description     | Complete UI mockup   |
    | Assigned To     | Developer A          |
    | Priority        | High                 |
    | Start Date      | 05-06-2024           |
    | Due Date        | 15-06-2024           |
    | Status          | To Do                |
  Then Ticket created
  And Activity log recorded
```

#### Feature Test - Ticket Workflow
```gherkin
Scenario: Ticket Status Flow
  Given Ticket created with status "To Do"
  When Developer start work
  Then Change to "In Progress"
  When Work done
  Then Change to "In Review"
  When QA approve
  Then Change to "Done"

Scenario: Ticket Comments & Collaboration
  Given Ticket in progress
  When Team member add comment
  Then Comment recorded + timestamp
  And @mention notify team
  And Attachment dapat ditambahkan

Scenario: Time Tracking (Optional)
  When Developer log time spent
  Then:
    - Estimated: 8 hours
    - Actual: 10 hours
    - Variance tracked
    - Total project time aggregated
```

---

## 📞 MODULE 8: SALES ACTIVITY & VISIT TRACKING

### A. VISIT ASSIGNMENT

#### Unit Test - Visit Assignment
```gherkin
Scenario: Create Visit Assignment
  Given Sales person: Budi Santoso
  When Create visit assignment:
    | Field              | Value              |
    | Sales Person       | Budi Santoso       |
    | Customer           | PT ABC Jaya        |
    | Visit Date         | 10-06-2024         |
    | Purpose            | Product Demo       |
    | Priority           | High               |
    | Assigned By        | Sales Manager      |
  Then Assignment created
  And Status: "Assigned"
  And Notification sent ke Budi

Scenario: Visit Assignment Rescheduling
  Given Visit assignment untuk 10-06-2024
  When Budi tidak bisa (urgent customer matter)
  Then Can request reschedule
  And Manager approve new date: 12-06-2024
  And Assignment updated
  And Notification sent to customer (if applicable)
```

#### Feature Test - Visit Tracking
```gherkin
Scenario: Record Visit Execution
  Given Visit assignment for today
  When Budi start visit:
    | Field           | Value         |
    | Check In Time   | 10:30         |
    | Location (GPS)  | -6.2088,106.8456 |
  Then Visit status: "In Progress"
  And GPS location stored
  And Check-in timestamp recorded

Scenario: Complete Visit with Report
  When Budi complete visit:
    | Field              | Value               |
    | Check Out Time     | 11:45               |
    | Visit Outcome      | Demo successful     |
    | Next Follow-up     | 15-06-2024          |
    | Note               | Customer interested |
  Then:
    - Status: "Completed"
    - Duration: 1 hour 15 min
    - Report stored
    - Activity logged

Scenario: Visit Photo Documentation
  When Budi upload photos during visit:
    | Photo 1: Product display |
    | Photo 2: Customer signature |
  Then Photos stored dengan timestamp
  And Can use as proof of visit
  And Attached to visit report
```

---

### B. VISIT RECORDS & ANALYTICS

#### Unit Test - Visit Analytics
```gherkin
Scenario: Visit Statistics Report
  When Generate weekly visit report
  Then Show:
    - Total visits: 25
    - Completed: 23
    - Cancelled: 1
    - Rescheduled: 1
    - Average duration: 1.5 hours
    - Per salesperson breakdown

Scenario: Customer Visit History
  Given Customer PT ABC Jaya
  When View visit history
  Then Show all visits chronologically:
    | Date       | Sales Person | Purpose        | Outcome |
    | 01-06-2024 | Budi         | Initial Demo   | Interested |
    | 05-06-2024 | Budi         | Proposal Review| Demo given |
    | 10-06-2024 | Andi         | Follow-up      | Quote requested |
```

#### Feature Test - Visit Performance Metrics
```gherkin
Scenario: Sales Person Productivity
  When Analyze sales person performance:
    - Visits completed per week
    - Conversion rate (Visit → Deal)
    - Average deal value per visit
  Then Display dashboard dengan metrics
  And Identify top performers
  And Identify underperformers untuk coaching

Scenario: Territory Visit Coverage
  Given Sales territory setup
  When Generate coverage report
  Then Show:
    - % of customers visited this month
    - Unvisited customers
    - Visit frequency per customer
    - Recommendation: Priority visits
```

---

## 💳 MODULE 9: FINANCE - REIMBURSEMENT

### A. REIMBURSEMENT REQUEST

#### Unit Test - Reimbursement Request
```gherkin
Scenario: Create Reimbursement Request
  Given Employee Budi attended conference with personal payment
  When Create reimbursement request:
    | Field              | Value              |
    | Request Date       | 10-06-2024         |
    | Purpose            | Training Conference|
    | Reason             | Company business   |
    | Total Amount       | 5,000,000          |
  And Add expense items:
    | Item              | Amount      | Receipt |
    | Ticket            | 3,000,000   | scan.pdf |
    | Hotel (2 nights)  | 2,000,000   | scan.pdf |
  Then Request created
  And Status: "Draft"
  And Total: 5,000,000

Scenario: Reimbursement Validation
  When Add expense without receipt
  Then Warning: "Receipt required"
  When Add expense > 1M without manager approval
  Then Auto-escalate untuk director approval

Scenario: Duplicate Prevention
  Given Previous reimbursement request untuk training
  When Create new request dengan same receipt
  Then Warning: "Receipt sudah digunakan di request lain"
```

#### Feature Test - Reimbursement Approval
```gherkin
Scenario: Reimbursement Approval Flow
  Given Draft reimbursement request
  When Submit untuk approval
  Then:
    - Status: "Pending Manager"
    - Assigned ke Budi's manager

  When Manager review
  Then Can approve/reject/request revision
  
  When Approved by manager
  If total > threshold
    Then escalate ke finance director
  Else
    Then Status: "Approved"
    And Payment scheduled

Scenario: Payment Processing
  Given Approved reimbursement 5,000,000
  When Finance officer process payment
  Then:
    - Payment method selected: Bank transfer
    - Accounting entry created:
      * Debit: Expense category
      * Credit: Bank
    - Status: "Paid"
    - Employee notified

Scenario: Partial Reimbursement
  Given Request 5,000,000
  When Manager approve only 3,000,000 (hotel not covered)
  Then:
    - Approved amount: 3,000,000
    - Rejected items noted
    - Employee can appeal
```

---

### B. FINANCIAL RECORDS & BUDGETING

#### Unit Test - Financial Records
```gherkin
Scenario: Record Manual Financial Entry
  When Finance officer create financial record:
    | Field           | Value          |
    | Category        | Office Supply  |
    | Amount          | 500,000        |
    | Date            | 10-06-2024     |
    | Reference       | Invoice #123   |
    | Approver        | Finance Head   |
  Then Record created
  And Accounting entry: Debit Expense, Credit Bank

Scenario: Budget vs Actual Tracking
  Given Monthly budget: 100,000,000
  When Track actual spending
  Then Show:
    | Category        | Budget  | Actual | Variance |
    | Office Supply   | 10M     | 8.5M   | -1.5M    |
    | Travel          | 20M     | 22M    | +2M      |
    | Total           | 100M    | 98M    | -2M      |
```

#### Feature Test - Financial Analysis
```gherkin
Scenario: Department Budget Monitoring
  When Monitor department spending
  Then Alert if:
    - Spending exceeds 80% of budget
    - Unusual spike in category
    - Missing documentation

Scenario: Year-over-Year Comparison
  When Generate YoY financial report
  Then Show comparison:
    | Category    | 2023  | 2024  | % Change |
    | Salary      | 500M  | 550M  | +10%     |
    | Office      | 50M   | 48M   | -4%      |
    | Travel      | 100M  | 120M  | +20%     |
```

---

## 🛒 MODULE 10: MARKETING & PROMOTIONS

### A. SLIDER/BANNER MANAGEMENT

#### Unit Test - Slider Setup
```gherkin
Scenario: Create Homepage Slider
  When Create slider:
    | Field              | Value              |
    | Name               | Summer Promo 2024  |
    | Position           | Homepage Hero      |
    | Status             | Active             |
    | Display Order      | 1                  |
    | Effective Date     | 01-06-2024         |
    | End Date           | 30-06-2024         |
  Then Slider created
  And Can upload banner images

Scenario: Add Slider Content
  When Add slide:
    | Field      | Value                    |
    | Image      | promo-summer.jpg         |
    | Heading    | Summer Sale 50% Off      |
    | Description| Limited time offer       |
    | CTA Link   | /products/summer-sale    |
    | CTA Button | Shop Now                 |
  Then Slide stored
  And Can preview

Scenario: Slider Ordering
  Given 3 slides created
  When Reorder slides
  Then Display berdasarkan order
  And First slide shows first
```

#### Feature Test - Slider Display
```gherkin
Scenario: Slider Rotation on Frontend
  When Customer visit homepage
  Then Slider automatically rotate:
    - Slide 1: 5 seconds
    - Slide 2: 5 seconds
    - Slide 3: 5 seconds
    - Loop back to Slide 1

Scenario: Slider Click Tracking
  When Customer click CTA button
  Then Track:
    - Click count per slide
    - Conversion rate per slide
    - User journey (slide → product view → purchase)
```

---

### B. POPUP BANNER CAMPAIGNS

#### Unit Test - Popup Banner
```gherkin
Scenario: Create Popup Campaign
  When Create popup:
    | Field              | Value                |
    | Campaign Name      | Newsletter Sign-up   |
    | Trigger            | On Page Load         |
    | Delay (seconds)    | 3                    |
    | Display Frequency  | Once per session     |
    | Target Page        | Homepage             |
    | Status             | Active               |
  Then Popup created
  And Can customize content

Scenario: Popup Content
  When Add popup content:
    | Heading   | Join Our Newsletter        |
    | Message   | Get 20% off on first order |
    | Image     | newsletter-banner.jpg      |
    | Button 1  | Subscribe (Green)          |
    | Button 2  | Not Now (Gray)             |
  Then Content stored
  And Preview available

Scenario: Popup Display Rules
  When Set rules:
    - Show on: Desktop + Mobile
    - Exclude users: Newsletter subscribers
    - Show: Every 7 days if dismissed
  Then Rules applied
```

#### Feature Test - Popup Analytics
```gherkin
Scenario: Popup Performance Tracking
  When Analyze popup campaign
  Then Show metrics:
    - Impressions: 10,000
    - Click-through rate: 25%
    - Conversion rate: 10%
    - Bounce rate: 65%

Scenario: A/B Testing Popup
  When Create variation of popup
  Then Split traffic 50-50
  And Track performance of each variant
  And Winner analysis
```

---

### C. PROMO CODE MANAGEMENT

#### Unit Test - Promo Code Creation
```gherkin
Scenario: Create Promo Code
  When Create promo code:
    | Field              | Value           |
    | Code               | SUMMER50        |
    | Description        | Summer 50% Off  |
    | Discount Type      | Percentage      |
    | Discount Value     | 50              |
    | Max Uses           | 1000            |
    | Min Order Value    | 500,000         |
    | Valid From         | 01-06-2024      |
    | Valid Until        | 30-06-2024      |
    | Status             | Active          |
  Then Promo code created
  And Can be used in checkout

Scenario: Promo Code Validation
  When Input code "SUMMER50" at checkout
  Then Verify:
    - Code exists
    - Code is active
    - Current date within valid range
    - Order value meets minimum
    - Uses < max limit
  Then Apply discount

Scenario: Invalid Promo Code
  When Input code "INVALID123"
  Then Error: "Promo code tidak valid"
  When Try using expired code
  Then Error: "Promo code telah kadaluarsa"
```

#### Feature Test - Promo Analytics
```gherkin
Scenario: Promo Code Usage Report
  When Generate promo report
  Then Show:
    | Code     | Uses | Revenue | Avg Order |
    | SUMMER50 | 250  | 62.5M   | 250K      |
    | WELCOME20| 150  | 30M     | 200K      |

Scenario: Revenue Impact Analysis
  When Analyze promo impact
  Then Calculate:
    - Total discount given: 15M
    - Revenue gained: 62.5M
    - Net revenue: 47.5M
    - ROI: Positive

Scenario: Customer Acquisition via Promo
  When Track customers using promo code
  Then Show:
    - New customers: 120
    - Repeat purchase rate: 35%
    - Customer lifetime value
```

---

## 🚚 MODULE 11: AFTER SALES SERVICE

### A. RETURN REQUEST MANAGEMENT

#### Unit Test - Return Request Creation
```gherkin
Scenario: Create Return Request from Invoice
  Given Customer purchased laptop via INV-2024-001
  When Customer create return request:
    | Field              | Value              |
    | Invoice No         | INV-2024-001       |
    | Product            | Laptop Dell        |
    | Return Reason      | Defective unit     |
    | Return Qty         | 1                  |
    | Serial/Batch       | SN-12345           |
  Then Return request created
  And Status: "Pending Review"
  And Reference: RET-2024-001

Scenario: Return Reason Tracking
  When Select return reason:
    - Defective/Damaged
    - Wrong product sent
    - Customer changed mind
    - Other (specify)
  Then Reason recorded
  And Used untuk quality analysis
```

#### Feature Test - Return Approval & Processing
```gherkin
Scenario: Review Return Request
  Given Return request pending
  When After Sales officer review
  Then Verify:
    - Invoice validity
    - Product condition
    - Warranty status
    - Return policy compliance
  When Approved
  Then:
    - Status: "Approved"
    - RMA (Return Material Authorization) number: RMA-001
    - Return shipping label generated
    - Customer notified

Scenario: Return Rejection
  When Reject return (outside warranty)
  Then:
    - Status: "Rejected"
    - Reason documented: "Outside warranty period"
    - Customer notified with explanation

Scenario: Goods Return Receiving
  When Warehouse receive returned item
  Then:
    - Inspect condition
    - Record physical status
    - QC inspection results
  Based on inspection:
    - If repairable: Send for repair
    - If defective batch: Record for vendor claim
    - If sellable: Return to inventory

Scenario: Return Completion
  When Item processed (repaired/refunded/replaced)
  Then:
    - Status: "Completed"
    - Action taken: Refund/Exchange/Repair
    - Amount processed: 10,000,000
    - Refund method: Original payment method
    - Timeline: 3-5 business days
```

#### Feature Test - Return Analytics
```gherkin
Scenario: Return Rate Analysis
  When Generate return report
  Then Show:
    | Month     | Sales    | Returns | Rate  |
    | May 2024  | 500      | 25      | 5%    |
    | June 2024 | 450      | 18      | 4%    |

Scenario: Defect Analysis
  When Analyze return reasons
  Then Identify:
    - Top defect categories
    - Product quality issues
    - Supplier problems
    - Recommendation: Quality improvement

Scenario: Customer Return History
  Given Customer PT ABC Jaya
  When View return history
  Then Show:
    - Previous returns count
    - Return patterns
    - Total refund amount
    - Warranty claim frequency
```

---

### B. WARRANTY TRACKING

#### Unit Test - Warranty Management
```gherkin
Scenario: Record Product Warranty
  Given Laptop sold on 01-06-2024
  When Register warranty:
    | Field              | Value              |
    | Product            | Laptop Dell        |
    | Serial Number      | SN-12345           |
    | Purchase Date      | 01-06-2024         |
    | Warranty Period    | 12 months          |
    | Warranty End Date  | 01-06-2025         |
  Then Warranty recorded
  And Linked to customer

Scenario: Warranty Validation
  When Customer claim warranty on 20-05-2025
  Then Check:
    - Warranty still active (Yes)
    - Return request approved
    - No damage due to misuse
  Then Proceed dengan claim
```

---

### C. CUSTOMER SUPPORT TICKET

#### Unit Test - Support Ticket
```gherkin
Scenario: Create Support Ticket
  When Customer create support ticket:
    | Field              | Value              |
    | Subject            | Laptop overheating |
    | Category           | Technical Support  |
    | Priority           | High               |
    | Description        | Device gets hot... |
    | Product            | Laptop Dell        |
    | Serial             | SN-12345           |
  Then Ticket created
  And Status: "Open"
  And Assigned to: Support team

Scenario: Ticket Assignment & Response
  When Support team review ticket
  Then:
    - Assign to technician
    - First response within 24 hours
    - Provide troubleshooting steps
    - Track response time

Scenario: Ticket Resolution
  When Issue resolved
  Then:
    - Status: "Closed"
    - Resolution documented
    - Satisfaction survey sent
    - Ticket history archived
```

---

## 🔐 INTEGRATION & SECURITY TESTING

### Cross-Module Integration

```gherkin
Scenario: Sales to Accounting Integration
  Given Customer purchase via Sales module
  When Invoice created
  Then:
    - Inventory auto-deducted
    - AR record auto-created
    - Revenue recognized in GL
    - Sales report includes accounting data

Scenario: HR to Finance Integration
  When Employee salary processed
  Then:
    - Salary expense posted to GL
    - Employee tax calculated
    - Payroll report generated
    - Linked to cost center

Scenario: Procurement to Accounting Integration
  Given PO created & GR received
  When Supplier invoice matched
  Then:
    - AP record created
    - GL entries posted
    - Budget tracked
    - Payment scheduled
```

### Security Testing

```gherkin
Scenario: User Role Permissions
  Given User with role: Sales Person
  When Access Accounting module
  Then Access denied: "Insufficient permission"
  And Activity log: "Unauthorized access attempt"

Scenario: Data Isolation (Multi-tenant if applicable)
  When Company A user access data
  Then Cannot see Company B data
  And Cannot modify other company records

Scenario: Audit Trail
  When Any CRUD operation performed
  Then Log recorded:
    - User ID
    - Operation (Create/Read/Update/Delete)
    - Timestamp
    - Old value (for updates)
    - New value (for updates)
    - IP address
```

---

## ✅ TESTING EXECUTION CHECKLIST

### Pre-Testing Setup
- [ ] Database seeded dengan test data
- [ ] Test user accounts created (role: Admin, Manager, Employee, etc)
- [ ] Test data cleanup automated
- [ ] Email service mocked
- [ ] File storage configured for test

### Unit Test Execution
```bash
php artisan test tests/Unit
```
- [ ] All unit tests passing
- [ ] Code coverage > 80%
- [ ] No deprecated warnings

### Feature Test Execution
```bash
php artisan test tests/Feature
```
- [ ] All feature tests passing
- [ ] Integration between modules verified
- [ ] Edge cases tested

### Manual Testing (Regression)
- [ ] HR Module workflows
- [ ] Inventory stock accuracy
- [ ] Sales to invoice flow
- [ ] Accounting reconciliation
- [ ] CRM pipeline
- [ ] Permission/security

### Performance Testing
- [ ] Page load time < 2s
- [ ] Report generation < 5s
- [ ] Bulk operations efficient

### UAT Test Plan
- [ ] Business users sign off
- [ ] Real scenario testing
- [ ] Data migration testing (if applicable)

---

## 🐛 BUG REPORTING TEMPLATE

```
Title: [Module] Brief description

Environment:
- URL: [link]
- User Role: [role]
- Date/Time: [timestamp]

Steps to Reproduce:
1. [step]
2. [step]
3. [step]

Expected Result:
[what should happen]

Actual Result:
[what actually happened]

Attachments:
- Screenshots
- Error logs
- Video recording (if complex)

Severity: [Critical/High/Medium/Low]
```

