<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UserLoginRequest;
use App\Models\User;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class UserPageController extends Controller
{
	public function index(): Response {
        return Inertia::render('Dashboard/Users/Index', [
            "activeUsersCount"=> User::query()->where("active", 1)->count(),
            "inactiveUsersCount"=> User::query()->where("active", 0)->count(),
            "trashedUsersCount"=> User::onlyTrashed()->count(),
        ]);
    }
    
    public function showLogin(): RedirectResponse|Response
    {
        if (Auth::check()) {
            return redirect()->intended('/');
        }
        
        return Inertia::render('Auth/Login', [
            'resetPasswordUrl' => route('reset-password'),
            'trans'=> __("auth/login")
        ]);
    }
    
    public function login(UserLoginRequest $request): RedirectResponse
    {
        $user = $request->authenticate();
        
        $methods = app(TwoFactorService::class)->availableMethods($user);
        
        if ($methods !== []) {
            Auth::logout();
            
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $request->session()->put("two_factor.user_id", $user->id);
            $request->session()->put("two_factor.remember", $request->boolean("remember"));
            $request->session()->put("two_factor.method", $methods[0]['value']);
            
            return redirect()->route("two-factor.show");
        }
        
        $request->session()->regenerate();
        
        return redirect()->intended('/');
    }
}
