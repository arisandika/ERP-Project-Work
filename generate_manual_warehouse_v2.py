# -*- coding: utf-8 -*-
"""
Generator Manual Book Role Warehouse v2 — lebih detail & terstruktur per BAB.
Output: Manual_Book_Role_Warehouse_V2.docx
Jalankan: python generate_manual_warehouse_v2.py
"""

from docx import Document
from docx.shared import Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.style import WD_STYLE_TYPE

OUT = "Manual_Book_Role_Warehouse_V2.docx"

doc = Document()

# ---------- Styles ----------
normal = doc.styles["Normal"]
normal.font.name = "Aptos"
normal.font.size = Pt(11)
normal.paragraph_format.line_spacing = 1.15
normal.paragraph_format.space_after = Pt(6)

for name, size in [("Heading 1", 16), ("Heading 2", 13), ("Heading 3", 12)]:
    st = doc.styles[name]
    st.font.name = "Aptos"
    st.font.size = Pt(size)
    st.font.bold = True

cap = doc.styles["Caption"]
cap.font.name = "Aptos"
cap.font.size = Pt(10)
cap.font.italic = True

marker = doc.styles.add_style("FotoMarker", WD_STYLE_TYPE.PARAGRAPH)
marker.base_style = doc.styles["Normal"]
marker.font.name = "Consolas"
marker.font.size = Pt(10)
marker.font.bold = True
marker.font.color.rgb = RGBColor(0xB0, 0x3A, 0x2E)
marker.paragraph_format.space_before = Pt(6)
marker.paragraph_format.space_after = Pt(6)
marker.paragraph_format.left_indent = Pt(18)

# ---------- Helpers ----------
fig = {"n": 0}


def h(text, level):
    doc.add_heading(text, level=level)


def para(text, bold_lead=None):
    p = doc.add_paragraph()
    if bold_lead:
        r = p.add_run(bold_lead)
        r.bold = True
        p.add_run(" " + text)
    else:
        p.add_run(text)
    return p


def foto(desc):
    p = doc.add_paragraph(style="FotoMarker")
    p.add_run(">> [FOTO DI SINI] %s" % desc)
    return p


def caption(judul):
    fig["n"] += 1
    p = doc.add_paragraph(style="Caption")
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.add_run("Gambar %d. %s" % (fig["n"], judul))
    return p


def step(judul, isi):
    p = doc.add_paragraph(style="List Paragraph")
    r = p.add_run(judul)
    r.bold = True
    p.add_run(" " + isi)
    return p


def bullet(judul, isi=None):
    p = doc.add_paragraph(style="List Bullet")
    r = p.add_run(judul)
    r.bold = True
    if isi:
        p.add_run(" " + isi)
    return p


def toc_entry(text, sub=False):
    p = doc.add_paragraph()
    r = p.add_run(("    " if sub else "") + text)
    r.bold = not sub
    return p


# ===================================================================
# DAFTAR ISI
# ===================================================================
doc.add_heading("DAFTAR ISI", level=1)
toc = [
    ("BAB I   PENDAHULUAN", False),
    ("BAB II  PERSIAPAN AWAL & LOGIN", False),
    ("2.1  Login ke Aplikasi", True),
    ("2.2  Halaman Pilih Modul Kerja", True),
    ("BAB III  MODUL ATTENDANCE (MANAJEMEN PRESENSI)", False),
    ("3.1  Presensi Harian", True),
    ("3.2  Pengajuan Cuti", True),
    ("3.3  Pengajuan Sakit", True),
    ("3.4  Riwayat Presensi", True),
    ("3.5  Informasi Lembur (Overtime)", True),
    ("BAB IV  MODUL INVENTORY (MANAJEMEN STOK)", False),
    ("4.1  Dashboard Inventory", True),
    ("4.2  Monitoring Inventory (Live)", True),
    ("4.3  Mengelola Data Gudang", True),
    ("4.4  Daftar Product", True),
    ("4.5  Laporan Stock Product", True),
    ("4.6  Laporan Transaksi", True),
    ("BAB V  PENGELOLAAN SERIAL NUMBER", False),
    ("5.1  Daftar Serial Number", True),
    ("5.2  Melapor Barang Rusak", True),
    ("5.3  Menandai Barang Hilang", True),
    ("5.4  Pelacakan Serial Number", True),
    ("BAB VI  MODUL PROCUREMENT (PENERIMAAN BARANG)", False),
    ("6.1  Penerimaan Barang dari Purchase Order (Goods Receipt)", True),
    ("BAB VII  MODUL AFTER-SALES (PASCA JUAL)", False),
    ("7.1  Penerimaan Return (RMA)", True),
    ("7.2  Servis Internal", True),
    ("7.3  Klaim Vendor", True),
    ("BAB VIII  MODUL PROJECT MANAGEMENT", False),
    ("8.1  Dashboard Project", True),
    ("8.2  Daftar Project", True),
    ("8.3  Project Board", True),
    ("8.4  Bekerja dengan Ticket", True),
    ("BAB IX  CATATAN HAK AKSES & PENUTUP", False),
]
for t, sub in toc:
    toc_entry(t, sub)

# ===================================================================
# BAB I PENDAHULUAN
# ===================================================================
doc.add_heading("BAB I  PENDAHULUAN", level=1)

h("Tujuan Dokumen", 2)
para(
    "Dokumen ini adalah panduan resmi penggunaan website ElviaERP (Nexicon ERP) "
    "bagi pengguna dengan role Warehouse. Tujuan dokumen ini adalah memberikan "
    "penjelasan yang lengkap, berurutan, dan mudah diikuti terhadap seluruh fitur "
    "yang dapat digunakan oleh petugas gudang, sehingga pengguna baru dapat "
    "menjalankan pekerjaan sehari-hari tanpa bantuan pihak lain."
)

h("Hak Akses Role Warehouse", 2)
para(
    "Setiap pengguna login memiliki hak akses (permission) yang diatur oleh Super "
    "Admin. Role Warehouse (petugas gudang) memiliki akses terhadap:",
)
bullet("Modul Attendance", "untuk presensi dan manajemen kehadiran harian.")
bullet("Modul Inventory", "untuk memantau dan mengelola stok product serta serial number.")
bullet("Modul Procurement", "untuk menerima barang dari supplier berdasarkan Purchase Order.")
bullet("Modul Project Management", "untuk mengikuti pekerjaan gudang dalam bentuk project dan ticket.")
bullet("Modul After-Sales", "untuk memproses pengembalian dan perbaikan unit pasca jual.")

