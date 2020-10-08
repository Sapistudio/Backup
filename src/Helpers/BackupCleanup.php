<?php
namespace SapiStudio\Backup\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class BackupCleanup
{
    protected $newestBackup;
    protected $cleanupConfig = [
            'keepAllBackupsForDays'         => 0,/** The number of days for which all backups must be kept. */
            'keepDailyBackupsForDays'       => 16,/** The number of days for which all daily backups must be kept.*/
            'keepWeeklyBackupsForWeeks'     => 8,/** The number of weeks for which all one weekly backup must be kept.*/
            'keepMonthlyBackupsForMonths'   => 4,/** The number of months for which one monthly backup must be kept.*/
            'keepYearlyBackupsForYears'     => 2,/** The number of years for which one yearly backup must be kept. */
            'deleteOldestBackupsWhenUsingMoreMegabytesThan' => 5000,/** After cleaning up backups, remove the oldest backup until this number of megabytes has been reached.*/
        ];
    
    /** BackupCleanup::deleteOldBackups()*/
    public function deleteOldBackups(BackupCollection $backups)
    {
        // Don't ever delete the newest backup.
        $this->newestBackup = $backups->shift();
        $dateRanges         = $this->calculateDateRanges();
        $backupsPerPeriod   = $dateRanges->map(function (Period $period) use ($backups) {
            return $backups->filter(function (BackupFile $backup) use ($period) {
                return $backup->date()->between($period->getStartDate(),$period->getEndDate());
            });
        });
        $backupsPerPeriod['daily']      = $this->groupByDateFormat($backupsPerPeriod['daily'], 'Ymd');
        $backupsPerPeriod['weekly']     = $this->groupByDateFormat($backupsPerPeriod['weekly'], 'YW');
        $backupsPerPeriod['monthly']    = $this->groupByDateFormat($backupsPerPeriod['monthly'], 'Ym');
        $backupsPerPeriod['yearly']     = $this->groupByDateFormat($backupsPerPeriod['yearly'], 'Y');
        $this->removeBackupsForAllPeriodsExceptOne($backupsPerPeriod);
        $this->removeBackupsOlderThan($dateRanges['yearly']->getEndDate(), $backups);
        $this->removeOldestsBackupsUntilUsingMaximumStorage($backups);
    }

    /** BackupCleanup::calculateDateRanges()*/
    protected function calculateDateRanges()
    {
        $daily      = new Period(
            Carbon::now()->subDays($this->cleanupConfig['keepAllBackupsForDays']),
            Carbon::now()->subDays($this->cleanupConfig['keepAllBackupsForDays'])->subDays($this->cleanupConfig['keepDailyBackupsForDays'])
        );
        $weekly     = new Period($daily->getEndDate(),$daily->getEndDate()->subWeeks($this->cleanupConfig['keepWeeklyBackupsForWeeks']));
        $monthly    = new Period($weekly->getEndDate(),$weekly->getEndDate()->subMonths($this->cleanupConfig['keepMonthlyBackupsForMonths']));
        $yearly     = new Period($monthly->getEndDate(),$monthly->getEndDate()->subYears($this->cleanupConfig['keepYearlyBackupsForYears']));
        return collect(compact('daily', 'weekly', 'monthly', 'yearly'));
    }

    /** BackupCleanup::groupByDateFormat()*/
    protected function groupByDateFormat(Collection $backups, $dateFormat)
    {
        return $backups->groupBy(function (BackupFile $backup) use ($dateFormat) {
            return $backup->date()->format($dateFormat);
        });
    }

    /** BackupCleanup::removeBackupsForAllPeriodsExceptOne()*/
    protected function removeBackupsForAllPeriodsExceptOne($backupsPerPeriod)
    {
        foreach ($backupsPerPeriod as $periodName => $groupedBackupsByDateProperty) {
            $groupedBackupsByDateProperty->each(function (Collection $group) {
                $group->shift();
                $group->each(function (BackupFile $backup) {
                    $backup->delete();
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
            $backup->delete();
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
}