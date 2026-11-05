<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    protected $guarded = [];

    public function from() { return $this->belongsTo(Branch::class, "from_branch_id"); }
    public function to() { return $this->belongsTo(Branch::class, "to_branch_id"); }
    public function user() { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(TransferItem::class); }
}
