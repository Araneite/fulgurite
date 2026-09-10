<?php

namespace App\Enums;

use DateTimeZone;

enum Timezone: string
{
    case UTC = 'UTC';
    
    public static function values(): array {
        return DateTimeZone::listIdentifiers();
    }
    
    public static function options(): array {
        return array_map(
            fn (string $timezone) => [
                'label'=> self::labelFor($timezone),
                'value' => $timezone
            ],
            self::values()
        );
    }
    
    public static function labelFor(string $timezone): string {
        $offset = self::offsetFor($timezone);
        
        return "{$timezone} ({$offset})";
    }
    
    public static function offsetFor(string $timezone): string {
        $dateTimeZone = new DateTimeZone($timezone);
        $offset = $dateTimeZone->getOffset(new \DateTimeImmutable('now', $dateTimeZone));
        
        $hours = intdiv(abs($offset), 3600);
        $minutes = intdiv(abs($offset) % 3600, 60);
        $sign = $offset >= 0 ? '+' : '-';
        
        return sprintf("UTC%s%02d:%02d", $sign, $hours, $minutes);
    }
    
    public static function isValid(string $timezone): bool {
        return in_array($timezone, self::values(), true);
    }
}
