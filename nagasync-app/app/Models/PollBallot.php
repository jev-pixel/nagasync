<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollBallot extends Model
{
    public $timestamps = false;
    protected $fillable = ['poll_id', 'poll_option_id'];
}