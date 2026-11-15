# MANUAL BOOK / BUKU PANDUAN PENGGUNAAN NEXICON ERP

---

## Daftar Isi

1. [Pendahuluan](#1-pendahuluan)
2. [Persyaratan dan Akses Sistem](#2-persyaratan-dan-akses-sistem)
3. [Panduan Super Admin](#3-panduan-super-admin)
4. [Panduan Sales](#4-panduan-sales)
5. [Panduan Warehouse](#5-panduan-warehouse)
6. [Panduan IT](#6-panduan-it)
7. [Panduan Sales External](#7-panduan-sales-external)
8. [Panduan Manager](#8-panduan-manager)
9. [Panduan Customer](#9-panduan-customer)
10. [Logout](#10-logout)
11. [Troubleshooting](#11-troubleshooting)

---

## 1. Pendahuluan

### 1.1 Tujuan Manual Book
Manual Book ini disusun untuk memandu pengguna akhir sistem Nexicon ERP dalam menggunakan sistem mulai dari akses, login, penggunaan fitur sesuai role, hingga logout.

### 1.2 Tujuan Sistem
Nexicon ERP (Enterprise Resource Planning) adalah sistem terintegrasi untuk mengelola operasional bisnis meliputi: penjualan, inventory, procurement, CRM, HR, finance, project management, marketing, dan administrasi sistem.

### 1.3 Gambaran Umum Sistem
Sistem dibangun berbasis web menggunakan Laravel dan Filament. Menggunakan pendekatan **Module-Based Access** di mana setiap pengguna mendapat akses ke modul sesuai role yang dimilikinya. Navigasi utama dilakukan melalui **Module Selector** (Pilih Modul).

### 1.4 Sasaran Pengguna
- Karyawan internal (Sales, Warehouse, IT, HR, Finance, Procurement, Marketing, Project)
- Super Admin (Administrator sistem)
- Manager (Pemantau dashboard)
- Sales External (Kunjungan luar)
- Customer (Portal pelanggan)

### 1.5 Role Pengguna
Berdasarkan audit source code, role yang tersedia di sistem:

| Role | Keterangan |
|------|------------|
| super_admin | Akses penuh ke semua modul kecuali Attendance |
| inventory_employees | Attendance, Inventory |
| hr_employees | Attendance, HR |
| employees | Attendance, Inventory, HR, Finance, Procurement, CRM, Sales, Marketing, Project |
| finance_employees | Attendance, Finance |
| procurement_employees | Attendance, Procurement |
| crm_employees | Attendance, Sales, CRM |
| sales_employees | Attendance, Sales, CRM |
| marketing_employees | Attendance, Marketing |
| project_employees | Attendance, Project |

> **Catatan:** Role di atas adalah role teknis yang didefinisikan di config/module-role-permissions.php. Mapping ke role bisnis (Sales, Warehouse, IT, dll) dijelaskan di setiap bagian panduan role.

[SCREENSHOT DIPERLUKAN: Halaman Module Selector -- tampilkan daftar modul yang tersedia -- untuk menjelaskan konsep pemilihan modul]

---

## 2. Persyaratan dan Akses Sistem

### 2.1 Perangkat yang Dapat Digunakan
- Desktop / Laptop (Windows, macOS, Linux)
- Tablet
- Smartphone (responsive)

### 2.2 Browser yang Direkomendasikan
- Google Chrome (versi terbaru)
- Mozilla Firefox (versi terbaru)
- Microsoft Edge (versi terbaru)
- Safari (versi terbaru)

### 2.3 Koneksi yang Diperlukan
- Koneksi internet stabil
- Akses ke URL sistem

### 2.4 URL/Alamat Sistem
| Tipe Akses | URL |
|------------|-----|
| Sistem Utama (Internal) | [PERLU DILENGKAPI: URL production] |
| Customer Portal | [PERLU DILENGKAPI: URL production]/customer-portal |
| Invoice Verification | [PERLU DILENGKAPI: URL production]/invoice/verify/{nomor_invoice} |
| External Project Dashboard | [PERLU DILENGKAPI: URL production]/external/{token} |

### 2.5 Persyaratan Lain
- Akun pengguna yang telah didaftarkan oleh Administrator
- Untuk Customer Portal: email terdaftar dan 5 digit terakhir nomor HP
- Untuk Invoice Verification: nomor invoice dan 4 digit terakhir nomor HP pelanggan
- Untuk External Project: token akses dan password yang diberikan oleh Project Manager

[PERLU DILENGKAPI: URL production sistem]
Test line
---

## 3. Panduan Super Admin

### 3.1 Akses Sistem
1. Buka browser dan akses URL sistem utama.
2. Akan diarahkan ke halaman Login.

[SCREENSHOT DIPERLUKAN: Halaman Login -- tampilkan field email, password, tombol Login -- untuk menjelaskan halaman masuk sistem]

### 3.2 Login
**Halaman Login**
- Field: Email, Kata Sandi
- Tombol: **Login**
- Validasi: Email wajib diisi, format email valid, kata sandi wajib diisi

**Kondisi Login Berhasil**
- Di pindahkan ke **Module Selector** (halaman Pilih Modul)

**Kondisi Login Gagal**
- Pesan error: Kredensial tidak cocok dengan catatan kami.

### 3.3 Halaman Awal / Module Selector
Setelah login, Super Admin melihat **Module Selector** yang menampilkan modul yang dapat diakses:

**Modul yang Tersedia untuk Super Admin:**
1. **Inventory** -- Kelola produk, gudang, stok, serial number, mutasi barang
2. **Human Resource** -- Kelola karyawan, absensi, jabatan, kebutuhan HR
3. **Finance** -- Kelola transaksi, pembayaran, laporan keuangan
4. **Procurement** -- Kelola PR, PO, penerimaan barang, invoice supplier
5. **CRM** -- Kelola lead, deal, customer, pipeline, follow up
6. **Sales** -- Kelola customer, quotation, sales order, delivery
7. **Marketing** -- Kelola promo, banner, slider, konten marketing
8. **Project Management** -- Kelola proyek, tugas, tim, timeline
9. **System** -- Kelola user, role, permission, pengaturan sistem

> **Catatan:** Super Admin **TIDAK** memiliki akses ke modul **Attendance** (Presensi) -- ini by design di source code (ModuleAccess.php line 57-59).

[SCREENSHOT DIPERLUKAN: Module Selector -- Role Super Admin -- tampilkan 9 modul yang tersedia (tanpa Attendance) -- untuk menjelaskan modul yang dapat diakses Super Admin]

### 3.4 Memilih Modul
1. Klik kartu modul yang diinginkan (misal: **Inventory**).
2. Sistem akan mengarahkan ke **Dashboard modul** tersebut.
3. URL berubah ke route dashboard modul (contoh: /inventory/dashboard).

### 3.5 Penggunaan Modul

#### 3.5.1 Modul Inventory
**Fungsi:** Kelola produk, gudang, stok, serial number, dan mutasi barang.

**Menu & Submenu:**
| Menu | Fitur Utama |
|------|-------------|
| **Dashboard Inventory** | Ringkasan stok, grafik pergerakan, distribusi stok per gudang |
| **Product** | CRUD produk, kategori, unit, package, service, serial number |
| **Warehouse** | CRUD gudang |
| **Transaction** | Mutasi barang (masuk/keluar/transfer) |
| **Stock Report** | Laporan stok, download PDF |
| **Transaction Report** | Laporan mutasi, download PDF |
| **Inventory Monitoring** | Monitoring stok real-time |

**Workflow Utama:**
1. **Tambah Produk** -> Isi nama, kategori, unit, harga, stok minimum -> Simpan
2. **Mutasi Barang** -> Pilih tipe (Masuk/Keluar/Transfer) -> Isi detail -> Simpan
3. **Laporan** -> Filter periode -> Download PDF

[SCREENSHOT DIPERLUKAN: Inventory -> Product List -- Role Super Admin -- tampilkan tabel produk dan tombol Create -- untuk menjelaskan halaman daftar produk]

[SCREENSHOT DIPERLUKAN: Inventory -> Transaction -- Role Super Admin -- tampilkan form mutasi barang -- untuk menjelaskan pencatatan mutasi]

[SCREENSHOT DIPERLUKAN: Inventory -> Stock Report -- Role Super Admin -- tampilkan laporan stok dan tombol Download PDF -- untuk menjelaskan laporan stok]

#### 3.5.2 Modul Human Resource (HR)
**Fungsi:** Kelola karyawan, absensi, jabatan, cuti, lembur, sakit, dan kebutuhan HR.

**Menu & Submenu:**
| Menu | Fitur Utama |
|------|-------------|
| **Dashboard HR** | Overview karyawan, grafik absensi, cuti pending, libur mendatang |
| **Employee** | CRUD karyawan, jabatan, departemen, shift, kantor |
| **Attendance** | Presensi harian (clock in/out), riwayat absensi |
| **Leave** | Pengajuan cuti, approval cuti, tipe cuti |
| **Overtime Monitoring** | Monitoring lembur |
| **Sick Request/Approval** | Pengajuan & approval sakit |
| **Holiday** | Manajemen hari libur nasional |
| **Department** | CRUD departemen |
| **Office** | CRUD kantor/lokasi |
| **Shift** | CRUD shift kerja |
| **Employee Performance** | Performa karyawan |

**Workflow Utama:**
1. **Kelola Karyawan** -> Tambah karyawan -> Isi data pribadi, jabatan, shift, kantor -> Simpan
2. **Pengajuan Cuti** -> Karyawan ajukan -> Atasan approve/reject
3. **Presensi** -> Karyawan clock in/out via halaman Attendance (bukan Super Admin)
4. **Libur** -> Admin tambah hari libur nasional

[SCREENSHOT DIPERLUKAN: HR -> Employee List -- Role Super Admin -- tampilkan tabel karyawan dan tombol Create -- untuk menjelaskan manajemen karyawan]

[SCREENSHOT DIPERLUKAN: HR -> Leave Request -- Role Super Admin -- tampilkan daftar pengajuan cuti dengan status -- untuk menjelaskan approval cuti]

#### 3.5.3 Modul Finance
**Fungsi:** Kelola transaksi keuangan, pembayaran, reimbursement, invoice pembelian.

**Menu & Submenu:**
| Menu | Fitur Utama |
|------|-------------|
| **Dashboard Finance** | Ringkasan keuangan |
| **Financial Record** | Pencatatan transaksi (pemasukan/pengeluaran) |
| **Purchase Invoice** | Invoice pembelian dari supplier |
| **Reimbursement Request** | Pengajuan reimburse karyawan |
| **Reimbursement Approval** | Approval reimburse |

**Workflow Utama:**
1. **Catat Transaksi** -> Pilih tipe (Income/Expense) -> Isi nominal, kategori, keterangan -> Simpan
2. **Invoice Pembelian** -> Terima invoice supplier -> Catat -> Proses pembayaran
3. **Reimbursement** -> Karyawan ajukan -> Finance/Manager approve -> Bayar

[SCREENSHOT DIPERLUKAN: Finance -> Financial Record -- Role Super Admin -- tampilkan form pencatatan transaksi -- untuk menjelaskan pencatatan keuangan]

#### 3.5.4 Modul Procurement
**Fungsi:** Kelola Purchase Requisition (PR), Purchase Order (PO), Goods Receipt (GR), Purchase Return, Supplier.

**Menu & Submenu:**
| Menu | Fitur Utama |
|------|-------------|
| **Dashboard Procurement** | Ringkasan procurement |
| **Purchase Requisition** | Ajukan kebutuhan pembelian (PR) |
| **Purchase Order** | Buat PO dari PR terpilih |
| **Goods Receipt** | Terima barang dari PO |
| **Purchase Return** | Retur pembelian ke supplier |
| **Supplier** | CRUD data supplier |

**Workflow Utama:**
1. **PR -> PO -> GR** -> Alur: Ajukan PR -> Approve -> Buat PO -> Kirim ke Supplier -> Terima Barang (GR)
2. **Retur** -> Buat Purchase Return dari GR yang bermasalah
3. **Supplier** -> Kelola data supplier

[SCREENSHOT DIPERLUKAN: Procurement -> Purchase Order -- Role Super Admin -- tampilkan daftar PO dengan status -- untuk menjelaskan alur PO]

[SCREENSHOT DIPERLUKAN: Procurement -> Goods Receipt -- Role Super Admin -- tampilkan form penerimaan barang -- untuk menjelaskan pencatatan GR]

#### 3.5.5 Modul CRM
**Fungsi:** Kelola Lead, Deal, Customer, Pipeline, Follow-up.

**Menu & Submenu:**
| Menu | Fitur Utama |
|------|-------------|
| **Dashboard CRM** | Pipeline, statistik lead/deal |
| **Lead** | CRUD lead, konversi ke deal, status (New, Contacted, Qualified, Unqualified, Converted, Dead) |
| **Deal** | CRUD deal, tahapan (Stage), probabilitas, nilai |
| **Customer** | CRUD customer (dari lead yang converted) |
| **Deal Stage** | Manajemen tahapan deal |

**Workflow Utama:**
1. **Lead Management** -> Tambah Lead -> Follow up -> Ubah status -> Qualified -> Convert ke Deal
2. **Deal Management** -> Buat Deal dari Lead -> Update stage -> Negosiasi -> Closed Won/Lost
3. **Customer** -> Otomatis terbuat dari Deal Won, atau manual

[SCREENSHOT DIPERLUKAN: CRM -> Lead List -- Role Super Admin -- tampilkan tabel lead dengan status dan tombol Convert -- untuk menjelaskan manajemen lead]

[SCREENSHOT DIPERLUKAN: CRM -> Deal Pipeline -- Role Super Admin -- tampilkan kanban board pipeline -- untuk menjelaskan visualisasi pipeline]

#### 3.5.6 Modul Sales
**Fungsi:** Kelola Quotation, Sales Order, Delivery Order, Invoice, Sales Person.

**Menu & Submenu:**
| Menu | Fitur Utama |
|------|-------------|
| **Dashboard Sales** | Ringkasan penjualan |
| **Quotation** | Buat penawaran, kirim ke customer, konversi ke Sales Order |
| **Sales Order** | Buat SO dari Quotation, kelola status |
| **Delivery Order** | Buat DO dari SO, kirim barang, print PDF |
| **Invoice** | Buat invoice dari SO/DO, verifikasi pembayaran |
| **Sales Person** | Kelola data sales person |

**Workflow Utama:**
\
Quotation -> (Customer Approve) -> Sales Order -> (Proses) -> Delivery Order -> (Kirim) -> Invoice -> (Bayar) -> Selesai
\

**Detail Fitur:**

| Menu | Fitur Kunci |
|------|-------------|
| **Quotation** | Buat dari Deal atau manual, pilih produk/jasa, hitung otomatis (subtotal, diskon, pajak, grand total), apply promo code, kirim email, konversi ke SO |
| **Sales Order** | Buat dari Quotation, konfirmasi ketersediaan stok, status (Draft, Confirmed, Processing, Completed, Cancelled) |
| **Delivery Order** | Buat dari SO, pilih gudang, input qty kirim, **Print PDF** untuk surat jalan, tracking external |
| **Invoice** | Buat dari SO/DO, status (Draft, Sent, Paid, Partial, Overdue, Cancelled), **Verifikasi Pembayaran** external |

**Validasi Penting:**
- Quotation: Harus ada Deal reference (optional), item minimal 1, grand total > 0
- SO: Hanya dari Quotation yang approved, stok cukup
- DO: Qty tidak boleh melebihi SO qty yang belum dikirim
- Invoice: Nomor unik otomatis, tanggal jatuh tempo

[SCREENSHOT DIPERLUKAN: Sales -> Quotation Create -- Role Super Admin -- tampilkan form quotation dengan repeater item, promo code, grand total -- untuk menjelaskan pembuatan penawaran]

[SCREENSHOT DIPERLUKAN: Sales -> Delivery Order -- Role Super Admin -- tampilkan daftar DO dan tombol Print PDF -- untuk menjelaskan pengiriman dan tracking]

[SCREENSHOT DIPERLUKAN: Sales -> Invoice -- Role Super Admin -- tampilkan daftar invoice dengan status pembayaran -- untuk menjelaskan manajemen invoice]

#### 3.5.7 Modul Marketing
**Fungsi:** Kelola Promo Code, Popup Banner, Slider.

**Menu & Submenu:**
| Menu | Fitur Utama |
|------|-------------|
| **Promo Code** | CRUD kode promo (persentase/fixed), masa berlaku, usage limit |
| **Popup Banner** | CRUD banner pop-up untuk website |
| **Slider** | CRUD slider/gambar carousel untuk website |

**Workflow Utama:**
1. **Promo Code** -> Buat kode -> Set tipe diskon, nilai, tanggal berlaku, limit penggunaan
3. **Banner/Slider** -> Upload gambar -> Set link, urutan tampil -> Publish

[SCREENSHOT DIPERLUKAN: Marketing -> Promo Code -- Role Super Admin -- tampilkan daftar promo code -- untuk menjelaskan manajemen promo]

#### 3.5.8 Modul Project Management
**Fungsi:** Kelola Project, Ticket/Task, Prioritas, Gantt Chart, External Access.

**Menu & Submenu:**
| Menu | Fitur Utama |
|----t----|-------------|
| **Dashboard PM** | Statistik project, progress, gantt chart |
| **Project** | CRUD project, tim, timeline, progress |
| **Ticket** | CRUD ticket/task, assignee, status, prioritas, komentar |
| **Ticket Priority** | Manajemen prioritas ticket |

**Fitur External Access:**
- Generate token akses untuk stakeholder eksternal (client/vendor)
- Stakeholder login di /external/{token} dengan password
- Melihat dashboard project, ticket, timeline (read-only sesuai hak akses)

**Workflow Utama:**
1. **Buat Project** -> Isi nama, deskripsi, tanggal mulai/selesai, tim -> Simpan
2. **Kelola Ticket** -> Tambah ticket -> Assign ke anggota -> Update status -> Selesai
3. **External Access** -> Buat token untuk client -> Kirim link + password

[SCREENSHOT DIPERLUKAN: Project -> Project List -- Role Super Admin -- tampilkan daftar project dengan progress -- untuk menjelaskan manajemen project]

[SCREENSHOT DIPERLUKAN: Project -> External Dashboard -- Role External -- tampilkan dashboard project eksternal -- untuk menjelaskan akses stakeholder eksternal]

#### 3.5.9 Modul System
**Fungsi:** Administrasi sistem -- User, Role, Permission, Audit Log.

**Menu & Submenu:**
| Menu | Fitur Utama |
|------|-------------|
| **Dashboard System** | Statistik user, aktivitas terbaru |
| **User** | CRUD user, assign role |
| **Role** | CRUD role, assign permission (CRUD + module access) |

**Workflow Utama:**
1. **Tambah User** -> Isi nama, email, password, pilih Role -> Simpan
2. **Kelola Role** -> Buat role baru -> Centang permission CRUD per resource + **Akses Modul** (checkbox dari config/erp-modules.php)

> **Catatan Penting:** Di form Role, ada section **Akses Modul** yang menampilkan checkbox untuk 10 modul dari config/erp-modules.php. Ini mengontrol akses ke Module Selector.

[SCREENSHOT DIPERLUKAN: System -> Role -- Role Super Admin -- tampilkan form role dengan section \ Akses Modul\ -- untuk menjelaskan pengaturan akses modul per role]

### 3.6 Ganti Modul (Switch Module)
1. Klik menu pengguna (avatar/nama) di pojok kanan atas.
2. Pilih **Ganti Modul** (icon: squares-2x2).
3. Akan kembali ke **Module Selector**.
4. Pilih modul lain.

### 3.7 Logout
1. Klik menu pengguna (avatar/nama) di pojok kanan atas.
2. Pilih **Logout**.
3. Sistem menghapus session dan mengarahkan ke halaman Login.

[SCREENSHOT DIPERLUKAN: Menu User -- tampilkan tombol \Ganti Modul\ dan \Logout\ -- untuk menjelaskan navigasi user menu]

[SCREENSHOT DIPERLUKAN: Tampilan setelah Logout -- halaman Login kembali -- untuk menjelaskan kondisi setelah logout]
---

## 4. Panduan Sales

> **Role Teknis:** \sales_employees\ (Attendance, Sales, CRM)

### 4.1 Akses Sistem
Sama dengan Super Admin: buka URL sistem utama -> halaman Login.

### 4.2 Login
Sama dengan Super Admin: isi Email & Kata Sandi -> klik **Login**.

### 4.3 Halaman Awal / Module Selector
Setelah login, Sales melihat **Module Selector** dengan 3 modul:

1. **Attendance** -- Presensi harian
2. **CRM** -- Lead, Deal, Customer
3. **Sales** -- Quotation, Sales Order, Delivery Order, Invoice

[SCREENSHOT DIPERLUKAN: Module Selector -- Role Sales -- tampilkan 3 modul (Attendance, CRM, Sales) -- untuk menjelaskan modul yang dapat diakses Sales]

### 4.4 Modul Attendance (Presensi)
**Halaman:** \/attendance\ (Presensi Harian)

**Fitur:**
- **Clock In** -- Tombol \ Presensi Masuk\ (hanya sekali per hari)
- **Clock Out** -- Tombol \Presensi Keluar\ (setelah clock in)
- **Map** -- Tampilkan lokasi kantor (jika dikonfigurasi)
- **Riwayat** -- Lihat riwayat absensi sendiri

**Validasi:**
- Hanya bisa clock in di jam kerja (berdasarkan shift)
- Harus dalam radius kantor (jika GPS aktif)
- Tidak bisa clock in di hari libur/akhir pekan

**Workflow:**
1. Buka modul **Attendance** dari Module Selector
2. Klik **\Presensi Masuk\** saat tiba di kantor
3. Klik **\Presensi Keluar\** saat pulang
4. Sistem mencatat waktu, lokasi (jika GPS)

[SCREENSHOT DIPERLUKAN: Attendance -> Presensi Harian -- Role Sales -- tampilkan tombol Clock In/Out, info shift, status hari ini -- untuk menjelaskan presensi harian]

### 4.5 Modul CRM
Sama fiturnya dengan Super Admin tapi terbatas pada **view, create, edit, delete** sesuai permission.

**Menu yang Tersedia:**
- Lead (List, Create, View, Edit, Delete)
- Deal (List, Create, View, Edit, Delete)
- Customer (List, Create, View, Edit, Delete)
- Deal Stage (View only biasanya)

**Workflow Sales di CRM:**
1. **Lead Baru** -> Tambah lead (individual/perusahaan) -> Isi PIC jika perusahaan
2. **Follow Up** -> Catat aktivitas (call, meeting, email) -> Update status
3. **Convert ke Deal** -> Saat qualified -> Klik \Convert to Deal\ -> Otomatis buat Deal
4. **Kelola Deal** -> Update stage (Prospecting -> Qualification -> Proposal -> Negotiation -> Closed Won/Lost)
5. **Customer** -> Otomatis dari Deal Won, atau buat manual

[SCREENSHOT DIPERLUKAN: CRM -> Lead -> Create -- Role Sales -- tampilkan form lead dengan field tipe, nama, email, phone, PIC -- untuk menjelaskan pembuatan lead]

[SCREENSHOT DIPERLUKAN: CRM -> Deal -> Kanban -- Role Sales -- tampilkan board pipeline dengan stage -- untuk menjelaskan manajemen deal]

### 4.6 Modul Sales
**Menu yang Tersedia:**
- Quotation (Penawaran)
- Sales Order
- Delivery Order
- Invoice
- Sales Person

**Workflow Penjualan Lengkap:**
\
Quotation -> (Customer Approve) -> Sales Order -> (Proses) -> Delivery Order -> (Kirim) -> Invoice -> (Bayar) -> Selesai
\

**Detail Fitur:**

| Menu | Fitur Kunci |
|------|-------------|
| **Quotation** | Buat dari Deal atau manual, pilih produk/jasa, hitung otomatis (subtotal, diskon, pajak, grand total), apply promo code, kirim email, konversi ke SO |
| **Sales Order** | Buat dari Quotation, konfirmasi ketersediaan stok, status (Draft, Confirmed, Processing, Completed, Cancelled) |
| **Delivery Order** | Buat dari SO, pilih gudang, input qty kirim, **Print PDF** untuk surat jalan, tracking external |
| **Invoice** | Buat dari SO/DO, status (Draft, Sent, Paid, Partial, Overdue, Cancelled), **Verifikasi Pembayaran** external |

**Validasi Penting:**
- Quotation: Harus ada Deal reference (optional), item minimal 1, grand total > 0
- SO: Hanya dari Quotation yang approved, stok cukup
- DO: Qty tidak boleh melebihi SO qty yang belum dikirim
- Invoice: Nomor unik otomatis, tanggal jatuh tempo

[SCREENSHOT DIPERLUKAN: Sales -> Quotation Create -- Role Sales -- tampilkan form quotation dengan repeater item, promo code, grand total -- untuk menjelaskan pembuatan penawaran]

[SCREENSHOT DIPERLUKAN: Sales -> Delivery Order -- Role Sales -- tampilkan tombol Print PDF dan tracking link -- untuk menjelaskan pengiriman dan tracking]

[SCREENSHOT DIPERLUKAN: Sales -> Invoice -- Role Sales -- tampilkan daftar invoice dengan status pembayaran -- untuk menjelaskan manajemen invoice]

### 4.7 Ganti Modul & Logout
Sama dengan Super Admin: gunakan menu user -> \Ganti Modul\ atau \Logout\.
---

## 5. Panduan Warehouse

> **Role Teknis:** \inventory_employees\ (Attendance, Inventory) + \procurement_employees\ (Attendance, Procurement)
> **Gabungan:** Attendance, Inventory, Procurement, Project Management

### 5.1 Akses Sistem & Login
Sama dengan role lain: buka URL -> Login dengan kredensial.

### 5.2 Module Selector
Modul yang tersedia:
1. **Attendance** -- Presensi harian
2. **Inventory** -- Produk, stok, gudang, mutasi
3. **Procurement** -- PR, PO, Goods Receipt, Supplier
4. **Project Management** -- Project, Ticket (read/dashboard)

[SCREENSHOT DIPERLUKAN: Module Selector -- Role Warehouse -- tampilkan 4 modul -- untuk menjelaskan akses Warehouse]

### 5.3 Modul Attendance
Sama dengan Sales: Clock In/Out harian, riwayat absensi.

### 5.4 Modul Inventory (Fokus Utama Warehouse)
**Menu & Fitur:**
| Menu | Fitur Kunci untuk Warehouse |
|------|----------------------------|
| **Dashboard Inventory** | Monitor stok rendah, pergerakan barang |
| **Product** | Lihat daftar produk, **tidak bisa create/edit** (read-only) |
| **Warehouse** | Kelola data gudang (CRUD) |
| **Transaction** | **Utama:** Input mutasi (Masuk/Keluar/Transfer), cek serial number |
| **Stock Report** | Lihat laporan stok per gudang, download PDF |
| **Transaction Report** | Lihat laporan mutasi, download PDF |
| **Inventory Monitoring** | Real-time stock level, alert stok minimum |
| **Category/Unit/Service/Package/Serial Number** | Reference data (biasanya read-only) |

**Workflow Warehouse Harian:**
1. **Penerimaan Barang (GR dari Procurement)** -> Cek fisik barang -> Input **Transaction: Masuk** -> Pilih GR reference -> Verifikasi qty & kondisi -> Simpan
2. **Pengeluaran Barang (DO dari Sales)** -> Cek DO -> Pick barang -> Input **Transaction: Keluar** -> Pilih DO reference -> Simpan
3. **Transfer Antar Gudang** -> Input **Transaction: Transfer** -> Pilih gudang asal & tujuan -> Simpan
4. **Cek Stok** -> Gunakan **Inventory Monitoring** atau **Stock Report**
5. **Serial Number** -> Untuk produk berserial: scan/isi SN saat masuk/keluar

**Validasi:**
- Transaction Masuk: Harus ada reference (GR/PO/Manual), qty > 0
- Transaction Keluar: Stok cukup di gudang asal, qty > 0
- Serial Number: Unik per produk, status update otomatis (Available -> Sold -> dll)

[SCREENSHOT DIPERLUKAN: Inventory -> Transaction Create -- Role Warehouse -- tampilkan form mutasi dengan tipe Masuk/Keluar/Transfer, reference GR/DO -- untuk menjelaskan pencatatan mutasi gudang]

[SCREENSHOT DIPERLUKAN: Inventory -> Inventory Monitoring -- Role Warehouse -- tampilkan tabel stok real-time dengan highlight stok minimum -- untuk menjelaskan monitoring stok]

### 5.5 Modul Procurement
**Menu yang Tersedia:**
- Purchase Requisition (PR) -- **Create, View** (ajukan kebutuhan)
- Purchase Order (PO) -- **View** (lihat PO yang dibuat purchasing)
- Goods Receipt (GR) -- **Create, View** (utama: terima barang)
- Purchase Return -- **Create, View** (retur ke supplier)
- Supplier -- **View** (lihat data supplier)

**Workflow Warehouse di Procurement:**
1. **Terima Barang (GR)** -> Buka GR dari PO -> Cek fisik barang vs PO -> Input qty diterima, kondisi (Baik/Rusak) -> Simpan GR -> Otomatis update stok Inventory
2. **Retur ke Supplier** -> Buat Purchase Return dari GR -> Isi alasan, qty retur -> Kirim ke supplier
3. **Ajukan PR** -> Jika stok habis, buat PR untuk dimintai ke purchasing

[SCREENSHOT DIPERLUKAN: Procurement -> Goods Receipt -- Role Warehouse -- tampilkan form penerimaan dengan qty received, kondisi, catatan -- untuk menjelaskan proses GR]

### 5.6 Modul Project Management
**Akses:** Hanya **Dashboard** dan **View** (read-only)
- Lihat daftar project
- Lihat ticket/task yang assign ke warehouse (jika ada)
- Tidak bisa create/edit project/ticket

### 5.7 Ganti Modul & Logout
Sama dengan role lain.
---

## 6. Panduan IT

> **Role Teknis:** \it_employees\ -- tidak terdefinisi eksplisit di config, tapi berdasarkan requirement user: Attendance, Project Management, System, Sales Activity

### 6.1 Akses Sistem & Login
Sama standar: URL -> Login.

### 6.2 Module Selector
Modul yang tersedia (berdasarkan requirement + audit):
1. **Attendance** -- Presensi harian
2. **Project Management** -- Project, Ticket (full access)
3. **System** -- User, Role, Permission (admin sistem)
4. **Sales Activity** -- Visit Assignment (jika role sales_activity_employees)

> **Catatan:** Role \it_employees\ tidak terdefinisi di \config/module-role-permissions.php\. Akses IT perlu dikonfigurasi oleh Super Admin via Role Management (centang module.access.project, module.access.system, module.access.attendance, module.access.sales_activity).

[SCREENSHOT DIPERLUKAN: Module Selector -- Role IT -- tampilkan 4 modul -- untuk menjelaskan akses IT]

### 6.3 Modul Attendance
Presensi harian standar (Clock In/Out).

### 6.4 Modul Project Management (Full Access)
**Menu & Fitur:**
- **Project** -- CRUD project, kelola tim, timeline, progress
- **Ticket** -- CRUD ticket, assign, status, prioritas, komentar, attachment
- **Ticket Priority** -- CRUD prioritas
- **External Access** -- Generate token untuk stakeholder eksternal
- **Gantt Chart** -- Visualisasi timeline
- **Export** -- Export data project/ticket

**Workflow IT:**
1. Setup project baru untuk tim development
2. Kelola ticket (bug, feature, task) -> Assign ke developer
3. Monitor progress via Dashboard & Gantt
4. Buat external access untuk client/vendor

[SCREENSHOT DIPERLUKAN: Project -> Ticket List -- Role IT -- tampilkan daftar ticket dengan filter status, prioritas, assignee -- untuk menjelaskan manajemen ticket]

### 6.5 Modul System (Administrasi Sistem)
**Menu & Fitur:**
- **User** -- CRUD user, reset password, assign role
- **Role** -- CRUD role, set permission CRUD per resource + **Akses Modul**
- **Audit Log** -- (jika terpasang filament-logger) Riwayat aktivitas user

**Tugas IT:**
- Provisioning user baru
- Reset password user lupa
- Konfigurasi role & permission
- Monitoring audit log

[SCREENSHOT DIPERLUKAN: System -> User -- Role IT -- tampilkan daftar user dengan role badge -- untuk menjelaskan manajemen user]

[SCREENSHOT DIPERLUKAN: System -> Role -- Role IT -- tampilkan form role dengan permission matrix dan Akses Modul -- untuk menjelaskan konfigurasi role]

### 6.6 Modul Sales Activity
**Menu:** Visit Assignment
**Fitur:** Kelola jadwal kunjungan sales, assign ke sales person, tracking check-in/out lokasi.

### 6.7 Ganti Modul & Logout
Standar.
---

## 7. Panduan Sales External

> **Role Teknis:** \sales_activity_employees\ (jika ada) atau akses via Sales Activity module
> **Akses:** Hanya **Sales Activity** (Visit Assignment)

### 7.1 Akses Sistem & Login
Sama standar: URL -> Login.

### 7.2 Module Selector
Hanya 1 modul: **Sales Activity**

[SCREENSHOT DIPERLUKAN: Module Selector -- Role Sales External -- tampilkan 1 modul (Sales Activity) -- untuk menjelaskan akses terbatas]

### 7.3 Modul Sales Activity
**Menu:** Visit Assignment
**Fitur:**
- Lihat jadwal kunjungan yang diassign
- Check-in di lokasi customer (GPS)
- Catat hasil kunjungan (notes, foto, next action)
- Check-out
- Lihat riwayat kunjungan

**Workflow:**
1. Buka **Sales Activity** -> **Visit Assignment**
2. Lihat daftar kunjungan hari ini
3. Pilih kunjungan -> Klik **\ Check In\** (di lokasi customer)
4. Isi hasil kunjungan -> Simpan
5. Klik **\Check Out\** saat selesai

[SCREENSHOT DIPERLUKAN: Sales Activity -> Visit Assignment -- Role Sales External -- tampilkan daftar kunjungan hari ini dengan tombol Check In/Out -- untuk menjelaskan aktivitas kunjungan]

### 7.4 Logout
Standar via menu user.
---

## 8. Panduan Manager

> **Role Teknis:** \manager\ -- **TIDAK terdefinisi di config/module-role-permissions.php**
> **Akses:** Berdasarkan requirement: Attendance + Semua modul **HANYA Dashboard**

### 8.1 Akses Sistem & Login
Standar: URL -> Login.

### 8.2 Module Selector
Manager melihat **SEMUA 10 modul** di Module Selector:
1. Attendance
2. Inventory
3. Human Resource
4. Finance
5. Procurement
6. CRM
7. Sales
8. Marketing
9. Project Management
10. System

> **Catatan:** Manager bisa **masuk ke semua modul** tapi **hanya bisa melihat Dashboard** setiap modul. Tidak bisa akses resource (List, Create, Edit, Delete).

[SCREENSHOT DIPERLUKAN: Module Selector -- Role Manager -- tampilkan 10 modul semua tersedia -- untuk menjelaskan akses penuh ke Module Selector]

### 8.3 Dashboard Setiap Modul
Manager hanya mengakses **Dashboard** di setiap modul. Berikut ringkasan informasi per dashboard:

| Modul | Informasi Dashboard |
|-------|---------------------|
| **Attendance** | Grafik kehadiran hari ini, karyawan hadir/absen, shift info |
| **Inventory** | Total produk, stok rendah, nilai stok, grafik pergerakan, distribusi per gudang |
| **HR** | Total karyawan, per departemen, status karyawan, absensi hari ini, cuti pending, libur mendatang |
| **Finance** | Saldo kas, pemasukan/pengeluaran periode, reimbursement pending |
| **Procurement** | PR pending, PO open, GR pending, supplier aktif |
| **CRM** | Pipeline deal, lead baru, konversi rate, aktivitas sales |
| **Sales** | Quotation pending, SO open, DO pending, invoice overdue, revenue |
| **Marketing** | Promo aktif, banner/slider published, statistik kampanye |
| **Project** | Project aktif, progress rata-rata, ticket open/closed, overdue |
| **System** | Total user, user aktif, role, aktivitas login terbaru |

**Fitur Dashboard yang Tersedia:**
- Widget statistik (KPI cards)
- Grafik/Chart (line, bar, doughnut)
- Tabel ringkasan (recent activity, pending approval)
- Filter periode (bulan ini, kuartal, tahun)
- Search (jika tersedia di widget)

**Tidak Tersedia untuk Manager:**
- X Create/Edit/Delete data apapun
- X Akses halaman List/Index resource
- X Form input data
- X Action buttons (Print, Export, Approve, dll)

[SCREENSHOT DIPERLUKAN: HR -> Dashboard HR -- Role Manager -- tampilkan widget overview, chart absensi, tabel cuti pending -- untuk menjelaskan dashboard read-only]

[SCREENSHOT DIPERLUKAN: Sales -> Dashboard Sales -- Role Manager -- tampilkan KPI penjualan, grafik revenue, tabel invoice overdue -- untuk menjelaskan dashboard sales]

### 8.4 Ganti Modul
Manager bisa pindah modul via **\ Ganti Modul\** di menu user untuk melihat dashboard modul lain.

### 8.5 Logout
Standar.
---

## 9. Panduan Customer

Customer menggunakan **Customer Portal** (bukan Filament admin panel) dan **Invoice Verification** (external link).

### 9.1 Customer Portal

#### 9.1.1 Akses Customer Portal
1. Buka URL: \[URL sistem]/customer-portal\
2. Halaman Login Customer Portal

[SCREENSHOT DIPERLUKAN: Customer Portal Login -- tampilkan field Email dan 5 digit terakhir No. HP -- untuk menjelaskan login customer]

#### 9.1.2 Login Customer Portal
**Field yang Diisi:**
1. **Email** -- Email terdaftar di sistem
2. **5 Digit Terakhir No. HP** -- 5 digit akhir nomor telepon yang terdaftar

**Validasi:**
- Email harus terdaftar sebagai Customer (model CRM\\Customer)
- 5 digit HP harus cocok dengan data customer
- Max 5 percobaan salah -> akun terkunci 15 menit

**Setelah Login Berhasil:**
- Redirect ke **Customer Portal Dashboard**

#### 9.1.3 Customer Portal Dashboard
**Informasi yang Ditampilkan:**
- **Statistik:**
  - Total Return Request
  - Return In Progress
  - Return Completed
  - Total Produk Terjual (Serial Number status Sold)
- **Daftar Return Request** -- Tabel dengan kolom: Nomor RMA, Tanggal, Produk, Status, Aksi

**Menu yang Tersedia:**
1. **Dashboard** -- Ringkasan return & produk
2. **Buat Return** -- Ajukan pengembalian barang
3. **Detail Return** -- Lihat detail RMA

#### 9.1.4 Buat Return Request (RMA)
1. Klik menu **\ Buat Return\** atau tombol di Dashboard
2. Pilih **Invoice** (dari daftar invoice customer)
3. Pilih **Produk/Serial Number** yang akan direturn
4. Isi **Alasan Return** (Rusak, Tidak Sesuai, Garansi, Lainnya)
4. Upload **Foto Bukti** (jika ada)
5. Klik **Submit**

**Validasi:**
- Hanya produk yang status Serial Number = **Sold** ke customer tersebut
- Invoice harus valid dan sudah lunas
- 1 Serial Number = 1 Return Request aktif

#### 9.1.5 Detail Return Request
- Lihat status proses: **Pending -> Inspection -> Repair/Replace -> Returned to Client / Rejected**
- Lihat catatan tim teknis/internal
- Download dokumen (jika ada)

[SCREENSHOT DIPERLUKAN: Customer Portal -> Dashboard -- tampilkan statistik cards dan tabel return request -- untuk menjelaskan dashboard customer]

[SCREENSHOT DIPERLUKAN: Customer Portal -> Buat Return -- tampilkan form pilih invoice, serial number, alasan, upload foto -- untuk menjelaskan pengajuan return]

#### 9.1.6 Logout Customer Portal
1. Klik menu user (nama/email) di pojok kanan atas.
2. Pilih **Logout**.
3. Redirect ke halaman Login Customer Portal.

[SCREENSHOT DIPERLUKAN: Customer Portal -> Menu User -- tampilkan tombol Logout -- untuk menjelaskan logout customer]

---

### 9.2 Invoice Validation (Verifikasi Invoice External)

#### 9.2.1 Akses Verifikasi Invoice
Customer menerima email invoice dengan link verifikasi:
\
[URL sistem]/invoice/verify/{nomor_invoice}
\
Contoh: \https://erp.nexicon.co.id/invoice/verify/INV-2024-00123\

#### 9.2.2 Halaman Verifikasi
**Field yang Diisi:**
- **4 Digit Terakhir No. HP** -- 4 digit akhir nomor telepon customer

**Proses:**
1. Buka link dari email
2. Masukkan 4 digit terakhir HP
3. Klik **Verifikasi**

#### 9.2.3 Hasil Verifikasi
**Jika Valid (Cocok):**
- Redirect ke **Signed URL** (valid 30 menit): \/invoice/view/{nomor_invoice}?signature=...\
- Halaman menampilkan **Detail Invoice Lengkap**: nomor, tanggal, customer, item, subtotal, diskon, pajak, grand total, status pembayaran
- Tersedia tombol **Download PDF**

**Jika Tidak Valid (Tidak Cocok):**
- Error: \4 digit terakhir tidak cocok dengan data kami.\
- Kembali ke form verifikasi

#### 9.2.4 Halaman Detail Invoice (Signed URL)
- Hanya bisa diakses via signed URL (valid 30 menit)
- Menampilkan: Header invoice, Customer info, Tabel item (produk, qty, harga, subtotal), Total, Status pembayaran
- Tombol **Download PDF** -> Generate PDF invoice dengan QR code & barcode

[SCREENSHOT DIPERLUKAN: Invoice Verification -- Halaman Verifikasi -- tampilkan field 4 digit HP dan tombol Verifikasi -- untuk menjelaskan verifikasi invoice]

[SCREENSHOT DIPERLUKAN: Invoice Detail (Signed URL) -- tampilkan detail invoice lengkap dengan tombol Download PDF -- untuk menjelaskan hasil verifikasi berhasil]
---

## 10. Logout (Semua Role)

### 10.1 Internal Users (Super Admin, Sales, Warehouse, IT, Manager)
1. Klik **avatar/nama pengguna** di pojok kanan atas header (Filament user menu).
2. Pilih menu **Logout**.
3. Sistem:
   - Menghapus session authentication
   - Mengarahkan ke halaman **Login**
4. Jika mencaks akses halaman internal setelah logout -> redirect ke Login.

[SCREENSHOT DIPERLUKAN: Menu User Internal -- tampilkan dropdown dengan \ Ganti Modul\ dan \Logout\ -- untuk menjelaskan logout internal]

### 10.2 Customer Portal
1. Klik **nama/email** di pojok kanan atas layout external.
2. Pilih **Logout**.
3. Session customer portal dihapus.
4. Redirect ke **Customer Portal Login**.

[SCREENSHOT DIPERLUKAN: Customer Portal Menu User -- tampilkan tombol Logout -- untuk menjelaskan logout customer]

### 10.3 External Project Dashboard
1. Klik tombol **Logout** di header/dashboard external.
2. Session external dihapus.
3. Redirect ke **External Login** (/external/{token}).

---

## 11. Troubleshooting

### 11.1 Login Gagal
| Gejala | Penyebab | Solusi |
|--------|----------|--------|
| \Kredensial tidak cocok\ | Email/password salah | Periksa ketik email & password, caps lock |
| \Terlalu banyak percobaan\ | 5x salah login | Tunggu 15 menit atau hubungi Admin |
| Tidak redirect ke Module Selector | Session error | Clear browser cache/cookies, login ulang |

### 11.2 Data Tidak Bisa Disimpan
| Gejala | Penyebab | Solusi |
|--------|----------|--------|
| Tombol Save tidak bereaksi | Validasi gagal (field wajib kosong) | Periksa field berwarna merah / pesan error di bawah field |
| \Unauthorized\ / 403 | Tidak punya permission create/edit | Hubungi Admin untuk cek role & permission |
| Error server (500) | Bug sistem / data tidak valid | Catat error, hubungi IT |

### 11.3 Field Wajib Belum Diisi
- Field wajib ditandai asterisk (*) merah
- Pesan error muncul di bawah field saat blur/submit
- Isi semua field wajib sebelum klik Save

### 11.4 Akses Menu Tidak Tersedia
| Gejala | Penyebab | Solusi |
|--------|----------|--------|
| Modul tidak muncul di Module Selector | Role tidak punya module.access.{modul} | Hubungi Super Admin untuk assign permission modul |
| Resource tidak muncul di sidebar | Tidak punya view_any permission | Hubungi Super Admin |
| Tombol Create/Edit/Delete tidak ada | Tidak punya create/update/delete permission | Hubungi Super Admin |

### 11.5 Invoice Tidak Bisa Divalidasi (Customer)
| Gejala | Penyebab | Solusi |
|--------|----------|--------|
| \4 digit terakhir tidak cocok\ | Nomor HP di sistem beda dengan yang diinput | Hubungi Sales/Admin untuk update nomor HP customer |
| \Link verifikasi kadaluarsa\ | Signed URL expired (>30 menit) | Klik link verifikasi dari email lagi (generate baru) |
| \Invoice tidak ditemukan\ | Nomor invoice salah / sudah dihapus | Periksa nomor invoice di email, hubungi Sales |

### 11.6 Session / Logout Masalah
| Gejala | Penyebab | Solusi |
|--------|----------|--------|
| Sering logout otomatis | Session timeout (default 2 jam idle) | Login ulang, aktifkan \Remember me\ jika ada |
| Bisa akses halaman setelah logout | Browser cache | Hard refresh (Ctrl+F5), clear cache, login ulang |
| Tidak bisa login di browser lain | Session conflict | Logout dari browser lain, atau tunggu session expire |

### 11.7 Masalah Umum Lainnya
| Masalah | Solusi |
|---------|--------|
| Halaman tidak load / blank | Cek koneksi internet, refresh browser, cek console (F12) untuk error JS |
| File PDF tidak download | Pop-up blocker aktif -> izinkan pop-up untuk domain sistem |
| Gambar tidak muncul (foto produk, banner) | File storage link belum dijalankan -> hubungi IT (php artisan storage:link) |
| Gagal upload file | Ukuran file > limit (default 2MB/10MB), format tidak didukung |
| Data tidak update real-time | Filament livewire polling -> refresh halaman (F5) |

[PERLU DILENGKAPI: Detail error code spesifik dari log sistem jika diperlukan]
---

## Lampiran: Role Access Mapping (Ringkasan)

| Role Bisnis | Role Teknis | Modul yang Diakses |
|-------------|-------------|-------------------|
| Super Admin | \super_admin\ | Inventory, HR, Finance, Procurement, CRM, Sales, Marketing, Project, System |
| Sales | \sales_employees\ | Attendance, CRM, Sales |
| Warehouse | \inventory_employees\ + \procurement_employees\ | Attendance, Inventory, Procurement, Project (dashboard) |
| IT | (Custom) | Attendance, Project, System, Sales Activity |
| Sales External | \sales_activity_employees\ | Sales Activity |
| Manager | \manager\ (custom) | **Semua modul -- Hanya Dashboard** |
| Customer | (External) | Customer Portal, Invoice Verification |

---

## Lampiran: Screenshot Checklist

| No | Role | Modul/Menu | Screenshot yang Diperlukan | Tujuan |
|----|------|------------|---------------------------|--------|
| 1 | All | Login | Halaman Login -- field email, password, tombol Login | Panduan login |
| 2 | All | Module Selector | Module Selector -- daftar modul per role | Pemilihan modul |
| 3 | Super Admin | System -> Role | Form Role -- section \ Akses Modul\ checkbox | Konfigurasi akses modul |
| 4 | Super Admin | Inventory -> Product | Product List -- tabel, tombol Create | Manajemen produk |
| 5 | Super Admin | Inventory -> Transaction | Form Mutasi -- tipe, reference, qty | Pencatatan mutasi |
| 6 | Super Admin | Inventory -> Stock Report | Laporan Stok -- tabel, tombol Download PDF | Laporan stok |
| 7 | Super Admin | HR -> Employee | Employee List -- tabel, tombol Create | Manajemen karyawan |
| 8 | Super Admin | HR -> Leave Request | Leave Request -- daftar dengan status | Approval cuti |
| 9 | Super Admin | Finance -> Financial Record | Form Transaksi -- tipe, nominal, kategori | Pencatatan keuangan |
| 10 | Super Admin | Procurement -> Purchase Order | PO List -- status, aksi | Alur PO |
| 11 | Super Admin | Procurement -> Goods Receipt | Form GR -- qty received, kondisi | Penerimaan barang |
| 12 | Super Admin | CRM -> Lead | Lead List -- status, tombol Convert | Manajemen lead |
| 13 | Super Admin | CRM -> Deal | Deal Kanban -- pipeline stages | Pipeline deal |
| 14 | Super Admin | Sales -> Quotation | Quotation Create -- item repeater, promo, total | Pembuatan penawaran |
| 15 | Super Admin | Sales -> Delivery Order | DO List -- tombol Print PDF, tracking | Pengiriman & tracking |
| 16 | Super Admin | Sales -> Invoice | Invoice List -- status pembayaran | Manajemen invoice |
| 17 | Super Admin | Marketing -> Promo Code | Promo Code List -- kode, tipe, tanggal | Manajemen promo |
| 18 | Super Admin | Project -> Project List | Project List -- progress bar | Manajemen project |
| 19 | Super Admin | Project -> External Dashboard | External Dashboard -- view stakeholder | Akses eksternal |
| 20 | Sales | Attendance | Presensi Harian -- Clock In/Out, shift info | Presensi harian |
| 21 | Sales | CRM -> Lead Create | Form Lead -- tipe, PIC, field dinamis | Pembuatan lead |
| 22 | Sales | CRM -> Deal Kanban | Deal Board -- drag drop stage | Manajemen deal visual |
| 23 | Sales | Sales -> Quotation Create | Form Quotation -- item, promo, grand total | Penawaran detail |
| 24 | Sales | Sales -> Delivery Order | DO Detail -- Print PDF, tracking link | DO & tracking |
| 25 | Warehouse | Inventory -> Transaction Create | Form Mutasi -- reference GR/DO | Mutasi gudang |
| 26 | Warehouse | Inventory -> Monitoring | Stock Monitoring -- highlight stok min | Monitoring stok |
| 27 | Warehouse | Procurement -> Goods Receipt | Form GR -- qty, kondisi, catatan | Proses GR |
| 28 | IT | Project -> Ticket List | Ticket List -- filter status, priority, assignee | Manajemen ticket |
| 29 | IT | System -> User | User List -- role badge | Manajemen user |
| 30 | IT | System -> Role | Role Form -- permission matrix + Akses Modul | Konfigurasi role |
| 31 | Manager | HR -> Dashboard HR | Dashboard HR -- widget, chart, tabel | Dashboard read-only |
| 32 | Manager | Sales -> Dashboard Sales | Dashboard Sales -- KPI, grafik, overdue | Dashboard read-only |
| 33 | Sales External | Sales Activity -> Visit Assignment | Visit List -- Check In/Out buttons | Kunjungan sales |
| 34 | Customer | Customer Portal Login | Login Portal -- email, 5 digit HP | Login customer |
| 35 | Customer | Customer Portal Dashboard | Dashboard -- stats cards, return table | Dashboard customer |
| 36 | Customer | Customer Portal -> Buat Return | Form Return -- pilih invoice, SN, alasan, foto | Pengajuan return |
| 37 | Customer | Invoice Verification | Verifikasi Form -- 4 digit HP, tombol Verifikasi | Verifikasi invoice |
| 38 | Customer | Invoice Detail (Signed URL) | Detail Invoice -- lengkap, Download PDF | Hasil verifikasi |
| 39 | All Internal | User Menu | Dropdown -- Ganti Modul, Logout | Navigasi user menu |
| 40 | All | After Logout | Halaman Login kembali | Konfirmasi logout |

---

**--- END OF MANUAL BOOK ---**

*Dokumen ini dibuat berdasarkan audit source code Nexicon ERP per tanggal 2026-09-17.*
*Versi: 1.0*
*Dibuat oleh: Codex AI Assistant*
