<?php

namespace App\Services\Users;

use App\Enums\ForcedActions;
use App\Mail\UserInvitationMail;
use App\Models\Invitation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserInvitationService
{
    public function create(User $inviter, array $data, array $roleIds, array $forcedActions): array {
        $plainToken = Str::random(64);
        
        $invitation = Invitation::create([
            'email'=> mb_strtolower(trim($data['email'])),
            'username'=> filled($data['username'] ?? null) ? trim($data['username']) : null,
            'payload'=> [
                'roles'=> $roleIds,
                'forced_actions'=> $forcedActions,
                'admin_notes'=> $data['admin_notes'] ?? null,
            ],
            'token'=> $plainToken,
            'token_hash'=> hash('sha256', $plainToken),
            'status'=> 'pending',
            'expires_at'=> now()->addDays(7),
            'invited_by'=> $inviter->id,
        ]);
        
        $acceptUrl = route('invitations.accept.show', ['token'=> $plainToken]);
        
        if (($data['mode'] ?? 'email') === 'email') {
            Mail::to($invitation->email)->queue(new UserInvitationMail(
                invitation: $invitation,
                acceptUrl: $acceptUrl,
                inviterName: $inviter->username,
                roleLabels: $this->roleLabels($roleIds),
                forcedActionLabels: $this->forcedActionLabels($forcedActions),
                mailLocale: $inviter->settings?->preferred_locale ?? app()->getLocale(),
            ));
        }
        
        return [
            'invitation'=> $invitation,
            'accept_url'=> $acceptUrl,
        ];
    }
    
    public function sendInvitation(Invitation $invite) {
        $acceptUrl = route('invitations.accept.show', ['token'=> $invite->token]);
        
        Mail::to($invite->email)->queue(new UserInvitationMail(
            invitation: $invite,
            acceptUrl: $acceptUrl,
            inviterName: $invite->inviter?->username,
            roleLabels: $this->roleLabels($invite->payload['roles'] ??  []),
            forcedActionLabels: $this->forcedActionLabels($invite->payload['forced_actions'] ??  []),
            mailLocale: $invite->inviter?->settings->preferred_locale ?? app()->getLocale(),
        ));
    }
    
    private function roleLabels(array $roleIds): array {
        return Role::query()
            ->whereIn('id', $roleIds)
            ->pluck('name')
            ->values()
            ->all();
    }
    
    private function forcedActionLabels(array $forcedActions): array {
        return collect($forcedActions)
            ->map(fn (string $value)=> ForcedActions::tryFrom($value)?->labelTitle())
            ->filter()
            ->values()
            ->all();
    }
}
