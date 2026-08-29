<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;

class ExportAttendancesAction
{
    public static function make(): Action
    {
        return Action::make('export_attendances')
            ->label('Export Excel')
            ->icon('heroicon-m-arrow-down-tray')
            ->color('success')
            ->form([
                Section::make('Filter Tanggal')
                    ->description('Pilih rentang waktu presensi yang ingin diekspor')
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Dari Tanggal')
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->required(),
                        DatePicker::make('end_date')
                            ->label('Sampai Tanggal')
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->required(),
                    ])->columns(['default' => 1, 'md' => 2]),

                Section::make('Pilih Kolom')
                    ->description('Pilih kolom yang ingin disertakan dalam file Excel')
                    ->schema([
                        CheckboxList::make('columns')
                            ->label('Kolom')
                            ->options([
                                'employee_name' => 'Nama Karyawan',
                                'date' => 'Tanggal',
                                'shift' => 'Shift',
                                'note' => 'Catatan',
                                'clock_in' => 'Jam Masuk',
                                'latitude_in' => 'Latitude Masuk',
                                'longitude_in' => 'Longitude Masuk',
                                'clock_out' => 'Jam Keluar',
                                'latitude_out' => 'Latitude Keluar',
                                'longitude_out' => 'Longitude Keluar',
                                'status' => 'Status Kehadiran',
                                'created_at' => 'Dibuat Pada',
                            ])
                            ->default([
                                'employee_name',
                                'date',
                                'shift',
                                'clock_in',
                                'clock_out',
                                'status'
                            ])
                            ->required()
                            ->minItems(1)
                            ->columns(['default' => 1, 'md' => 2])
                            ->gridDirection('row')
                    ])
            ])
            ->action(function (array $data, $livewire): void {
                // Mengirim seluruh data form (tanggal & kolom) ke livewire page
                $livewire->exportAttendances($data);
            });
    }
}