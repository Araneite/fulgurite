<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laragear\WebAuthn\Http\Requests\AttestationRequest;
use Laragear\WebAuthn\Http\Requests\AttestedRequest;

class SecurityKeyController extends Controller
{
    public function index(Request $request): Response {
        return Inertia::render("Dashboard/SercurityKeys/Index", [
            "credentials"=> $request->user()
                ->webAuthnCredentials()
                ->latest()
                ->get()
                ->map(fn ($credential) => [
                    "id"=> $credential->id,
                    "alias"=> $credential->alias,
                    "origin"=> $credential->origin,
                    "enabled"=> $credential->isEnabled(),
                    "created_at"=> $credential->created_at?->format("d/m/Y H:i")
            ])
        ]);
    }
    
    public function options(AttestationRequest $request) {
        return $request->secureRegistration()->toCreate();
    }
    
    public function store(AttestationRequest $request): RedirectResponse {
        $validated = $request->validate([
            "alias"=> ["nullable", "string", "max:120"]
        ]);
        
        $request->save([
            "alias"=> $validated["alias"] ?: trans("auth/security-key.security_key")
        ]);
        
        return back()->with("success", trans("auth/security-key.success.store"));
    }
    
    public function destroy(Request $request, string $credential): RedirectResponse {
        $credential = $request->user()
            ->webAuthnCredentials()
            ->whereKey($credential)
            ->firstOrFail();
        
        $credential->delete();
        
        return back()->with("success", trans("auth/security-key.success.destroy"));
    }
}
