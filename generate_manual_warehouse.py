# -*- coding: utf-8 -*-
"""
Generator Manual Book Role Warehouse (Nexicon ERP).
Meniru gaya penulisan Manual_Book_Nexicons.docx:
- Font Aptos, paragraf normal 11pt, spasi 1.15
- Heading 1/2/3
- Caption "Gambar N. <judul>" utk placeholder foto
- Marker [FOTO] sebagai penanda screenshot yg wajib dilampirkan user

Output: Manual_Book_Role_Warehouse.docx di root repo.
Jalankan: python generate_manual_warehouse.py
"""

import docx
from docx import Document
from docx.shared import Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.style import WD_STYLE_TYPE

OUT = "Manual_Book_Role_Warehouse.docx"

doc = Document()

# ---------- Default styles (menyesuaikan dok referensi) ----------
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

# Style khusus marker foto
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
fig_counter = {"n": 0}


def h(text, level):
    doc.add_heading(text, level=level)


def para(text, style="Normal", bold_lead=None):
    """Prosa. bold_lead = teks awal yang ditebalkan, mis. '(Gudang)'."""
    p = doc.add_paragraph(style=style)
    if bold_lead:
        r = p.add_run(bold_lead)
        r.bold = True
        p.add_run(" " + text)
    else:
        p.add_run(text)
    return p


def foto(desc):
    """Marker: tempat user menyisipkan screenshot."""
    p = doc.add_paragraph(style="FotoMarker")
    p.add_run(">> [FOTO DI SINI] %s" % desc)
    return p


def caption(judul):
    """Caption gambar di bawah marker. Judul = nama tampilan yg difoto."""
    fig_counter["n"] += 1
    p = doc.add_paragraph(style="Caption")
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.add_run("Gambar %d. %s" % (fig_counter["n"], judul))
    return p


def step(judul, isi):
    """Langkah: lead-in bold + isi (gaya List Paragraph spt referensi)."""
    p = doc.add_paragraph(style="List Paragraph")
    r = p.add_run(judul)
    r.bold = True
    p.add_run(" " + isi)
    return p


def bullet(judul, isi=None):
    """Sub-item bernomor gaya List Bullet."""
    p = doc.add_paragraph(style="List Bullet")
    r = p.add_run(judul)
    r.bold = True
    if isi:
        p.add_run(" " + isi)
    return p


# ---------- Konten ----------

# Catatan kecil utk penggabungan
p = doc.add_paragraph(style="FotoMarker")
p.add_run(
    "CATATAN PENGGABUNGAN: semua penanda [FOTO DI SINI] wajib diganti screenshot "
    "dari aplikasi. Nomor 'Gambar N.' bersifat sementara; sesuaikan kembali setelah "
    "dilebur ke manual utama."
)

doc.add_heading("Panduan Penggunaan Aplikasi untuk Role Warehouse", level=1)

para(
    "Bagian ini menjelaskan secara lengkap panduan penggunaan website ElviaERP "
    "(Nexicon ERP) untuk pengguna dengan role Warehouse. Role ini memiliki hak akses "
    "terhadap modul Attendance, Inventory, Procurement, Project Management, serta "
    "After-Sales. Dengan hak akses tersebut, petugas gudang dapat melakukan presensi "
    "harian beserta seluruh manajemen kehadiran (cuti, sakit, riwayat, dan lembur), "
    "memantau dan mengelola stok product, menerima barang dari supplier berdasarkan "
    "Purchase Order, memproses keluhan barang pasca jual (after-sales), serta turut "
    "serta dalam pengelolaan project terkait pekerjaan pergudangan. Uraian berikut "
    "disusun langkah demi langkah agar mudah diikuti oleh pengguna baru."
)

# ============================================================
h("Persiapan Awal", 2)

h("Login ke Aplikasi", 3)
para(
    "Sebelum dapat menggunakan seluruh fitur, Anda harus masuk ke dalam aplikasi "
    "dengan akun yang diberikan oleh Super Admin. Pastikan jaringan internet Anda "
    "stabil karena sistem berjalan secara daring (online).",
)
step(
    "Buka halaman login",
    "Buka ElviaERP melalui web browser (Google Chrome, Microsoft Edge, atau Mozilla "
    "Firefox) lalu masukkan URL aplikasi pada kolom alamat. Halaman login akan "
    "tampil dan meminta Anda untuk memasukkan kredensial.",
)
step(
    "Isi kredensial",
    "Masukkan Alamat email dan Kata sandi yang diberikan oleh Super Admin pada "
    "kolom yang tersedia. Pastikan penulisan email dan kata sandi benar, termasuk "
    "huruf besar dan kecil, lalu klik tombol Masuk untuk mengakses sistem.",
)
foto("Halaman Login aplikasi/website dengan kolom email dan kata sandi")
caption("Halaman Login")
step(
    "Logout",
    "Untuk keluar dari aplikasi, klik menu akun Anda pada pojok kanan atas, lalu "
    "pilih Keluar. Selalu logout setelah selesai bekerja terutama jika memakai "
    "perangkat bersama agar akun tidak disalahgunakan.",
)
foto("Menu dropdown akun pada pojok kanan atas beserta tombol Keluar")
caption("Menu Logout")

