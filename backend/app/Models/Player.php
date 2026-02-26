<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    protected $table = 'players';
    
    protected $fillable = [
        'player_id',
        'role',
        'balance'
    ];

    protected $hidden = [
        'id'
    ];

    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}
