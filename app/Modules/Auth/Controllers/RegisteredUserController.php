<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Models\Tenant;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Requests\RegisterRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth::register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $tenant = Tenant::create(['nome' => $request->validated('nome_fazenda')]);
            $user = new User($request->safe()->only(['name', 'email', 'password']));
            $user->forceFill(['tenant_id' => $tenant->id, 'role' => 'Admin'])->save();

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return to_route('dashboard')->with('success', 'Conta criada! Bem-vindo ao GPec.');
    }
}