# ============================================================
h("Halaman Pilih Modul Kerja", 3)
para(
    "Setelah berhasil login, sistem menampilkan halaman Pilih Modul Kerja. Halaman "
    "ini merupakan pusat navigasi utama: dari sini Anda dapat membuka setiap modul "
    "yang diizinkan untuk role Anda. Selain daftar modul, halaman ini juga memuat "
    "berbagai informasi ringkas yang membantu Anda mengelola hari kerja.",
)
step(
    "Membaca kapsul informasi (info pills)",
    "Di bawah sapaan selamat datang, sistem menampilkan beberapa kapsul kecil "
    "(pill) berisi: Role pengguna, Departemen, status Hari Kerja Aktif atau Sedang "
    "Akhir Pekan, Jadwal Shift beserta jam kerjanya, jadwal cuti yang sedang "
    "berlangsung atau akan datang, serta libur nasional terdekat dalam 30 hari. "
    "Mengetahui shift Anda di sini membantu memastikan jam presensi yang benar.",
)
foto("Halaman Pilih Modul Kerja beserta kapsul informasi role, departemen, dan shift")
caption("Halaman Pilih Modul Kerja")
step(
    "Mendaftar modul yang tersedia",
    "Modul yang dapat dibuka oleh role Warehouse adalah Attendance, Inventory, "
    "Procurement, dan Project Management. Setiap modul ditampilkan dalam bentuk "
    "kartu dengan ikon, nama, dan deskripsi singkat. Untuk membuka sebuah modul, "
    "arahkan kursor ke kotak modul terkait lalu klik tombol Buka modul.",
)
foto("Grid kartu modul dengan kursor di kotak modul Inventory")
caption("Kartu Modul yang Dapat Diakses")
step(
    "Memahami banner status presensi",
    "Di bagian atas halaman terdapat banner berwarna yang memberi tahu status "
    "presensi Anda hari ini. Warna merah berarti Anda belum presensi masuk, biru "
    "berarti sudah masuk tetapi belum presensi keluar, dan hijau berarti presensi "
    "hari ini telah selesai. Banner juga muncul apabila saat ini akhir pekan atau "
    "hari libur nasional, dan memberi tahu informasi tersebut secara otomatis.",
)
foto("Banner status presensi warna merah berisi pengingat presensi masuk")
caption("Banner Status Presensi")

# ============================================================
h("Panduan Penggunaan Menu", 2)

# ============================================================
# ATTENDANCE
doc.add_heading("Modul Attendance (Manajemen Presensi)", level=2)
para(
    "Modul Attendance adalah modul pengelolaan seluruh aktivitas kehadiran "
    "karyawan. Berbeda dengan sekadar presensi masuk-pulang, modul ini memuat "
    "beberapa menu yang saling terhubung: Presensi Harian, Pengajuan Cuti, "
    "Pengajuan Sakit, dan Riwayat Presensi. Seluruh menu tersebut berada pada "
    "kategori Manajemen Presensi di sidebar. Modul ini hanya dapat diakses oleh "
    "karyawan (bukan Super Admin), dan setiap karyawan hanya dapat melihat data "
    "presensinya sendiri.",
)

# ---------- Presensi harian ----------
h("Presensi Harian", 3)
para(
    "Setiap hari kerja, petugas gudang wajib melakukan presensi masuk dan pulang "
    "melalui menu Presensi Harian. Sistem menggunakan foto wajah (selfie) dan "
    "titik lokasi GPS untuk mencatat kehadiran secara akurat. Presensi hanya dapat "
    "dilakukan pada hari kerja; sistem menolak presensi pada hari Sabtu, Minggu, "
    "hari libur nasional, atau saat Anda sedang dalam masa cuti/sakit yang "
    "disetujui.",
)
step(
    "Membuka menu Presensi Harian",
    "Buka modul Attendance, lalu pilih menu Presensi Harian pada kategori Manajemen "
    "Presensi di sidebar. Halaman menampilkan status presensi hari ini, jam masuk, "
    "jam pulang, serta kartu informasi karyawan seperti Departemen, Kantor, "
    "Jadwal Shift, dan Tipe Karyawan.",
)
step(
    "Memahami kartu informasi karyawan",
    "Pada sisi kanan halaman terdapat kartu berisi foto dan nama Anda, Departemen, "
    "Kantor, Jadwal (nama shift beserta jam mulai dan selesai), serta Tipe "
    "Karyawan. Tipe Karyawan terbagi dua: (a) Bekerja Dimana Saja (WFA) atau "
    "Bekerja dari Kantor, dan (b) Jam Kerja Fleksibel atau Jam Kerja Tetap. "
    "Kombinasi tipe ini menentukan seberapa ketat peraturan lokasi dan jam "
    "presensi Anda.",
)
foto("Halaman Presensi Harian beserta kartu informasi karyawan di sisi kanan")
caption("Presensi Harian - Tampilan Awal")
step(
    "Melakukan presensi masuk (Check In)",
    "Pada bagian Check In, klik area bingkai bertuliskan \"Klik untuk membuka "
    "kamera\". Sistem akan mengaktifkan kamera pada perangkat Anda. Posisikan "
    "wajah agar terlihat jelas di tengah bingkai, lalu klik tombol kamera untuk "
    "mengambil foto. Setelah foto tampil, Anda dapat memilih Ambil Ulang jika "
    "ingin mengganti foto. Tambahkan Catatan pada kolom yang tersedia bila "
    "diperlukan (opsional), lalu klik tombol Tandai Lokasi.",
)
foto("Halaman Presensi Harian bagian Check In dengan jendela kamera terbuka")
caption("Presensi Harian - Check In")
step(
    "Menunggu verifikasi lokasi",
    "Saat tombol Tandai Lokasi diklik, sistem mencari titik GPS perangkat Anda "
    "dengan akurasi tinggi (hingga ±10 meter). Pada peta akan muncul lingkaran "
    "biru yang menunjukkan radius kantor. Di bawah peta, sistem menampilkan "
    "Alamat, Koordinat, dan Status; contoh status: \"Dalam radius\" atau \"Luar "
    "radius\". Sistem juga menampilkan badge akurasi seperti \"Akurasi: ±22m\". "
    "Jika sinyal GPS lemah, sistem akan meminta izin lokasi browser atau meminta "
    "Anda mencoba kembali.",
)
foto("Peta dengan lingkaran radius kantor, marker posisi, dan badge akurasi")
caption("Verifikasi Lokasi Presensi")
step(
    "Aturan lokasi dan jam kerja",
    "Sistem memblokir presensi apabila: (a) Anda berada di luar radius kantor dan "
    "tidak memiliki izin WFA; (b) Anda presensi di luar jam kerja (sekitar "
    "toleransi shift) dan tidak memiliki izin jam kerja fleksibel; (c) hari ini "
    "akhir pekan atau libur nasional; atau (d) Anda sedang dalam cuti/sakit yang "
    "disetujui. Apabila salah satu aturan dilanggar, sistem menampilkan "
    "pemberitahuan berwarna merah dan presensi tidak tersimpan.",
)
step(
    "Mengenali status kehadiran",
    "Setelah presensi masuk berhasil, sistem menetapkan status berupa badge di "
    "halaman: Hadir (presensi tepat waktu), Terlambat (melewati batas toleransi "
    "shift), dan status lainnya seperti Belum Presensi, Absen, Tidak Presensi "
    "Keluar, Cuti, atau Sakit yang dihitung sistem pada akhir hari.",
)
step(
    "Melakukan presensi pulang (Check Out)",
    "Di akhir jam kerja, lakukan hal yang sama pada bagian Check Out: buka kamera, "
    "ambil foto diri, tambahkan catatan jika ada, lalu klik tombol Presensi "
    "Keluar. Sistem hanya mengizinkan presensi keluar setelah jam selesai shift. "
    "Setelah itu, status menampilkan jam masuk dan jam pulang lengkap dengan titik "
    "lokasi, dan halaman berubah menjadi tampilan \"Presensi Selesai\".",
)
foto("Halaman Presensi Harian bagian Check Out beserta ringkasan jam masuk-pulang")
caption("Presensi Harian - Check Out")
step(
    "Melihat riwayat presensi hari ini di peta",
    "Pada sisi kanan bawah halaman terdapat peta beserta dua penanda: hijau "
    "bertuliskan IN untuk titik presensi masuk dan merah bertuliskan OUT untuk "
    "titik presensi keluar. Klik penanda tersebut untuk melihat waktu, foto, "
    "dan lokasi presensi; klik foto untuk memperbesarnya.",
)
step(
    "Mengenali proteksi data",
    "Sistem mencatat foto selfie dan koordinat GPS pada setiap presensi. Data ini "
    "dijadikan bukti digital yang dapat diperiksa oleh pihak HR dan Super Admin. "
    "Sistem juga memiliki deteksi GPS palsu: apabila kecepatan pergerakan "
    "perangkat tidak wajar saat mengambil lokasi, sistem menolak presensi dan "
    "menampilkan peringatan.",
)
foto("Tampilan peta riwayat dengan penanda IN hijau dan OUT merah")
caption("Peta Riwayat Presensi")

