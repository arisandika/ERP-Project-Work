<?php

namespace App\Models\SalesActivity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class VisitPhoto extends Model
{
    // Tidak pakai SoftDeletes — foto dihapus langsung bersama recordnya
    // cascade delete ditangani dari VisitRecord::booted()

    protected $table = 'nx_visit_photos';

    protected $fillable = [
        'nx_visit_record_id',
        'file_path',
        'photo_type',
        'latitude',
        'longitude',
        'taken_at',
        'caption',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'taken_at' => 'datetime',
    ];

    //----------------------------------------------------------------------
    // Constants
    //----------------------------------------------------------------------

    const TYPE_DOCUMENTATION = 'documentation';
    const TYPE_EVIDENCE = 'evidence';
    const TYPE_LOCATION = 'location';
    const TYPE_OTHER = 'other';

    public static function typeOptions(): array
    {
        return [
            self::TYPE_DOCUMENTATION => 'Dokumentasi',
            self::TYPE_EVIDENCE => 'Bukti',
            self::TYPE_LOCATION => 'Lokasi',
            self::TYPE_OTHER => 'Lainnya',
        ];
    }

    //----------------------------------------------------------------------
    // Relations
    //----------------------------------------------------------------------

    public function visitRecord(): BelongsTo
    {
        return $this->belongsTo(VisitRecord::class, 'nx_visit_record_id');
    }

    //----------------------------------------------------------------------
    // Helpers
    //----------------------------------------------------------------------

    /**
     * URL publik foto — dipakai di blade, Filament, dan API response.
     */
    public function url(): string
    {
        return Storage::url($this->file_path);
    }

    public function hasCoordinates(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    public function googleMapsUrl(): ?string
    {
        if (!$this->hasCoordinates())
            return null;

        return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
    }

    //----------------------------------------------------------------------
    // Lifecycle Hooks
    //----------------------------------------------------------------------

    protected static function booted(): void
    {
        // Set taken_at ke sekarang jika tidak dikirim dari client
        static::creating(function (VisitPhoto $photo) {
            if (!$photo->taken_at) {
                $photo->taken_at = now();
            }
        });

        // Hapus file fisik dari storage saat record foto dihapus
        static::deleted(function (VisitPhoto $photo) {
            if (Storage::exists($photo->file_path)) {
                Storage::delete($photo->file_path);
            }
        });
    }
}