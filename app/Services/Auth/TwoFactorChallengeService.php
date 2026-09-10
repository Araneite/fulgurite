<?php

namespace App\Services\Auth;

use App\Enums\Method2FA;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Http\Requests\PasskeyVerificationRequest;
use Laravel\Passkeys\Support\WebAuthn;
use Psy\Util\Json;
use Throwable;

readonly class TwoFactorChallengeService
{
    public function __construct(
        private TwoFactorService $twoFactorService,
    ) {}
    
    public function availableMethodValues(User $user): array {
        return collect($this->twoFactorService->availableMethods($user))
            ->pluck('value')
            ->all();
    }

    public function ensureMethodIsAvailableForUser(User $user, string $method): void {
        if (in_array($method, $this->availableMethodValues($user), true)) {
            return;
        }

        $message = $method === Method2FA::passkey->value
            ? trans('global/confirmation-identity.errors.passkey_unavailable')
            : trans('global/confirmation-identity.errors.method_unavailable');

        throw ValidationException::withMessages([
            'method' => $message,
            'toast' => json_encode([
                'title' => trans('global/confirmation-identity.errors.method_unavailable_title'),
                'description' => $message,
            ]),
        ]);
    }
    
    public function resolveSelectedMethod(Request $request, User $user, string $sessionKey): ?string {
        $methods = collect($this->availableMethodValues($user));
        
        if ($methods->isEmpty()) return null;
        
        $preferredMethod = $user->settings?->primary_second_factor;
        $selectedMethod = $request->session()->get($sessionKey);
        
        if (!$selectedMethod || !$methods->contains($preferredMethod)) {
            $selectedMethod = $methods->contains($preferredMethod)
                ? $preferredMethod
                : $methods->first();
            
            $request->session()->put($sessionKey, $selectedMethod);
        }
        
        return $selectedMethod;
    }
    
    public function changeMethod(Request $request, User $user, string $sessionKey): RedirectResponse {
        $validated = $request->validate([
            'method'=> ['required', 'string', Rule::in(Method2FA::values())]
        ]);
        
        $this->ensureMethodIsAvailableForUser($user, $validated['method']);
        
        $request->session()->put($sessionKey, $validated['method']);
        
        if ($validated['method'] === Method2FA::email->value) {
            $this->twoFactorService->sendEmailCode($user);
        }
        
        return back();
    }
    
    public function sendEmailCode(User $user): RedirectResponse {
        $this->ensureMethodIsAvailableForUser($user, Method2FA::email->value);
        $this->twoFactorService->sendEmailCode($user);
        
        return back()->with('success', trans('forms/confirmation-identity.messages.email_code_sent'));
    }
    
    public function verifyOtp(Request $request, User $user, string $sessionKey): void {
        $method = $request->session()->get($sessionKey);
        
        $validated = $request->validate([
            'code'=> ['required_unless:method,' . Method2FA::passkey->value, 'nullable'],
            'method'=> ['required', 'string', Rule::in(Method2FA::values())]
        ]);
        
        $this->ensureMethodIsAvailableForUser($user, $validated['method']);
        
        if ($validated['method'] !== $method) {
            $method = $validated['method'];
            $request->session()->put($sessionKey, $method);
        }
        
        if ($method === Method2FA::passkey->value) {
            throw ValidationException::withMessages([
                'passkey'=> trans('forms/confirmation-identity.errors.invalid_passkey')
            ]);
        }
        
        $code = is_array($validated['code'] ?? null)
            ? join($validated['code'])
            : ($validated['code'] ?? null);
        
        if (!$this->twoFactorService->verify($user, $method, $code)) {
            throw ValidationException::withMessages([
                'code'=> trans('forms/confirmation-identity.errors.invalid_code')
            ]);
        }
    }
    
    public function passkeyOptions(
        Request $request,
        User $user,
        string $sessionKey,
        GenerateVerificationOptions $generate
    ): JsonResponse {
        if ($request->session()->get($sessionKey) !== Method2FA::passkey->value) {
            abort(403);
        }
        
        $options = $generate($user);
        $serialized = WebAuthn::toJson($options);
        
        $request->session()->put('passkey.verification_options', $serialized);
        
        return response()->json([
            'options'=> json_decode($serialized, true),
        ]);
    }
    
    public function verifyPasskey(
        PasskeyVerificationRequest $request,
        VerifyPasskey $verify,
        User $user,
        string $sessionKey
    ): void {
        if ($request->session()->get($sessionKey) !== Method2FA::passkey->value) {
            abort(403);
        }
        
        try {
            $verify(
                $request->credential(),
                $request->verificationOptions(),
                $user
            );
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'passkey'=> trans('forms/confirmation-identity.errors.invalid_passkey')
            ]);
        }
    }
    
    public function invalidPasskeyResponse(): JsonResponse {
        return response()->json([
            'success'=> false,
            'message'=> trans('forms/confirmation-identity.errors.invalid_passkey'),
            'errors'=> [
                'passkey'=> [trans('forms/confirmation-identity.errors.invalid_passkey')]
            ]
        ], 422);
    }
}
