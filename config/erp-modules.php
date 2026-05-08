<?php

return [
    'attendance' => [
        'key' => 'attendance',
        'label' => 'Attendance',
        'description' => 'Absensi masuk, pulang, dan riwayat kehadiran.',
        'icon' => 'heroicon-o-calendar-days',
        'permission' => 'access module attendance',
        'route' => 'filament.admin.pages.attendance-dashboard',
        'color' => 'success',
    ],

    'inventory' => [
        'key' => 'inventory',
        'label' => 'Inventory',
        'description' => 'Kelola produk, gudang, stok, serial number, dan mutasi barang.',
        'icon' => 'heroicon-o-cube',
        'permission' => 'access module inventory',
        'route' => 'filament.admin.pages.inventory-dashboard',
        'color' => 'danger',
    ],

    'hr' => [
        'key' => 'hr',
        'label' => 'Human Resource',
        'description' => 'Kelola karyawan, absensi, jabatan, dan kebutuhan HR.',
        'icon' => 'heroicon-o-users',
        'permission' => 'access module hr',
        'route' => 'filament.admin.pages.hr-dashboard',
        'color' => 'info',
    ],

    'finance' => [
        'key' => 'finance',
        'label' => 'Finance',
        'description' => 'Kelola transaksi, pembayaran, dan laporan keuangan.',
        'icon' => 'heroicon-o-banknotes',
        'permission' => 'access module finance',
        'route' => 'filament.admin.pages.finance-dashboard',
        'color' => 'warning',
    ],

    'procurement' => [
        'key' => 'procurement',
        'label' => 'Procurement',
        'description' => 'Kelola PR, PO, penerimaan barang, dan invoice supplier.',
        'icon' => 'heroicon-o-clipboard-document-list',
        'permission' => 'access module procurement',
        'route' => 'filament.admin.pages.procurement-dashboard',
        'color' => 'gray',
    ],

    'crm' => [
        'key' => 'crm',
        'label' => 'CRM',
        'description' => 'Kelola lead, deal, customer, pipeline, dan follow up.',
        'icon' => 'heroicon-o-user-group',
        'permission' => 'access module crm',
        'route' => 'filament.admin.pages.crm.dashboard',
        'color' => 'info',
    ],

    'sales' => [
        'key' => 'sales',
        'label' => 'Sales',
        'description' => 'Kelola customer, quotation, sales order, dan delivery.',
        'icon' => 'heroicon-o-shopping-cart',
        'permission' => 'access module sales',
        'route' => 'filament.admin.pages.sales-dashboard',
        'color' => 'primary',
    ],

    'marketing' => [
        'key' => 'marketing',
        'label' => 'Marketing',
        'description' => 'Kelola promo, banner, slider, dan konten marketing.',
        'icon' => 'heroicon-o-megaphone',
        'permission' => 'access module marketing',
        'route' => 'filament.admin.pages.marketing.dashboard',
        'color' => 'warning',
    ],

    'project' => [
        'key' => 'project',
        'label' => 'Project Management',
        'description' => 'Kelola proyek, tugas, tim, dan timeline proyek.',
        'icon' => 'heroicon-o-briefcase',
        'permission' => 'access module project_management',
        'route' => 'filament.admin.pages.project.dashboard',
        'color' => 'primary',
    ],

    'system' => [
        'key' => 'system',
        'label' => 'System',
        'description' => 'Kelola user, role, permission, dan pengaturan sistem.',
        'icon' => 'heroicon-o-cog-6-tooth',
        'permission' => 'access module system',
        'route' => 'filament.admin.pages.system.dashboard',
        'color' => 'gray',
    ],
];