para(
    "Bab-bab berikut menjelaskan setiap modul tersebut secara berurutan mengikuti "
    "urutan pekerjaan yang paling sering dilakukan petugas gudang: presensi di pagi "
    "hari, memantau stok, menerima barang, hingga menangani klaim pasca jual."
)

# ===================================================================
# BAB II PERSIAPAN AWAL & LOGIN
# ===================================================================
doc.add_heading("BAB II  PERSIAPAN AWAL & LOGIN", level=1)

h("2.1  Login ke Aplikasi", 2)
para(
    "Sebelum menggunakan aplikasi, pastikan Anda sudah memiliki akun (email dan "
    "kata sandi) dari Super Admin serta data diri lengkap sebagai karyawan "
    "(termasuk departemen, kantor, dan shift), karena data tersebut dipakai untuk "
    "menghitung presensi Anda."
)
step("Buka halaman login",
     "Bukalah ElviaERP melalui web browser (Google Chrome, Microsoft Edge, atau "
     "Mozilla Firefox). Masukkan alamat URL aplikasi pada kolom alamat browser, "
     "lalu tekan Enter. Browser akan menampilkan halaman login.")
step("Isi email dan kata sandi",
     "Pada halaman login, masukkan Alamat email dan Kata sandi yang telah "
     "diberikan. Perhatikan penulisan huruf besar/kecil dan pastikan tidak ada "
     "spasi. Klik tombol Masuk untuk masuk ke aplikasi.")
foto("Halaman Login aplikasi berisi kolom email dan kata sandi serta tombol Masuk")
caption("Halaman Login")
step("Apabila lupa kata sandi",
     "Gunakan tombol Lupa Sandi / reset kata sandi pada halaman login. Sistem "
     "akan mengirimkan tautan reset ke email Anda. Hubungi Super Admin apabila "
     "email sudah tidak aktif.")
step("Keluar (Logout)",
     "Untuk mengakhiri sesi, klik foto/ikon akun Anda di pojok kanan atas, lalu "
     "pilih menu Keluar. Selalu keluar dari akun setelah selesai bekerja, "
     "terutama bila menggunakan perangkat bersama.")
foto("Menu dropdown akun di pojok kanan atas dengan pilihan Keluar")
caption("Menu Logout")

h("2.2  Halaman Pilih Modul Kerja", 2)
para(
    "Setelah login berhasil, sistem menampilkan halaman Pilih Modul Kerja. Halaman "
    "ini adalah pusat navigasi utama. dari sini Anda memilih modul yang akan "
    "dibuka. Halaman ini juga menampilkan informasi penting hari kerja Anda."
)
step("Membaca kapsul informasi",
     "Di bawah sapaan pembuka, sistem menampilkan kapsul (pill) kecil berisi: "
     "Role Pengguna, Departemen, status Hari Kerja Aktif / Akhir Pekan, Jadwal "
     "Shift beserta jam kerjanya, jadwal Cuti yang sedang berjalan atau mendatang, "
     "dan Libur Nasional terdekat dalam 30 hari. Bacalah kapsul ini setiap pagi "
     "untuk memastikan tanggal, shift, dan status hari kerja Anda.")
foto("Halaman Pilih Modul Kerja beserta kapsul informasi role, departemen, dan shift")
caption("Halaman Pilih Modul Kerja")
step("Membuka modul",
     "Seluruh modul yang diizinkan ditampilkan dalam bentuk kartu berisi ikon, "
     "nama, dan deskripsi singkat. Arahkan kursor ke kartu modul lalu klik tombol "
     "Buka modul. Setelah terbuka, sisi kiri layar akan menampilkan menu-menu "
     "khusus modul tersebut.")
foto("Grid kartu modul dengan tombol Buka modul")
caption("Kartu Modul yang Dapat Diakses")
step("Memahami banner status presensi",
     "Di bagian atas halaman terdapat banner berwarna. Warna merah berarti Anda "
     "belum presensi masuk, biru berarti sudah masuk belum keluar, dan hijau "
     "berarti presensi hari ini selesai. Banner juga muncul pada hari libur "
     "nasional atau akhir pekan untuk memberi tahu bahwa sistem presensi sedang "
     "ditutup.")
foto("Banner status presensi berwarna merah dengan pengingat presensi masuk")
caption("Banner Status Presensi")

# ===================================================================
# BAB III ATTENDANCE
# ===================================================================
doc.add_heading("BAB III  MODUL ATTENDANCE (MANAJEMEN PRESENSI)", level=1)

para(
    "Modul Attendance mengelola seluruh kehadiran karyawan. Modul ini memuat empat "
    "menu utama pada kategori Manajemen Presensi di sidebar: Presensi Harian, "
    "Pengajuan Cuti, Pengajuan Sakit, dan Riwayat Presensi. Modul ini hanya dapat "
    "diakses oleh karyawan (bukan Super Admin), dan setiap karyawan hanya melihat "
    "datanya sendiri. Sistem memadukan foto wajah (selfie), lokasi GPS, dan aturan "
    "shift untuk mencatat kehadiran secara akurat."
)

# ---- 3.1 Presensi Harian ----
h("3.1  Presensi Harian", 2)
para(
    "Setiap hari kerja, petugas gudang wajib melakukan presensi masuk dan pulang "
    "secara daring dari perangkat masing-masing. Karena role ini bersifat mobile, "
    "presensi dapat dilakukan lewat ponsel dengan membuka elviaERP melalui "
    "browser. Lokasi pemeriksaan menggunakan GPS; pastikan GPS ponsel menyala dan "
    "berikan izin lokasi kepada browser saat diminta."
)

h("3.1.1  Membuka halaman Presensi Harian", 3)
step("Masuk ke modul Attendance",
     "Pada halaman Pilih Modul Kerja, klik Buka modul pada kartu Attendance.")
step("Pilih menu Presensi Harian",
     "Pada sidebar, buka kategori Manajemen Presensi lalu klik menu Presensi "
     "Harian. Halaman menampilkan status presensi hari ini, jam masuk, jam pulang, "
     "catatan, dan kartu informasi karyawan.")
