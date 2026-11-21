<?php

namespace App\Exports;

use App\Exports\Sheets\AppMealsDetailedSheet;
use App\Exports\Sheets\MealExecutiveSummarySheet;
use App\Exports\Sheets\PosMealsDetailedSheet;
use App\Exports\Sheets\TopDishesPerformanceSheet;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MealAnalyticsExport implements WithMultipleSheets, Export
{
    use Exportable;

    protected ?Carbon $startDate;
    protected ?Carbon $endDate;
    protected string $range;
    protected string $channel;

    public function __construct(?Carbon $startDate, ?Carbon $endDate, string $range = 'all', string $channel = 'all')
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->range = $range;
        $this->channel = strtolower($channel ?: 'all');
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];

        // 1. Executive Summary Sheet (Always included)
        $sheets[] = new MealExecutiveSummarySheet($this->startDate, $this->endDate, $this->range, $this->channel);

        // 2. POS Terminal Sales Sheet
        if ($this->channel === 'all' || $this->channel === 'pos') {
            $sheets[] = new PosMealsDetailedSheet($this->startDate, $this->endDate);
        }

        // 3. Mobile App Deliveries Sheet
        if ($this->channel === 'all' || $this->channel === 'app') {
            $sheets[] = new AppMealsDetailedSheet($this->startDate, $this->endDate);
        }

        // 4. Top Performing Dishes Ranking Sheet
        $sheets[] = new TopDishesPerformanceSheet($this->startDate, $this->endDate, $this->channel);

        return $sheets;
    }
}
