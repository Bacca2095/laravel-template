<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Passkey extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'credential_id',
        'public_key',
        'credential_hash',
        'counter',
        'transports',
        'device_type',
        'backed_up',
    ];

    protected $casts = [
        'counter' => 'integer',
        'backed_up' => 'boolean',
        'transports' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
