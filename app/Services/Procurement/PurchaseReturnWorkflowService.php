<?php

namespace App\Services\Procurement;

use App\Models\Procurement\PurchaseReturn;
use App\Services\Finance\PurchaseReturnFinancialService;
use Illuminate\Support\Facades\DB;

/**
 * Workflow PurchaseReturn — satu-satunya jalur resmi untuk mengubah status
 * dokumen. Enforce state machine: draft → approved → shipped → completed.
 * Transisi yang melompati state DITOLAK (throw InvalidArgumentException).
 *
 * Authorization per-action juga divalidasi di sini (defense-in-depth di luar
 * pemfilteran UI): tiap method menuntut permission aksinya sendiri, sehingga
 * pemegang `update_procurement::purchase::return` TIDAK otomatis dapat
 * approve/ship/complete.
 */
class PurchaseReturnWorkflowService
{
    /**
     * Transisi yang diizinkan. Key = status saat ini, value = suksesor valid.
     */
    private const ALLOWED = [
        PurchaseReturn::STATUS_DRAFT     => [PurchaseReturn::STATUS_APPROVED],
        PurchaseReturn::STATUS_APPROVED  => [PurchaseReturn::STATUS_SHIPPED],
        PurchaseReturn::STATUS_SHIPPED   => [PurchaseReturn::STATUS_COMPLETED],
        PurchaseReturn::STATUS_COMPLETED => [],
        PurchaseReturn::STATUS_CANCELLED => [],
    ];

    public function approve(PurchaseReturn $pr, ?array $data = []): PurchaseReturn
    {
        $this->assertCan('approve_procurement::purchase::return');

        return DB::transaction(function () use ($pr, $data) {
            $this->guardTransition($pr, PurchaseReturn::STATUS_APPROVED);
            $pr->update([
                'status' => PurchaseReturn::STATUS_APPROVED,
                'notes'  => $data['notes'] ?? $pr->notes,
            ]);
            return $pr->fresh();
        });
    }

    public function ship(PurchaseReturn $pr, ?array $data = []): PurchaseReturn
    {
        $this->assertCan('ship_procurement::purchase::return');

        return DB::transaction(function () use ($pr, $data) {
            $this->guardTransition($pr, PurchaseReturn::STATUS_SHIPPED);
            $pr->update([
                'status' => PurchaseReturn::STATUS_SHIPPED,
                'notes'  => $data['notes'] ?? $pr->notes,
            ]);
            return $pr->fresh();
        });
    }

    public function complete(PurchaseReturn $pr): PurchaseReturn
    {
        $this->assertCan('complete_procurement::purchase::return');

        return DB::transaction(function () use ($pr) {
            $this->guardTransition($pr, PurchaseReturn::STATUS_COMPLETED);
            // Financial posting + status completed di dalam satu transaksi.
            app(PurchaseReturnFinancialService::class)->execute($pr);
            return $pr->fresh();
        });
    }

    /**
     * Guard autentikasi/otorisasi. Nullable fallback: bila tidak ada guard aktif
     * (context CLI/queue), dianggap tidak berhak → throw, kecuali super_admin.
     */
    private function assertCan(string $permission): void
    {
        $user = auth()->user();

        if ($user && $user->hasRole('super_admin')) {
            return;
        }

        if (! $user || ! $user->can($permission)) {
            throw new \InvalidArgumentException(
                "Anda tidak memiliki izin untuk melakukan aksi ini ({$permission})."
            );
        }
    }

    private function guardTransition(PurchaseReturn $pr, string $target): void
    {
        $allowedNext = self::ALLOWED[$pr->status] ?? [];

        if (! in_array($target, $allowedNext, true)) {
            throw new \InvalidArgumentException(
                "Transisi tidak valid: {$pr->status} → {$target}. " .
                'Workflow PurchaseReturn hanya: draft → approved → shipped → completed.'
            );
        }
    }
}