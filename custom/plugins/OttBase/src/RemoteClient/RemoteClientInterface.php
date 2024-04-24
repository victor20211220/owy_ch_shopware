<?php declare(strict_types=1);

namespace Ott\Base\RemoteClient;

interface RemoteClientInterface
{
    public function connect(array $config = []): bool;

    public function getList(string $sourcePath = '.'): array;

    public function get(string $sourceFile, string $targetFile): bool;

    public function put(string $sourceFile, string $targetFile): bool;

    public function delete(string $file): bool;

    public function chDir(string $path): void;

    public function close(): bool;
}
