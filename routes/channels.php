<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('warehouse.{id}', function ($user, $id) {
    return $user !== null; // Authenticated staff and warehouse managers
});

Broadcast::channel('batch.{id}', function ($user, $id) {
    return $user !== null;
});

Broadcast::channel('admin.alerts', function ($user) {
    return $user !== null && ($user->hasRole('super_admin') || $user->hasPermission('audit:read'));
});
