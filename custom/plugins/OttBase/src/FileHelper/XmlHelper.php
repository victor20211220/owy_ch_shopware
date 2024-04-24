<?php declare(strict_types=1);

namespace Ott\Base\FileHelper;

class XmlHelper
{
    public const NODE_DEPTH = 'depth';
    public const NODE_NAME = 'name';
    private \XMLReader $reader;

    public function getXmlAsArray(string $filePath): array
    {
        $xml = simplexml_load_string(file_get_contents($filePath), 'SimpleXMLElement', \LIBXML_NOCDATA);

        return json_decode(json_encode($xml, \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR);
    }

    public function getDataCount(string $filePath, array $nodes): int
    {
        $this->openFile($filePath);
        $i = 0;
        while (null !== $this->next($nodes)) {
            ++$i;
        }

        $this->close();

        return $i;
    }

    public function openFile(string $filePath): void
    {
        $this->reader = new \XMLReader();
        $this->reader->open($filePath);
    }

    public function getFirstNode(string $filePath, int $depth, int $offset = 1): ?array
    {
        $this->openFile($filePath);
        $i = 0;
        while ($this->reader->read()) {
            if (
                $this->reader->depth === $depth
                && \XMLReader::ELEMENT === $this->reader->nodeType
            ) {
                ++$i;
                if ($i === $offset) {
                    try {
                        return [
                            'name' => $this->reader->localName,
                            'node' => (array) new \SimpleXMLElement($this->reader->readOuterXML()),
                        ];
                    } catch (\Exception $exception) {
                        return null;
                    }
                }
            }
        }
        $this->close();

        return null;
    }

    /**
     * $nodes = [
     *     [
     *          'depth' => 1,
     *          'name'  => 'Artikel'
     *     ]
     * ].
     */
    public function next(array $nodes): ?\SimpleXMLElement
    {
        foreach ($nodes as $node) {
            while ($this->reader->read()) {
                if ($this->reader->depth === $node[static::NODE_DEPTH] && $this->reader->localName === $node[static::NODE_NAME] && \XMLReader::ELEMENT === $this->reader->nodeType) {
                    try {
                        return new \SimpleXMLElement($this->reader->readOuterXML());
                    } catch (\Exception $exception) {
                        return null;
                    }
                }
            }
        }

        return null;
    }

    public function close(): void
    {
        $this->reader->close();
    }

    public function convertFile(string $filePath, string $encoding = 'UTF-8'): void
    {
        $fileContents = file_get_contents($filePath);
        file_put_contents($filePath, mb_convert_encoding($fileContents, $encoding, mb_detect_encoding($fileContents, 'UTF-8, ISO-8859-1, ISO-8859-15', true)));
        unset($fileContents);
    }
}
