<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $guarded = [];

    public function branch() { return $this->belongsTo(Branch::class); }
    public function sales() { return $this->hasMany(Sale::class); }
}
