# Task: Audit & Fix Responsive Mobile untuk Seluruh Filament Widgets

Saya memiliki project **Laravel + Filament 3** yang sudah menggunakan **Filament ApexCharts**.

Saat ini banyak widget/chart pada halaman Filament yang pada tampilan mobile masih mengalami masalah:

* Widget tersusun 2–3 kolom ke samping.
* Chart mengalami horizontal overflow.
* Content widget keluar dari viewport.
* Table/widget terlalu lebar.
* Grid/column layout desktop terbawa ke mobile.
* ApexCharts tidak mengikuti lebar container mobile.
* Beberapa widget menggunakan `columnSpan` yang menyebabkan layout tidak responsive.
* Widget custom yang menggunakan `Filament\Widgets\Widget` juga perlu diperiksa.

## Tujuan Utama

Lakukan **full project scan dan langsung execute perbaikannya**.

Saya ingin seluruh widget Filament pada project ini memiliki responsive behavior yang baik, khususnya:

### Desktop

Tetap pertahankan layout desktop yang sudah ada semaksimal mungkin.

Contoh:

* `columnSpan = 8`
* `columnSpan = 4`
* `columnSpan = 6`
* widget berdampingan 2–3 kolom jika memang desain desktop menggunakannya.

### Mobile

**SEMUA widget harus menjadi 1 column / full width.**

Artinya pada breakpoint mobile:

```text
Widget A
──────────────
Widget B
──────────────
Widget C
──────────────
Widget D
──────────────
```

Bukan:

```text
Widget A | Widget B
Widget C | Widget D
```

dan tidak boleh ada horizontal scrolling/overflow akibat layout widget.

---

# 1. FULL SCAN PROJECT

Jangan hanya memperbaiki widget yang saya berikan sebagai contoh.

Scan seluruh project untuk mencari seluruh class yang berhubungan dengan Filament Widget.

Minimal cari:

```php
use Filament\Widgets\Widget;
```

```php
use Filament\Widgets\StatsOverviewWidget;
```

```php
use Filament\Widgets\ChartWidget;
```

```php
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
```

dan seluruh turunan/implementasi widget lainnya.

Gunakan pencarian repository seperti:

```bash
grep -R "extends Widget" app/Filament
grep -R "extends ChartWidget" app/Filament
grep -R "extends ApexChartWidget" app/Filament
grep -R "use Filament\\Widgets\\Widget" app/Filament
grep -R "columnSpan" app/Filament
```

Namun jangan terbatas pada command tersebut. Gunakan metode scanning yang sesuai dengan struktur project.

Scan seluruh:

```text
app/Filament/
resources/views/filament/
resources/css/
resources/js/
```

serta konfigurasi/layout Filament yang relevan.

---

# 2. AUDIT SEMUA WIDGET

Untuk setiap widget yang ditemukan, periksa:

### A. columnSpan

Cari property seperti:

```php
protected int|string|array $columnSpan = 8;
```

```php
protected int|string|array $columnSpan = 4;
```

```php
protected int|string|array $columnSpan = [
    'md' => 6,
    'xl' => 4,
];
```

dan bentuk lainnya.

Pastikan layout tersebut tidak menyebabkan overflow pada mobile.

Jika memungkinkan, gunakan responsive column configuration yang sesuai dengan Filament 3 sehingga:

```text
mobile = 1 column
tablet/desktop = existing layout
```

Jangan merusak layout desktop yang sudah bagus.

Contoh target:

```php
protected int|string|array $columnSpan = [
    'default' => 'full',
    'md' => 6,
    'xl' => 4,
];
```

Tetapi **jangan blindly mengganti semua menjadi contoh tersebut**.

Sesuaikan dengan fungsi dan layout widget masing-masing.

---

# 3. APEXCHARTS

Project menggunakan:

```php
Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
```

Audit seluruh ApexChartWidget.

Pastikan chart:

* Tidak overflow horizontal.
* Lebar mengikuti container.
* Tetap readable di mobile.
* Tidak memaksa fixed width.
* Tidak menyebabkan parent widget melebar.
* Toolbar/chart legend tidak keluar viewport.
* Axis/category tidak menyebabkan layout rusak.
* Chart height tetap reasonable pada mobile.

Periksa konfigurasi seperti:

```php
'chart' => [
    'type' => 'bar',
    'height' => 350,
],
```

dan konfigurasi:

```php
'xaxis'
'yaxis'
'legend'
'dataLabels'
'plotOptions'
'responsive'
'grid'
```

Jika diperlukan, tambahkan ApexCharts responsive configuration.

Contoh konsep:

```php
'responsive' => [
    [
        'breakpoint' => 640,
        'options' => [
            'chart' => [
                'height' => 300,
            ],
            'legend' => [
                'position' => 'bottom',
            ],
        ],
    ],
],
```

Namun sesuaikan dengan chart masing-masing.

Jangan menambahkan konfigurasi yang tidak didukung oleh versi ApexCharts/Filament ApexCharts yang digunakan project.

---

# 4. CUSTOM WIDGET YANG MENGGUNAKAN Filament\Widgets\Widget

Cari seluruh widget custom seperti:

```php
class AnalyticsSectionHeaderWidget extends Widget
```

Contoh:

```php
class AnalyticsSectionHeaderWidget extends Widget
{
    protected static string $view =
        'filament.widgets.employee-analytics.sections.analytics-section-header-widget';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public string $title = '';
    public string $description = '';
    public string $icon = 'heroicon-o-chart-bar';
}
```

Audit view Blade-nya juga.

Cari kemungkinan masalah seperti:

```css
width: 100vw;
min-width:
width:
display: flex;
grid-template-columns:
white-space: nowrap;
```

atau Tailwind:

```text
w-screen
min-w-*
w-[...]
flex-nowrap
grid-cols-*
```

yang dapat menyebabkan horizontal overflow.

Pastikan custom widget benar-benar responsive.

---

# 5. TABLE WIDGET

Scan semua widget yang berisi:

```php
Table
```

atau:

```php
Filament\Tables
```

Audit khusus:

* Table terlalu lebar di mobile.
* Kolom memaksa width tertentu.
* Text tidak wrap.
* Action terlalu banyak.
* Badge/status menyebabkan overflow.
* Table container tidak responsive.

Gunakan fitur responsive Filament yang memang tersedia.

Jangan menghapus informasi penting hanya demi menghilangkan overflow.

Jika kolom tertentu memang tidak cocok untuk mobile, gunakan strategi responsive yang sesuai dengan Filament 3, misalnya visibility/responsive column behavior atau alternatif yang sesuai.

---

# 6. STATS / OVERVIEW WIDGET

Scan semua:

```php
StatsOverviewWidget
```

Pastikan cards pada mobile:

```text
┌─────────────────────┐
│ Stat 1              │
└─────────────────────┘

┌─────────────────────┐
│ Stat 2              │
└─────────────────────┘

┌─────────────────────┐
│ Stat 3              │
└─────────────────────┘
```

Tidak:

```text
┌──────────┐ ┌──────────┐
│ Stat 1   │ │ Stat 2   │
└──────────┘ └──────────┘
```

jika menyebabkan layout terlalu sempit/overflow.

---

# 7. FORM / FILTER / HEADER WIDGET

Audit juga:

* Filter forms
* DatePicker
* Select
* Section
* Header widgets
* Custom header widgets
* Action buttons
* Widget descriptions
* Icon + text layout

Pastikan pada mobile tidak ada elemen yang keluar viewport.

Contoh pada page:

```php
Section::make('Filter Analytics')
    ->schema([
        DatePicker::make('startDate'),
        DatePicker::make('endDate'),
    ])
    ->columns(2)
```

Desktop boleh:

```text
Tanggal Awal | Tanggal Akhir
```

Tetapi mobile sebaiknya:

```text
Tanggal Awal
─────────────

Tanggal Akhir
─────────────
```

Gunakan responsive column configuration Filament jika diperlukan.

---

