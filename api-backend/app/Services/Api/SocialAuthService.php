<?php

namespace App\Services\Api;

use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialUser;
use Illuminate\Support\Str;

class SocialAuthService
{
    public function findOrCreateUser(SocialUser $socialUser): User
    {
        $user = User::where('google_id', $socialUser->getId())->first();

        if ($user) {
            return $user;
        }

        $user = User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            $user->update([
                'google_id' => $socialUser->getId(),
                'avatar'    => $socialUser->getAvatar(),
            ]);
            return $user;
        }

        return User::create([
            'full_name'      => $socialUser->getName() ?? explode('@', $socialUser->getEmail())[0],
            'email'     => $socialUser->getEmail(),
            'google_id' => $socialUser->getId(),
            'avatar'    => $socialUser->getAvatar(),
            'password'  => bcrypt(Str::random(24)), 
        ]);
    }
}