# ---------- Pengajuan Cuti ----------
h("Pengajuan Cuti", 3)
para(
    "Menu Pengajuan Cuti digunakan untuk mengajukan permohonan cuti dengan "
    "menentukan jenis cuti, rentang tanggal, dan alasan. Setiap karyawan hanya "
    "dapat melihat dan mengelola pengajuan cutinya sendiri. Panjang cuti dihitung "
    "otomatis berdasarkan hari kerja; akhir pekan tidak dihitung. Apabila "
    "pengajuan disetujui, sistem otomatis menandai hari-hari tersebut sebagai "
    "status Cuti pada presensi dan mencegah Anda melakukan presensi pada hari itu.",
)
step(
    "Membuka menu Pengajuan Cuti",
    "Pada modul Attendance, klik menu Pengajuan Cuti pada kategori Manajemen "
    "Presensi. Halaman menampilkan daftar pengajuan cuti milik Anda beserta "
    "Jenis Cuti, Status, Tanggal Mulai, Tanggal Selesai, dan Durasi. Pada menu "
    "ini juga tersedia badge berwarna kuning yang menunjukkan jumlah pengajuan "
    "yang masih menunggu persetujuan.",
)
foto("Halaman daftar Pengajuan Cuti milik pengguna")
caption("Menu Pengajuan Cuti")
step(
    "Membuat pengajuan cuti baru",
    "Klik tombol Buat di pojok kanan atas. Pada formulir, pilih Jenis Cuti dari "
    "daftar; setiap jenis menampilkan sisa jatah hari, misalnya \"Cuti Tahunan "
    "(Sisa 10 Hari)\". Jenis cuti yang bersifat khusus laki-laki atau khusus "
    "perempuan hanya muncul sesuai gender Anda. Kemudian tentukan Tanggal Mulai "
    "dan Tanggal Selesai, periksa Durasi Cuti (Hari Kerja) yang terhitung "
    "otomatis, isi Alasan Cuti pada kolom yang tersedia, lalu unggah Bukti "
    "Cuti/Sakit (opsional, misalnya surat dokter) dengan tombol Upload. Klik "
    "tombol Buat untuk menyimpan pengajuan.",
)
foto("Formulir Buat Pengajuan Cuti lengkap dengan dropdown jenis cuti")
caption("Formulir Pengajuan Cuti")
step(
    "Mengubah atau membatalkan pengajuan",
    "Selama pengajuan masih berstatus Menunggu, Anda dapat mengubah datanya "
    "melalui tombol Ubah pada baris pengajuan. Anda juga dapat membatalkan "
    "pengajuan yang berstatus Menunggu atau Disetujui melalui tombol Batalkan; "
    "sistem akan meminta konfirmasi sebelum membatalkan. Setelah dibatalkan, "
    "status berubah menjadi Dibatalkan dan hari-hari yang tadinya dicatat sebagai "
    "Cuti dikembalikan otomatis.",
)
step(
    "Membaca status pengajuan",
    "Status pengajuan ditampilkan dalam badge berwarna: Menunggu (kuning), "
    "Disetujui (hijau), Ditolak (merah), Dibatalkan, atau Kadaluwarsa. Untuk "
    "melacak keputusan, klik tombol Lihat pada baris pengajuan untuk melihat "
    "detail termasuk siapa yang menyetujui dan kapan disetujui.",
)
foto("Detail pengajuan cuti beserta status persetujuan")
caption("Detail Pengajuan Cuti")

