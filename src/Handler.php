<?php
namespace SapiStudio\Backup;

class Handler extends Helpers\BackupJobs
{
    /** Handler::create() */
    public static function create($backupDestination = null)
    {
        return new static($backupDestination);
    }
    
    /** Handler::getConsoleOutput() */
    public static function getConsoleOutput(){
        return self::loadConsoleOutput();
    }
    
    /** Handler::setMaxFileSize() */
    public function setMaxFileSize($maxFileSize = null)
    {
        $this->maximumFileSize = (is_int($maxFileSize)) ? $maxFileSize : null;
        return $this;
    }
    
    /** Handler::setAllowedExtensions() */
    public function setAllowedExtensions($allowedExtensions = null)
    {
        $this->allowedMimeTypes = (is_array($allowedExtensions)) ? $allowedExtensions : null;
        return $this;
    }
    
    /** Handler::excludeFilesFrom() */
    public function excludeFilesFrom($excludeFilesAndDirectories)
    {
        $this->excludeFilesAndDirectories = $this->sanitize($excludeFilesAndDirectories);
        return $this;
    }
    
    /** Handler::includeFilesFrom() */
    public function includeFilesFrom($includeFilesAndDirectories)
    {
        $this->includeFilesAndDirectories = $this->sanitize($includeFilesAndDirectories);
        return $this;
    }

    /** Handler::shouldFollowLinks() */
    public function shouldFollowLinks($shouldFollowLinks)
    {
        $this->shouldFollowLinks = $shouldFollowLinks;
        return $this;
    }
    
    /** Handler::setFilename()*/
    public function setFilename($filename)
    {
        $this->filename = $filename;
        return $this;
    }
    
    /** Handler::createBackup()*/
    public function createBackup()
    {
        return $this->runBackup();
    }
    
    /** Handler::cleanupBackups()*/
    public function cleanupBackups()
    {
        $this->onlyCliAllowed();
        $deleteBackups = (new Helpers\BackupCleanup)->deleteOldBackups($this->getBackups());
        if($deleteBackups){
            consoleOutput()->question('following backups were deleted');
            consoleOutput()->outputTable($deleteBackups);
        }else{
            consoleOutput()->error('no old backup was found');
        }
    }
    
    /** Handler::listBackups()*/
    public function listBackups()
    {
        $data = ['backupFiles' => $this->getBackups()->convertToList(),'totalBackups' => $this->getAmountOfBackups(),'usedStorage' => $this->getUsedStorage()];
        return (php_sapi_name() === 'cli') ? consoleOutput()->outputTable($data['backupFiles']) : $data;
    }
    
    /** Handler::getUsedStorage()*/
    public function getUsedStorage()
    {
        return $this->getBackups()->size();
    }

    /** Handler::getNewestBackup()*/
    public function getNewestBackup()
    {
        return $this->getBackups()->newest();
    }
    
    /** Handler::getDateOfNewestBackup()*/
    public function getDateOfNewestBackup()
    {
        $newestBackup = $this->getNewestBackup();
        return (is_null($newestBackup)) ? null : $newestBackup->date();
    }
    
    /** Handler::getAmountOfBackups()*/
    public function getAmountOfBackups()
    {
        return $this->getBackups()->count();
    }
}
