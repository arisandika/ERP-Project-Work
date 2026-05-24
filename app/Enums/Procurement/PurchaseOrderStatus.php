<?php

namespace App\Enums\Procurement;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;

enum PurchaseOrderStatus: string implements HasLabel, HasColor
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case PARTIAL = 'partial';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SENT => 'Terkirim',
            self::PARTIAL => 'Parsial',
            self::COMPLETED => 'Selesai',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function getColor(): string|array|null {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SENT => 'info',
            self::PARTIAL => 'warning',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
        };
    }
}