# 8. SCAN SELURUH MODUL

Jangan hanya fokus pada:

```text
EmployeeAnalytics
```

Scan semua module/page/widget di:

```text
app/Filament/
```

Termasuk jika ada struktur seperti:

```text
app/Filament/Widgets/
app/Filament/Pages/
app/Filament/Resources/
app/Filament/Resources/*/Widgets/
```

Cari seluruh widget dari semua domain/module, misalnya:

```text
HR
Attendance
Employee
Finance
Accounting
Inventory
Purchasing
Sales
CRM
Project
Dashboard
Reports
dll.
```

Nama module tidak penting. **Semua widget harus diaudit.**

---

# 9. JANGAN MERUSAK DESKTOP

Ini sangat penting.

Saya **tidak meminta semua widget dibuat full width pada semua device**.

Requirement:

```text
Mobile:
1 column

Desktop:
Pertahankan layout existing semaksimal mungkin
```

Jadi jika sekarang:

```php
protected int|string|array $columnSpan = 8;
```

jangan otomatis mengubahnya menjadi:

```php
protected int|string|array $columnSpan = 'full';
```

untuk semua breakpoint.

Gunakan responsive configuration jika diperlukan.

Target:

```text
Mobile → full width / 1 column
Tablet/Desktop → existing layout
```

---

# 10. PAGE GRID

Audit juga bagaimana widget-widget tersebut dipanggil oleh Page.

Contoh:

```php
protected function getHeaderWidgets(): array
{
    return [
        EmployeeOverviewStats::class,
    ];
}
```

dan:

```php
public function getVisibleWidgets(): array
{
    return [
        AttendanceTrendChart::class,
        AttendanceStatusDonutChart::class,
        WorkHoursChart::class,
    ];
}
```

Pastikan parent widget grid Filament juga tidak menyebabkan child widget overflow.

Jika page menggunakan custom Blade:

```php
protected static string $view =
    'filament.pages.hr.employee-analytics-dashboard';
```

audit Blade tersebut juga.

---

# 11. CSS GLOBAL

Scan CSS project untuk kemungkinan global CSS yang menyebabkan widget Filament overflow.

Cari:

```css
overflow-x
min-width
width: 100vw
width: max-content
white-space: nowrap
```

dan Tailwind classes seperti:

```text
min-w-*
w-screen
w-[...]
whitespace-nowrap
flex-nowrap
```

Jangan menghapus CSS secara sembarangan.

Jika ditemukan CSS yang memang menjadi penyebab global overflow, perbaiki pada level yang paling tepat.

Hindari solusi buruk seperti:

```css
body {
    overflow-x: hidden;
}
```

sebagai satu-satunya solusi.

`overflow-x: hidden` **tidak boleh digunakan untuk menyembunyikan bug layout** tanpa memperbaiki root cause.

---

# 12. BLADE CUSTOM WIDGET

Untuk setiap:

```php
extends Widget
```

temukan view-nya:

```php
protected static string $view = '...';
```

kemudian audit Blade tersebut.

Pastikan:

* Container menggunakan width yang responsive.
* Flex/grid dapat wrap.
* Text dapat wrap.
* Icon tidak memaksa width.
* Button/action tidak keluar viewport.
* Tidak menggunakan fixed width yang tidak diperlukan.
* Tidak menggunakan `w-screen` di dalam widget.
* Tidak menggunakan `min-w-max` tanpa alasan.
* Tidak ada absolute positioning yang membuat content keluar viewport.

---

# 13. VALIDATION

Setelah melakukan perubahan:

1. Jalankan static checks yang relevan.
2. Pastikan tidak ada syntax error PHP.
3. Pastikan Blade tidak error.
4. Pastikan seluruh widget masih bisa di-load.
5. Pastikan tidak ada class/property Filament yang invalid.
6. Pastikan konfigurasi ApexCharts valid.
7. Clear cache Laravel jika diperlukan.

Gunakan command yang sesuai project, misalnya:

```bash
php artisan optimize:clear
```

dan jika tersedia:

