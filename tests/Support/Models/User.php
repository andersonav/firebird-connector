<?php

namespace AndersonAv\Firebird\Tests\Support\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing = false;

    protected $guarded = [];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
