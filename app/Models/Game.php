<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'state',
        'combination',
        'plays',
        'time',
        'attempts',
        'evaluation'
    ];

    protected $casts = [
        'plays' => 'array',
        'combination' => 'array',
    ];

    public function player(){
        return $this->belongsTo(User::class);
    }

    public function ranking(){
        return $this->belongsTo(User::class);
    }
}