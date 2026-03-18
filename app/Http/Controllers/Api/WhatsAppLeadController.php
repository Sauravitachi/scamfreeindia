<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ScamLead;
use Illuminate\Support\Facades\Log;

class WhatsAppLeadController extends Controller
{
    public function store(Request $request)
    {
        Log::info('WhatsApp Lead:', $request->all());

        $request->validate([
            'phone' => 'required|string',
            'message' => 'nullable|string',
            'name' => 'nullable|string'
        ]);

        try {
            $phone = preg_replace('/\D/', '', $request->phone);

            // 🔥 DUPLICATE CHECK
            $existing = ScamLead::where('phone_number', $phone)->first();

            if ($existing) {
                $existing->increment('count');
                $existing->update([
                    'is_duplicate' => 1,
                    'customer_description' => $request->message
                ]);

                return response()->json([
                    'status' => 'duplicate updated'
                ]);
            }

            // CREATE NEW
            ScamLead::create([
                'phone_number' => $phone,
                'customer_description' => $request->message,
                'name' => $request->name,
                'country_code' => 'IN',
                'dial_code' => '+91',
                'scam_source_id' => 2, // WhatsApp
            ]);

            return response()->json([
                'status' => 'success'
            ]);

        } catch (\Exception $e) {
            Log::error('WhatsApp Error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error'
            ], 500);
        }
    }
}