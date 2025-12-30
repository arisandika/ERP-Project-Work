<?php

namespace App\Models\Finance;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialRecord extends Model
{
    use SoftDeletes;

    protected $table = 'nx_financial_records';


    protected $fillable = [
        'transaction_date',
        'description',
        'type',
        'amount',
        'category',
    ];
}
