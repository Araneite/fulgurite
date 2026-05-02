<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ trans('emails.forgot_password.subject') }}</title>
</head>
<body>
<p>{{ trans("emails.forgot_password.greeting", ["user"=> $username ?: trans("emails.forgot_password.user_falback")]) }}</p>

<p>{{ trans("emails.forgot_password.intro") }}</p>

<p>
    <a href="{{ $resetUrl }}">{{ trans('emails.forgot_password.cta') }}</a>
</p>

<p>{{ trans('emails.forgot_password.copy_link') }}</p>
<p>{{ $resetUrl }}</p>

<p>{{ trans('emails.forgot_password.outro') }}</p>
</body>
</html>
