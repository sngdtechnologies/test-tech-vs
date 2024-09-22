<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobMetadata extends Model
{
    use HasFactory;

    protected $fillable = [
        "job_name", 
        "number_execution",
        "last_completed_at"
    ];
}
