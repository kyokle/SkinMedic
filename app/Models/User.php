<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // ✅ Your actual primary key
    protected $primaryKey = 'user_id';

    // ✅ Your actual table name
    protected $table = 'users';

    // ✅ Your actual fillable columns
    protected $fillable = [
        'firstName',
        'lastName',
        'email',
        'password_hash',
        'gender',
        'address',
        'phone_no',
        'role',
        'profile_image',
        'is_regular',
        'preferred_time',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_regular'        => 'boolean',
        ];
    }

    // ✅ Laravel looks for 'password' by default — point it to your column
    public function getAuthPassword()
    {
        return $this->password_hash;
    }
}