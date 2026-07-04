# Kuesioner User Acceptance Test (UAT) — Sistem ERP

**Skala Pengukuran: Likert 1–5**

| Skala | Keterangan |
|-------|------------|
| 1 | Sangat Tidak Setuju |
| 2 | Tidak Setuju |
| 3 | Netral |
| 4 | Setuju |
| 5 | Sangat Setuju |

---

## Instruksi Pengisian

Silakan beri tanda centang atau lingkaran pada kolom yang paling sesuai dengan pendapat Anda mengenai pernyataan di bawah ini. Tidak ada jawaban yang benar atau salah — kami hanya ingin mengetahui pengalaman Anda dalam menggunakan sistem ini.

**Identitas Responden (opsional):**

| | |
|---|---|
| Nama | |
| Departemen | |
| Posisi/Jabatan | |

---

## Daftar Pernyataan

| No. | Pernyataan | Skala 1 | Skala 2 | Skala 3 | Skala 4 | Skala 5 | Aspek yang Diukur | Fitur/Modul Terkait |
|:---:|-----------|:-------:|:-------:|:-------:|:-------:|:-------:|-------------------|---------------------|
| 1 | Sistem ini mudah digunakan tanpa memerlukan pelatihan khusus yang panjang. | ☐ | ☐ | ☐ | ☐ | ☐ | Kemudahan Penggunaan (Usability) | Seluruh modul — navigasi yang konsisten di semua halaman sistem |
| 2 | Menu dan tampilan antarmuka disusun secara rapi sehingga saya dapat dengan cepat menemukan fitur yang saya butuhkan. | ☐ | ☐ | ☐ | ☐ | ☐ | Kejelasan Antarmuka | Struktur navigasi per modul (HR, CRM, Sales, Inventory, dll.) dengan ikon dan pengelompokan yang teratur |
| 3 | Fitur-fitur yang tersedia di dalam sistem mencakup kebutuhan pekerjaan saya sehari-hari di departemen. | ☐ | ☐ | ☐ | ☐ | ☐ | Kesesuaian Fitur dengan Kebutuhan Kerja | Seluruh modul — HR, CRM, Sales, Inventory, Procurement, Project, Finance, Marketing, After-Sales |
| 4 | Dengan menggunakan sistem ini, pekerjaan saya dapat diselesaikan lebih cepat dan efisien dibandingkan metode sebelumnya. | ☐ | ☐ | ☐ | ☐ | ☐ | Efektivitas & Efisiensi | Otomasi alur kerja: pembuatan Delivery Order otomatis dari Sales Order, pengecekan stok rendah secara berkala, pengarsipan data terpusat |
| 5 | Sistem menyediakan papan Kanban (kartu yang dapat digeser/di-*drag*) yang memudahkan saya memantau alur kerja penjualan atau proyek secara visual. | ☐ | ☐ | ☐ | ☐ | ☐ | Kejelasan Antarmuka & Efisiensi | **CRM Deal Pipeline** — papan Kanban *drag-and-drop* untuk tahapan prospek/penjualan; **Project Board** — papan Kanban *drag-and-drop* untuk tiket proyek |
| 6 | Fitur pencatatan kehadiran secara otomatis mencatat lokasi saya saat melakukan *clock in/out*, sehingga proses absensi menjadi lebih mudah dan akurat. | ☐ | ☐ | ☐ | ☐ | ☐ | Kemudahan Penggunaan & Efisiensi | **HR Attendance** — *clock in/out* berbasis GPS dengan peta, validasi jarak ke kantor (*geofencing*), penyimpanan koordinat lokasi saat masuk dan keluar |
| 7 | Sistem mengirimkan pemberitahuan secara otomatis saat ada pengajuan yang membutuhkan persetujuan saya (seperti cuti atau reimbursement), sehingga saya tidak ketinggalan informasi. | ☐ | ☐ | ☐ | ☐ | ☐ | Efektivitas & Efisiensi | **Notifikasi otomatis** — pemberitahuan ke admin/manager saat ada pengajuan cuti atau reimbursement baru, serta pemberitahuan ke karyawan saat pengajuan disetujui/ditolak; juga peringatan otomatis saat stok barang menipis |
| 8 | Laman pelacakan pengiriman dapat diakses oleh pihak pelanggan atau penerima barang tanpa perlu login ke sistem. | ☐ | ☐ | ☐ | ☐ | ☐ | Kesesuaian Fitur | **Pelacakan Delivery Order** — halaman publik di mana penerima dapat melihat status pengiriman dan menekan tombol "Terima" tanpa perlu masuk ke sistem |
| 9 | Saya dapat mengunduh data laporan dalam format Excel atau PDF dari data yang ada di sistem untuk keperluan analisis atau pelaporan kerja. | ☐ | ☐ | ☐ | ☐ | ☐ | Efektivitas & Efisiensi | **Ekspor Laporan** — ekspor data tiket proyek dan kehadiran karyawan ke Excel; ekspor laporan stok dan transaksi inventori ke PDF |
| 10 | Sistem berjalan dengan stabil dan jarang mengalami gangguan atau pesan error saat saya menggunakannya dalam aktivitas kerja sehari-hari. | ☐ | ☐ | ☐ | ☐ | ☐ | Stabilitas Sistem | Pengujian keseluruhan modul — mekanisme pengaman transaksi data, validasi status berlapis pada alur kerja, pemulihan otomatis (kedaluwarsa cuti pending, penyelesaian pengiriman otomatis) |

