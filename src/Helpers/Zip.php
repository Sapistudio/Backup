<?php
namespace SapiStudio\Backup\Helpers;

use ZipArchive;

class Zip
{
    protected $zipFile;
    protected $fileCount = 0;
    protected $pathToZip;
    
    public static function create($pathToZip)
    {
        return new static($pathToZip);
    }

    public function __construct($pathToZip)
    {
        $this->zipFile      = new ZipArchive();
        $this->pathToZip    = $pathToZip;
        $this->open($pathToZip);
    }

    public function getZipPath()
    {
        return $this->pathToZip;
    }

    public function getSize()
    {
        return filesize($this->pathToZip);
    }

    protected function open()
    {
        $this->zipFile->open($this->pathToZip, ZipArchive::CREATE);
    }

    protected function close()
    {
        $this->zipFile->close();
    }

    public function add($files)
    {
        if (is_string($files))
            $files = [$files];
        $this->open();
        foreach($files as $nameInZip => $file) {
            $nameInZip = (is_int($nameInZip)) ? null : $nameInZip;
            $this->zipFile->addFile($file, $nameInZip);
            ++$this->fileCount;
        }
        $this->zipFile->addFromString('manifest.txt', implode("\n",array_keys($files))."\n".'Total files:'.$this->fileCount);
        $this->close();
        return $this;
    }

    public function count()
    {
        return $this->fileCount;
    }
}