foto("Halaman Presensi Harian beserta kartu informasi karyawan di sisi kanan")
caption("Halaman Presensi Harian")

h("3.1.2  Memahami kartu informasi karyawan", 3)
para(
    "Pada sisi kanan halaman terdapat kartu berisi foto dan nama Anda, Departemen, "
    "Kantor, Jadwal (nama shift beserta jam mulai dan selesai), serta Tipe "
    "Karyawan. Tipe karyawan menentukan ketatnya aturan presensi Anda:",
)
bullet("Lokasi kerja:", "Bekerja Dimana Saja (WFA) atau Bekerja dari Kantor.")
bullet("Jam kerja:", "Jam Kerja Fleksibel atau Jam Kerja Tetap.")
para(
    "Kombinasi keduanya menentukan apakah Anda bebas presensi dari mana saja dan "
    "di jam mana saja. Bila bertuliskan Bekerja dari Kantor dan Jam Kerja Tetap, "
    "Anda harus presensi di dalam radius kantor dan di dalam jam kerja, kecuali "
    "mendapat izin khusus dari HR/Super Admin."
)

h("3.1.3  Melakukan presensi masuk (Check In)", 3)
step("Buka kamera",
     "Pada bagian Check In, klik bingkai bertuliskan \"Klik untuk membuka "
     "kamera\". Sistem mengaktifkan kamera perangkat. Tunggu hingga lampu "
     "penggunaan kamera menyala; beberapa browser meminta izin kamera terlebih "
     "dahulu — klik Izinkan.")
step("Ambil foto wajah",
     "Posisikan wajah di tengah bingkai dengan pencahayaan cukup, lalu klik "
     "tombol kamera. Foto yang tampil dapat diganti dengan memilih Ambil Ulang "
     "bila kurang jelas (redup, terhalang, atau blur).")
foto("Jendela kamera terbuka pada bagian Check In")
caption("Presensi Harian - Pengambilan Foto Check In")
step("Tulis catatan (opsional)",
     "Isi kolom Catatan bila diperlukan, misalnya keterangan keterlambatan atau "
     "ringkasan tugas yang dikerjakan.")
step("Tandai lokasi",
     "Klik tombol Tandai Lokasi. Sistem mencari titik GPS perangkat dengan "
     "akurasi tinggi dan menampilkan peta berisi lingkaran biru (radius kantor), "
     "badge akurasi (contoh \"Akurasi: ±22m\"), alamat, koordinat, dan status "
     "lokasi seperti \"Dalam radius\" atau \"Luar radius\".")
foto("Peta dengan lingkaran radius kantor, marker posisi, dan badge akurasi GPS")
caption("Verifikasi Lokasi Presensi")
step("Kirim presensi",
     "Pastikan status lokasi menunjukkan dalam radius, lalu klik tombol presensi. "
     "Sistem menyimpan foto, koordinat, dan waktu, lalu menampilkan notifikasi "
     "berhasil serta status kehadiran Anda.")
foto("Notifikasi sukses presensi masuk beserta status kehadiran")
caption("Berhasil Presensi Masuk")

h("3.1.4  Aturan yang memblokir presensi", 3)
para(
    "Sistem menolak presensi masuk apabila salah satu kondisi berikut terjadi. "
    "Bila Anda mengalami penolakan, bacalah notifikasi merah yang muncul:",
)
bullet("Sedang cuti", "Anda memiliki pengajuan cuti yang disetujui dan mencakup hari ini.")
bullet("Sedang sakit", "Anda memiliki pengajuan sakit yang disetujui dan mencakup hari ini.")
bullet("Akhir pekan", "Hari ini Sabtu atau Minggu; sistem presensi ditutup otomatis.")
bullet("Libur nasional", "Hari ini masuk dalam rentang hari libur nasional yang terdaftar.")
bullet("Di luar radius", "Anda berada di luar radius kantor tetapi tidak memiliki izin WFA.")
bullet("Di luar jam kerja", "Anda presensi di luar jam kerja beserta toleransinya tetapi tidak memiliki izin jam fleksibel.")
para(
    "Catatan: kondisi \"akhir pekan\" dan \"libur nasional\" hanya memblokir "
    "presensi masuk. Presensi keluar tetap diperbolehkan bagi yang sudah presensi "
    "masuk sebelum hari libur/akhir pekan, agar siklus kehadiran tercatat penuh."
)

h("3.1.5  Status kehadiran", 3)
para("Setelah presensi masuk berhasil, sistem menetapkan status berupa badge:",)
bullet("Hadir", "presensi masuk tepat waktu, tidak melewati batas toleransi shift.")
bullet("Terlambat", "presensi masuk melewati batas toleransi jam mulai shift.")
para(
    "Status berikut dihitung oleh sistem pada akhir hari / diisi otomatis:",
)
bullet("Belum Presensi", "hari ini belum ada presensi masuk.")
bullet("Absen", "tidak ada presensi sama sekali pada hari kerja.")
bullet("Izin / Cuti / Sakit", "hari tersebut tercatat sebagai izin, cuti, atau sakit yang disetujui.")
bullet("Tidak Presensi Keluar", "sudah presensi masuk tetapi belum melakukan presensi keluar, misalnya pulang mendadak.")
bullet("Libur", "hari libur nasional atau akhir pekan.")

h("3.1.6  Melakukan presensi keluar (Check Out)", 3)
step("Waktu yang diizinkan",
     "Presensi keluar hanya dapat dilakukan setelah jam selesai shift. Bila "
     "diklik terlalu cepat, sistem menampilkan notifikasi \"Belum Waktunya "
     "Presensi Keluar\".")
step("Lakukan seperti check in",
     "Pada bagian Check Out, buka kamera, ambil foto diri, tambahkan catatan jika "
     "ada, lalu klik tombol Presensi Keluar.")
step("Periksa ringkasan",
     "Setelah selesai, halaman menampilkan jam masuk, jam pulang, titik lokasi "
     "masuk dan keluar, serta berubah menjadi tampilan \"Presensi Selesai\".")
foto("Bagian Check Out beserta ringkasan jam masuk dan jam pulang")
caption("Presensi Keluar")