```bash
php artisan view:clear
php artisan config:clear
php artisan route:clear
```

Jangan menjalankan command destructive seperti migration:fresh, db:wipe, atau menghapus database.

---

# 14. MOBILE BREAKPOINT TEST

Setelah perubahan, lakukan audit dengan viewport minimal:

```text
320px
375px
390px
414px
```

dan desktop:

```text
1024px
1280px
1440px
```

Perhatikan terutama:

* Horizontal overflow
* Chart overflow
* Widget grid
* Table
* Filter form
* Buttons
* Header
* Badge
* Long text
* Chart legend
* Chart labels
* Date picker
* Dropdown
* Cards

Jika environment memungkinkan browser testing, gunakan browser/devtools untuk melakukan pengecekan.

Jika browser testing tidak tersedia, lakukan static inspection secara menyeluruh dan jelaskan bagian yang tidak dapat diverifikasi secara visual.

---

# 15. ACCEPTANCE CRITERIA

Task dianggap selesai jika:

### Mobile

* Semua widget berada dalam 1 column.
* Tidak ada widget yang berdampingan 2–3 kolom pada mobile jika menyebabkan layout sempit.
* Tidak ada horizontal overflow dari widget.
* ApexCharts responsive.
* Table responsive.
* Stats responsive.
* Custom Widget responsive.
* Filter responsive.
* Section/header widget responsive.
* Tidak ada fixed width yang menyebabkan widget keluar viewport.

### Desktop

* Layout existing tetap dipertahankan sebisa mungkin.
* Widget yang sebelumnya 8/4, 6/6, 4/4/4 tetap dapat berdampingan pada desktop.
* Tidak mengubah desain desktop tanpa alasan.

### Code Quality

* Jangan duplicate CSS jika bisa menggunakan utility/class yang sudah tersedia.
* Jangan membuat workaround global yang menyembunyikan masalah.
* Jangan mengubah business logic.
* Jangan mengubah query database kecuali benar-benar diperlukan untuk responsive behavior.
* Jangan mengubah permission/authorization.
* Jangan mengubah routing.
* Jangan mengubah struktur database.
* Jangan menghapus widget.
* Jangan menghapus informasi hanya karena sulit dibuat responsive.
* Ikuti pola coding yang sudah digunakan project.
* Gunakan API Filament 3 yang kompatibel dengan versi project.

---

# 16. EXECUTE, BUKAN HANYA ANALYZE

Ini adalah **implementation task**, bukan sekadar code review.

Jadi setelah scan:

1. Identifikasi seluruh widget.
2. Identifikasi seluruh masalah responsive.
3. Edit file yang diperlukan.
4. Implementasikan perbaikannya.
5. Jalankan validation/check.
6. Review kembali perubahan.
7. Perbaiki jika masih ada potensi overflow.

**Jangan berhenti setelah memberikan daftar rekomendasi.**

Saya ingin agent **langsung melakukan perubahan pada repository/workspace**.

---

# 17. FINAL REPORT

Setelah selesai, berikan report singkat dengan format:

## Files Changed

List file yang diubah.

## Widgets Audited

Jumlah widget yang ditemukan dan kategori:

```text
ApexChartWidget: X
ChartWidget: X
StatsOverviewWidget: X
Custom Widget: X
Table Widget: X
Other: X
```

## Changes

Contoh:

```text
- Added responsive column spans for mobile.
- Fixed ApexCharts mobile sizing.
- Fixed custom widget flex overflow.
- Fixed responsive filter layout.
- Fixed table overflow.
```

## Validation

Berikan command/check yang dijalankan dan hasilnya.

## Remaining Issues

Jika masih ada masalah yang tidak bisa diverifikasi atau membutuhkan browser testing, jelaskan secara eksplisit.

---

## Important

**Prioritas utama:**

```text
Mobile = 1 column + no horizontal overflow
Desktop = preserve existing layout
```

Jangan hanya memperbaiki `EmployeeAnalyticsDashboard`.

**Scan dan perbaiki SELURUH widget Filament di project, lintas semua module.**
