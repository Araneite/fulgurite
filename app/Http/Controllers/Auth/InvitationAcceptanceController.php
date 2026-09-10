<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\InvitationAcceptanceException;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class InvitationAcceptanceController extends Controller
{
    public function show(string $token): Response {        
        try {
            $invitation = $this->findInvitationForAcceptance($token);
        } catch (InvitationAcceptanceException $exception) {
            return Inertia::render('Errors/InvitationUnavailable', [
                'reason'=> $exception->reason,
                '__'=> trans("global/errors.invitations.unavailable.{$exception->reason}")
            ]);
        }
        
        return Inertia::render('Auth/AcceptInvitation', [
            'token'=> $token,
            'invitation'=> [
                'email'=> $invitation->email,
                'username'=> $invitation->username,
                'expires_at'=> $invitation->expires_at?->translatedFormat('d F Y H:i'),
                'inviter'=> $invitation->inviter?->username,
            ],
            '__'=> trans('auth/invitations')
        ]);
    }
    
    public function accept(Request $request, string $token): RedirectResponse {
        $invitation = $this->findPendingInvitation($token);
        
        $validated = $request->validate([
            'username'=> ['required', 'string', 'max:255', 'unique:fg_users,username'],
            'password'=> ['required', 'confirmed', Password::defaults()]
        ]);
        
        DB::transaction(function () use ($invitation , $validated) {
            $payload = $invitation->payload ?? [];
            
            $user = User::create([
                'username'=> $validated['username'],
                'email'=> $invitation->email,
                'password'=> Hash::make($validated['password']),
                'active'=> true,
                'admin_notes'=> $payload['admin_notes'] ?? null,
                'password_set_at'=> now(),
                'created_by'=> $invitation->invited_by
            ]);
            
            $settings = UserSetting::create([
                'preferred_locale'=> 'en_US',
                'preferred_timezone'=> 'UTC',
                'preferred_start_page'=> 'dashboard',
                'repo_scope_mode'=> 'all',
                'host_scope_mode'=> 'all',
                'force_actions_json' => $payload['forced_actions'] ?? [],
                'primary_second_factor'=> null,
                'totp_enabled'=> false,
                'user_id'=> $user->id,
            ]);
            
            $user->update(['user_settings_id'=> $settings->id]);
            
            $roleIds = collect($payload['roles'] ?? [])
                ->filter()
                ->unique()
                ->values()
                ->all();
            
            if ($roleIds !== []) {
                $user->roles()->sync($roleIds);
            }
            
            $invitation->accept($user);
        });
        
        return redirect()
            ->route('login')
            ->with('success', trans('resources/invitations.messages.accepted'));
    }

    /**
     * @throws InvitationAcceptanceException
     */
    private function findInvitationForAcceptance(string $token): Invitation {
        $invitation = Invitation::query()
            ->where('token_hash', hash('sha256', $token))
            ->first();
        
        if (!$invitation) {
            throw InvitationAcceptanceException::notFound();
        }
        
        switch ($invitation->status) {
            case 'accepted': 
                throw InvitationAcceptanceException::accepted();
            case 'revoked': 
                throw InvitationAcceptanceException::revoked();
        }
        
        if ($invitation->expires_at !== null && $invitation->expires_at->isPast()) {
            throw InvitationAcceptanceException::expired();
        }
        
        if ($invitation->status !== 'pending') {
            throw InvitationAcceptanceException::notFound();
        }
        
        return $invitation;
    }
}
