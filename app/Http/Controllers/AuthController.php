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
use JetBrains\PhpStorm\ArrayShape;

class AuthController extends Controller
{
    /**
     * Handle an authentication attempt.
     */
    public function authenticate(Request $request): JsonResponse|Response
    {
        try {
            $request->validate($this->rules($request));
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $credentials = $request->only(['username', 'password']);

        if (filter_var($request->get('username'), FILTER_VALIDATE_EMAIL)) {
            $credentials['email'] = $credentials['username'];
            unset($credentials['username']);
        }

        if (auth()->attempt($credentials, $request->get('remember'))) {
            $request->session()->regenerate();

            return response()->noContent();
        }

        return response()->json([
            'errors' => ['invalid_credentials' => __('Le credenziali non sono valide.')],
        ], Response::HTTP_BAD_REQUEST);
    }

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

    /**
     * Log the user out of the application.
     *
     * @noinspection RepetitiveMethodCallsInspection
     */
    public function logout(Request $request): Response
    {
        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    #[ArrayShape(['username' => 'string', 'password' => 'string', 'remember' => 'string'])]
    private function rules(Request $request): array
    {
        $additional_validation = '';
        $db_field = 'username';
        if (filter_var($request->input('username'), FILTER_VALIDATE_EMAIL)) {
            $additional_validation = '|email';
            $db_field = 'email';
        }

        return [
            'username' => "required|string|exists:users,$db_field|$additional_validation",
            'password' => 'required|string',
            'remember' => 'boolean',
        ];
    }
}
