<?php
namespace fpdidev;

use Symfony\Component\Finder\Finder;
use hanneskod\classtools\Iterator\SplFileInfo;

class Converter
{
    private $finder, $header, $writer;

    public function __construct(Finder $finder, $header = '<?php ', FpdiWriter $writer = null)
    {
        $this->finder = $finder;
        $this->header = $header;
        $this->writer = $writer ?: new FpdiWriter;
    }

    public function convert(callable $output)
    {
        foreach ($this->finder as $fileInfo) {
            $fileInfo = new SplFileInfo($fileInfo);
            $output(
                self::buildFilename($fileInfo),
                $this->header . $this->writer->write($fileInfo->getReader()->readAll())
            );
        }
    }

    public static function buildFilename(SplFileInfo $fileInfo)
    {
        $defs = $fileInfo->getReader()->getDefinitionNames();
        if (isset($defs[0])) {
            return $defs[0] . '.php';
        }
        return $fileInfo->getFileName();
    }
}
