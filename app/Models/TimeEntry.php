<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
    protected $fillable = [
        'case_id', 
        'work_date', 
        'hours', 
        'activity', 
        'description'
    ];

    protected $casts = [
        'work_date' => 'date',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }
}