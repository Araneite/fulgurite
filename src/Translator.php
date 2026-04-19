<?php
// =============================================================================
// Translator.php — Couche i18n centralisee
// =============================================================================
//
// Usage :
// t('key') → chaine traduite for the locale active
// t('key', ['name' => 'Alice']) → with remplacement of :name
// Translator::locale() → code locale actif (ex. 'fr', 'en')
// Translator::init('en') → force the locale (ex. from a endpoint CLI)
//
// Resolution of locale (by priorite) :
// 1. $_SESSION['preferred_locale'] (preference user connecte)
// 2. AppConfig::get('interface_default_locale', 'fr') (default of instance)
// 3. 'fr' (fallback dur)
//
// the catalogues are charges from translations/{locale}.php comme tableaux
// PHP plats with of keys en notation dotted. if a key missing in the
// locale active, the catalog 'fr' sert of fallback automatique.
// =============================================================================

class Translator
{
    private static string $locale = 'fr';

    /** catalog of the locale active */
    private static array $catalog = [];

    /** catalog of fallback (fr) – charge only quand locale !== 'fr' */    private static array $fallback = [];

    private static bool $initialized = false;

    // -------------------------------------------------------------------------
    // public API
    // -------------------------------------------------------------------------

    /**
     * Initialise the locale. Appele automatiquement to the premier appel of get().     * can be appele explicitement for forcer a locale (ex. CLI/cron).
     */
    public static function init(?string $locale = null): void
    {
        $resolved = $locale ?? self::resolveLocale();
        $supported = array_keys(AppConfig::localeOptions());
        self::$locale = in_array($resolved, $supported, true) ? $resolved : 'fr';

        self::$catalog = self::loadCatalog(self::$locale);

        // the catalog 'fr' serves of safety net of security for toute key missing
        if (self::$locale !== 'fr') {
            self::$fallback = self::loadCatalog('fr');
        } else {
            self::$fallback = [];
        }

        self::$initialized = true;
    }

    /**
     * Returns the locale actuellement active.
     */
    public static function locale(): string
    {
        if (!self::$initialized) {
            self::init();
        }
        return self::$locale;
    }

    /**
     * Traduit a key with remplacement optionnel of placeholders.
     *
     * Exemples :
     * t('common.save')
     * t('auth.login.attempts_warning', ['remaining' => 2])
     * t('repos.added_success', ['name' => $repoName])
     */
    public static function get(string $key, array $params = []): string
    {
        if (!self::$initialized) {
            self::init();
        }

        // Key in active locale, then fallback locale, then raw key
        $str = self::$catalog[$key] ?? self::$fallback[$key] ?? $key;

        if (!empty($params)) {
            foreach ($params as $placeholder => $value) {
                $str = str_replace(':' . $placeholder, (string) $value, $str);
            }
        }

        return $str;
    }

    /**
     * Resets internal state to zero (used in tests).
     */
    public static function reset(): void
    {
        self::$locale      = 'fr';
        self::$catalog     = [];
        self::$fallback    = [];
        self::$initialized = false;
    }

    // -------------------------------------------------------------------------
    // Resolution and chargement
    // -------------------------------------------------------------------------

    private static function resolveLocale(): string
    {
        // 1. User preference in session
        if (
            function_exists('session_status')
            && session_status() === PHP_SESSION_ACTIVE
            && isset($_SESSION['preferred_locale'])
            && is_string($_SESSION['preferred_locale'])
            && $_SESSION['preferred_locale'] !== ''
        ) {
            return $_SESSION['preferred_locale'];
        }

        // 2. default of instance configure in AppConfig
        try {
            $default = AppConfig::get('interface_default_locale', 'fr');
            if (is_string($default) && $default !== '') {
                return $default;
            }
        } catch (Throwable $e) {
            // AppConfig not yet available (e.g., very early first call)
        }

        // 3. Fallback dur
        return 'fr';
    }

    private static function loadCatalog(string $locale): array
    {
        $file = dirname(__DIR__) . '/translations/' . $locale . '.php';
        if (!file_exists($file)) {
            return [];
        }

        $data = require $file;
        return is_array($data) ? $data : [];
    }
}
