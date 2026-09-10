<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Method2FA;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\TwoFactorChallengeService;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Http\Requests\PasskeyVerificationRequest;
use Laravel\Passkeys\Support\WebAuthn;
use Psy\Util\Json;
use Throwable;

class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactorService,
        private readonly TwoFactorChallengeService $twoFactorChallengeService
    ) {}

    // === Login 2FA ===
    public function show(Request $request): RedirectResponse | Response {
        $user = $this->pendingUser($request);

        if (!$user) {
            return redirect()->route("login");
        }

        $selectedMethod = $this->twoFactorChallengeService->resolveSelectedMethod(
            $request,
            $user,
            'two_factor.method'
        );

        return Inertia::render("Auth/TwoFactorChallenge", [
            "methods"=> $this->twoFactorService->availableMethods($user),
            "selectedMethod"=> $selectedMethod,
            "maskedEmail"=> $this->twoFactorService->maskedEmail($user->email),
            "trans"=> trans("global/confirmationIdentity")
        ]);
    }

    public function changeMethod(Request $request): RedirectResponse {
        $user = $this->pendingUser($request);

        if (!$user) {
            return redirect()->route("login");
        }

        return $this->twoFactorChallengeService->changeMethod($request, $user, 'two_factor.method');
    }

    public function sendEmailCode(Request $request): RedirectResponse {
        $user = $this->pendingUser($request);

        if (!$user) {
            return redirect()->route("login");
        }

        return $this->twoFactorChallengeService->sendEmailCode($user);
    }

    public function verify(Request $request): RedirectResponse {
        $user = $this->pendingUser($request);

        if (!$user) {
            return redirect()->route("login");
        }

        $this->twoFactorChallengeService->verifyOtp($request, $user, 'two_factor.method');

        Auth::login($user, $request->session()->pull("two_factor.remember", false));

        $request->session()->forget("two_factor");
        $request->session()->regenerate();

        return redirect()->intended("/");
    }

    function passkeyOptions(Request $request, GenerateVerificationOptions $generate) {
        $user = $this->pendingUser($request);

        if (!$user) {
            abort(403);
        }

        return $this->twoFactorChallengeService->passkeyOptions(
            $request, 
            $user,
            'two_factor.method',
            $generate
        );
    }

    public function verifyPasskey(PasskeyVerificationRequest $request, VerifyPasskey $verify) {
        $user = $this->pendingUser($request);

        if (!$user) {
            abort(403);
        }

        try {
            $this->twoFactorChallengeService->verifyPasskey(
                $request,
                $verify,
                $user,
                'two_factor.method',
                
            );
        } catch (ValidationException) {
           return $this->twoFactorChallengeService->invalidPasskeyResponse();
        }

        Auth::login($user, $request->session()->pull('two_factor.remember', false));

        $request->session()->forget("two_factor");
        $request->session()->regenerate();

        return response()->json([
            'success'=> true,
            'redirect'=> url('/')
        ]);
    }

    private function pendingUser(Request $request): ?User {
        $userId = $request->session()->get("two_factor.user_id");

        if (!$userId) {
            return null;
        }

        return User::query()->with("settings")->find($userId);
    }
    
    // === Confirmation 2FA ===
    public function changeIdentityMethod(Request $request): RedirectResponse {
        $user = $request->user()?->loadMissing("settings");
        
        if (!$user) {
            abort(403);
        }
        
        return $this->twoFactorChallengeService->changeMethod(
            $request,
            $user,
            'confirm_identity.method'
        );
    }
    
    public function sendIdentityEmailCode(Request $request): RedirectResponse {
        $user = $request->user()?->loadMissing("settings");
        
        if (!$user) {
            abort(403);
        }
        
        return $this->twoFactorChallengeService->sendEmailCode($user);
    }
    
    public function confirmIdentity(Request $request): RedirectResponse {
        $user = $request->user()?->loadMissing("settings");
        
        if (!$user) {
            abort(403);
        }
        
        $validated = $request->validate([
            'password'=> ['required', 'string']
        ]);
        
        if (!Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => auth('forms/login.errors.password_failed')
            ]);
        }
        
        if (empty($this->twoFactorChallengeService->availableMethodValues($user))) {
            $request->session()->put("confirm_identity.identity_confirmed_at", now()->timestamp);
            $request->session()->forget('confirm_identity');
            
            return back();
        }
        
        $this->twoFactorChallengeService->resolveSelectedMethod($request, $user, 'confirm_identity.method');
        
        $this->twoFactorChallengeService->verifyOtp($request, $user, 'confirm_identity.method');
        
        $request->session()->put("auth.identity_confirmed_at", now()->timestamp);
        $request->session()->forget('confirm_identity');
        
        return back();
    }
    
    public function identityPasskeyOptions(Request $request, GenerateVerificationOptions $generate): JsonResponse {
        $user = $request->user()?->loadMissing("settings");
        
        if (!$user) {
            abort(403);
        }
        
        $this->twoFactorChallengeService->resolveSelectedMethod($request, $user, 'confirm_identity.method');
        
        return $this->twoFactorChallengeService->passkeyOptions(
            $request,
            $user,
            'confirm_identity.method',
            $generate
        );
    }
    
    public function verifyIdentityPasskey(PasskeyVerificationRequest $request, VerifyPasskey $verify): JsonResponse {
        $user = $request->user()?->loadMissing("settings");
        
        if (!$user) {
            abort(403);
        }
        
        try {
            $this->twoFactorChallengeService->verifyPasskey(
                $request,
                $verify,
                $user,
                'confirm_identity.method',
            );
        } catch (ValidationException) {
            return $this->twoFactorChallengeService->invalidPasskeyResponse();
        }
        
        $request->session()->put('auth.identity_confirmed_at', now()->timestamp);
        $request->session()->forget('confirm_identity');
        
        return response()->json([
            'success'=> true,
        ]);
    }
}