# ---------- Pengajuan Sakit ----------
h("Pengajuan Sakit", 3)
para(
    "Menu Pengajuan Sakit digunakan untuk melaporkan ketidakhadiran karena sakit. "
    "Berbeda dengan cuti yang memakai jatah hari libur tahunan, pengajuan sakit "
    "dihitung berdasarkan hari kalender (termasuk akhir pekan) dan dapat "
    "disertai bukti surat dokter. Apabila disetujui, masa sakit ditandai sebagai "
    "status Sakit dan Anda tidak dapat melakukan presensi pada hari-hari tersebut.",
)
step(
    "Membuka menu Pengajuan Sakit",
    "Pada modul Attendance, klik menu Pengajuan Sakit pada kategori Manajemen "
    "Presensi. Halaman menampilkan daftar pengajuan sakit milik Anda. Sama seperti "
    "menu cuti, tersedia badge kuning untuk jumlah pengajuan yang belum direview.",
)
step(
    "Membuat pengajuan sakit baru",
    "Klik tombol Buat, lalu isi Tanggal Mulai dan Tanggal Selesai. Durasi Sakit "
    "(Hari) dihitung otomatis. Tuliskan Keterangan Sakit pada kolom yang tersedia, "
    "misalnya keluhan yang dialami. Unggah Bukti/Surat Dokter (opsional, format "
    "JPEG/PNG/WEBP maksimal 2 MB) dengan mengklik tombol Upload, lalu klik "
    "tombol Buat.",
)
foto("Formulir Buat Pengajuan Sakit beserta kolom bukti surat dokter")
caption("Formulir Pengajuan Sakit")
step(
    "Mengubah, membatalkan, dan memantau status",
    "Pengajuan yang masih berstatus Menunggu dapat diubah atau dibatalkan. Status "
    "pengajuan ditampilkan sebagai badge: Menunggu, Disetujui, Ditolak, "
    "Dibatalkan, atau Kadaluwarsa. Gunakan tombol Lihat untuk melihat detail "
    "termasuk keterangan sakit, bukti, dan catatan persetujuan dari admin.",
)
foto("Detail pengajuan sakit dengan gambar bukti dan status persetujuan")
caption("Detail Pengajuan Sakit")

# ---------- Riwayat Presensi ----------
h("Riwayat Presensi", 3)
para(
    "Menu Riwayat Presensi menampilkan seluruh catatan kehadiran Anda. Menu ini "
    "berguna untuk memeriksa jam masuk, jam pulang, status, lembur, dan lokasi "
    "setiap hari secara lengkap. Karyawan hanya dapat melihat riwayatnya sendiri.",
)
step(
    "Membuka menu Riwayat Presensi",
    "Pada modul Attendance, klik menu Riwayat Presensi pada kategori Manajemen "
    "Presensi. Halaman menampilkan tabel berisi Nama Karyawan, Status, Tanggal, "
    "Jam Masuk, Jam Keluar, dan kolom Lembur.",
)
step(
    "Menyaring riwayat",
    "Gunakan filter Status Presensi untuk menampilkan hanya status tertentu "
    "(Hadir, Terlambat, Absen, Cuti, Izin, Sakit, Libur, Tidak Presensi Keluar) "
    "dan filter rentang tanggal Dibuat Dari sampai Dibuat Hingga untuk membatasi "
    "periode. Kolom juga dapat diurutkan dan dicari berdasarkan tanggal.",
)
foto("Halaman Riwayat Presensi dengan filter status dan rentang tanggal")
caption("Riwayat Presensi")
step(
    "Melihat detail harian",
    "Klik tombol Lihat pada baris riwayat untuk membuka detail berisi Informasi "
    "Presensi (nama, tanggal, jam masuk-keluar, durasi lembur, status, catatan) "
    "dan Lokasi Presensi berupa peta dengan titik masuk dan keluar. Ini menjadi "
    "bukti yang dapat Anda gunakan apabila terjadi perbedaan data.",
)
foto("Detail riwayat presensi beserta peta lokasi masuk dan keluar")
caption("Detail Riwayat Presensi")

# ---------- Catatan lembur ----------
h("Informasi Lembur (Overtime)", 3)
para(
    "Sistem menghitung lembur secara otomatis dari data presensi. Lembur dihitung "
    "apabila Anda pulang melewati jam selesai shift lebih dari 30 menit, dan "
    "diabaikan apabila bertambah lebih dari 12 jam (dianggap data tidak wajar). "
    "Durasi lembur ditampilkan pada kolom Lembur di menu Riwayat Presensi dan "
    "Detail Presensi dalam bentuk seperti \"1 jam 30 menit\". Nilai lembur ini "
    "menjadi dasar perhitungan bagi pihak HR. Tidak ada pengajuan terpisah yang "
    "perlu dibuat; cukup pastikan data presensi masuk dan keluar Anda lengkap "
    "setiap hari.",
)
foto("Kolom Lembur pada riwayat presensi beserta badge durasi")
caption("Indikator Lembur pada Riwayat Presensi")

# ============================================================
doc.add_heading("Modul Inventory (Manajemen Stok)", level=2)
para(
    "Modul Inventory adalah modul utama pekerjaan petugas gudang. Modul ini "
    "digunakan untuk memantau stok, mengelola gudang, mengamati rincian product, "
    "memeriksa laporan, serta mengelola unit berseri (serial number). Seluruh "
    "menu berada pada kategori Manajemen Inventory di sidebar.",
)

h("Dashboard Inventory", 3)
para(
    "Setelah masuk ke modul Inventory, halaman pertama yang muncul adalah "
    "Dashboard Inventory. Halaman ini menampilkan ringkasan kondisi persediaan "
    "secara real-time untuk memudahkan pemantauan kesehatan stok tanpa membuka "
    "setiap menu.",
)
step(
    "Memahami kartu statistik",
    "Pada bagian atas dashboard terdapat kartu-kartu KPI: Capital Investment "
    "(total nilai aset inventaris), Stok Tersedia (unit yang siap "
    "didistribusikan), Inventory Health (persentase kondisi stok sehat), dan "
    "Mutasi Bulan Ini (jumlah seluruh transaksi stok pada periode berjalan).",
)
step(
    "Membaca grafik",
    "Di bawah kartu statistik tersedia grafik tren pergerakan mutasi stok serta "
    "grafik distribusi stok berdasarkan gudang. Grafik membantu mengetahui gudang "
    "mana yang persediaannya paling banyak atau sedang menipis.",
)
foto("Tampilan Dashboard Inventory beserta kartu statistik dan grafiknya")
caption("Dashboard Inventory")

