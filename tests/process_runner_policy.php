<?php

$root = dirname(__DIR__);
$scanRoots = [
    $root . DIRECTORY_SEPARATOR . 'src',
    $root . DIRECTORY_SEPARATOR . 'public',
];

$forbiddenFunctions = ['exec', 'shell_exec', 'system', 'passthru', 'popen'];

// Documented allowlist: proc_open() is centralized in ProcessRunner only.
$procOpenAllowlist = [
    'src' . DIRECTORY_SEPARATOR . 'ProcessRunner.php',
];

// Documented allowlist: no exec/shell_exec/system/passthru/popen call is allowed in src/ or public/.
$functionAllowlist = [];

$violations = [];

foreach ($scanRoots as $scanRoot) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($scanRoot, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
            continue;
        }

        $fullPath = $file->getPathname();
        $relativePath = ltrim(str_replace($root . DIRECTORY_SEPARATOR, '', $fullPath), DIRECTORY_SEPARATOR);
        $code = file_get_contents($fullPath);
        if ($code === false) {
            $violations[] = $relativePath . ': impossible de lire le fichier';
            continue;
        }

        $tokens = token_get_all($code);
        $count = count($tokens);
        for ($index = 0; $index < $count; $index++) {
            $token = $tokens[$index];
            if (!is_array($token) || $token[0] !== T_STRING) {
                continue;
            }

            $name = strtolower($token[1]);
            if (!in_array($name, array_merge($forbiddenFunctions, ['proc_open']), true)) {
                continue;
            }

            $prev = previousSignificantToken($tokens, $index);
            if (is_array($prev) && in_array($prev[0], [T_FUNCTION, T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NEW], true)) {
                continue;
            }
            if ($prev === '->' || $prev === '::') {
                continue;
            }

            $next = nextSignificantToken($tokens, $index);
            if ($next !== '(') {
                continue;
            }

            if ($name === 'proc_open') {
                if (!in_array($relativePath, $procOpenAllowlist, true)) {
                    $violations[] = $relativePath . ':' . $token[2] . ' interdit proc_open() hors ProcessRunner';
                }
                continue;
            }

            if (!in_array($relativePath, $functionAllowlist, true)) {
                $violations[] = $relativePath . ':' . $token[2] . ' interdit ' . $name . '()';
            }
        }
    }
}

if ($violations !== []) {
    fwrite(STDERR, "Violations de politique ProcessRunner detectees:\n");
    foreach ($violations as $violation) {
        fwrite(STDERR, ' - ' . $violation . "\n");
    }
    exit(1);
}

fwrite(STDOUT, "Politique ProcessRunner OK\n");

function previousSignificantToken(array $tokens, int $index): mixed
{
    for ($cursor = $index - 1; $cursor >= 0; $cursor--) {
        $token = $tokens[$cursor];
        if (is_array($token)) {
            if (in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            return $token;
        }

        if (trim($token) === '') {
            continue;
        }
        return $token;
    }

    return null;
}

function nextSignificantToken(array $tokens, int $index): mixed
{
    $count = count($tokens);
    for ($cursor = $index + 1; $cursor < $count; $cursor++) {
        $token = $tokens[$cursor];
        if (is_array($token)) {
            if (in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            return $token[1];
        }

        if (trim($token) === '') {
            continue;
        }
        return $token;
    }

    return null;
}
