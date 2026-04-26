<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseModel extends Model
{
    // Important: Specify the table name if it's just 'cases'
    protected $table = 'cases';

    protected $fillable = [
        'client_id', 
        'case_name', 
        'description', 
        'status', 
        'priority', 
        'progress'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class, 'case_id');
    }
}