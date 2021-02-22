<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Models\Log;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'username' => 'required|string',
            'password' => 'required|string',
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     *
     * @return void
     */
    public function authenticate()
    {
        $this->ensureIsNotRateLimited();

        if (!Auth::attempt($this->only('username', 'password'), $this->filled('remember'))) {
            RateLimiter::hit($this->throttleKey());

            // Log di accesso
            $this->registerLog('failed');

            throw ValidationException::withMessages(['username' => tr('auth.failed')]);
        }

        // Informazioni sull'utente
        $user = auth()->user();
        if (empty($user->enabled)) {
            $status = 'disabled';
        } elseif ($user->getFirstAvailableModule() === null) {
            $status = 'unauthorized';
        } else {
            $status = 'success';
        }
        $this->registerLog($status);

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     *
     * @return void
     */
    public function ensureIsNotRateLimited()
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages(['username' => trans('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)])]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     *
     * @return string
     */
    public function throttleKey()
    {
        return Str::lower($this->input('username')).'|'.$this->ip();
    }

    /**
     * Registra i log di accesso per il tentativo corrente.
     *
     * @param string $status
     */
    protected function registerLog($status)
    {
        $user = auth()->user();

        // Log di accesso
        $log = new Log();

        $log->username = $this->input('username');
        $log->ip = $this->ip();
        $log->id_utente = $user ? $user->id : null;
        $log->setStatus($status);

        $log->save();
    }
}
