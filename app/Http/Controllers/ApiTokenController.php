<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ApiTokenController extends Controller
{
    public function index(Request $request): View
    {
        return view('Account.ApiTokensIndex', [
            'tokens' => $request->user()->tokens()->latest()->get(),
            'plainTextToken' => $request->session()->pull('api_token_once'),
            'allowedAbilities' => $this->allowedAbilities($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! Hash::check((string) $request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        $allowed = $this->allowedAbilities($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['required', 'string', Rule::in($allowed)],
        ]);
        $token = $user->createToken(
            $validated['name'],
            array_values(array_unique($validated['abilities'])),
            now()->addDays(30)
        );
        $request->session()->flash('api_token_once', $token->plainTextToken);

        return redirect()->route('account.api-tokens.index')->with('success', 'API token created. Copy it now; it will only be shown once.');
    }

    public function destroy(Request $request, int $token): RedirectResponse
    {
        $request->user()->tokens()->whereKey($token)->delete();

        return back()->with('success', 'API token revoked.');
    }

    private function allowedAbilities(Request $request): array
    {
        return match (strtolower($request->user()->role)) {
            'student' => ['profile:read', 'academic:read', 'finance:read'],
            default => ['profile:read'],
        };
    }
}