h("Monitoring Inventory (Live)", 3)
para(
    "Menu Monitoring Inventory menyajikan pemantauan stok secara langsung (live) "
    "untuk seluruh product di semua gudang. Halaman ini sangat berguna saat stock "
    "opname atau pemantauan harian karena secara otomatis memperbarui data setiap "
    "30 detik.",
)
step(
    "Membuka menu Monitoring Inventory",
    "Pada sidebar kategori Manajemen Inventory, klik menu Monitoring Inventory. "
    "Tabel menampilkan Kode Product, Nama Product, kolom Gudang (seluruh gudang "
    "penyimpan), Stok Siap Jual, Dipesan (Reserved), Dikirim (Delivery), Terjual, "
    "dan Total Fisik.",
)
step(
    "Mengenali indikator warna stok",
    "Kolom Siap Jual menggunakan badge berwarna: merah jika stok habis (0 unit), "
    "kuning jika stok menipis (1-5 unit), dan hijau jika aman. Kolom Total Fisik "
    "merupakan penjumlahan Siap Jual + Dipesan + Dikirim.",
)
step(
    "Menyaring stok rendah",
    "Gunakan filter \"Stok Siap Jual ≤ 10\" pada bagian atas halaman. Sistem "
    "menampilkan hanya product dengan stok siap jual maksimal 10 unit sehingga "
    "Anda dapat memprioritaskan pengisian. Klik tombol View pada baris product "
    "untuk membuka halaman detail product yang bersangkutan pada tab baru.",
)
foto("Halaman Monitoring Inventory dengan filter stok rendah aktif")
caption("Monitoring Inventory")

h("Mengelola Data Gudang", 3)
step(
    "Membuka menu Gudang",
    "Pada sidebar kategori Manajemen Inventory, klik menu Gudang. Halaman daftar "
    "menampilkan seluruh gudang terdaftar beserta Nama Gudang, Lokasi, Penanggung "
    "Jawab, No. Telepon, Jumlah Product, Total Stock, dan Status Aktif.",
)
step(
    "Membaca indikator stok gudang",
    "Kolom Total Stock menggunakan badge berwarna: hijau menandakan stok aman, "
    "oranye menandakan stok menipis, dan merah menandakan stok habis atau tidak "
    "tersedia. Anda juga dapat menyaring daftar berdasarkan Status Gudang "
    "(Aktif/Nonaktif) atau rentang tanggal dibuat.",
)
foto("Halaman daftar Gudang beserta badge Total Stock dan filter status")
caption("Menu Daftar Gudang")
step(
    "Melihat detail gudang",
    "Klik nama gudang pada daftar. Halaman detail menampilkan informasi lengkap "
    "gudang, tautan Google Maps, jumlah product, total stock, serta tabel "
    "\"Daftar Product di Gudang\" berisi Kode Product, Nama Product, Kategori, "
    "Stock, dan Satuan. Gunakan filter Stock Menipis pada tabel tersebut untuk "
    "menampilkan hanya product yang jumlahnya sama dengan atau di bawah batas "
    "minimum stok.",
)
foto("Halaman detail gudang beserta daftar product dengan filter Stock Menipis")
caption("Detail Gudang dan Daftar Product")

h("Melihat Product", 3)
step(
    "Membuka menu Product",
    "Pada sidebar kategori Manajemen Inventory, klik menu Product. Halaman daftar "
    "menampilkan Foto, Kode Product, Nama Product, Kategori, Gudang penyimpan, "
    "Stock Tersedia, Reserved Stock, Total Stock, dan Harga Jual.",
)
step(
    "Mengenali product berserial",
    "Beberapa product (misalnya barang IT seperti laptop atau router) diwajibkan "
    "memakai Serial Number (SN). Product seperti ini ditandai pada pengaturan data "
    "sehingga setiap unitnya tercatat satu per satu.",
)
step(
    "Menyaring daftar product",
    "Gunakan filter Stock untuk menampilkan product berstok rendah, dan filter "
    "Kategori untuk mengelompokkan per jenis. Pada menu Product juga tersedia "
    "badge pemberitahuan apabila jumlah product dengan stok rendah meningkat.",
)
foto("Halaman daftar Product lengkap dengan kolom stok dan filter")
caption("Menu Daftar Product")

h("Laporan Stock Product", 3)
step(
    "Membuka Laporan Stock Product",
    "Klik menu Laporan Stock Product pada kategori Manajemen Inventory. Laporan "
    "merangkum seluruh persediaan: Kode dan Nama Product, Kategori, Lokasi Gudang, "
    "Tersedia (Siap Jual), Dipesan (Reserved), Pengiriman (Delivery), Total Fisik "
    "Keseluruhan, dan Harga Jual.",
)
step(
    "Menyaring dan menggunakan laporan",
    "Gunakan filter stock dan Lokasi Gudang untuk mempersempit data. Laporan ini "
    "biasanya dicetak atau diekspor untuk keperluan audit stok dan stock opname.",
)
foto("Halaman Laporan Stock Product beserta total fisik tiap product")
caption("Laporan Stock Product")

h("Laporan Transaksi", 3)
step(
    "Membuka Laporan Transaksi",
    "Klik menu Laporan Transaksi pada kategori Manajemen Inventory. Laporan "
    "menampilkan riwayat seluruh mutasi stok: No. Transaksi, Referensi (misal "
    "nomor GR, DO, atau PO), Tanggal, Kode dan Nama Product, Gudang, Jenis "
    "Mutasi (masuk/keluar), Qty, Harga Satuan, Total, Input Oleh, dan Catatan.",
)
step(
    "Memfilter transaksi",
    "Gunakan filter Tanggal Transaksi Dari/Hingga, Nama Product, Gudang, Input "
    "Oleh, dan Jenis mutasi. Laporan ini menjadi bukti audit atas setiap "
    "pergerakan barang di gudang dan wajib diperiksa saat terjadi "
    "ketidaksesuaian stok.",
)
foto("Halaman Laporan Transaksi stok beserta filter dan kolom mutasi")
caption("Laporan Transaksi")

