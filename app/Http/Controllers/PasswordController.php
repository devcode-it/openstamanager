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
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
//    public function forgotPassword(Request $request): Response|JsonResponse
//    {
//        try {
//            $request->validate([
//                'email' => 'required|email|exists:users,email',
//            ]);
//        } catch (ValidationException $e) {
//            return response()->json([
//                'message' => $e->getMessage(),
//                'errors' => $e->errors(),
//            ], Response::HTTP_UNPROCESSABLE_ENTITY);
//        }
//
//        $response = Password::broker()->sendResetLink($request->input('email'));
//
//        return $response === Password::RESET_LINK_SENT
//            ? response()->noContent()
//            : \response()->json(['message' => __($response)], Response::HTTP_UNPROCESSABLE_ENTITY);
//    }
//
//    public function resetPassword(Request $request): JsonResponse|Response
//    {
//        try {
//            $request->validate([
//                'token' => 'required|string',
//                'email' => 'required|email|exists:users,email',
//                'password' => ['required|string|confirmed', \Illuminate\Validation\Rules\Password::defaults()],
//            ]);
//        } catch (ValidationException $e) {
//            return response()->json([
//                'message' => $e->getMessage(),
//                'errors' => $e->errors(),
//            ], Response::HTTP_UNPROCESSABLE_ENTITY);
//        }
//
//        $response = Password::broker()->reset(
//            $request->only(['email', 'password', 'password_confirmation', 'token']),
//            static function (User $user, string $password): void {
//                $user->password = Hash::make($password);
//                $user->setRememberToken(Str::random(60));
//                $user->save();
//                event(new PasswordReset($user));
//            }
//        );
//
//        return $response === Password::PASSWORD_RESET
//            ? response()->noContent()
//            : response()->json(['message' => __($response)], Response::HTTP_UNPROCESSABLE_ENTITY);
//    }
}
