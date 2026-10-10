<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollParticipation extends Model
{
    // The table has created_at only, so tell Eloquent not to write updated_at
    const UPDATED_AT = null;

    protected $fillable = ['poll_id', 'user_id'];
}