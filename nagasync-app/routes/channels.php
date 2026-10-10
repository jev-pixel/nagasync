<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
Broadcast::channel('dispatch', fn (User $user) => $user->hasRole('mdrrmo', 'admin'));
Broadcast::channel('citizen.{id}', fn (User $user, int $id) => $user->id === $id);
