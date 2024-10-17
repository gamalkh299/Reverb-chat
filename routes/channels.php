<?php

use Illuminate\Support\Facades\Broadcast;


Route::post('/broadcast/auth', function (Request $request) {
    return Broadcast::auth($request);
});
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.{id}', function ($user, $receiver_id) {
    return (int) $user->id === (int) $receiver_id;
});