h("3.1.7  Peta riwayat presensi hari ini", 3)
para(
    "Pada sisi kanan bawah halaman terdapat peta berisi dua penanda: hijau "
    "bertuliskan IN (titik presensi masuk) dan merah bertuliskan OUT (titik "
    "presensi keluar). Klik penanda tersebut untuk melihat waktu, foto, dan "
    "lokasinya; klik foto untuk memperbesar.")
foto("Peta riwayat dengan penanda IN hijau dan OUT merah")
caption("Peta Riwayat Presensi Harian")

h("3.1.8  Perlindungan data dan deteksi GPS palsu", 3)
para(
    "Setiap presensi menyimpan foto selfie dan koordinat GPS sebagai bukti "
    "digital yang dapat diperiksa HR/Super Admin. Sistem membandingkan posisi "
    "GPS dengan lokasi kantor dan menghitung jarak menggunakan penghitung jarak "
    "geografis (haversine). Sistem juga mendeteksi GPS palsu: bila kecepatan "
    "perpindahan perangkat saat pengambilan lokasi tidak wajar, presensi ditolak "
    "dan muncul peringatan. Selalu gunakan GPS asli yang aktif, bukan aplikasi "
    "pemalsu lokasi."
)

# ---- 3.2 Pengajuan Cuti ----
h("3.2  Pengajuan Cuti", 2)
para(
    "Menu Pengajuan Cuti digunakan untuk mengajukan permohonan cuti dengan "
    "menentukan jenis cuti, rentang tanggal, dan alasan. Panjang cuti dihitung "
    "otomatis berdasarkan hari kerja: Sabtu dan Minggu tidak dihitung. Bila "
    "disetujui, hari-hari cuti dicatat sebagai status Cuti dan Anda tidak dapat "
    "presensi pada hari-hari tersebut.")
step("Membuka menu",
     "Pada sidebar modul Attendance, klik menu Pengajuan Cuti pada kategori "
     "Manajemen Presensi. Halaman menampilkan daftar pengajuan milik Anda: Jenis "
     "Cuti, Status, Tanggal Mulai, Tanggal Selesai, Durasi. Di dekat judul menu "
     "terdapat badge kuning yang menampilkan jumlah pengajuan yang masih menunggu "
     "persetujuan.")
foto("Daftar Pengajuan Cuti milik pengguna dengan badge notifikasi")
caption("Menu Pengajuan Cuti")
step("Membuat pengajuan baru",
     "Klik tombol Buat di pojok kanan atas. Lengkapi formulir:",
)
bullet("Jenis Cuti", "pilih dari daftar; tiap jenis menampilkan sisa jatah hari, misalnya \"Cuti Tahunan (Sisa 10 Hari)\". Jenis cuti khusus gender hanya tampil sesuai jenis kelamin Anda.")
bullet("Tanggal Mulai & Tanggal Selesai", "tentukan rentang cuti.")
bullet("Durasi Cuti (Hari Kerja)", "terisi otomatis; hanya menghitung hari kerja, akhir pekan dilewati.")
bullet("Alasan Cuti", "jelaskan alasan mengajukan cuti.")
bullet("Bukti Cuti/Sakit", "tombol Upload (opsional); terima format gambar JPEG/PNG/WEBP maksimal 2 MB.")
step("Simpan pengajuan",
     "Klik tombol Buat. Pengajuan tersimpan dengan status Menunggu dan masuk "
     "antrian persetujuan HR.")
foto("Formulir Buat Pengajuan Cuti lengkap")
caption("Formulir Pengajuan Cuti")
step("Mengubah pengajuan",
     "Selama berstatus Menunggu, klik tombol Ubah pada baris untuk memperbaiki "
     "data (misalnya mengubah tanggal). Pengajuan yang sudah disetujui/ditolak "
     "tidak dapat diubah; ajukan cuti baru bila perlu.")
step("Membatalkan pengajuan",
     "Klik tombol Batalkan untuk mengurungkan pengajuan yang masih Menunggu atau "
     "yang sudah Disetujui. Sistem meminta konfirmasi. Setelah dibatalkan, status "
     "berubah menjadi Dibatalkan dan hari-hari cuti dikembalikan otomatis.")
step("Memantau persetujuan",
     "Klik tombol Lihat untuk membuka detail. Bagian Status Persetujuan "
     "menampilkan Disetujui Oleh, Waktu Persetujuan, dan Catatan Admin. "
     "Notifikasi pada aplikasi juga memberi tahu saat pengajuan diputuskan.")
foto("Detail pengajuan cuti beserta bagian status persetujuan")
caption("Detail Pengajuan Cuti")

# ---- 3.3 Pengajuan Sakit ----
h("3.3  Pengajuan Sakit", 2)
para(
    "Menu Pengajuan Sakit digunakan untuk melaporkan ketidakhadiran karena sakit. "
    "Berbeda dengan cuti, durasi sakit dihitung berdasarkan hari kalender "
    "(termasuk akhir pekan). Anda dapat melampirkan bukti/surat dokter sebagai "
    "pendukung. Bila disetujui, hari-hari sakit ditandai sebagai status Sakit dan "
    "sistem mencegah presensi pada hari-hari tersebut.")
step("Membuka menu",
     "Klik menu Pengajuan Sakit pada kategori Manajemen Presensi. Halaman "
     "menampilkan daftar pengajuan sakit milik Anda beserta badge jumlah yang "
     "masih menunggu.")
step("Membuat pengajuan baru",
     "Klik tombol Buat lalu lengkapi:",
)
bullet("Tanggal Mulai & Tanggal Selesai", "rentang sakit.")
bullet("Durasi Sakit (Hari)", "terisi otomatis berdasarkan hari kalender.")
bullet("Keterangan Sakit", "tulis keluhan atau keterangan sakit Anda.")
bullet("Bukti/Surat Dokter", "upload (opsional), format JPEG/PNG/WEBP maksimal 2 MB.")
step("Simpan dan pantau",
     "Klik tombol Buat. Selama berstatus Menunggu, pengajuan dapat diubah atau "
     "dibatalkan. Status berupa badge: Menunggu, Disetujui, Ditolak, Dibatalkan, "
     "atau Kadaluwarsa. Gunakan tombol Lihat untuk melihat detail beserta bukti "
     "dan catatan persetujuan admin.")
foto("Formulir Buat Pengajuan Sakit beserta bukti surat dokter")
caption("Formulir Pengajuan Sakit")

