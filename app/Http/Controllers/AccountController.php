<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index() {
        $accounts = Account::all();

        $accountsForVue = $accounts->map(function ($account) {
            return [
                'id' => $account->id,
                'header' => $account->email,
                'type' => 'account',
                'status' => "-",
                'target' => "-",
                'limit' => "-",
                'reviewer' => $account->nome,
            ];
        });

        return view('auth.pages.cerca-dati', compact('accountsForVue'));
    }

    public function create() {
        return view('pagine.invia-dati');
    }

    public function store(Request $request) {
        
        $validati = $request->validate([
            'nome' => 'required|string|max:255',
            'email' => 'required|email',
        ]);

        Account::create($validati);
        
        return redirect()->route('pagine.home')->with('successo', 'Dati salvati!');
    }

    public function edit($id) {
        
        $account = Account::findOrFail($id);
        
        return view('pagine.modifica-dati', compact('account'));
    }

    public function update(Request $request, $id) {
        
        $account = Account::findOrFail($id);
        
        $dati = $request->validate([
            'nome' => 'required|string|max:255',
            'email' => 'required|email',
        ]);
        
        $account->update($dati);
        
        return redirect()->route('pagine.cerca-dati')->with('successo', 'Aggiornato!');
    }

    public function destroy($id){
        
        $account = Account::findOrFail($id);
        
        $account->delete(); // Questo attiverà automaticamente la Soft Delete

        return redirect()->back()->with('success', 'Account spostato nel cestino!');
    }
}