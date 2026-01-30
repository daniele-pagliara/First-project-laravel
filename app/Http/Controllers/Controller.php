<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

abstract class Controller
{
    public function reset(Request $request)
    {
        $status = Password::reset(
            
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                
                $user->forceFill([
                'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60))->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}
