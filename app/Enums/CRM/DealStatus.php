<?php
namespace App\Enums\CRM;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum DealStatus: string implements HasColor, HasLabel, HasIcon
{
    case Proposal    = 'proposal';
    case Negotiation = 'negotiation';
    case Deal        = 'deal';
    case Closed      = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Proposal    => 'Proposal',
            self::Negotiation => 'Negosiasi',
            self::Deal        => 'Deal',
            self::Closed      => 'Ditutup',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Proposal    => 'info',
            self::Negotiation => 'warning',
            self::Deal        => 'success',
            self::Closed      => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Proposal    => 'heroicon-o-document-text',
            self::Negotiation => 'heroicon-o-chat-bubble-left-right',
            self::Deal        => 'heroicon-o-check-badge',
            self::Closed      => 'heroicon-o-x-circle',
        };
    }
}
