<?php

namespace App\Enums;

enum ForcedActions: string
{
    case ChangePassword = "change_password";
    case Add2FA = "add_2fa";
    case VerifyEmail = "verify_email";
    case VerifyProfile = "verify_profile";

    public function labelKey(): string {
        return match ($this) {
            self::ChangePassword =>"global/forced-actions.change_password",
            self::Add2FA =>"global/forced-actions.add_2fa",
            self::VerifyEmail =>"global/forced-actions.verify_email",
            self::VerifyProfile =>"global/forced-actions.verify_profile",
        };
    }
    
    public function labelTitle(): string {
        return trans($this->labelKey() . '.title');
    }
    
    public function labelDescription(): string {
        return trans($this->labelKey() . '.description');
    }
    
    public function values(): array {
        return array_column(self::cases(), 'value');
    }
    
    public static function options(): array {
        return array_map(
            fn (self $forcedAction)=> [
                'label'=> [
                    'title'=> $forcedAction->labelTitle(),
                    'description'=> $forcedAction->labelDescription(),
                ],
                'value'=> $forcedAction->value
            ],
            self::cases()
        );
    }
}
