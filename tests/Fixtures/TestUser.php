<?php

namespace Veda\LaravelClient\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class TestUser extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'users';

    protected $fillable = ['name', 'email', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
