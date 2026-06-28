<?php

namespace App\Services\Api;

use App\Models\User;
use App\Notifications\OTPNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OTPService 
{
    public function sendOTP(object $user)
    {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = Carbon::now()->addMinutes(5);

        $user->update([ 'otp' => $otp,
                        'expires_at' => $expiresAt
                    ]);

        $user->notify(new OTPNotification($otp));
    }
    //************************************************* */
    public function verifyOtp(array $request)
    {
        $user = User::where('email', $request['email'])->first();

        if (!$user) {
            return 'UserNotFound!';
        }
    //     dd( $request['otp']);
    //    Log::info("DEBUG: User DB OTP: " . $user->otp);
    // Log::info("DEBUG: User Request OTP: " . $request['otp']);
    //     if ((string)$user->otp !==(string) $request['otp']) {
    //         return 'NotValidOTP';
    //     }

        if ($user->expires_at->isPast()) {
            return 'OTPHasExpired';
        }

        $user->update([
            'otp' => null,
            'expires_at' => null,
        ]);

        if($user->otp == null){
            'OTP used';
        }

        return 'CorrectOTP';
    }
    //************************************************* */
    public function resendOtp(array $request)
    {
        $user = User::where('email', $request['email'])->first();

        if (!$user) {
            return 'UserNotFound';
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = Carbon::now()->addMinutes(5);

        $user->update([
            'otp' => $otp,
            'expires_at' => $expiresAt,
        ]);

        $user->notify(new OTPNotification($otp));

        return 'OTPResentSuccessfully';
    }
}