# ============================================================
doc.add_heading("Pengelolaan Serial Number", level=2)
para(
    "Serial Number (SN) adalah nomor identitas unik setiap unit barang tertentu, "
    "umumnya barang elektronik. Pengelolaan SN memastikan setiap unit dapat "
    "dilacak keberadaannya dari saat masuk hingga terjual atau rusak. Role "
    "Warehouse bertanggung jawab menjaga ketepatan status setiap unit.",
)

h("Daftar Serial Number", 3)
step(
    "Membuka Daftar Serial Number",
    "Pada sidebar kategori Manajemen Inventory, klik menu Daftar Serial Number. "
    "Halaman memuat daftar setiap unit beserta Serial Number (SN), Nama Product, "
    "Lokasi Gudang, Supplier Asal, Klien/Pembeli, Garansi Habis, Tanggal Masuk, "
    "Tanggal Keluar, dan Status Unit.",
)
step(
    "Mengenali status unit",
    "Status unit ditampilkan sebagai badge berwarna: Available (siap jual, hijau), "
    "Reserved (dipesan, kuning), On Delivery (dalam pengiriman, biru), Sold "
    "(terjual, abu-abu), Defective (rusak, merah), dan Lost (hilang, merah).",
)
step(
    "Menyaring daftar unit",
    "Gunakan filter Filter Produk, Filter Gudang, dan Status SN untuk menemukan "
    "unit tertentu. Anda juga dapat mencari langsung berdasarkan nomor serial "
    "pada kolom pencarian.",
)
foto("Halaman Daftar Serial Number dengan badge status unit dan filter")
caption("Daftar Serial Number")

h("Melaporkan Barang Rusak", 3)
step(
    "Menemukan unit yang rusak",
    "Pada halaman Daftar Serial Number, cari unit yang rusak (misalnya jatuh saat "
    "diangkat dari rak) pada baris berstatus Available.",
)
step(
    "Melaporkan kerusakan",
    "Klik tombol Lapor Rusak pada baris unit tersebut. Isi Alasan Kerusakan pada "
    "form yang muncul, lalu konfirmasi. Status unit berubah menjadi Defective dan "
    "sistem mencatat transaksi stok keluar (adjustment) sebagai bukti audit, "
    "sehingga unit tidak lagi dihitung sebagai stok siap jual.",
)
foto("Tombol Lapor Rusak beserta form alasan kerusakan")
caption("Lapor Barang Rusak")

h("Menandai Barang Hilang", 3)
step(
    "Menemukan unit yang hilang",
    "Untuk unit yang tidak ditemukan saat stock opname, cari unit tersebut pada "
    "halaman Daftar Serial Number.",
)
step(
    "Menandai kehilangan",
    "Klik tombol Tandai Hilang pada baris unit (tersedia untuk status Available "
    "dan Reserved). Isi Alasan/Keterangan, lalu konfirmasi. Status unit berubah "
    "menjadi Lost dan dicatat sebagai transaksi stok keluar.",
)
foto("Tombol Tandai Hilang beserta form keterangan kehilangan")
caption("Tandai Barang Hilang")

h("Pelacakan Serial Number", 3)
para(
    "Menu Pelacakan Serial Number digunakan untuk menelusuri riwayat lengkap "
    "suatu unit sejak barang diterima. Menu ini sangat membantu saat menangani "
    "pertanyaan pelanggan, klaim garansi, atau saat mencocokkan data stock "
    "opname.",
)
step(
    "Membuka menu Pelacakan Serial Number",
    "Pada sidebar kategori Manajemen Inventory, klik menu Pelacakan Serial "
    "Number.",
)
step(
    "Mencari unit",
    "Ketik nomor serial pada kolom Serial Number lalu tekan Enter, atau gunakan "
    "tombol Scan Kamera Barcode/QR untuk memindai label unit. Pencarian otomatis "
    "dijalankan setelah scan berhasil tanpa perlu menekan tombol Cari.",
)
step(
    "Membaca hasil pelacakan",
    "Sistem menampilkan informasi unit: Serial Number, Nama dan Kode Product, "
    "Posisi Gudang, Status Terkini, Supplier Asal, No. PO, Klien/Pembeli, "
    "Tanggal Masuk, Tanggal Keluar, dan status Garansi (Aktif/Expired). Di "
    "bawahnya ditampilkan riwayat transaksi unit berupa tabel No. Transaksi, "
    "Tanggal, Jenis Mutasi, Referensi, Gudang, Input Oleh, dan Catatan.",
)
foto("Hasil pelacakan serial number beserta riwayat transaksi unit")
caption("Pelacakan Serial Number")

# ============================================================
doc.add_heading("Modul Procurement (Penerimaan Barang)", level=2)
para(
    "Modul Procurement digunakan untuk mengelola proses pengadaan mulai dari "
    "permintaan pembelian, purchase order, sampai penerimaan barang. Tugas utama "
    "petugas gudang pada modul ini adalah memproses Penerimaan Barang (Goods "
    "Receipt) berdasarkan Purchase Order (PO) yang telah dikirim ke supplier.",
)