# ---- 3.4 Riwayat Presensi ----
h("3.4  Riwayat Presensi", 2)
para(
    "Menu Riwayat Presensi menampilkan seluruh catatan kehadiran Anda dan berguna "
    "untuk memeriksa jam masuk, jam pulang, status, lembur, dan lokasi pada setiap "
    "hari. Karyawan hanya dapat melihat riwayatnya sendiri.")
step("Membuka menu",
     "Pada kategori Manajemen Presensi, klik menu Riwayat Presensi. Tabel "
     "menampilkan Nama Karyawan, Status, Tanggal, Jam Masuk, Jam Keluar, dan "
     "Lembur.")
foto("Halaman Riwayat Presensi")
caption("Riwayat Presensi")
step("Menyaring data",
     "Gunakan filter Status Presensi untuk menampilkan hanya status tertentu "
     "(Hadir, Terlambat, Absen, Cuti, Izin, Sakit, Libur, Tidak Presensi Keluar) "
     "dan filter rentang tanggal untuk membatasi periode. Kolom dapat dicari dan "
     "diurutkan.")
step("Melihat detail harian",
     "Klik tombol Lihat pada baris. Detail menampilkan Informasi Presensi (nama, "
     "tanggal, jam masuk-keluar, durasi lembur, status, catatan) dan Lokasi "
     "Presensi berupa peta dengan titik masuk dan keluar. Simpan bukti ini "
     "apabila terjadi perbedaan data kehadiran dengan HR.")
foto("Detail riwayat presensi beserta peta lokasi")
caption("Detail Riwayat Presensi")

# ---- 3.5 Lembur ----
h("3.5  Informasi Lembur (Overtime)", 2)
para(
    "Lembur dihitung otomatis dari data presensi tanpa perlu pengajuan terpisah. "
    "Kriterianya: Anda pulang melewati jam selesai shift lebih dari 30 menit. "
    "Apabila selisihnya melebihi 12 jam (720 menit), sistem menganggapnya data "
    "tidak wajar dan mengabaikannya. Durasi lembur tampil pada kolom Lembur di "
    "Riwayat Presensi dan pada detail presensi dalam bentuk seperti \"1 jam 30 "
    "menit\". Data ini menjadi dasar perhitungan kompensasi oleh HR.")
foto("Kolom Lembur pada riwayat presensi beserta badge durasi")
caption("Indikator Lembur")

# ===================================================================
# BAB IV INVENTORY
# ===================================================================
doc.add_heading("BAB IV  MODUL INVENTORY (MANAJEMEN STOK)", level=1)

para(
    "Modul Inventory adalah modul utama pekerjaan petugas gudang: memantau stok "
    "(live), mengelola gudang, memeriksa product, membaca laporan, hingga memproses "
    "klaim pasca jual. Seluruh menu berada di sidebar modul Inventory."
)

h("4.1  Dashboard Inventory", 2)
para(
    "Halaman pertama saat membuka modul Inventory adalah Dashboard Inventory, "
    "ringkasan kondisi persediaan secara real-time.",
)
bullet("Capital Investment", "total nilai aset inventaris yang tersimpan.")
bullet("Stok Tersedia", "unit yang siap didistribusikan/dijual.")
bullet("Inventory Health", "persentase kondisi kesehatan stok.")
bullet("Mutasi Bulan Ini", "jumlah seluruh transaksi stok pada bulan berjalan.")
para(
    "Di bawah kartu statistik terdapat grafik tren mutasi stok dan grafik "
    "distribusi stok per gudang, yang membantu mengetahui gudang dengan stok "
    "terbanyak maupun yang menipis.")
foto("Dashboard Inventory dengan kartu statistik dan grafik")
caption("Dashboard Inventory")

h("4.2  Monitoring Inventory (Live)", 2)
para(
    "Menu Monitoring Inventory menyajikan pemantauan stok secara langsung untuk "
    "seluruh product di semua gudang; data memperbarui otomatis setiap 30 detik "
    "(indikator poll aktif). Sangat berguna saat stock opname atau pemantauan "
    "harian.")
step("Membuka menu",
     "Pada kategori Manajemen Inventory, klik menu Monitoring Inventory. Tabel "
     "menampilkan: Kode Product, Nama Product, Gudang (badge seluruh gudang "
     "penyimpan), Stok Siap Jual, Dipesan (Reserved), Dikirim (Delivery), Terjual, "
     "dan Total Fisik.")
step("Mengenali indikator warna",
     "Stok Siap Jual berbadge: merah bila habis (0), kuning bila menipis (1-5), "
     "hijau bila aman. Total Fisik = Siap Jual + Dipesan + Dikirim.")
step("Menyaring stok rendah",
     "Gunakan filter \"Stok Siap Jual ≤ 10\" untuk melihat product yang perlu "
     "diisi lebih dulu. Klik tombol View pada baris untuk membuka halaman detail "
     "product di tab baru.")
foto("Halaman Monitoring Inventory dengan filter stok rendah aktif")
caption("Monitoring Inventory")

h("4.3  Mengelola Data Gudang", 2)
step("Membuka menu Gudang",
     "Klik menu Gudang pada kategori Manajemen Inventory. Daftar menampilkan Nama "
     "Gudang, Lokasi, Penanggung Jawab, No. Telepon, Jumlah Product, Total Stock "
     "(badge warna: hijau aman, oranye menipis, merah habis), dan status Aktif.")
step("Menyaring daftar",
     "Gunakan filter Status Gudang (Aktif/Nonaktif) dan rentang tanggal dibuat.")
foto("Daftar Gudang beserta badge total stock dan filter status")
caption("Daftar Gudang")
step("Melihat detail gudang",
     "Klik nama gudang. Halaman detail menampilkan informasi lengkap, tautan "
     "Google Maps, jumlah product, total stock, dan tabel \"Daftar Product di "
     "Gudang\" berisi Kode, Nama, Kategori, Stock (badge), dan Satuan. Gunakan "
     "filter Stock Menipis untuk menampilkan produk pada atau di bawah batas "
     "minimum stok.")
foto("Detail gudang beserta daftar product dan filter Stock Menipis")
caption("Detail Gudang")

h("4.4  Daftar Product", 2)
step("Membuka menu Product",
     "Klik menu Product pada kategori Manajemen Inventory. Tabel menampilkan Foto, "
     "Kode Product, Nama Product, Kategori, Gudang, Stock Tersedia, Reserved "
     "Stock, Total Stock, dan Harga Jual.")
