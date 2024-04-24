<?php declare(strict_types=1);

namespace Ott\Base\FileHelper;

class CsvHelper
{
    /** @var resource */
    private $fileHandle;
    private array $headers;

    public function getCsvAsArray(
        string $filePath,
        string $delimiter = ';',
        string $enclosure = '"',
        bool $assoc = true
    ): array
    {
        $data = [];
        $this->openFile($filePath);
        $i = 0;
        while (!feof($this->fileHandle)) {
            if (0 === $i) {
                $this->getHeaders($delimiter, $enclosure);
                ++$i;
                continue;
            }

            $assocRow = $this->next($delimiter, $enclosure, $assoc);
            $data[] = $assocRow;
        }

        $this->close();

        return $data;
    }

    public function getRowCount(string $filePath): int
    {
        $this->openFile($filePath);
        $i = 0;
        while (!feof($this->fileHandle)) {
            fgets($this->fileHandle);
            ++$i;
        }

        $this->close();

        return $i;
    }

    public function openFile(string $filePath, string $mode = 'r+'): void
    {
        $this->fileHandle = fopen($filePath, $mode);

        if (!$this->fileHandle) {
            throw new \Exception(sprintf('File %s does not exist or cannot be opened', $filePath));
        }
    }

    public function getHeaders(string $delimiter = ';', string $enclosure = '"', int $length = 4096): void
    {
        $headers = fgetcsv($this->fileHandle, $length, $delimiter, $enclosure);
        $this->headers = $headers ?: [];
    }

    public function next(
        string $delimiter = ';',
        string $enclosure = '"',
        bool $assoc = true,
        int $length = 4096
    ): ?array
    {
        if (!feof($this->fileHandle)) {
            $row = fgetcsv($this->fileHandle, $length, $delimiter, $enclosure);

            if (!\is_array($row) || empty($row)) {
                return null;
            }

            if ($assoc) {
                $assocRow = [];
                foreach ($row as $key => $value) {
                    $assocRow[$this->headers[$key]] = $value;
                }

                $row = $assocRow;
            }

            return $row;
        }

        return null;
    }

    public function put(array $data, string $delimiter = ';', $enclosure = '"', $escape = '\\'): bool
    {
        return (bool) fputcsv($this->fileHandle, $data, $delimiter, $enclosure, $escape);
    }

    public function close(): void
    {
        fclose($this->fileHandle);
        $this->headers = [];
        $this->fileHandle = null;
    }

    public function convertFile(string $filePath, string $encoding = 'UTF-8'): void
    {
        $fileContents = file_get_contents($filePath);
        file_put_contents(
            $filePath,
            mb_convert_encoding(
                $fileContents,
                $encoding,
                mb_detect_encoding($fileContents, 'UTF-8, ISO-8859-1, ISO-8859-15', true)
            )
        );
        unset($fileContents);
    }

    public function removeUtf8Bom(string $filePath): void
    {
        $fileContents = file_get_contents($filePath);
        $bom = pack('H*', 'EFBBBF');
        $fileContents = preg_replace(sprintf('/^%s/', $bom), '', $fileContents);
        if (empty($fileContents)) {
            return;
        }
        file_put_contents($filePath, $fileContents);
        unset($fileContents);
    }

    public function fixUnescapedEnclosures(string $filePath, string $delimiter = ';', string $enclosure = '"'): void
    {
        $fileContents = file_get_contents($filePath);
        $fileContents = preg_replace(
            sprintf(
                '/(?<![\r\n]|%s|^|\\\)\%s(?![\r\n%s])/',
                $delimiter,
                $enclosure,
                $delimiter
            ),
            sprintf(
                '\\\%s',
                $enclosure
            ),
            $fileContents
        );
        if (empty($fileContents)) {
            return;
        }
        file_put_contents($filePath, $fileContents);
        unset($fileContents);
    }
}
