<?php

namespace App\Http\Controllers\Api;

use App\Constants;
use App\Exceptions\AuthenticationError;
use App\Exceptions\AuthorizationError;
use App\Exceptions\InvariantError;
use App\Http\Controllers\Controller;
use App\Mail\ResetPassword;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users',
        ], [
            'email.exists' => "The email address you entered could not be found",
        ]);

        $user = User::where('email', $request->email)->first();

        $token = Password::createToken($user);

        $user = User::where('email', $request->email)->first();

        Mail::to($user->email)->queue(new ResetPassword($user, $token));

        return response()->json([
            "code" => 200,
            "status" => "success",
            "message" => "Password reset link has been sent to your email",
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        switch ($status) {
            case Password::PASSWORD_RESET:
                return response()->json([
                    "code" => 200,
                    "status" => "success",
                    "message" => "Password reset successfully",
                ]);
            case Password::INVALID_TOKEN:
                throw new InvariantError("Password reset link is invalid or expired");
            case Password::INVALID_USER:
                throw new InvariantError("User not found");
            case Password::RESET_THROTTLED:
                throw new InvariantError("Password reset has been throttled");
        }
    }
}
