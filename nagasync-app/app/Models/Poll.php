<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Poll extends Model
{
    use Auditable;

    protected $fillable = ['title', 'description', 'barangay_id', 'opens_at', 'closes_at', 'is_published', 'created_by'];

    protected $casts = ['opens_at' => 'datetime', 'closes_at' => 'datetime', 'is_published' => 'boolean'];

    public function options() { return $this->hasMany(PollOption::class)->orderBy('position'); }
    public function participations() { return $this->hasMany(PollParticipation::class); }
    public function barangay() { return $this->belongsTo(Barangay::class); }
}