step("Mengenali product berseri",
     "Sebagian product (barang IT seperti laptop, router) diwajibkan memakai "
     "Serial Number. Keberadaan kewajiban ini ditandai pada pengaturan data "
     "product sehingga tiap unit dicatat satu per satu.")
step("Menyaring product",
     "Gunakan filter Stock untuk menampilkan product berstok rendah dan filter "
     "Kategori untuk mengelompokkan per jenis. Menu menampilkan badge bila jumlah "
     "product berstok rendah meningkat.")
foto("Daftar Product lengkap dengan kolom stok dan filter")
caption("Daftar Product")

h("4.5  Laporan Stock Product", 2)
step("Membuka laporan",
     "Klik menu Laporan Stock Product. Laporan merangkum persediaan: Kode dan Nama "
     "Product, Kategori, Lokasi Gudang, Tersedia (Siap Jual), Dipesan (Reserved), "
     "Pengiriman (Delivery), Total Fisik, dan Harga Jual.")
step("Menggunakan laporan",
     "Gunakan filter stock dan lokasi gudang untuk mempersempit data. Laporan ini "
     "dicetak atau diekspor untuk audit stok dan stock opname.")
foto("Laporan Stock Product")
caption("Laporan Stock Product")

h("4.6  Laporan Transaksi", 2)
step("Membuka laporan",
     "Klik menu Laporan Transaksi. Laporan menampilkan riwayat seluruh mutasi stok: "
     "No. Transaksi, Referensi (no. GR/DO/PO), Tanggal, Kode & Nama Product, "
     "Gudang, Jenis Mutasi (masuk/keluar), Qty, Harga Satuan, Total, Input Oleh, "
     "dan Catatan.")
step("Memfilter transaksi",
     "Gunakan filter Tanggal Dari/Hingga, product, gudang, dan jenis mutasi. "
     "Laporan menjadi bukti audit setiap pergerakan barang; periksa saat terjadi "
     "ketidaksesuaian stok.")
foto("Laporan Transaksi dengan filter dan kolom mutasi")
caption("Laporan Transaksi")

# ===================================================================
# BAB V SERIAL NUMBER
# ===================================================================
doc.add_heading("BAB V  PENGELOLAAN SERIAL NUMBER", level=1)

para(
    "Serial Number (SN) adalah nomor identitas unik setiap unit barang tertentu, "
    "umumnya barang elektronik. Pengelolaan SN memastikan tiap unit terlacak dari "
    "saat masuk, terjual, rusak, hingga hilang. Role Warehouse bertanggung jawab "
    "memastikan status setiap unit selalu tepat."
)

h("5.1  Daftar Serial Number", 2)
step("Membuka menu",
     "Pada kategori Manajemen Inventory, klik menu Daftar Serial Number. Daftar "
     "menampilkan Serial Number, Nama Product, Lokasi Gudang, Supplier Asal, "
     "Klien/Pembeli, Garansi Habis, Tanggal Masuk, Tanggal Keluar, dan Status Unit.")
step("Mengenali status unit",
     "Status berupa badge berwarna: Available (siap jual, hijau), Reserved "
     "(dipesan, kuning), On Delivery (dalam pengiriman, biru), Sold (terjual, "
     "abu-abu), Defective (rusak, merah), Lost (hilang, merah).")
step("Menyaring daftar",
     "Gunakan filter Filter Produk, Filter Gudang, dan Status SN, atau cari "
     "langsung melalui kolom pencarian.")
foto("Daftar Serial Number dengan badge status dan filter")
caption("Daftar Serial Number")

h("5.2  Melapor Barang Rusak", 2)
step("Temukan unit rusak",
     "Cari unit yang rusak pada daftar (baris berstatus Available), misalnya "
     "terjatuh saat diangkat dari rak.")
step("Lapor kerusakan",
     "Klik tombol Lapor Rusak, isi Alasan Kerusakan, lalu konfirmasi. Status "
     "berubah menjadi Defective, unit tidak lagi dihitung sebagai stok siap jual, "
     "dan sistem mencatat transaksi stok keluar sebagai bukti audit.")
foto("Tombol Lapor Rusak beserta form alasan kerusakan")
caption("Melapor Barang Rusak")

h("5.3  Menandai Barang Hilang", 2)
step("Temukan unit hilang",
     "Untuk unit tidak ditemukan saat stock opname, cari pada daftar Serial Number.")
step("Tandai hilang",
     "Klik tombol Tandai Hilang (tersedia untuk status Available dan Reserved), "
     "isi Alasan/Keterangan, lalu konfirmasi. Status berubah menjadi Lost dan "
     "tercatat sebagai transaksi stok keluar.")
foto("Tombol Tandai Hilang beserta form keterangan")
caption("Menandai Barang Hilang")

h("5.4  Pelacakan Serial Number", 2)
step("Membuka menu",
     "Klik menu Pelacakan Serial Number pada kategori Manajemen Inventory.")
step("Mencari unit",
     "Ketik nomor serial pada kolom lalu Enter, atau gunakan tombol Scan Kamera "
     "Barcode/QR untuk memindai label unit. Setelah scan berhasil, pencarian "
     "otomatis dijalankan tanpa perlu menekan tombol Cari.")
step("Membaca hasil",
     "Hasil menampilkan SN, nama & kode product, posisi gudang, status terkini, "
     "supplier asal, no. PO, klien/pembeli, tanggal masuk/keluar, dan status "
     "garansi (Aktif/Expired). Di bawahnya terdapat riwayat transaksi unit: No. "
     "Transaksi, Tanggal, Jenis Mutasi, Referensi, Gudang, Input Oleh, dan Catatan.")
foto("Hasil pelacakan serial number beserta riwayat transaksi unit")
caption("Pelacakan Serial Number")

# ===================================================================
# BAB VI PROCUREMENT
# ===================================================================
doc.add_heading("BAB VI  MODUL PROCUREMENT (PENERIMAAN BARANG)", level=1)

para(
    "Modul Procurement mengelola pengadaan mulai dari permintaan pembelian, "
    "purchase order (PO), hingga penerimaan barang. Tugas utama petugas gudang di "
    "modul ini adalah memproses Penerimaan Barang (Goods Receipt / GR) berdasarkan "
    "PO yang diterbitkan tim procurement."
)

