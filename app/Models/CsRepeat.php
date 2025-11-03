<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CsRepeat extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_pelanggan',
        'no_tlp',
        'product_id',
        'tanggal',
        'chat',
        'kota',
        'provinsi',
        'transaksi',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}