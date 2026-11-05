<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];

    protected $casts = ["is_active" => "boolean"];
    public function category() { return $this->belongsTo(Category::class); }
    public function stocks() { return $this->hasMany(Stock::class); }
}