h("Penerimaan Barang dari Purchase Order (Goods Receipt)", 3)
para(
    "Goods Receipt adalah dokumen yang mencatat penerimaan fisik barang dari "
    "supplier. Proses ini menambah stok ke gudang tujuan, mencatat transaksi stok "
    "masuk, dan memperbarui status PO secara otomatis. Pastikan PO telah dikirim "
    "ke supplier sebelum membuat penerimaan; sistem hanya menampilkan PO "
    "berstatus Sent atau Partial.",
)
step(
    "Membuka menu Goods Receipts",
    "Pada halaman Pilih Modul Kerja, buka modul Procurement, lalu klik menu Goods "
    "Receipts pada kategori Manajemen Procurement. Klik tombol Buat di pojok "
    "kanan atas untuk membuat dokumen penerimaan baru.",
)
foto("Halaman daftar Goods Receipts beserta tombol Buat")
caption("Menu Goods Receipts")
step(
    "Mengisi informasi penerimaan",
    "Isi Nama/Judul Penerimaan (contoh: \"Penerimaan Laptop Batch 1\"). Sistem "
    "menampilkan No. Penerimaan (GR) secara otomatis. Pilih dokumen Purchase "
    "Order pada kolom Dari Purchase Order (PO); kolom Supplier terisi otomatis "
    "mengikuti PO. Tentukan gudang tujuan pada kolom Masuk ke Gudang, atur "
    "Tanggal Diterima, dan cantumkan No. Surat Jalan Supplier bila ada.",
)
foto("Formulir Penerimaan Barang bagian informasi penerimaan dan pemilihan PO")
caption("Formulir Goods Receipt - Informasi Penerimaan")
step(
    "Memeriksa barang fisik",
    "Sistem otomatis memuat daftar barang dari PO. Setiap baris menampilkan Nama "
    "Barang, Qty PO, Sudah Diterima, Sisa Saat Ini, dan Sisa Setelah Input. Pada "
    "kolom Diterima Sekarang, sesuaikan jumlah barang yang benar-benar diterima "
    "sesuai kondisi fisik; jumlah ini tidak boleh melebihi sisa yang tersedia.",
)
foto("Ceklis barang fisik berupa daftar item dari PO dengan kolom Diterima Sekarang")
caption("Ceklis Barang Fisik")
step(
    "Scan serial number (barang berserial)",
    "Untuk barang berserial, muncul tombol Scanner Barcode/QR pada baris item. "
    "Scan setiap nomor serial pada unit yang diterima; SN berhasil ditambahkan ke "
    "Daftar Serial Number. Sistem menolak SN yang duplikat, sudah terdaftar di "
    "gudang lain, atau melebihi jumlah qty yang diterima. Jumlah SN harus sama "
    "persis dengan jumlah barang yang diterima, jika tidak sistem menolak "
    "penyimpanan.",
)
foto("Scanner barcode/QR beserta daftar serial number hasil scan")
caption("Scan Serial Number pada Penerimaan Barang")
step(
    "Menyimpan penerimaan",
    "Setelah semua item terverifikasi, klik tombol untuk menyimpan. Sistem "
    "otomatis menambahkan stok ke gudang tujuan, mencatat transaksi stok masuk, "
    "memperbarui status PO menjadi Partial (jika belum lengkap) atau Completed, "
    "serta membentuk catatan hutang dagang di modul finance.",
)
step(
    "Memeriksa status dan detail GR",
    "Pada daftar Goods Receipts, setiap dokumen menampilkan No. GR, Nama Dokumen, "
    "Supplier, Tanggal Diterima, Penerima (Gudang), dan Status (Draft, "
    "Completed, atau Cancelled). Klik tombol Lihat untuk membuka detail berisi "
    "informasi penerimaan lengkap dan rincian barang yang diterima beserta "
    "serial number-nya.",
)
foto("Detail Goods Receipt beserta daftar barang yang diterima dan SN")
caption("Detail Goods Receipt")

# ============================================================
doc.add_heading("Modul After-Sales (Pasca Jual)", level=2)
para(
    "Role Warehouse berperan penting pada modul After-Sales: tim gudang adalah "
    "tim yang memproses seluruh keluhan unit pasca jual. Alur dimulai dari "
    "pengajuan klaim oleh pelanggan melalui portal pelanggan, kemudian petugas "
    "gudang memproses penerimaan unit, mengarahkan ke asuransi vendor atau servis "
    "internal, hingga unit siap dikembalikan ke klien. Seluruh menu berada pada "
    "kategori Manajemen After-Sales di sidebar modul Inventory.",
)

h("Penerimaan Return (RMA)", 3)
para(
    "Menu Penerimaan Return menampilkan seluruh pengajuan pengembalian unit "
    "(Return Merchandise Authorization / RMA) dari klien. Dokumen RMA dibuat "
    "oleh klien melalui portal pelanggan; tim gudang bertugas memproses "
    "alurnya.",
)
step(
    "Membuka menu Penerimaan Return",
    "Pada sidebar modul Inventory, buka kategori Manajemen After-Sales lalu klik "
    "menu Penerimaan Return. Tabel menampilkan No. RMA, Klien, Jenis Kendala "
    "(misalnya rusak, tidak berfungsi), Rute Garansi (Supplier atau Toko), dan "
    "Status.",
)
step(
    "Mengenali status RMA",
    "Status RMA berubah sepanjang alur: Diterima, Sedang Ditinjau, Disetujui, "
    "Menunggu Pengembalian Unit, Unit Diterima, Dikirim ke Vendor, Servis "
    "Internal, Menunggu Refund, Siap Dikembalikan, Dikembalikan ke Klien, atau "
    "Ditolak. Pantau status ini untuk mengetahui tindakan yang harus dilakukan.",
)
foto("Halaman daftar Penerimaan Return beserta kolom status RMA")
caption("Menu Penerimaan Return")

h("Servis Internal", 3)
para(
    "Apabila unit yang dikembalikan akan diperbaiki di dalam perusahaan, tim "
    "gudang memprosesnya melalui menu Servis Internal. Setiap perbaikan dicatat "
    "dengan rincian keluhan, teknisi, dan hasil pengerjaan.",
)
step(
    "Memulai servis",
    "Pada menu Daftar Servis Internal, cari baris RMA berstatus Menunggu, lalu "
    "klik tombol Mulai Servis. Isi Catatan Kerja (misalnya diagnosa awal atau "
    "rencana perbaikan), lalu konfirmasi. Status berubah menjadi Sedang Dikerjakan "
    "dan Anda tercatat sebagai teknisi yang menangani unit tersebut.",
)
foto("Tombol Mulai Servis beserta form catatan kerja")
caption("Mulai Servis Internal")
step(
    "Menyelesaikan servis",
    "Setelah perbaikan selesai, klik tombol Selesaikan Servis. Pilih Hasil "
    "Pengerjaan: Repair (unit diperbaiki dan dikembalikan ke klien) atau Replace "
    "(unit diganti dengan unit baru). Jika memilih Replace, pilih SN Unit "
    "Pengganti dari daftar unit Available. Isi Catatan Teknisi lalu konfirmasi; "
    "sistem memperbarui SN unit, stok, dan status RMA menjadi Siap Dikembalikan.",
)
foto("Tombol Selesaikan Servis beserta pilihan hasil pengerjaan dan SN pengganti")
caption("Selesaikan Servis Internal")

