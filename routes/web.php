<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\TelegramController;


Route::get('/', function () {
    return view('welcome');
});

Route::post('/your-webhook-url/{chatId}', [TelegramController::class, 'handle']);

