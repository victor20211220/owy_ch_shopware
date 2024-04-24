<?php declare(strict_types=1);

namespace Ott\Base\RemoteClient;

class FtpClient extends AbstractRemoteClient implements RemoteClientInterface
{
    /** @var resource|bool|mixed */
    public $connection;
    private int $transferMode = \FTP_ASCII;
    private bool $isPassiveMode = false;

    public function __construct(?string $url = null, array $config = [])
    {
        if (!empty($config)) {
            $this->connect($config);
        }
        if (null !== $url) {
            $this->initFtpConnectionByUrl($url);
        }
    }

    public function connect(array $config = []): bool
    {
        if (
            empty($config)
            || empty($config['user'])
            || empty($config['pass'])
            || empty($config['host'])
        ) {
            throw new \Exception(static::ERROR_MISSING_USER_CREDENTIALS);
        }

        $this->transferMode = $config['mode'] ?? \FTP_ASCII;

        $this->connection = ftp_connect($config['host']);

        if (!$this->connection) {
            throw new \Exception(static::ERROR_SERVER_CONNECTION);
        }

        $loginResult = ftp_login(
            $this->connection,
            $config['user'],
            $config['pass']
        );

        if (isset($config['path']) && !empty($config['path'])) {
            $this->chDir($config['path']);
        }

        ftp_pasv($this->connection, $config['passive'] ?? false);

        if (!$loginResult) {
            throw new \Exception(static::ERROR_USER_CREDENTIALS);
        }

        return true;
    }

    public function initFtpConnectionByUrl(string $url): void
    {
        // Split FTP URI into:
        // $match[0] = ftp://username:password@sld.domain.tld/path1/path2/
        // $match[1] = username
        // $match[2] = password
        // $match[3] = sld.domain.tld
        // $match[4] = /path
        preg_match('/ftp:\\/\\/(.*?):(.*?)@(.*?)(\\/.*)/i', $url, $match);

        if (4 > \count($match)) {
            throw new \Exception(static::ERROR_MISSING_USER_CREDENTIALS);
        }

        $this->connection = ftp_connect($match[3]);
        if (ftp_login($this->connection, $match[1], $match[2])) {
            ftp_pasv($this->connection, $this->isPassiveMode);
            ftp_chdir($this->connection, $match[4]);
        }
    }

    public function getList(string $sourcePath = '.'): array
    {
        $contents = ftp_nlist($this->connection, $sourcePath);

        $entries = [];
        if (!empty($contents)) {
            foreach ($contents as $content) {
                if (\in_array($content, ['.', '..'])) {
                    continue;
                }
                $entries[] = $content;
            }
        }

        return $entries;
    }

    public function get(string $sourceFile, string $targetFile): bool
    {
        if (-1 === ftp_size($this->connection, $sourceFile)) {
            return false;
        }

        return ftp_get($this->connection, $targetFile, $sourceFile, $this->transferMode);
    }

    public function put(string $sourceFile, string $targetFile): bool
    {
        return ftp_put(
            $this->connection,
            $targetFile,
            $sourceFile,
            $this->transferMode
        );
    }

    public function delete(string $file): bool
    {
        return ftp_delete($this->connection, $file);
    }

    public function setTransferMode(int $mode): void
    {
        $this->transferMode = $mode;
    }

    public function setPassiveMode(bool $isPassive): void
    {
        $this->isPassiveMode = $isPassive;
        if ($this->connection) {
            ftp_pasv($this->connection, $this->isPassiveMode);
        }
    }

    public function chDir(string $path): void
    {
        ftp_chdir($this->connection, $path);
    }

    public function close(): bool
    {
        return ftp_close($this->connection);
    }
}
