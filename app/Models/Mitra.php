<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mitra extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'no_telp',
        'produk',
        'chat',
        'kota',
        'provinsi',
        'transaksi',
        'komentar',
    ];

    protected $casts = [
        'transaksi' => 'decimal:2',
    ];
}
