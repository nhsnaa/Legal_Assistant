<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'name', 
        'email', 
        'phone', 
        'status'
    ];

    public function cases(): HasMany
    {
        // Links back to CaseModel
        return $this->hasMany(CaseModel::class, 'client_id');
    }
}