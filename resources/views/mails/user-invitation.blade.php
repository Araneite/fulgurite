<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width">
    <title>{{ trans('mails/user-invitation.subject', ['app'=> config('app.name', 'Fulgurite')]) }}</title>
</head>
<body style="margin: 0; padding: 0; background: #f6f7fb; color: #111827; font-family: Arial,Helvetica,sans-serif;">
    <span style="display:none;max-height:0;overflow:hidden;color:#f6f7fb;">
        {{ trans('mails/user-invitation.preheader', ['app' => config('app.name', 'Fulgurite')]) }}
    </span>
    
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f6f7fb;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="padding:28px 32px 20px 32px;">
                            <img src="{{ $logoUrl }}" width="54" height="54" alt="Fulgurite" style="display:block;border:0;">
                        </td>
                    </tr>
    
                    <tr>
                        <td style="padding:0 32px 24px 32px;">
                            @if($heroImageUrl)
                                <img src="{{ $heroImageUrl }}" alt="{{ trans('mails/user-invitation.hero_alt') }}" width="576" style="display:block;width:100%;max-width:576px;border:0;border-radius:14px;">
                            @else
                                <div style="border-radius:14px;background:#111827;padding:28px;color:#ffffff;">
                                    <p style="margin:0 0 10px 0;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#fdc104;font-weight:700;">
                                        {{ trans('mails/user-invitation.image_placeholder_label') }}
                                    </p>
                                    <p style="margin:0;font-size:15px;line-height:1.6;color:#f9fafb;">
                                        {{ trans('mails/user-invitation.image_placeholder_description') }}
                                    </p>
                                </div>
                            @endif
                        </td>
                    </tr>
    
                    <tr>
                        <td style="padding:0 32px 8px 32px;">
                            <p style="display:inline-block;margin:0 0 16px 0;padding:7px 12px;border-radius:999px;background:#fff7d6;color:#7a5600;font-size:12px;font-weight:700;">
                                {{ trans('mails/user-invitation.badge') }}
                            </p>
    
                            <h1 style="margin:0 0 14px 0;font-size:28px;line-height:1.2;color:#111827;">
                                {{ trans('mails/user-invitation.title', ['app' => config('app.name', 'Fulgurite')]) }}
                            </h1>
    
                            <p style="margin:0 0 18px 0;font-size:16px;line-height:1.7;color:#4b5563;">
                                {{ trans('mails/user-invitation.greeting', [
                                    'user' => $invitation->username ?: trans('mails/user-invitation.fallback_user'),
                                ]) }}
                            </p>
    
                            <p style="margin:0 0 22px 0;font-size:16px;line-height:1.7;color:#4b5563;">
                                {{ trans('mails/user-invitation.intro', [
                                    'app' => config('app.name', 'Fulgurite'),
                                    'inviter' => $inviterName ?: trans('mails/user-invitation.fallback_inviter'),
                                ]) }}
                            </p>
                        </td>
                    </tr>
    
                    <tr>
                        <td style="padding:0 32px 24px 32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:14px;">
                                <tr>
                                    <td style="padding:18px 20px;">
                                        <p style="margin:0 0 12px 0;font-size:13px;font-weight:700;color:#111827;">
                                            {{ trans('mails/user-invitation.details_title') }}
                                        </p>
    
                                        <p style="margin:0 0 8px 0;font-size:14px;color:#4b5563;">
                                            <strong style="color:#111827;">{{ trans('mails/user-invitation.email') }}</strong>
                                            {{ $invitation->email }}
                                        </p>
    
                                        @if($invitation->username)
                                            <p style="margin:0 0 8px 0;font-size:14px;color:#4b5563;">
                                                <strong style="color:#111827;">{{ trans('mails/user-invitation.username') }}</strong>
                                                {{ $invitation->username }}
                                            </p>
                                        @endif
    
                                        <p style="margin:0 0 8px 0;font-size:14px;color:#4b5563;">
                                            <strong style="color:#111827;">{{ trans('mails/user-invitation.expires_at') }}</strong>
                                            {{ $invitation->expires_at ? $invitation->expires_at->translatedFormat('d F Y à H:i') : trans('internal/mails/user_invitation.no_expiration') }}
                                        </p>
    
                                        @if(count($roleLabels) > 0)
                                            <p style="margin:12px 0 6px 0;font-size:14px;color:#111827;font-weight:700;">
                                                {{ trans('mails/user-invitation.roles') }}
                                            </p>
                                            <p style="margin:0;font-size:14px;line-height:1.6;color:#4b5563;">
                                                {{ implode(', ', $roleLabels) }}
                                            </p>
                                        @endif
    
                                        @if(count($forcedActionLabels) > 0)
                                            <p style="margin:12px 0 6px 0;font-size:14px;color:#111827;font-weight:700;">
                                                {{ trans('mails/user-invitation.forced_actions') }}
                                            </p>
                                            <p style="margin:0;font-size:14px;line-height:1.6;color:#4b5563;">
                                                {{ implode(', ', $forcedActionLabels) }}
                                            </p>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
    
                    <tr>
                        <td align="center" style="padding:4px 32px 26px 32px;">
                            <a href="{{ $acceptUrl }}" style="display:inline-block;background:#fdc104;color:#111827;text-decoration:none;font-size:16px;font-weight:700;padding:15px 24px;border-radius:12px;">
                                {{ trans('mails/user-invitation.cta') }}
                            </a>
                        </td>
                    </tr>
    
                    <tr>
                        <td style="padding:0 32px 30px 32px;">
                            <p style="margin:0 0 8px 0;font-size:13px;line-height:1.6;color:#6b7280;">
                                {{ trans('mails/user-invitation.copy_link') }}
                            </p>
                            <p style="margin:0;word-break:break-all;font-size:13px;line-height:1.6;color:#374151;">
                                {{ $acceptUrl }}
                            </p>
    
                            <p style="margin:22px 0 0 0;font-size:13px;line-height:1.6;color:#6b7280;">
                                {{ trans('mails/user-invitation.security_note') }}
                            </p>
                        </td>
                    </tr>
    
                    <tr>
                        <td style="padding:20px 32px;background:#111827;">
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#d1d5db;">
                                {{ trans('mails/user-invitation.footer', ['app' => config('app.name', 'Fulgurite')]) }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
