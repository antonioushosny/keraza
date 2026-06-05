<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $credentials['type'] = 'parent';

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Redirect based on role
            $referer = request()->headers->get('referer', '');
            $prefix = (request()->is('e3dady') || request()->is('e3dady/*') || str_contains($referer, '/e3dady')) ? '/e3dady' : '';
            if (auth()->user()->type === 'admin') {
                return redirect($prefix . '/admin');
            }

            return redirect($prefix . '/parent');
        }

        return back()->withErrors([
            'phone' => 'بيانات الدخول غير صحيحة.',
        ])->onlyInput('phone');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $referer = request()->headers->get('referer', '');
        $prefix = (request()->is('e3dady') || request()->is('e3dady/*') || str_contains($referer, '/e3dady')) ? '/e3dady' : '';
        return redirect($prefix . '/');
    }
}
