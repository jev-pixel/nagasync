<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class MarketPrice extends Model
{
    use Auditable;

    public const CATEGORIES = ['rice', 'meat', 'poultry', 'fish', 'vegetables', 'fruits', 'other'];

    protected $fillable = [
        'market', 'commodity', 'category', 'unit', 'price', 'srp', 'recorded_on', 'recorded_by',
    ];

    protected $casts = ['recorded_on' => 'date'];

    public function recorder() { return $this->belongsTo(User::class, 'recorded_by'); }
}