<?php
namespace SapiStudio\Backup\Helpers;

use Illuminate\Support\Collection;

class BackupCollection extends Collection
{
    /** BackupCollection::createFromFiles()*/
    public static function createFromFiles(array $files)
    {
        return (new static($files))
            ->filter(function ($path) {return pathinfo($path, PATHINFO_EXTENSION) === 'zip';})
            ->map(function($path){return new BackupFile($path);})
            ->sortByDesc(function (BackupFile $backup) {return $backup->date()->timestamp;})
            ->values();
    }

    /** BackupCollection::convertToList()*/
    public function convertToList()
    {
        return $this->map(function (BackupFile $backup) {return ['name' => $backup->backupName(),'size' => Format::getHumanReadableSize($backup->size()),'date' => $backup->date()->toDateTimeString()];})->toArray();
    }
    
    /** BackupCollection::newest()*/
    public function newest()
    {
        return $this->first();
    }

    /** BackupCollection::oldest()*/
    public function oldest()
    {
        return $this->filter(function (BackupFile $backup) {return $backup->exists();})->last();
    }

    /** BackupCollection::size() */
    public function size()
    {
        return $this->reduce(function ($totalSize, BackupFile $backup) {return $totalSize + $backup->size();}, 0);
    }
}