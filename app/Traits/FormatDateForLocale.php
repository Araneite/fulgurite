<?php

namespace App\Traits;

trait FormatDateForLocale
{
    public function formatDateForLocale($date): ?string {
        if (!$date) return null;
        
        return match (app()->getLocale()) {
            'fr_FR'=> $date->locale('fr')->translatedFormat('d/m/Y H:i:s'),
            'en_US'=> $date->locale('fr')->translatedFormat('m/d/Y h:i:s A'),
            default => $date->toISOString(),
        };
    }
}
