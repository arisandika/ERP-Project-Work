<?php

namespace App\Traits;

trait GeneratesDocumentNumber
{
    /**
     * Generate nomor dokumen dengan format PREFIX-YYMM-SEQ
     *
     * @param string $prefix Kode dokumen (contoh: 'GR', 'PO', 'RMA')
     * @param string $columnName Nama kolom di tabel (default: 'document_number')
     * @return string
     */
    public static function generateDocNumber(string $prefix, string $columnName): string
    {
        $formatPrefix = $prefix . '-' . date('ym') . '-';

        // Cek apakah model pakai SoftDeletes
        $query = static::query();
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(static::class))) {
            $query = $query->withTrashed();
        }

        $lastRecord = $query->where($columnName, 'like', $formatPrefix . '%')
            ->lockForUpdate()
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastRecord ? ((int) substr($lastRecord->{$columnName}, -3)) + 1 : 1;

        return $formatPrefix . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }
}
