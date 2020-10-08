<?php
namespace SapiStudio\Backup\Helpers;

use Carbon\Carbon;
use Illuminate\Filesystem\Filesystem;

class BackupFile
{
    protected $disk;
    protected $path;

    /** BackupFile::__construct()*/
    public function __construct($path)
    {
        $this->disk = new Filesystem();
        $this->path = $path;
    }

    /** BackupFile::path() */
    public function path()
    {
        return $this->path;
    }
    
    /** BackupFile::backupName()*/
    public function backupName()
    {
        return basename($this->path);
    }

    /** BackupFile::exists()*/
    public function exists()
    {
        return $this->disk->exists($this->path);
    }

    /** BackupFile::date()*/
    public function date()
    {
        return Carbon::createFromTimestamp($this->disk->lastModified($this->path));
    }
    
    /** BackupFile::size() */
    public function size()
    {
        return (!$this->exists()) ? 0 : $this->disk->size($this->path);
    }

    /** BackupFile::delete()*/
    public function delete()
    {
        $this->disk->delete($this->path);
    }
}