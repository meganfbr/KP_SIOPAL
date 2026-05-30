<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanPerbaikanLog extends Model
{
    public $timestamps = false; // Only created_at, managed by DB default

    protected $table = 'laporan_perbaikan_logs';

    protected $fillable = [
        'laporan_perbaikan_id',
        'user_id',
        'action',
        'field_changed',
        'old_value',
        'new_value',
        'description',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(LaporanPerbaikan::class, 'laporan_perbaikan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