---

## Ringkasan Aspek yang Diukur

| Aspek | Nomor Pernyataan |
|-------|:----------------:|
| Kemudahan Penggunaan (Usability) | 1, 6 |
| Kesesuaian Fitur dengan Kebutuhan Kerja | 3, 8 |
| Kejelasan Antarmuka | 2, 5 |
| Efektivitas & Efisiensi | 4, 7, 9 |
| Stabilitas Sistem | 10 |

---

## Referensi Fitur di Kode Sumber

Pernyataan nomor 5–9 merujuk pada fitur spesifik yang teridentifikasi dari codebase:

| Pernyataan | Bukti di Kode |
|:----------:|---------------|
| **5** | `app/Filament/Pages/CRM/DealPipeline.php` — Kanban CRM dengan *drag-and-drop*, pengurutan per kolom, aturan bisnis tahapan; `app/Filament/Pages/Project/ProjectBoard.php` — Kanban tiket proyek dengan filter pengguna |
| **6** | `app/Filament/Pages/HR/Attendance.php` — *Clock in/out* dengan GPS (*Leaflet maps*), penyimpanan `latitude_in`, `longitude_in`, `latitude_out`, `longitude_out`; `app/Http/Controllers/API/HR/AttendanceController.php` — validasi jarak ke kantor |
| **7** | `app/Observers/LeaveRequestObserver.php` — notifikasi ke admin saat cuti diajukan, notifikasi ke karyawan saat disetujui/ditolak; `app/Observers/ReimbursementRequestObserver.php` — alur serupa untuk reimbursement; `app/Notifications/LowStockNotification.php` — peringatan stok menipis |
| **8** | `app/Http/Controllers/Sales/DeliveryOrderTrackingController.php` — halaman publik `/tracking/do/{nomor}` dengan konfirmasi penerimaan tanpa autentikasi |
| **9** | `app/Exports/TicketsExport.php` dan `app/Exports/AttendancesExport.php` — ekspor Excel via Maatwebsite/Excel; `app/Http/Controllers/Inventory/StockReportController.php` — ekspor PDF laporan stok |

---

*Kuesioner ini disusun berdasarkan analisis kode sumber sistem ERP yang diimplementasikan. Semua fitur yang disebutkan dalam pernyataan telah diverifikasi keberadaannya di dalam codebase.*
