<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $guarded = [];

    public const CATEGORIES = ['Rent', 'Utilities', 'Salaries', 'Transport', 'Marketing', 'Maintenance', 'Supplies', 'Other'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'amount' => 'decimal:2'];
    }

    public function branch() { return $this->belongsTo(Branch::class); }
    public function user() { return $this->belongsTo(User::class); }

    /** Expenses whose dates touch the period. */
    public function scopeOverlapping(Builder $q, string $from, string $to): Builder
    {
        return $q->whereDate('start_date', '<=', $to)->whereDate('end_date', '>=', $from);
    }

    /** The part of this expense that falls inside [from, to]; a range expense is spread evenly per day. */
    public function allocatedBetween(string $from, string $to): float
    {
        $start = $this->start_date->copy()->startOfDay();
        $end = $this->end_date->copy()->startOfDay();
        $a = max($start->timestamp, strtotime($from));
        $b = min($end->timestamp, strtotime($to));
        if ($b < $a) {
            return 0.0;
        }
        $days = $start->diffInDays($end) + 1;
        $inside = (int) round(($b - $a) / 86400) + 1;
        return round((float) $this->amount * $inside / $days, 2);
    }
}
