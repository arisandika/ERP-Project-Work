<?php

namespace App\Filament\Resources\Inventory\WarehouseResource\Pages;

use App\Filament\Resources\Inventory\WarehouseResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewWarehouse extends ViewRecord
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Gudang')
                    ->schema([
                        Infolists\Components\TextEntry::make('warehouse_code')
                            ->label('Kode Gudang')
                            ->badge()
                            ->color('primary'),
                        
                        Infolists\Components\TextEntry::make('warehouse_name')
                            ->label('Nama Gudang')
                            ->weight('bold')
                            ->size('lg'),
                        
                        Infolists\Components\TextEntry::make('location')
                            ->label('Lokasi/Alamat')
                            ->columnSpanFull(),
                        
                        Infolists\Components\TextEntry::make('manager_name')
                            ->label('Penanggung Jawab')
                            ->default('-'),
                        
                        Infolists\Components\TextEntry::make('phone')
                            ->label('No. Telepon')
                            ->default('-'),
                        
                        Infolists\Components\IconEntry::make('is_active')
                            ->label('Status')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                    ])
                    ->columns(2),
                
                Infolists\Components\Section::make('Statistik Stok')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_products')
                            ->label('Total Produk')
                            ->badge()
                            ->color('info')
                            ->suffix(' items'),
                        
                        Infolists\Components\TextEntry::make('total_stock')
                            ->label('Total Stok')
                            ->badge()
                            ->color('success')
                            ->suffix(' unit'),
                        
                        Infolists\Components\TextEntry::make('low_stock_items')
                            ->label('Stok Rendah')
                            ->getStateUsing(fn ($record) => $record->low_stock_items->count())
                            ->badge()
                            ->color(fn ($state) => $state > 0 ? 'warning' : 'success')
                            ->suffix(' items'),
                    ])
                    ->columns(3),
            ]);
    }
}

