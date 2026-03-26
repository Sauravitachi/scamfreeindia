<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WhatsAppLeadController;
use App\Http\Controllers\Api\ScamLeadController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/whatsapp/lead', [WhatsAppLeadController::class, 'store']);
Route::post('/scam/lead', [ScamLeadController::class, 'store']);