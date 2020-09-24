<?php
namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithTitle;

class ServiceSheet implements WithTitle
{
    private $month;
    private $year;

    public function __construct(int $year, int $month)
    {
        $this->month = $month;
        $this->year  = $year;
    }


    /**
     * @return string
     */
    public function title(): string
    {
        return 'Month ' . $this->month;
    }
}
