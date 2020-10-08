# Fork & adaptation after
(https://github.com/spatie/laravel-backup)

# Init
```php
use SapiStudio\Backup\Handler;

$bkHandler = Handler::create('/tmp/backups');// set up your backup folder location
$bkHandler->setMaxFileSize(5)//maximum filesize in Mb to be added to backup,skip it will add all files
->setAllowedExtensions(['php'])//desired extensions to backup,skip it will add all files
->includeFilesFrom(['/'])//the main directory to backup
->excludeFilesFrom(['/','/srv/www/storage/','/srv/www/images/','/srv/www/resources/'])//directories to ignore on backup
;
```
# Create backup
```php
$bkHandler->createBackup();// only runs from cli
```

# list backups
```php
$bkHandler->listBackups();
```

# cleanup backups
```php
// this is the default config,you do not have to passed it if you dont change it
$cleanupConfig = [
        'numberOfBackupsPerPeriod'      => 0,/** The number of backups must be kept on period. */
        'keepDailyBackupsForDays'       => 16,/** The number of days for which all daily backups must be kept.*/
        'keepWeeklyBackupsForWeeks'     => 8,/** The number of weeks for which all one weekly backup must be kept.*/
        'keepMonthlyBackupsForMonths'   => 4,/** The number of months for which one monthly backup must be kept.*/
        'deleteOldestBackupsWhenUsingMoreMegabytesThan' => 5000,/** After cleaning up backups, remove the oldest backup until this number of megabytes has been reached.*/
];
$bkHandler->cleanupBackups($cleanupConfig);// only runs from cli
```

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
