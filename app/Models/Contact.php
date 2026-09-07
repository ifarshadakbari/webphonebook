<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = [
        'user_id',
        'social_title',
        'first_name',
        'last_name',
        'mobile',
        'photo',
    ];

    public function phones()
    {
        return $this->hasMany(ContactPhone::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
