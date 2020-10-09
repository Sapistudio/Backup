<?php
namespace SapiStudio\Backup\Helpers;

use Exception;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class BackupJobs
{
    protected $includeFilesAndDirectories;
    protected $excludeFilesAndDirectories;
    protected $backupDestination    = null;
    protected $filename             = null;
    protected $shouldFollowLinks    = false;
    protected $filesManifest        = [];
    protected $maximumFileSize      = null;
    protected $allowedMimeTypes     = null;
    protected $backupFiles          = [];
    protected static $consoleOutput = null;
    
    /** BackupJobs::__construct()*/
    public function __construct($backupDestination = null)
    {
        $this->setBackupDestination($backupDestination);
        $this->filename                     = date('Y-m-d-His').'.zip';/** default bk filename*/
        $this->includeFilesAndDirectories   = collect();
        $this->excludeFilesAndDirectories   = collect();
        self::loadConsoleOutput();
    }
    
    /** BackupJobs::__construct()*/
    protected static function loadConsoleOutput(){
        if(!self::$consoleOutput)
            self::$consoleOutput = \SapiStudio\Backup\Console\Base::createConsole();
        return self::$consoleOutput;
    }
    
    /** BackupJobs::runBackup()*/
    protected function runBackup()
    {
        $this->onlyCliAllowed();
        if (!$this->backupDestination || !is_dir($this->backupDestination))
            throw new Exception('A backup job cannot run without a destination to backup to!'.$this->backupDestination);
        try{
            consoleOutput()->comment('Starting backup...');
            $zip = Zip::create(rtrim($this->backupDestination,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$this->filename)->add($this->getSelectedFiles());
            consoleOutput()->comment('Backup completed!');
        }catch (Exception $exception) {
            consoleOutput()->error("Backup failed because: {$exception->getMessage()}.");
        }
    }
    
    /** BackupJobs::getBackups()*/
    protected function getBackups()
    {
        if(!$this->backupFiles){
            foreach((new Finder())->ignoreDotFiles(false)->ignoreVCS(false)->files()->in($this->getBackupDestination())->getIterator() as $file)
                $this->backupFiles[] = $file->getPathname();
        }
        return BackupCollection::createFromFiles($this->backupFiles);
    }
    
    /** BackupJobs::getSelectedFiles()*/
    protected function getSelectedFiles()
    {
        if ($this->includeFilesAndDirectories->isEmpty())
            return;
        $finder = (new Finder())->ignoreDotFiles(false)->ignoreVCS(false)->files();
        if ($this->shouldFollowLinks) {
            $finder->followLinks();
        }
        foreach ($this->includedFiles() as $includedFile)
            $this->filesManifest[] = $includedFile;
        $files = $finder->in($this->includedDirectories())->getIterator();
        consoleOutput()->startProgressBar(iterator_count($files));
        foreach ($files as $file) {
            consoleOutput()->updateProgressBar('Reading file: '.$file->getPathname());
            if ($this->shouldExclude($file))
                continue;
            $this->filesManifest[] = $file->getPathname();
        }
        return $this->filterNamesPath();
    }
    
    /** BackupJobs::getBackupDestination()*/
    protected function getBackupDestination(){
        return $this->backupDestination;
    }
    
    /** BackupJobs::setBackupDestination()*/
    protected function setBackupDestination($backupDestination = null)
    {
        $filesystem = (new FileSystem);
        if(!$backupDestination)
            throw new Exception('Invalid backup destination');
        if(!$filesystem->isDirectory($backupDestination)) {
            $filesystem->makeDirectory($backupDestination, 0755, true);
        }
        $this->backupDestination = $backupDestination;
        return $this;
    }
    
    /** BackupJobs::includedFiles()*/
    protected function includedFiles()
    {
        return $this->includeFilesAndDirectories->filter(function ($path) {return is_file($path);})->toArray();
    }

    /** BackupJobs::includedDirectories() */
    protected function includedDirectories()
    {
        return $this->includeFilesAndDirectories->reject(function ($path){return is_file($path);})->toArray();
    }

    /** BackupJobs::shouldExclude()*/
    protected function shouldExclude($path)
    {
        foreach ($this->excludeFilesAndDirectories as $excludedPath) {
            if(Str::startsWith($path, $excludedPath))
                return true;
            if($this->maximumFileSize){
                $filesize = (filesize($path)) / 1024 / 1024;
                if($filesize > $this->maximumFileSize)
                    return true;
            }
            if($this->allowedMimeTypes && !in_array(pathinfo($path, PATHINFO_EXTENSION),$this->allowedMimeTypes)){
                return true;
            }
        }
        return false;
    }
    
    /** BackupJobs::filterNamesPath()*/
    protected function filterNamesPath()
    {
        $keys           = [];
        $directories    = $this->includeFilesAndDirectories;
        array_walk($this->filesManifest, function($value, &$key) use ($directories,&$keys){
            $newKey             = null;
            $totalDirectories   = $directories->count();
            foreach($directories as $includedPath){
                $replaceValue   = ($totalDirectories > 1) ? basename($includedPath) : '';
                $value          = str_replace($includedPath,$replaceValue,$value);
            }
            $keys[] = trim($value,DIRECTORY_SEPARATOR);
        });
        $this->filesManifest = array_combine($keys, $this->filesManifest);
        return $this->filesManifest;
    }

    /** BackupJobs::sanitize()*/
    protected function sanitize($paths)
    {
        return (new Collection($paths))
            ->reject(function ($path)   {return $path == '';})
            ->flatMap(function ($path)  {return glob($path);})
            ->map(function ($path)      {return realpath($path);})
            ->reject(function ($path)   {return $path === false;});
    }
    
    /** BackupJobs::onlyCliAllowed()*/
    protected function onlyCliAllowed(){
        if((php_sapi_name() !== 'cli'))
            throw new Exception('Backup must run only from cli');
    }
}
