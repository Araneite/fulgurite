<?php
declare(strict_types=1);

final class FileSystem
{
    public static function resolveContainedDirectory(string $baseDir, string $requestedPath): string
    {
        $baseRealPath = realpath($baseDir);
        if ($baseRealPath === false || !is_dir($baseRealPath)) {
            throw new InvalidArgumentException('Dossier de staging invalide.');
        }

        $normalizedPath = self::normalizeSnapshotDirectoryPath($requestedPath);
        if ($normalizedPath === '/') {
            return $baseRealPath;
        }

        $candidatePath = $baseRealPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($normalizedPath, '/'));
        $candidateRealPath = realpath($candidatePath);
        if ($candidateRealPath === false || !is_dir($candidateRealPath)) {
            throw new RuntimeException('Le dossier restaure demande est introuvable.');
        }

        $normalizedBase = rtrim($baseRealPath, '/\\');
        if ($candidateRealPath !== $normalizedBase && !str_starts_with($candidateRealPath, $normalizedBase . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Le dossier restaure demande sort du staging temporaire.');
        }

        return $candidateRealPath;
    }

    public static function removeDirectory(string $path): void
    {
        $path = trim($path);
        if ($path === '' || !is_dir($path)) {
            return;
        }

        $path = FilesystemScopeGuard::assertMutableTree($path, 'delete');

        $items = scandir($path);
        if ($items === false) {
            if (!@rmdir($path)) {
                throw new RuntimeException('Impossible de supprimer le repertoire ' . $path);
            }
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $child = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($child) && !is_link($child)) {
                self::removeDirectory($child);
                continue;
            }

            if (!@unlink($child)) {
                throw new RuntimeException('Impossible de supprimer le chemin ' . $child);
            }
        }

        if (!@rmdir($path)) {
            throw new RuntimeException('Impossible de supprimer le repertoire ' . $path);
        }
    }

    public static function deleteFile(string $path, string $operation = 'delete'): void
    {
        $path = trim($path);
        if ($path === '') {
            return;
        }

        $canonicalPath = FilesystemScopeGuard::assertPathAllowed($path, $operation, true);
        if (!is_file($canonicalPath)) {
            throw new RuntimeException('Le chemin cible n est pas un fichier: ' . $canonicalPath);
        }
        if (!@unlink($canonicalPath)) {
            throw new RuntimeException('Impossible de supprimer le fichier ' . $canonicalPath);
        }
    }

    public static function createTarGzFromDirectory(string $sourceDir, string $targetTarGz, string $archiveRootName): bool
    {
        $sourceDir = rtrim($sourceDir, '/\\');
        if ($sourceDir === '' || !is_dir($sourceDir)) {
            return false;
        }

        $archiveRootName = trim($archiveRootName, '/\\');
        if ($archiveRootName === '') {
            $archiveRootName = basename($sourceDir);
        }

        $targetTarGz = rtrim($targetTarGz, '/\\');
        if ($targetTarGz === '') {
            return false;
        }

        $targetTar = preg_replace('/\.gz$/i', '', $targetTarGz);
        if (!is_string($targetTar) || $targetTar === '') {
            return false;
        }

        @unlink($targetTarGz);
        @unlink($targetTar);

        if (!class_exists('PharData') || !extension_loaded('zlib')) {
            return self::createTarGzWithTarBinary($sourceDir, $targetTarGz);
        }

        try {
            $archive = new PharData($targetTar);
            $archive->startBuffering();
            $archive->addEmptyDir($archiveRootName);

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                $pathName = $item->getPathname();
                $relative = substr($pathName, strlen($sourceDir) + 1);
                if ($relative === false) {
                    continue;
                }
                $archivePath = $archiveRootName . '/' . str_replace('\\', '/', $relative);

                if ($item->isDir()) {
                    $archive->addEmptyDir($archivePath);
                    continue;
                }

                $archive->addFile($pathName, $archivePath);
            }

            $archive->stopBuffering();
            $archive->compress(Phar::GZ);
            unset($archive);

            if (is_file($targetTar . '.gz') && $targetTar . '.gz' !== $targetTarGz) {
                @unlink($targetTarGz);
                rename($targetTar . '.gz', $targetTarGz);
            }

            @unlink($targetTar);
            return is_file($targetTarGz);
        } catch (Throwable $e) {
            @unlink($targetTar);
            @unlink($targetTar . '.gz');
            @unlink($targetTarGz);
            return self::createTarGzWithTarBinary($sourceDir, $targetTarGz);
        }
    }

    private static function createTarGzWithTarBinary(string $sourceDir, string $targetTarGz): bool
    {
        $result = ProcessRunner::run([
            'tar',
            '-czf',
            $targetTarGz,
            '-C',
            dirname($sourceDir),
            basename($sourceDir),
        ]);

        return !empty($result['success']) && is_file($targetTarGz);
    }

    public static function normalizeSnapshotDirectoryPath(string $path): string
    {
        $path = trim($path);
        if ($path === '' || $path === '.') {
            return '/';
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $path) === 1) {
            throw new InvalidArgumentException('Chemin de dossier invalide.');
        }
        if (str_contains($path, '\\')) {
            throw new InvalidArgumentException('Chemin de dossier invalide.');
        }

        $normalized = preg_replace('~/+~', '/', $path);
        if (!is_string($normalized) || $normalized === '') {
            throw new InvalidArgumentException('Chemin de dossier invalide.');
        }
        if (!str_starts_with($normalized, '/')) {
            throw new InvalidArgumentException('Chemin de dossier invalide.');
        }
        if (str_contains($normalized, ':')) {
            throw new InvalidArgumentException('Chemin de dossier invalide.');
        }
        if (preg_match('~(^|/)\.\.?(/|$)~', $normalized) === 1) {
            throw new InvalidArgumentException('Chemin de dossier invalide.');
        }

        $trimmed = rtrim($normalized, '/');
        return $trimmed === '' ? '/' : $trimmed;
    }
}
