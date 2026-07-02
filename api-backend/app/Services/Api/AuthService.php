<?php

namespace app\Services\Api;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AuthService
{
    public function register(array $data)
    {
        $user = User::create([
            'full_name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return $user;
    }

    // ///////////////////////////////////////////////////////////////////////////////////
    public function login(array $data)
    {
        $credentials = ['email' => $data['email'],
            'password' => $data['password']];

        if (! Auth::attempt($credentials)) {
            return 'unauthorized';
        }

        $user = User::where('email', $data['email'])->first();

        $user->tokens()->delete();

        $accessTokenExpiresAt = Carbon::now()->addDays(1);

        $accessToken = $user->createToken('access_token', ['*'], $accessTokenExpiresAt)->plainTextToken;

        return [
            'user' => $user,
            'access_token' => $accessToken,
            'access_token_expires_at' => '1 day',
            'token_type' => 'Bearer',
        ];
    }

    /**
     * Get the profile details of the given user.
     *
     * @return User
     */
    public function getUserProfile(User $user)
    {
        return $user->load('profile');
    }

    /**
     * Update the profile details of the given user.
     *
     * @return User
     */
    public function updateProfile(User $user, array $data, $avatarFile = null)
    {
        // dd($avatarFile);
        if ($avatarFile instanceof UploadedFile) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = $avatarFile->store('avatars', 'public');
        }

        $user->update([
            'full_name' => $data['full_name'] ?? $user->full_name,
            'avatar' => $user->avatar ?? $user->avatar,
        ]);
        $medicalData = array_intersect_key($data, array_flip([
            'birth_date', 'gender', 'is_smoker', 'has_diabetes',
            'has_hypertension', 'is_pregnant', 'activity_level',
        ]));

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            $medicalData
        );

        return $user->fresh()->load('profile');
    }
}
