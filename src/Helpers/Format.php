<?php
namespace SapiStudio\Backup\Helpers;

use Carbon\Carbon;

class Format
{
    public static function getHumanReadableSize($sizeInBytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        if ($sizeInBytes === 0) {
            return '0 '.$units[1];
        }
        for ($i = 0; $sizeInBytes > 1024; ++$i) {
            $sizeInBytes /= 1024;
        }

        return round($sizeInBytes, 2).' '.$units[$i];
    }

    public static function getEmoji($bool)
    {
        return ($bool) ? "\xe2\x9c\x85" : "\xe2\x9d\x8c";
    }

    public static function ageInDays(Carbon $date)
    {
        return number_format(round($date->diffInMinutes() / (24 * 60), 2), 2).' ('.$date->diffForHumans().')';
    }
}