h("Klaim Vendor", 3)
para(
    "Apabila kerusakan ditanggung oleh supplier (vendor), unit dikirim ke vendor "
    "dan dipantau melalui menu Klaim Vendor. Petugas gudang mencatat hasil klaim "
    "yang diterima dari vendor.",
)
step(
    "Menerima unit dari vendor",
    "Pada Daftar Klaim Vendor, cari baris berstatus Dikirim, lalu klik tombol "
    "Terima dari Vendor. Pilih Hasil Klaim Supplier: Repair (unit yang sama "
    "diperbaiki dan akan dikembalikan) atau Replace (vendor mengirim unit baru; "
    "isi SN Baru dari Vendor). Lengkapi catatan vendor lalu konfirmasi.",
)
foto("Formulir Terima dari Vendor beserta pilihan hasil klaim")
caption("Terima Unit dari Vendor")
step(
    "Mencatat klaim ditolak vendor",
    "Jika vendor menolak garansi, klik tombol Klaim Ditolak Vendor dan isi Alasan "
    "Penolakan. Unit tetap berada di vendor, dan RMA menunggu tindakan "
    "selanjutnya.",
)
foto("Form konfirmasi Klaim Ditolak Vendor")
caption("Klaim Ditolak Vendor")
step(
    "Menerima kembali unit yang ditolak",
    "Apabila unit yang klaimnya ditolak vendor sudah benar-benar diterima kembali "
    "di gudang, klik tombol Unit Ditangguhkan Diterima, isi Catatan Penerimaan "
    "Unit, lalu konfirmasi. RMA siap dikembalikan ke klien.",
)
foto("Form penerimaan kembali unit dari vendor")
caption("Unit Ditangguhkan Diterima")

# ============================================================
doc.add_heading("Modul Project Management", level=2)
para(
    "Role Warehouse juga memiliki akses ke modul Project Management untuk "
    "mengikuti pekerjaan atau permintaan yang berkaitan dengan gudang, misalnya "
    "perbaikan rak atau pengadaan fasilitas. Melalui modul ini, petugas gudang "
    "dapat melihat daftar project, memantau progress melalui board, memperbarui "
    "ticket, dan mencatat pekerjaan sehari-hari.",
)

h("Dashboard Project", 3)
step(
    "Membuka modul Project Management",
    "Pada halaman Pilih Modul Kerja, klik tombol Buka modul pada kartu Project "
    "Management. Dashboard Project menampilkan ringkasan statistik project, "
    "progress setiap project, tabel ticket terbaru, dan ticket yang melewati "
    "batas waktu (overdue).",
)
foto("Dashboard Project Management beserta kartu statistik dan daftar ticket")
caption("Dashboard Project")

h("Melihat Daftar Project", 3)
step(
    "Membuka menu Project",
    "Klik menu Project pada kategori Manajemen Project untuk membuka daftar "
    "seluruh project. Setiap project menampilkan Nama Project, Project Manager, "
    "tanggal mulai dan selesai, serta badge jumlah ticket.",
)
step(
    "Melihat detail project",
    "Klik nama project untuk melihat detail termasuk daftar ticket, anggota "
    "project, dan progress. Gunakan menu Project Timeline atau Ticket Timeline "
    "pada kategori Manajemen Project untuk melihat urutan kegiatan project dalam "
    "bentuk garis waktu.",
)
foto("Halaman daftar Project beserta informasinya")
caption("Daftar Project")

h("Menggunakan Project Board", 3)
step(
    "Membuka Project Board",
    "Use menu Project Board untuk melihat ticket per project dalam bentuk kolom "
    "status seperti Backlog, To Do, In Progress, Review, dan Done. Petugas "
    "gudang dapat menyeret (drag) berbagai kartu ticket antar kolom untuk "
    "memperbarui status pekerjaannya sesuai progres nyata di lapangan.",
)
foto("Project Board dengan kolom status Backlog, To Do, In Progress, Review, Done")
caption("Project Board")

h("Bekerja dengan Ticket", 3)
step(
    "Membuat ticket baru",
    "Untuk mencatat pekerjaan baru seperti perbaikan gudang atau pengadaan rak, "
    "klik menu Ticket lalu tombol Buat Ticket. Pilih atau isi Project, prioritas, "
    "judul, dan deskripsi, lalu simpan.",
)
step(
    "Memperbarui status ticket",
    "Ticket yang dibuat muncul pada board dan dapat diubah statusnya oleh petugas "
    "gudang maupun Project Manager. Perbarui status seiring progres pekerjaan "
    "agar semua pihak mengetahui kondisi terkini.",
)
foto("Halaman Buat Ticket beserta kolom Project, prioritas, judul, dan deskripsi")
caption("Membuat Ticket")

# ============================================================
doc.add_heading("Catatan Hak Akses", level=2)
para(
    "Seluruh menu pada manual ini hanya dapat diakses sesuai hak akses role yang "
    "diberikan oleh Super Admin melalui konfigurasi peran (role) dan permissions "
    "pada modul System. Apabila suatu menu tidak muncul di sidebar, berarti role "
    "warehouse yang digunakan belum memiliki izin akses terhadap menu tersebut. "
    "Hubungi Super Admin untuk menyesuaikan permission, atau mintalah agar data "
    "karyawan Anda dihubungkan dengan akun login (relasi employee) bila "
    "fitur presensi tidak dapat diakses. Konfigurasi ini diperlukan agar seluruh "
    "fitur bekerja sesuai tanggung jawab Anda sebagai petugas gudang.",
)

doc.save(OUT)
print("OK ->", OUT)