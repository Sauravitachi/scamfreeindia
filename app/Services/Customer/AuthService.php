<?php

namespace App\Services\Customer;

use App\Http\Requests\Customer\Auth\SendOtpRequest;
use App\Jobs\Sms\SendOtpToCustomer;
use App\Models\Customer;
use App\Services\Service;

class AuthService extends Service
{
    public function sendOtp(SendOtpRequest $request)
    {
        $customer = Customer::wherePhoneDetails($request->phone_number, 'in')->first(['id']);
        $userAgent = $request->userAgent();
        $ipAddress = $request->ip();
        $sessionId = $request->session()->getId();
        SendOtpToCustomer::dispatchSync($customer->id, $sessionId, $userAgent, $ipAddress);
    }
}
