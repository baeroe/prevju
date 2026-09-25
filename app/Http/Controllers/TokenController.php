<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class TokenController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('tokens', [
            'mcp_url' => url('/mcp'),
            'tokens' => $request->user()->tokens()->latest()->get()->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'created_at' => $t->created_at->toIso8601String(),
                'last_used_at' => $t->last_used_at?->toIso8601String(),
            ]),
            // plain text exists only right after creation (flashed once)
            'new_token' => $request->session()->get('new_token'),
        ]);
    }

    public function store(Request $request)
    {
        $name = $request->validate(['name' => 'required|string|max:100'])['name'];

        return redirect('/tokens')->with('new_token', $request->user()->createToken($name)->plainTextToken);
    }

    public function destroy(Request $request, int $token)
    {
        $request->user()->tokens()->whereKey($token)->delete();

        return back();
    }
}
