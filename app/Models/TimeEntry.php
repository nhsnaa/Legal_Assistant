<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_id',
        'work_date',
        'hours',
        'activity',
        'billable',
        'description',
    ];

    protected $casts = [
        'work_date' => 'date',
        'hours' => 'decimal:2',
        'billable' => 'boolean',
    ];

    public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }
}
