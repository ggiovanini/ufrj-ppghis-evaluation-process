<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ImpersonationController extends Controller
{
    public function impersonate(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('users.manage');

        if ($user->id === Auth::id()) {
            return back();
        }

        // Armazena o ID do usuário original na sessão
        $request->session()->put('impersonated_by', Auth::id());

        // Faz login como o novo usuário
        Auth::login($user);

        return to_route('dashboard');
    }

    public function leave(Request $request): RedirectResponse
    {
        if (! $request->session()->has('impersonated_by')) {
            return back();
        }

        $originalUserId = $request->session()->get('impersonated_by');
        $originalUser = User::findOrFail($originalUserId);

        // Remove o ID da sessão
        $request->session()->forget('impersonated_by');

        // Faz login de volta como o usuário original
        Auth::login($originalUser);

        return to_route('team.index');
    }
}
