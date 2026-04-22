<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hearing extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'case_name',
        'hearing_time',
        'status',
        'notes',
    ];

    protected $casts = [
        'hearing_time' => 'datetime',
    ];
}

