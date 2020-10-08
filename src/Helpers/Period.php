<?php
namespace SapiStudio\Backup\Helpers;

use Carbon\Carbon;

class Period
{
    protected $startDate;

    protected $endDate;

    /** Period::__construct()*/
    public function __construct(Carbon $startDate, Carbon $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /** Period::getStartDate()*/
    public function getStartDate()
    {
        return $this->startDate->copy();
    }
    
    /** Period::getEndDate()*/
    public function getEndDate()
    {
        return $this->endDate->copy();
    }
}