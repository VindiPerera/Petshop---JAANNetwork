<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [];

    public const SOURCES = ['website' => 'Website', 'facebook' => 'Facebook', 'instagram' => 'Instagram', 'whatsapp' => 'WhatsApp', 'phone' => 'Phone call', 'other' => 'Other'];
    public const STATUSES = ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'shipped' => 'Shipped', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
    public const STATUS_COLORS = ['pending' => 'warning', 'confirmed' => 'info', 'shipped' => 'primary', 'completed' => 'success', 'cancelled' => 'secondary'];

    public function branch() { return $this->belongsTo(Branch::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(OrderItem::class); }
    public function sale() { return $this->belongsTo(Sale::class); }

    /** An order can be billed or cancelled until it is completed/cancelled. */
    public function isOpen(): bool { return ! in_array($this->status, ['completed', 'cancelled'], true); }
}
