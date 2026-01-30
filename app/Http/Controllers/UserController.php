<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {

        $users = User::all();

        $usersForVue = $users->map(function ($user) {
            return [
                //campi per la tabella
                'id' => $user->id,
                'header' => $user->email,

                //campi grezzi per la modale
                'first_name' => $user->name,
                'last_name' => $user->surname,
                'email' => $user->email,
                'password' => '', // Lascia vuoto per motivi di sicurezza
                'address' => $user->address ?? '',
                'phone' => $user->phone ?? '',

                //campi per la tabella
                'type' => $user->cf,
                'status' => $user->disabled ? 'Disabilitato' : 'Attivo',
                'target' => $user->phone,
                'limit' => "-",
                'reviewer' => $user->name . ' ' . $user->surname,
            ];
        });
        return view('auth.pages.cerca-dati', compact('usersForVue'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'type' => 'nullable|string|max:16',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'surname' => $validated['surname'] ?? '',
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'cf' => $validated['type'] ?? null,
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'disabled' => false,
        ]);

        return response()->json(['success' => true, 'user' => $user], 201);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|min:8|confirmed',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update($validated);

        return response()->json(['success' => true]);
    }


    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete(); // Soft Delete
        return response()->json(['success' => true]);
    }

    public function disable($id)
    {
        $user = User::findOrFail($id);
        $user->disabled = true;
        $user->save();
        return response()->json(['success' => true]);
    }

    public function enable($id)
    {
        $user = User::findOrFail($id);
        $user->disabled = false;
        $user->save();
        return response()->json(['success' => true]);
    }
}
