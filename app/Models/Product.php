<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];

    protected $casts = ["is_active" => "boolean"];

    protected static function booted(): void
    {
        // Every new product automatically gets a unique, scannable EAN-13 barcode (unless one was typed in).
        static::created(function (Product $p) {
            if (! $p->barcode) {
                $p->barcode = static::ean13($p->id);
                $p->saveQuietly();
            }
        });
    }

    /** Internal-use EAN-13: "2" + 11-digit product id + check digit. */
    public static function ean13(int $id): string
    {
        $base = '2'.str_pad((string) $id, 11, '0', STR_PAD_LEFT);
        $sum = 0;
        foreach (str_split($base) as $i => $d) {
            $sum += (int) $d * ($i % 2 ? 3 : 1);
        }
        return $base.((10 - $sum % 10) % 10);
    }

    public function category() { return $this->belongsTo(Category::class); }
    public function stocks() { return $this->hasMany(Stock::class); }
}