h("6.1  Penerimaan Barang dari Purchase Order (Goods Receipt)", 2)
para(
    "Goods Receipt mencatat penerimaan fisik barang dari supplier: menambah stok ke "
    "gudang tujuan, mencatat transaksi stok masuk, dan memperbarui status PO "
    "otomatis. Hanya PO berstatus Sent atau Partial yang dapat dipilih; PO draft "
    "tidak bisa diproses.")
step("Membuka menu",
     "Pada halaman Pilih Modul Kerja, buka modul Procurement, lalu klik menu Goods "
     "Receipts pada kategori Manajemen Procurement. Klik tombol Buat di pojok "
     "kanan atas untuk membuat penerimaan baru.")
foto("Daftar Goods Receipts beserta tombol Buat")
caption("Menu Goods Receipts")
step("Mengisi informasi penerimaan",
     "Lengkapi formulir utama:",
)
bullet("No. Penerimaan (GR)", "terisi otomatis oleh sistem.")
bullet("Nama/Judul", "misalnya \"Penerimaan Laptop Batch 1\".")
bullet("Dari Purchase Order (PO)", "pilih PO; hanya status Sent/Partial yang muncul. Label menampilkan nomor PO dan judulnya.")
bullet("Supplier", "terisi otomatis mengikuti PO yang dipilih.")
bullet("Masuk ke Gudang", "pilih gudang tujuan (gudang aktif).")
bullet("Tanggal Diterima", "tanggal barang diterima fisik.")
bullet("No. Surat Jalan Supplier", "catat nomor surat jalan dari supplier bila ada.")
foto("Formulir Goods Receipt bagian informasi penerimaan dan pemilihan PO")
caption("Formulir Penerimaan Barang")
step("Memeriksa barang fisik",
     "Sistem memuat daftar barang dari PO. Tiap baris menampilkan Nama Barang, "
     "Qty PO, Sudah Diterima, Sisa Saat Ini, dan Sisa Setelah Input. Sesuaikan "
     "kolom Diterima Sekarang dengan jumlah barang yang benar-benar diterima; "
     "jumlah tidak boleh melebihi sisa yang tersedia.")
foto("Ceklis barang fisik berupa daftar item dari PO dengan kolom Diterima Sekarang")
caption("Ceklis Barang Fisik")
step("Scan serial number",
     "Untuk barang berseri, muncul tombol Scanner Barcode/QR pada baris item. "
     "Scan setiap SN unit yang diterima; SN bertambah ke daftar. Sistem menolak "
     "SN duplikat, SN yang sudah terdaftar di gudang lain, atau jumlah SN melebihi "
     "qty yang diterima. Jumlah SN harus persis sama dengan jumlah barang, jika "
     "tidak sistem menolak penyimpanan.")
foto("Scanner barcode/QR beserta daftar serial number hasil scan")
caption("Scan Serial Number pada Penerimaan")
step("Menyimpan penerimaan",
     "Setelah semua item terverifikasi, simpan. Sistem otomatis menambah stok ke "
     "gudang tujuan, mencatat transaksi stok masuk, memperbarui PO menjadi Partial "
     "(bila belum lengkap) atau Completed, serta membentuk catatan hutang dagang "
     "di modul finance.")
step("Memeriksa status GR",
     "Pada daftar Goods Receipts, tiap dokumen menampilkan No. GR, Nama Dokumen, "
     "Supplier, Tanggal Diterima, Penerima (Gudang), dan Status: Draft (belum "
     "dikonfirmasi), Completed (selesai), atau Cancelled. Klik tombol Lihat untuk "
     "melihat detail lengkap beserta rincian barang dan serial number yang "
     "diterima.")
foto("Detail Goods Receipt beserta daftar barang yang diterima dan SN")
caption("Detail Goods Receipt")

# ===================================================================
# BAB VII AFTER-SALES
# ===================================================================
doc.add_heading("BAB VII  MODUL AFTER-SALES (PASCA JUAL)", level=1)

para(
    "Role Warehouse berperan besar pada modul After-Sales: tim gudang memproses "
    "seluruh keluhan unit pasca jual. Alurnya: klien mengajukan klaim melalui "
    "portal pelanggan (RMA dibuat otomatis), tim gudang menerima unit, menentukan "
    "rute garansi (supplier/vendor atau servis internal), lalu unit dipantau "
    "hingga siap dikembalikan ke klien. Seluruh menu berada pada kategori "
    "Manajemen After-Sales di sidebar modul Inventory."
)

h("7.1  Penerimaan Return (RMA)", 2)
para(
    "Menu Penerimaan Return menampilkan seluruh pengajuan pengembalian unit "
    "(Return Merchandise Authorization / RMA) dari klien. RMA dibuat oleh klien "
    "di portal pelanggan; tim gudang memproses alurnya.")
step("Membuka menu",
     "Pada modul Inventory, buka kategori Manajemen After-Sales lalu klik menu "
     "Penerimaan Return. Tabel menampilkan No. RMA, Klien, Jenis Kendala, Rute "
     "Garansi (Supplier/Toko), dan Status.")
foto("Daftar Penerimaan Return beserta kolom status RMA")
caption("Penerimaan Return")
step("Mengenali status RMA",
     "Status mengikuti alur: Diterima, Sedang Ditinjau, Disetujui, Menunggu "
     "Pengembalian Unit, Unit Diterima, Dikirim ke Vendor, Servis Internal, "
     "Menunggu Refund, Siap Dikembalikan, Dikembalikan ke Klien, Ditolak, atau "
     "Garansi Ditolak. Pantau status untuk menentukan tindakan berikutnya.")

h("7.2  Servis Internal", 2)
para(
    "Apabila unit yang dikembalikan diperbaiki di dalam perusahaan, tim gudang "
    "memprosesnya melalui menu Servis Internal. Tiap perbaikan dicatat lengkap "
    "dengan keluhan, teknisi, dan hasil pengerjaan.")
step("Membuka menu",
     "Klik menu Servis Internal untuk membuka Daftar Servis Internal.")
step("Memulai servis",
     "Cari baris RMA berstatus Menunggu, klik tombol Mulai Servis. Isi Catatan "
     "Kerja (diagnosa awal/rencana perbaikan) lalu konfirmasi. Status menjadi "
     "Sedang Dikerjakan dan Anda tercatat sebagai teknisi yang menangani unit.")
