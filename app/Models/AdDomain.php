<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdDomain extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'hosts',
        'base_dn',
        'username',
        'password',
        'port',
        'use_ssl',
        'use_tls',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
        ];
    }
}
