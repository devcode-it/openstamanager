<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    public function forgotPassword(Request $request): Response|JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $response = Password::broker()->sendResetLink($request->input('email'));

        return $response === Password::RESET_LINK_SENT
            ? response()->noContent()
            : \response()->json(['errors' => ['email' => [__($response)]]], Response::HTTP_BAD_REQUEST);
    }

    public function resetPassword(Request $request): JsonResponse|Response
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email|exists:users,email',
            'password' => ['required|string|confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $response = Password::broker()->reset(
            $request->only(['email', 'password', 'password_confirmation', 'token']),
            function (User $user, string $password) {
                $user->password = Hash::make($password);
                $user->setRememberToken(Str::random(60));
                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $response === Password::PASSWORD_RESET
            ? response()->noContent()
            : response()->json(['errors' => ['email' => [__($response)]]], Response::HTTP_BAD_REQUEST);
    }
}
