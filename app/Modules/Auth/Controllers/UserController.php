<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Requests\UserRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        return view('auth::users', [
            'users' => User::where('tenant_id', $request->user()->tenant_id)->orderBy('name')->paginate(20),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = new User($request->safe()->only(['name', 'email', 'password']));
        $user->forceFill(['tenant_id' => $request->user()->tenant_id, 'role' => $request->validated('role')])->save();

        return to_route('usuarios.index')->with('success', 'Usuário cadastrado para esta fazenda.');
    }
}
