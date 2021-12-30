<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use JetBrains\PhpStorm\ArrayShape;

class AuthController extends Controller
{
    /**
     * Handle an authentication attempt.
     */
    public function login(Request $request): JsonResponse|Response
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

        if (auth()->attempt($credentials, $request->get('remember') === 'on')) {
            $request->session()->regenerate();
            if ($request->hasSession()) {
                $request->session()->put('auth.password_confirmed_at', time());
            }

            return response()->noContent();
        }

        return response()->json([
            'errors' => ['invalid_credentials' => __('Le credenziali non sono valide.')],
        ], Response::HTTP_BAD_REQUEST);
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
            'remember' => 'string|in:on',
        ];
    }
}
