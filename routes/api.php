<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WhatsAppLeadController;
use App\Http\Controllers\Api\ScamStatusReportController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/scam-status-report', [ScamStatusReportController::class, 'index']);
Route::post('/whatsapp/lead', [WhatsAppLeadController::class, 'store']);
