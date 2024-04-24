<?php declare(strict_types=1);

namespace Ott\Base\RemoteClient;

class SftpClient extends AbstractRemoteClient implements RemoteClientInterface
{
    /** @var resource|bool|mixed */
    public $connection;
    private const SFTP_STREAM_SCHEME = 'ssh2.sftp://%s%s';
    private string $sftpStream;
    private string $rootStream;

    public function __construct(array $config = [])
    {
        if (!empty($config)) {
            $this->connect($config);
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

        $this->connection = ssh2_connect(
            $config['host'],
            $config['port']
        );

        if (!$this->connection) {
            throw new \Exception(static::ERROR_SERVER_CONNECTION);
        }

        $loginResult = ssh2_auth_password(
            $this->connection,
            $config['user'],
            $config['pass']
        );

        if (!$loginResult) {
            throw new \Exception(static::ERROR_USER_CREDENTIALS);
        }

        $this->connection = ssh2_sftp($this->connection);

        // when connecting to the connections root dir we need to add ./ to the path instead of only connecting to /
        $this->sftpStream = sprintf(
            self::SFTP_STREAM_SCHEME,
            (int) $this->connection,
            $config['path'] ?? ''
        );

        $this->rootStream = $this->sftpStream;

        return true;
    }

    public function get(string $sourceFile, string $targetFile): bool
    {
        $stream = @fopen($this->sftpStream . $sourceFile, 'r');

        $contents = @fread($stream, filesize($this->sftpStream . $sourceFile));
        $result = file_put_contents($targetFile, $contents);

        @fclose($stream);

        return $result;
    }

    public function getList(string $sourcePath = '.'): array
    {
        if ('.' !== $sourcePath) {
            $this->chDir($sourcePath);
        }

        $list = [];
        if ($dh = opendir($this->sftpStream)) {
            while (false !== ($file = readdir($dh))) {
                if (!\in_array($file, ['.', '..']) && !$this->isDir($this->sftpStream . $file)) {
                    $list[] = $file;
                }
            }
            closedir($dh);
        }

        return $list;
    }

    public function put(string $sourceFile, string $targetFile): bool
    {
        $stream = @fopen($this->sftpStream . $targetFile, 'w');
        $result = @fwrite($stream, file_get_contents($sourceFile));

        @fclose($stream);

        return $result;
    }

    public function delete(string $file): bool
    {
        return unlink($this->sftpStream . $file);
    }

    public function chDir(string $path): void
    {
        $this->sftpStream = '/' !== $path ? str_replace('./', '', $this->rootStream) . $path : $this->rootStream;
    }

    public function isDir(string $name): bool
    {
        return is_dir($this->sftpStream . $name);
    }

    public function close(): bool
    {
        return ssh2_disconnect($this->connection);
    }
}
