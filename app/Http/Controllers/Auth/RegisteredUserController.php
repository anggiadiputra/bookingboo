<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Caregiver;
use App\Models\Customer;
use App\Models\User;
use App\Rules\Turnstile;
use App\Services\AuditLogService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'max:20'],
            'role' => ['required', 'in:customer,caregiver'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'cf-turnstile-response' => config('turnstile.enabled') ? ['required', new Turnstile] : ['nullable'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'status' => 'active',
            'password' => Hash::make($request->password),
        ]);

        // Buat profil sesuai role
        if ($request->role === 'caregiver') {
            Caregiver::create([
                'user_id' => $user->id,
                'verification_status' => 'pending',
            ]);
        } else {
            Customer::create([
                'user_id' => $user->id,
            ]);
        }

        event(new Registered($user));

        app(AuditLogService::class)->logSystem('auth.register',
            "Registrasi akun baru {$user->name} (role: {$user->role}).",
            $user,
            ['role' => $user->role, 'ip' => $request->ip()]);

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
