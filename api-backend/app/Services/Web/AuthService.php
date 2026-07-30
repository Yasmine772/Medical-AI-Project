<?php

namespace App\Services\Web;

use App\Http\Resources\doctorResource;
use App\Jobs\UploadDoctorProfileFiles;
use App\Models\Doctor;
use App\Models\User;
use App\Services\Api\OTPService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AuthService 
{
    protected OTPService $otpService;
    public function __construct(OTPService $otpService)
    {
        $this->otpService = $otpService;
    }
    /////////////////////////////////////////////
    public function login(array $data , string $role)
    {
        $credentials = ['email' => $data['email'],'password' => $data['password']];

        if (!Auth::attempt($credentials)) {
            return 'unauthorized';
        }

        $user = User::where('email', $data['email'])->first();

        if (!$user->hasRole($role)) {
            if($role == 'admin'){
                return 'adminOnly';
            }
            return 'doctorOnly';
        }

        if (is_null($user->email_verified_at)) {
            $this->otpService->sendOTP($user);
            return 'EmailNotVerified';
        }

        $user->tokens()->delete();
        $accessTokenExpiresAt = Carbon::now()->addDays(1);
        $accessToken = $user->createToken('access_token', [$role], $accessTokenExpiresAt)->plainTextToken;

        return [
            'user' => ['email' => $user->email],
            'access_token' =>  $accessToken,
        ];
    }
    //**************************************************** */
    public function verifyOtp(array $request ,string $role)
    {
        $user = User::where('email', $request['email'])->first();

        if($user->email_verified_at === null )
        {
            $result = $this->otpService->verifyOTP($request);

            if ($result === 'CorrectOTP') {
                $accessTokenExpiresAt = Carbon::now()->addDays(1);
                $token = $user->createToken('access_token', [$role], $accessTokenExpiresAt)->plainTextToken;

                $data = ['email' => $request['email'], 'token' => $token];
                return  $data;
            }
            return $result;
        }
        return 'emailVerified';
    }
    //**************************************************** */
    public function viewProfile() 
    {
        $doctor = Doctor::where('user_id', auth()->user()->id)->first();
        return $doctor ? new DoctorResource($doctor) : null;
    }
    //************************************************************************ */
    public function updateProfile($data) 
    {
        $user = auth()->user();
        $doctor = Doctor::where('user_id', $user->id)->first();

        if (isset($data['full_name'])) {
            $user->full_name = $data['full_name'];
        }
        if (isset($data['email'])) {
            $user->email = $data['email'];
        }
        $user->save();

        $doctor->fill(collect($data)->only([
            'phone',
            'specialization',
            'years_of_experience',
            'clinic_phone',
            'clinic_address',
            'license_number',
            'biography'
        ])->toArray());

        $doctor->save();

        $tempFiles = [];

        if (isset($data['license_file'])) {
            $tempFiles['license_file'] = $data['license_file']->store('temp/licenses', 'public');
        }

        if (isset($data['cv_file'])) {
            $tempFiles['cv_file'] = $data['cv_file']->store('temp/cvs', 'public');
        }

        if (isset($data['photo'])) {
            $tempFiles['photo'] = $data['photo']->store('temp/photos', 'public');
        }

        if (!empty($tempFiles)) {
            UploadDoctorProfileFiles::dispatch($doctor->id, $tempFiles)->onQueue('default');
        }

        return new doctorResource($doctor);
    }
}