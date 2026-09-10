{{ trans('mails/user-invitation.title', ['app' => config('app.name', 'Fulgurite')]) }}

{{ trans('mails/user-invitation.greeting', [
    'user' => $invitation->username ?: trans('mails/user-invitation.fallback_user'),
]) }}

{{ trans('mails/user-invitation.intro', [
    'app' => config('app.name', 'Fulgurite'),
    'inviter' => $inviterName ?: trans('mails/user-invitation.fallback_inviter'),
]) }}

{{ trans('mails/user-invitation.email') }} {{ $invitation->email }}
@if($invitation->username)
    {{ trans('mails/user-invitation.username') }} {{ $invitation->username }}
@endif
{{ trans('mails/user-invitation.expires_at') }} {{ $invitation->expires_at ? $invitation->expires_at->translatedFormat('d F Y à H:i') : trans('mails/user-invitation.no_expiration') }}

@if(count($roleLabels) > 0)
    {{ trans('mails/user-invitation.roles') }} {{ implode(', ', $roleLabels) }}
@endif

@if(count($forcedActionLabels) > 0)
    {{ trans('mails/user-invitation.forced_actions') }} {{ implode(', ', $forcedActionLabels) }}
@endif

{{ trans('mails/user-invitation.cta') }}
{{ $acceptUrl }}

{{ trans('mails/user-invitation.security_note') }}
