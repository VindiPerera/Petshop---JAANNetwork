<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $guarded = [];

    public function scopeShops($q) { return $q->where("type", "shop"); }
    public static function warehouse(): self { return static::where("type", "warehouse")->firstOrFail(); }
    public function users() { return $this->hasMany(User::class); }
    public function stocks() { return $this->hasMany(Stock::class); }
    public function sales() { return $this->hasMany(Sale::class); }
    public function customers() { return $this->hasMany(Customer::class); }
    public function getLogoUrlAttribute(): string { return asset($this->logo_path ?: 'images/logo.png'); }
}
