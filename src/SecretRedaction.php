<?php

final class SecretRedaction
{
    private const REDACTED_VALUE = '[redacted-secret]';
    private const REDACTED_REF = 'secret://[redacted]';
    private const SENSITIVE_KEY_PATTERN = '/(^|_)(secret|password|token|key)(_|$)|secret_ref$|totp_secret$/i';

    public static function redactText(string $text, array $explicitValues = []): string
    {
        foreach ($explicitValues as $value) {
            if (!is_string($value)) {
                continue;
            }
            $trimmed = trim($value);
            if ($trimmed === '') {
                continue;
            }
            $text = str_replace($trimmed, self::REDACTED_VALUE, $text);
        }

        return preg_replace('#secret://[a-z0-9_-]+(?:/[^\\s\'"\\\\]+)+#i', self::REDACTED_REF, $text) ?? $text;
    }

    public static function redactValue(mixed $value, array $explicitValues = []): mixed
    {
        if (is_string($value)) {
            return self::redactText($value, $explicitValues);
        }

        if (!is_array($value)) {
            return $value;
        }

        $redacted = [];
        foreach ($value as $key => $item) {
            if (is_string($key) && preg_match(self::SENSITIVE_KEY_PATTERN, $key) === 1) {
                $redacted[$key] = is_array($item) ? self::REDACTED_VALUE : self::REDACTED_VALUE;
                continue;
            }
            $redacted[$key] = self::redactValue($item, $explicitValues);
        }

        return $redacted;
    }

    public static function safeThrowableMessage(Throwable $throwable, array $explicitValues = []): string
    {
        return self::redactText($throwable->getMessage(), $explicitValues);
    }

    public static function errorLog(string $message, array $explicitValues = []): void
    {
        error_log(self::redactText($message, $explicitValues));
    }
}
