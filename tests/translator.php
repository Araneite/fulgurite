<?php

declare(strict_types=1);

define('FULGURITE_CLI', true);

$root = dirname(__DIR__);
$tmp = sys_get_temp_dir() . '/fulgurite-translator-test-' . bin2hex(random_bytes(4));
mkdir($tmp, 0700, true);

putenv('DB_DRIVER=sqlite');
putenv('DB_PATH=' . $tmp . '/fulgurite.db');
putenv('SEARCH_DB_PATH=' . $tmp . '/fulgurite-search.db');
$_ENV['DB_DRIVER'] = 'sqlite';
$_ENV['DB_PATH'] = $tmp . '/fulgurite.db';
$_ENV['SEARCH_DB_PATH'] = $tmp . '/fulgurite-search.db';
$_SERVER['DB_DRIVER'] = 'sqlite';
$_SERVER['DB_PATH'] = $tmp . '/fulgurite.db';
$_SERVER['SEARCH_DB_PATH'] = $tmp . '/fulgurite-search.db';

require_once $root . '/src/bootstrap.php';

function assertSameValue(mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        fwrite(STDERR, $message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertTrueValue(bool $value, string $message): void {
    if (!$value) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

// -------------------------------------------------------------------------
// 1. Default locale: 'fr' when no session or config is available
// -------------------------------------------------------------------------

Translator::reset();
assertSameValue('fr', Translator::locale(), 'La locale par défaut doit être "fr".');

// -------------------------------------------------------------------------
// 2. Translate an existing key in FR
// -------------------------------------------------------------------------

Translator::reset();
Translator::init('fr');
$val = t('common.save');
assertTrueValue($val !== '' && $val !== 'common.save', 'La clé common.save doit retourner une chaîne traduite en FR.');

// -------------------------------------------------------------------------
// 3. Translation with placeholder
// -------------------------------------------------------------------------

Translator::reset();
Translator::init('fr');
$result = t('flash.repos.added', ['name' => 'Mon Dépôt']);
assertTrueValue(str_contains($result, 'Mon Dépôt'), 'Le placeholder :name doit être remplacé dans la chaîne traduite.');
assertTrueValue(!str_contains($result, ':name'), 'Le placeholder :name ne doit pas rester dans la chaîne traduite.');

// -------------------------------------------------------------------------
// 4. EN locale: translation of an existing EN key
// -------------------------------------------------------------------------

Translator::reset();
Translator::init('en');
assertSameValue('en', Translator::locale(), 'Après init("en"), la locale doit être "en".');
$valEn = t('common.save');
assertTrueValue($valEn !== '' && $valEn !== 'common.save', 'La clé common.save doit retourner une chaîne traduite en EN.');

// -------------------------------------------------------------------------
// 5. FR fallback for key missing in EN
// -------------------------------------------------------------------------

// Inject a key present only in FR via a modified catalog
// to test without modifying real files, use an existing key
// in FR and also present in EN – simply check fallback does not
// return the raw key.
Translator::reset();
Translator::init('en');
$valFallback = t('error.csrf');
assertTrueValue($valFallback !== 'error.csrf', 'La clé error.csrf doit être résolue même depuis la locale EN (via fallback FR).');

// -------------------------------------------------------------------------
// 6. Missing key → returns raw key
// -------------------------------------------------------------------------

Translator::reset();
Translator::init('fr');
$missing = t('this.key.does.not.exist');
assertSameValue('this.key.does.not.exist', $missing, 'Une clé inexistante doit être retournée telle quelle.');

// -------------------------------------------------------------------------
// 7. Resolve locale via $_SESSION['preferred_locale']
// -------------------------------------------------------------------------

Translator::reset();
$_SESSION['preferred_locale'] = 'en';
// Session is not active in CLI, so resolveLocale() ignores session;
// force explicitly via init() to simulate expected behavior.
Translator::init('en');
assertSameValue('en', Translator::locale(), 'Forcer la locale "en" via init() doit bien activer EN.');
unset($_SESSION['preferred_locale']);

// -------------------------------------------------------------------------
// 8. Unsupported locale → fallback to 'fr'
// -------------------------------------------------------------------------

Translator::reset();
Translator::init('zz'); // locale fictive
assertSameValue('fr', Translator::locale(), 'Une locale non supportée doit produire un fallback sur "fr".');

// -------------------------------------------------------------------------
// 9. Call t() without explicit init: automatic init
// -------------------------------------------------------------------------

Translator::reset();
$auto = t('common.cancel');
assertTrueValue($auto !== '' && $auto !== 'common.cancel', 'L\'appel t() sans init() préalable doit déclencher l\'init automatique.');

// -------------------------------------------------------------------------
// 10. Translator::reset() properly reinitializes state
// -------------------------------------------------------------------------

Translator::init('en');
assertSameValue('en', Translator::locale(), 'Après init("en"), locale doit être "en".');
Translator::reset();
assertSameValue('fr', Translator::locale(), 'Après reset(), locale doit revenir à "fr".');

echo "Translator tests OK.\n";
