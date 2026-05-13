<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{userId}.wallet', function ($user, int $userId) {
    return (int) $user->id === $userId;
});

Broadcast::channel('user.{userId}.bets', function ($user, int $userId) {
    return (int) $user->id === $userId;
});

Broadcast::channel('admin.dashboard', function ($user) {
    return $user->hasRole('admin');
});
