<?php
namespace SapiStudio\Backup\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class BackupCleanup
{
    protected $deletedBackups = null;
    protected $currentBackups = null;
    protected $newestBackup;
    protected $cleanupConfig = [
        'numberOfBackupsPerPeriod'      => 0,/** The number of backups must be kept on period. */
        'keepDailyBackupsForDays'       => 16,/** The number of days for which all daily backups must be kept.*/
        'keepWeeklyBackupsForWeeks'     => 8,/** The number of weeks for which all one weekly backup must be kept.*/
        'keepMonthlyBackupsForMonths'   => 4,/** The number of months for which one monthly backup must be kept.*/
        'deleteOldestBackupsWhenUsingMoreMegabytesThan' => 5000,/** After cleaning up backups, remove the oldest backup until this number of megabytes has been reached.*/
    ];
    
    /** BackupCleanup::make()*/
    public static function make(BackupCollection $backups){
        return new static($backups);
    }
    
    /** BackupCleanup::__construct()*/
    public function __construct(BackupCollection $backups){
        $this->currentBackups   = $backups;
        $this->deletedBackups   = new BackupCollection();
    }
    
    /** BackupCleanup::setupCleanupConfig()*/
    public function setupCleanupConfig($configData = []){
        $configData             = (!is_array($configData)) ? [] : $configData;
        $this->cleanupConfig    = array_merge($this->cleanupConfig,$configData);
        print_R($this->cleanupConfig);die();
        return $this;
    }
    
    /** BackupCleanup::deleteOldBackups()*/
    public function deleteOldBackups()
    {
        $backups            = $this->currentBackups;
        $this->newestBackup = $backups->shift();// Don't ever delete the newest backup.
        $dateRanges         = $this->calculateDateRanges();
        $backupsPerPeriod   = $dateRanges->map(function (Period $period) use ($backups) {
            return $backups->filter(function (BackupFile $backup) use ($period){
                return $backup->date()->between($period->getStartDate(),$period->getEndDate());
            });
        });
        $backupsPerPeriod['daily']      = $this->groupByDateFormat($backupsPerPeriod['daily'], 'Ymd');
        $backupsPerPeriod['weekly']     = $this->groupByDateFormat($backupsPerPeriod['weekly'], 'YW');
        $backupsPerPeriod['monthly']    = $this->groupByDateFormat($backupsPerPeriod['monthly'], 'Ym');
        
        $this->removeBackupsForAllPeriods($backupsPerPeriod);
        $this->removeBackupsOlderThan($dateRanges['monthly']->getEndDate(),$backups);
        $this->removeOldestsBackupsUntilUsingMaximumStorage($backups);
        $cleanedList = $this->deletedBackups->convertToList();
        $this->deletedBackups->each(function (BackupFile $backup){
            $backup->delete();
        });
        return $cleanedList;
    }

    /** BackupCleanup::calculateDateRanges()*/
    protected function calculateDateRanges()
    {
        $daily      = new Period(Carbon::now()->subDays(0),Carbon::now()->subDays(0)->subDays($this->cleanupConfig['keepDailyBackupsForDays']));
        $weekly     = new Period($daily->getEndDate(),$daily->getEndDate()->subWeeks($this->cleanupConfig['keepWeeklyBackupsForWeeks']));
        $monthly    = new Period($weekly->getEndDate(),$weekly->getEndDate()->subMonths($this->cleanupConfig['keepMonthlyBackupsForMonths']));
        return collect(compact('daily', 'weekly', 'monthly'));
    }

    /** BackupCleanup::groupByDateFormat()*/
    protected function groupByDateFormat(Collection $backups, $dateFormat)
    {
        return $backups->groupBy(function (BackupFile $backup) use ($dateFormat) {
            return $backup->date()->format($dateFormat);
        });
    }

    /** BackupCleanup::removeBackupsForAllPeriods()*/
    protected function removeBackupsForAllPeriods($backupsPerPeriod)
    {
        $numberOfBackups = $this->cleanupConfig['numberOfBackupsPerPeriod'];
        foreach ($backupsPerPeriod as $periodName => $groupedBackupsByDateProperty) {
            $groupedBackupsByDateProperty->each(function (Collection $group) use ($numberOfBackups) {
                if($numberOfBackups > 0){
                    for($keepBackup = 0;$keepBackup < $numberOfBackups;$keepBackup++)
                        $group->shift();
                }
                $group->each(function (BackupFile $backup){
                    $this->deleteBackup($backup);
                });
            });
        }
    }

    /** BackupCleanup::removeBackupsOlderThan()*/
    protected function removeBackupsOlderThan(Carbon $endDate, BackupCollection $backups)
    {
        $backups->filter(function (BackupFile $backup) use ($endDate) {
            return $backup->exists() && $backup->date()->lt($endDate);
        })->each(function (BackupFile $backup) {
            $this->deleteBackup($backup);
        });
    }

    /** BackupCleanup::removeOldestsBackupsUntilUsingMaximumStorage()*/
    protected function removeOldestsBackupsUntilUsingMaximumStorage(BackupCollection $backups)
    {
        $maximumSize = $this->cleanupConfig['deleteOldestBackupsWhenUsingMoreMegabytesThan'] * 1024 * 1024;
        if (!$oldestBackup = $backups->oldest()) {
            return;
        }
        if (($backups->size() + $this->newestBackup->size()) <= $maximumSize) {
            return;
        }
        $oldestBackup->delete();
        $this->removeOldestsBackupsUntilUsingMaximumStorage($backups);
    }
    
    /** BackupCleanup::removeOldestsBackupsUntilUsingMaximumStorage()*/
    protected function deleteBackup(BackupFile $backup){
        $this->deletedBackups->push($backup);
    }
}
