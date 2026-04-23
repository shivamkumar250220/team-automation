<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Industry extends Model
{
    use HasFactory;
    protected $table = 'industry';

    protected $fillable = [
        'name',
        'created_at',
        'updated_at',
    ];
}
