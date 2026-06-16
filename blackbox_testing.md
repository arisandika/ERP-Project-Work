# Blackbox Testing - 20 Prioritized Scenarios (ERP)

Dokumen berisi 20 skenario pengujian blackbox untuk fitur penting ERP. Kolom "Hasil Aktual" dan "Kesimpulan" diset ke "Belum diuji" / "Pending" untuk diisi saat eksekusi.

| ID Uji | Skenario Pengujian | Data Uji (Input) | Hasil yang Diharapkan | Hasil Aktual | Kesimpulan |
|---|---|---|---|---|---|
| BB-001 | Login dengan kredensial valid | email: qa@example.com / password: Password123 | Berhasil login; redirect ke dashboard; status 200 | Belum diuji | Pending |
| BB-002 | Login dengan password salah | email: qa@example.com / password: WrongPass | Gagal login; pesan "Kredensial salah"; status 401 | Belum diuji | Pending |
| BB-003 | Reset password (email flow) | email: qa@example.com -> request reset | Menerima email reset; link valid; bisa set password baru | Belum diuji | Pending |
| BB-004 | Role-based Access Control (admin vs viewer) | login user role=viewer -> akses /admin | Akses ditolak (403) atau redirect; menu admin tidak muncul | Belum diuji | Pending |
| BB-005 | Membuat Produk Baru | name: "Produk A", sku: P-001, price: 10000, stock: 50 | Produk tersimpan; tampil di daftar; detail benar | Belum diuji | Pending |
| BB-006 | Edit Produk | ubah price=12000 pada P-001 | Perubahan tersimpan; detail memperlihatkan price baru | Belum diuji | Pending |
| BB-007 | Hapus Produk | hapus produk P-001 (tidak digunakan di SO) | Produk terhapus; tidak tampil di daftar | Belum diuji | Pending |
| BB-008 | Membuat Customer Baru | name: "PT. Contoh", email: cust@ex.com | Customer tersimpan; muncul di daftar; bisa pilih di SO | Belum diuji | Pending |
| BB-009 | Membuat Sales Order (SO) | customer: PT. Contoh; item: P-002 qty=5 | SO tersimpan (Draft/Open); subtotal benar; stok terreservasi | Belum diuji | Pending |
| BB-010 | Konfirmasi SO dan pengurangan stok | confirm SO id=SO-001 | Status SO -> Confirmed/Fulfilled; stok fisik berkurang sesuai qty | Belum diuji | Pending |
| BB-011 | Membuat Purchase Order (PO) dan penerimaan | PO supplier X item P-002 qty=20; terima 20 | PO status -> Received; stok bertambah 20 | Belum diuji | Pending |
| BB-012 | Pembayaran dan pembuatan Invoice | buat invoice untuk SO-001; bayar via bank transfer | Invoice status -> Paid; saldo customer terupdate | Belum diuji | Pending |
| BB-013 | Upload attachment pada transaksi | upload invoice.pdf (PDF, 200KB) pada SO-001 | File terupload; dapat preview/download | Belum diuji | Pending |
| BB-014 | Export data ke CSV/Excel | generate sales report; export CSV | File terdownload; kolom dan nilai sesuai tampilan | Belum diuji | Pending |
| BB-015 | Import customer via CSV (valid dan invalid rows) | CSV: 8 valid, 2 invalid rows | 8 terimport; 2 eror dilaporkan dengan alasan; proses tidak crash | Belum diuji | Pending |
| BB-016 | API GET produk (auth) | GET /api/products with Bearer token | 200 OK; response JSON berisi list produk; schema sesuai dokumen | Belum diuji | Pending |
| BB-017 | Validasi input form (email/qty/required) | submit form customer dengan email invalid/qty -5 | Validasi client/server muncul; data tidak disimpan; pesan error spesifik | Belum diuji | Pending |
| BB-018 | Session timeout dan auto-logout | login, idle > timeout (misal 30m) lalu aksi | Setelah idle, akses men-trigger login ulang; session invalidated | Belum diuji | Pending |
| BB-019 | Notifikasi realtime (order status) | buat SO, confirm -> user menerima notifikasi | Notifikasi muncul di UI (toast/inbox) atau via email | Belum diuji | Pending |
| BB-020 | Audit log untuk tindakan penting | tindakan: delete customer / confirm payment | Log tersimpan: user, action, timestamp, target resource | Belum diuji | Pending |

---

Petunjuk: Sesuaikan identifier (email, sku, ids) dengan data lingkungan testing. Isi kolom Hasil Aktual & Kesimpulan setelah menjalankan tiap langkah.
