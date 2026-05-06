<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ trans('internal/mails/forgot_password.subject') }}</title>
</head>
<body>
<p>{{ trans("internal/mails/forgot_password.greeting", ["user"=> $username ?: trans("internal/mails/forgot_password.user_falback")]) }}</p>

<p>{{ trans("internal/mails/forgot_password.intro") }}</p>

<p>
    <a href="{{ $resetUrl }}">{{ trans('internal/mails/forgot_password.cta') }}</a>
</p>

<p>{{ trans('internal/mails/forgot_password.copy_link') }}</p>
<p>{{ $resetUrl }}</p>

<p>{{ trans('internal/mails/forgot_password.outro') }}</p>
</body>
</html>
