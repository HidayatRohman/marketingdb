<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MitraLabelHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'mitra_id',
        'old_label_id',
        'new_label_id',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function mitra()
    {
        return $this->belongsTo(Mitra::class);
    }

    public function oldLabel()
    {
        return $this->belongsTo(Label::class, 'old_label_id');
    }

    public function newLabel()
    {
        return $this->belongsTo(Label::class, 'new_label_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
