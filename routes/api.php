<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/messages/{user}', function (App\Models\User $user , Request $request) {
    $result = \App\Models\ChatMessage::query()
        ->where(function ($query) use ($user, $request) {
            $query->where('receiver_id', $user->id)
                ->where('sender_id', $request->user()->id);
        })
        ->orWhere(function ($query) use ($user, $request) {
            $query->where('receiver_id', $request->user()->id)
                ->where('sender_id', $user->id);
        })
        ->get();

    return response()->json(['response' => 'Messages fetched', 'status' => 200 ,'data' => $result]);

})->middleware('auth:sanctum');


Route::post('/messages/{user}', function (App\Models\User $user , Request $request) {
    $request->validate([
        'message' => 'required|string'
    ]);
    $message=\App\Models\ChatMessage::query()
        ->create([
            'receiver_id' => $user->id,
            'sender_id' => $request->user()->id,
            'message' => $request->input('message')
        ]);

    return response()->json(['response' => 'Message sent', 'status' => 200 ,'data' => $message]);

})->middleware('auth:sanctum');


