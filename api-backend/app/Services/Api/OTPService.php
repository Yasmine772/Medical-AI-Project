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
                        'expires_at' => $expiresAt ,
                        'otp_verified_at' => now()
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

        if($user->otp != $request['otp']) {
            return 'NotValidOTP';
        }

        if ($user->expires_at->isPast()) {
            return 'OTPHasExpired';
        }

        $user->update([
            'otp' => null,
            'expires_at' => null,
            'otp_verified_at' => now(),
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
            'otp_verified_at' => null
        ]);

        $user->notify(new OTPNotification($otp));

        return 'OTPResentSuccessfully';
    }
}