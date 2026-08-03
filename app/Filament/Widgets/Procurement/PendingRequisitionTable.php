<?php

namespace App\Filament\Widgets\Procurement;

use App\Models\Procurement\PurchaseRequisition;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PendingRequisitionTable extends BaseWidget
{
    protected static ?string $heading = 'Approval Queue';

    protected static ?int $sort = 4;

    protected static ?string $pollingInterval = '5s';

    protected int | string | array $columnSpan = [
        'xl' => 12,
    ];

    public static function canView(): bool
    {
        return auth()->user()->can('approve_purchase_requisition');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PurchaseRequisition::query()
                    ->with('requester')
                    ->where('status', PurchaseRequisition::STATUS_PENDING)
                    ->latest('submitted_at')
            )

            ->striped()

            ->columns([

                Tables\Columns\TextColumn::make('pr_number')
                    ->label('PR Number')
                    ->searchable()
                    ->weight('bold')
                    ->copyable()
                    ->description(
                        fn (PurchaseRequisition $record)
                            => $record->title
                    ),

                Tables\Columns\TextColumn::make('requester.name')
                    ->label('Requester')
                    ->icon('heroicon-m-user'),

                Tables\Columns\TextColumn::make('required_date')
                    ->label('Due Date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'low' => 'gray',
                        'medium' => 'warning',
                        'high' => 'danger',
                        default => 'gray',
                    }),
            ])

            ->actions([
                Action::make('Review')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->button()
                    ->url(
                        fn (PurchaseRequisition $record)
                            => route(
                                'filament.admin.resources.procurement.purchase-requisitions.edit',
                                $record
                            )
                    )
                    ->openUrlInNewTab(),
            ])

            ->emptyStateHeading('No Pending Approval')

            ->emptyStateDescription(
                'Semua purchase requisition sudah diproses.'
            )

            ->emptyStateIcon('heroicon-o-check-badge')

            ->paginated([5]);
    }
}
