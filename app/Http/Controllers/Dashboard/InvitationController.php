<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Invitation\InvitationRenewExpirationRequest;
use App\Http\Requests\Dashboard\Invitation\UserInvitationStoreRequest;
use App\Models\Invitation;
use App\Services\Users\UserInvitationService;
use App\Traits\AuthorizesRequests;
use App\Traits\FormatDateForLocale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Psy\Util\Json;

class InvitationController extends Controller
{
    use AuthorizesRequests, FormatDateForLocale;
    
    public function store(
        UserInvitationStoreRequest $request,
        UserInvitationService $service
    ): RedirectResponse {
        $result = $service->create(
            inviter: $request->user(),
            data: $request->validated(),
            roleIds: $request->roleIds(),
            forcedActions: $request->forcedActions()
        );
        
        if ($request->validated('mode') === 'link') {
            return back()
                ->with('success', ['title'=> trans('resources/invitations.messages.link_created')])
                ->with('invitation_link', $result['accept_url']);
        }
        
        return back()->with('success', trans('resources/invitations.messages.sent'));
    }
    
    public function sendEmail(Request $request, Invitation $invite, UserInvitationService $service): RedirectResponse {
        $this->authorizeUserAction($request, 'send', 'actions.send', $invite);
        
        $service->sendInvitation($invite);
        
        return back()->with('success', [
            'title'=> trans('resources/invitations.messages.mail_sent', ['email' => $invite->email])
        ]);
    }

    public function link(Request $request, Invitation $invite): RedirectResponse | JsonResponse {
        $this->authorizeUserAction($request, 'view', 'actions.view', $invite);
        
        if (!$invite->token || $invite->status !== 'pending') {
            return response()->json([
                'error'=> trans('resources/invitations.messages.link_unavailable')
            ], 409);
        }
        
        return response()->json([
            'link'=> route('invitations.accept.show', ['token'=> $invite->token])
        ]);
    }
    
    public function renew(InvitationRenewExpirationRequest $request, Invitation $invite): RedirectResponse
    {
        $this->authorizeUserAction($request, 'renew', 'actions.renew', $invite);
        
        $data = $request->getData();
        $newDate = $invite->expires_at !== null ? $invite->expires_at->addDays($data['renew_amount'] ?? 7) : now()->addDays($data['renew_amount'] ?? 7);
        $status = $invite->status === 'expired' && $newDate->isFuture() ? 'pending' : $invite->status;
        
        if ($data['expiration_date'] !== null) {
            $newDate = Carbon::parse($data['expiration_date']);
            $invite->update(['expires_at'=> $newDate, 'status' => $status]);
            
            return back()->with('success', [
                'title'=> trans('resources/invitations.messages.updated.renew.title'),
                'description'=> trans('resources/invitations.messages.updated.renew.description', [
                    'date' => $this->formatDateForLocale($newDate)
                ])
            ]);
        } 
        
        $invite->update(['expires_at'=> $newDate, 'status' => $status]);

        return back()->with('success', [
            'title'=> trans('resources/invitations.messages.updated.renew.title'),
            'description'=> trans('resources/invitations.messages.updated.renew.description', [
                'date' => $this->formatDateForLocale($newDate)
            ])
        ]);
    }
    
    public function removeExpiration(Request $request, Invitation $invite): RedirectResponse {
        $this->authorizeUserAction($request, 'update', 'actions.renew', $invite);
        
        $invite->update(['expires_at' => null]);
        
        return back()->with('success', [
            'title'=> trans('resources/invitations.messages.updated.remove_expiration.title'),
            'description'=> trans('resources/invitations.messages.updated.remove_expiration.description', ['email'=> $invite->email])
        ]);
    }
    
    public function revoke(Request $request, Invitation $invite): RedirectResponse {
        $this->authorizeUserAction($request, 'revoke', 'actions.revoke', $invite);
        
        if ($invite->status === 'revoked') {
            return back()->with('error', [
                'title'=> trans('errors/invitations.already_status.title', ['status'=> trans("resources/invitations.status.{$invite->status}")]),
                'description'=> trans('errors/invitations.already_status.description', [
                    'email'=> $invite->email,
                    'status'=> strtolower(trans("resources/invitations.status.{$invite->status}"))
                ])
            ]);
        }
        if ($invite->status === 'accepted' || $invite->status === 'expired') {
            return back()->with('error', [
                'title'=> trans('errors/invitations.unavailable_revoke.title'),
                'description'=> trans('errors/invitations.unavailable_revoke.description')
            ]);
        }
        
        $invite->update(['status'=> 'revoked']);
        
        return back()->with('success', [
            'title'=> trans('resources/invitations.messages.revoked.title'),
            'description'=> trans('resources/invitations.messages.revoked.description', ['email' => $invite->email])
        ]);
    }
    
    public function reactive(Request $request, Invitation $invite): RedirectResponse {
        $this->authorizeUserAction($request, 'reactive', 'actions.reactive', $invite);
        
        if ($invite->status !== 'revoked') {
            return back()->with('error', [
                'title'=> trans('errors/invitations.unavailable_reactive.title'),
                'description'=> trans('errors/invitations.unavailable_reactive.description')
            ]);
        }
        
        $invite->update(['status'=> 'pending']);
        
        return back()->with('success', [
            'title'=> trans('resources/invitations.messages.reactive.title'),
            'description'=> trans('resources/invitations.messages.reactive.description', ['email' => $invite->email])
        ]);
    }
    
    public function restore(Request $request, Invitation $invite): RedirectResponse {
        $this->authorizeUserAction($request, 'restore', 'actions.restore', $invite);
        
        if (!$invite->deleted_at) {
            return back()->with('error', [
                'title'=> trans('errors/invitations.unavailable_restore.title'),
                'description'=> trans('errors/invitations.unavailable_restore.description', ['email' => $invite->email])
            ]);
        }
        
        $invite->restore();
        
        return back()->with('success', [
            'title'=> trans('resources/invitations.messages.restored.title'),
            'description'=> trans('resources/invitations.messages.restored.description', ['email' => $invite->email])
        ]);
    }
    
    public function destroy(Request $request, Invitation $invite): RedirectResponse {
        $this->authorizeUserAction($request, 'delete', 'actions.delete', $invite);
        
        $email = $invite->email;
        $invite->delete();
        
        return back()->with('success', [
            'title'=> trans('resources/invitations.messages.deleted.title'),
            'description'=> trans('resources/invitations.messages.deleted.description', ['email'=> $email, 'retention_days' => config('app.retention_days')])
        ]);
    }
    
    public function forceDelete(Request $request, Invitation $invite): RedirectResponse {
        $this->authorizeUserAction($request, 'forceDelete', 'actions.force_delete', $invite);
        
        $email = $invite->email;
        
        if (!$invite->deleted_at) {
            return back()->with('error', [
                'title'=> trans('errors/invitations.unavailable_force_delete.title'),
                'description'=> trans('errors/invitations.unavailable_force_delete.description')
            ]);
        }
        
        $invite->forceDelete();
        
        return back()->with('success', [
            'title'=> trans('resources/invitations.messages.force_deleted.title'),
            'description'=> trans('resources/invitations.messages.force_deleted.description', ['email' => $email])
        ]);
    }
}