foto("Tombol Mulai Servis beserta form catatan kerja")
caption("Mulai Servis Internal")
step("Menyelesaikan servis",
     "Setelah perbaikan selesai, klik tombol Selesaikan Servis. Pilih Hasil "
     "Pengerjaan: Repair (unit diperbaiki, dikembalikan ke klien) atau Replace "
     "(unit diganti; pilih SN Unit Pengganti dari daftar unit Available). Isi "
     "Catatan Teknisi lalu konfirmasi. Sistem memperbarui SN unit, stok, dan "
     "status RMA menjadi Siap Dikembalikan.")
foto("Selesaikan Servis beserta pilihan hasil pengerjaan dan SN pengganti")
caption("Selesaikan Servis Internal")

h("7.3  Klaim Vendor", 2)
para(
    "Apabila kerusakan ditanggung supplier (vendor), unit dikirim ke vendor dan "
    "dipantau melalui menu Klaim Vendor. Petugas gudang mencatat hasil klaim dari "
    "vendor.")
step("Membuka menu",
     "Klik menu Klaim Vendor untuk membuka Daftar Klaim Vendor.")
step("Menerima dari vendor",
     "Cari baris berstatus Dikirim, klik tombol Terima dari Vendor. Pilih Hasil "
     "Klaim Supplier: Repair (unit yang sama diperbaiki) atau Replace (vendor "
     "mengirim unit baru; isi SN Baru dari Vendor, SN harus unik). Lengkapi "
     "catatan lalu konfirmasi.")
foto("Formulir Terima dari Vendor beserta pilihan hasil klaim")
caption("Terima Unit dari Vendor")
step("Mencatat klaim ditolak",
     "Jika vendor menolak garansi, klik tombol Klaim Ditolak Vendor dan isi Alasan "
     "Penolakan.")
foto("Konfirmasi Klaim Ditolak Vendor")
caption("Klaim Ditolak Vendor")
step("Menerima kembali unit",
     "Bila unit yang klaimnya ditolak vendor diterima kembali di gudang, klik "
     "tombol Unit Ditangguhkan Diterima, isi Catatan Penerimaan Unit, lalu "
     "konfirmasi. RMA siap dikembalikan ke klien.")
foto("Form penerimaan kembali unit dari vendor")
caption("Unit Ditangguhkan Diterima")

# ===================================================================
# BAB VIII PROJECT MANAGEMENT
# ===================================================================
doc.add_heading("BAB VIII  MODUL PROJECT MANAGEMENT", level=1)

para(
    "Role Warehouse juga mengakses modul Project Management untuk mengikuti "
    "pekerjaan berkaitan gudang (perbaikan rak, pengadaan fasilitas, dsb.). "
    "Petugas gudang dapat melihat project, memantau progres melalui board, "
    "memperbarui ticket, dan mencatat pekerjaan harian."
)

h("8.1  Dashboard Project", 2)
step("Membuka modul",
     "Pada halaman Pilih Modul Kerja, klik Buka modul pada kartu Project "
     "Management. Dashboard menampilkan ringkasan statistik project, progress "
     "tiap project, tabel ticket terbaru, dan ticket overdue.")
foto("Dashboard Project Management")
caption("Dashboard Project")

h("8.2  Daftar Project", 2)
step("Membuka menu",
     "Klik menu Project pada kategori Manajemen Project. Daftar menampilkan Nama "
     "Project, Project Manager, tanggal mulai/selesai, dan badge jumlah ticket.")
step("Melihat detail dan timeline",
     "Klik nama project untuk melihat detail: daftar ticket, anggota, progress. "
     "Gunakan menu Project Timeline / Ticket Timeline untuk melihat urutan "
     "kegiatan dalam garis waktu.")
foto("Daftar Project")
caption("Daftar Project")

h("8.3  Project Board", 2)
step("Membuka board",
     "Klik menu Project Board. Ticket ditampilkan per project dalam kolom status: "
     "Backlog, To Do, In Progress, Review, Done.")
step("Memperbarui status",
     "Seret (drag) kartu ticket antar kolom sesuai progres nyata di lapangan agar "
     "semua pihak mengetahui kondisi terkini pekerjaan.")
foto("Project Board dengan kolom Backlog, To Do, In Progress, Review, Done")
caption("Project Board")

h("8.4  Bekerja dengan Ticket", 2)
step("Membuat ticket",
     "Klik menu Ticket lalu tombol Buat Ticket. Pilih Project (bila berlaku), "
     "prioritas, judul, dan deskripsi, lalu simpan.")
step("Memperbarui status ticket",
     "Ticket yang dibuat muncul di board dan dapat diubah statusnya oleh petugas "
     "gudang maupun project manager. Pastikan status selalu mencerminkan progres "
     "pekerjaan.")
foto("Form Buat Ticket beserta kolom project, prioritas, judul, dan deskripsi")
caption("Membuat Ticket")

# ===================================================================
# BAB IX PENUTUP
# ===================================================================
doc.add_heading("BAB IX  CATATAN HAK AKSES & PENUTUP", level=1)

h("Hak Akses dan Permission", 2)
para(
    "Seluruh menu pada manual ini hanya tampil sesuai hak akses role yang "
    "diberikan Super Admin melalui konfigurasi role dan permission di modul "
    "System. Bila suatu menu tidak muncul di sidebar, berarti role warehouse yang "
    "dipakai belum memiliki izin terhadap menu tersebut. Hubungi Super Admin "
    "untuk menyesuaikan permission, atau memastikan akun login sudah terhubung "
    "dengan data karyawan (relasi employee) bila fitur presensi tidak dapat "
    "diakses. Konfigurasi ini mutlak agar seluruh fitur bekerja sesuai tanggung "
    "jawab petugas gudang."
)

h("Penutup", 2)
para(
    "Demikian panduan pengguna role Warehouse pada ElviaERP (Nexicon ERP). "
    "Pantau terus pembaruan sistem karena menu atau alur dapat berubah mengikuti "
    "perkembangan aplikasi. Untuk pertanyaan lebih lanjut, hubungi tim IT atau "
    "Super Admin. Selamat bekerja."
)

doc.save(OUT)
print("OK ->", OUT)