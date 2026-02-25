<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DealStage extends Model
{
    use SoftDeletes;
    
    protected $table = 'nx_deal_stages';

    protected $fillable = [
        'name',
        'order',
        'probability'
    ];

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class, 'nx_deal_stage_id');
    }
}