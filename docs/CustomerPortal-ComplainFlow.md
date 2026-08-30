# Customer Portal — Complain / Return Flow

## Overview

Customer bisa langsung ngajuin complain / return request lewat link yang dikirim di email invoice. Gak perlu login manual — link-nya berisi token yang otomatis autentikasi customer.

## Flow End-to-End

### 1. Email Invoice
- **Pencetus**: Sistem kirim invoice via `InvoiceSent` mailable
- **Isi email**: Lampiran PDF + tombol **"Ajukan Complain Sekarang"**
- **Tombol link**: `GET /customer-portal/invoice/{token}` (signed token, 7 hari expiry, max 5 uses)

### 2. Token Resolution
- Route publik `/customer-portal/invoice/{token}`
- Resolve `CustomerPortalToken` by token hash
- Validasi: token masih valid + belum kedaluwarsa
- **Auto-login**: set session `customer_portal_id` + `customer_portal_authenticated`
- Consume: increment usage counter
- **Redirect**: ke `/customer-portal/return/create?invoice_id={id}`

### 3. Return Request Wizard (Livewire)
Multi-step form — `CustomerPortalReturnCreate` component.

#### Step 1: Invoice (auto-prefill)
- Dari URL `?invoice_id=X` → load invoice + items otomatis
- Skip form pencarian invoice kalau sudah prefilled
- List item produk → pilih yang mau diretur

#### Step 2: Detail Keluhan
- Warranty type: `garansi` atau `berbayar`
- Deskripsi kendala (min 10 karakter)

#### Step 3: Identifikasi Barang
- **Jika serialized**: scan SN via scanner → validasi SN milik customer + product ini belum ada RMA aktif
- **Jika non-serialized**: input qty (max = qty di invoice)

#### Step 4: Upload Bukti + Submit
- Upload 1-5 foto (max 5MB each, image only)
- Submit → buat `ReturnRequest`:
  - `status` = `STATUS_RECEIVED`
  - `source` = `SOURCE_CUSTOMER_PORTAL`
  - `created_by` = null
  - Simpan evidence di `rma-evidence/{customer_id}/`

### 4. Admin — After-Sales Management
- **Location**: Filament admin → Manajemen After-Sales → Penerimaan Return
- **View page** (`/admin/after-sales/return-requests/{record}`):
  - Informasi Return (RMA, customer, invoice, keluhan, status)
  - **SN Origin Info**: supplier, PO, inbound/outbound date, warehouse, garansi
  - **Traceability**: riwayat semua stock transactions untuk SN ini (termasuk RMA ke vendor)

#### Actions di View
1. **Kirim ke Vendor** (supplier warranty) → status: `sent_to_vendor`
2. **Terima dari Vendor** → repair/replace + new SN
3. **Proses Internal** (store warranty) → repair/replace dari gudang
4. **Serahkan ke Klien** → status: `returned_to_client`

## Database Schema

### `nx_customer_portal_tokens`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| invoice_id | foreign | → nx_invoices |
| customer_id | foreign | → nx_customers |
| token | string(64) | unique |
| expires_at | timestamp | 7 hari default |
| used_at | timestamp | nullable |
| max_uses | int | default 5 |
| usage_count | int | default 0 |

### `nx_rma_requests` (existing)
ReturnRequest model — fields: `rma_number`, `customer_id`, `serial_number_id`, `invoice_id`, `invoice_item_id`, `product_id`, `qty`, `warranty_type`, `status`, `issue_description`, `evidence_files`, `resolution_type`, `new_serial_number_id`, dates, `created_by`, `source`.

## Security

- Token hash random (Str::random 64)
- Session regeneration on login (anti session fixation)
- Lockout customer portal setelah 5 login attempt gagal (15 min)
- SN validasi: harus benar milik customer + product sesuai invoice
- Evidence upload: image only, max 5MB × 5 files

## File Paths

**Mailable + Token**
- `app/Mail/InvoiceSent.php` — inject `complaintUrl`
- `app/Actions/InvoiceGeneratePortalToken.php` — generate + url helper
- `app/Models/CustomerPortal/CustomerPortalToken.php`

**Routes**
- `routes/web.php` line ~67: customer-portal group (login, public token, dashboard, return/create)

**Portal Components**
- `app/Livewire/CustomerPortal.php` — login (email + 5 digit HP)
- `app/Livewire/CustomerPortalDashboard.php` — list RMA customer
- `app/Livewire/CustomerPortalReturnCreate.php` — wizard (4 steps)

**Admin Pages**
- `app/Filament/Resources/AfterSales/ReturnRequestResource/Pages/ViewReturnRequest.php` — infolist SN origin + traceability
- `app/Filament/Resources/AfterSales/ReturnRequestResource/Pages/ListReturnRequests.php`
- `app/Filament/Resources/AfterSales/ReturnRequestResource/Pages/EditReturnRequest.php`

**Models**
- `app/Models/Inventory/SerialNumber.php` — relasi `transactions()` untuk traceability
- `app/Models/Sales/Invoice.php` — relasi `portalTokens()`
- `app/Models/CRM/Customer.php` — login attempt counter, lockout
- `app/Models/AfterSales/ReturnRequest.php` — constants, status labels, source constants

**Views (blade)**
- `resources/views/emails/invoice.blade.php` — tombol "Ajukan Complain"
- `resources/views/livewire/customer-portal-login.blade.php`
- `resources/views/livewire/customer-portal-dashboard.blade.php`
- `resources/views/livewire/customer-portal-return-create.blade.php`
