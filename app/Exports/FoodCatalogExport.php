<?php

namespace App\Exports;

use App\Exports\Sheets\FoodCatalogSheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class FoodCatalogExport implements WithMultipleSheets, Export
{
    use Exportable;

    protected string $channel;
    protected string $sort;
    protected ?string $type;

    public function __construct(string $channel = 'all', string $sort = 'name', ?string $type = 'all')
    {
        $this->channel = strtolower($channel ?: 'all');
        $this->sort = strtolower($sort ?: 'name');
        $this->type = $type;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];

        if ($this->channel === 'pos') {
            $sheets[] = new FoodCatalogSheet('pos', $this->sort, $this->type, '🛒 POS Terminal Items');
        } elseif ($this->channel === 'app') {
            $sheets[] = new FoodCatalogSheet('app', $this->sort, $this->type, '📱 Mobile App Meals');
        } elseif ($this->channel === 'beverage') {
            $sheets[] = new FoodCatalogSheet('beverage', $this->sort, $this->type, '🍹 Beverages & Drinks');
        } else {
            // All Channels Multi-Sheet Workbook
            $sheets[] = new FoodCatalogSheet('all', $this->sort, $this->type, 'All Catalog Items');
            $sheets[] = new FoodCatalogSheet('pos', $this->sort, $this->type, '🛒 POS Terminal Items');
            $sheets[] = new FoodCatalogSheet('app', $this->sort, $this->type, '📱 Mobile App Meals');
            $sheets[] = new FoodCatalogSheet('beverage', $this->sort, $this->type, '🍹 Beverages & Drinks');
        }

        return $sheets;
    }
}
