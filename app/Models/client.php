<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model 
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'slug',
        'industry',
        'city',
        'zip',
        'status',
        'user_id',
        'team_id',
        'created_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function client_properties()
    {
        return $this->hasMany(Client_propertiesModel::class, 'user_id'); // assuming 'client_id' is the foreign key in the budget table
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    // Who created this client record
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}