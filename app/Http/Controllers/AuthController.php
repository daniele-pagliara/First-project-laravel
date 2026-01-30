<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showGuestHome()
    {
        return view('guest.pages.home');
    }

    public function showLogin()
    {
        return view('guest.pages.login');
    }

    public function login(Request $request)
    {
        // Generiamo la chiave univoca
        $throttleKey = Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());

        // 1. Controlliamo se l'utente ha superato i 3 tentativi
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {

            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => "Troppi tentativi di accesso. Riprova tra $seconds secondi."
            ])->withInput($request->only('email'));
        }

        // 2. Tentiamo il login vero e proprio
        if (Auth::attempt($request->only('email', 'password'), $request->has('remember'))) {

            // Se il login riesce, resettiamo il contatore dei tentativi
            RateLimiter::clear($throttleKey);

            $request->session()->regenerate();
            $idLogged = Auth::id();
            return redirect()->route('pagine.home');
        }

        // 3. Se il login fallisce, incrementiamo il contatore dei fallimenti
        RateLimiter::hit($throttleKey, 60);

        return back()->withErrors([
            'email' => 'Le credenziali inserite non sono corrette.',
        ])->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('successo', 'Disconnesso!');
    }

    public function showRegistrati()
    {
        return view('guest.pages.register');
    }

    public function registrati(Request $request)
    {
        $dati = $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'cf' => 'required|string|size:16|unique:users,cf',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20|unique:users,phone',
        ]);

        $utente = User::create([
            'name' => $dati['name'],
            'surname' => $dati['surname'],
            'cf' => $dati['cf'],
            'email' => $dati['email'],
            'password' => Hash::make($dati['password']),
            'address' => $dati['address'] ?? null,
            'phone' => $dati['phone'] ?? null,
        ]);

        Auth::login($utente);

        return redirect()->route('pagine.home');
    }
}
