<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiLeadHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaksi_id',
        'old_lead_brand_id',
        'new_lead_brand_id',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class);
    }

    public function oldLeadBrand()
    {
        return $this->belongsTo(Brand::class, 'old_lead_brand_id');
    }

    public function newLeadBrand()
    {
        return $this->belongsTo(Brand::class, 'new_lead_brand_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
