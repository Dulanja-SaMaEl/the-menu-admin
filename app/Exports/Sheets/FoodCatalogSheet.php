<?php

namespace App\Exports\Sheets;

use App\Models\Food;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FoodCatalogSheet implements FromArray, WithTitle, WithStyles, ShouldAutoSize
{
    protected string $channel;
    protected string $sort;
    protected ?string $type;
    protected string $title;

    protected int $headerRow = 5;
    protected int $lastDataRow = 5;
    protected int $summaryRow = 6;

    public function __construct(string $channel = 'all', string $sort = 'name', ?string $type = 'all', string $title = 'Catalog Items')
    {
        $this->channel = strtolower($channel ?: 'all');
        $this->sort = strtolower($sort ?: 'name');
        $this->type = $type;
        $this->title = $title;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function array(): array
    {
        $query = Food::with('ingredients');

        // Channel filter
        if ($this->channel === 'pos') {
            $query->where(function ($q) {
                $q->where('channel', 'pos')->orWhere('channel', 'all');
            });
        } elseif ($this->channel === 'app') {
            $query->where(function ($q) {
                $q->where('channel', 'app')->orWhere('channel', 'all')->orWhereNull('channel');
            });
        } elseif ($this->channel === 'beverage') {
            $query->where(function ($q) {
                $q->where('type', 'Beverage')->orWhere('type', 'like', '%beverage%');
            });
        }

        // Category filter if specified
        if ($this->type && strtolower($this->type) !== 'all') {
            $typeInput = trim($this->type);
            $lower = strtolower($typeInput);
            $query->where(function ($q) use ($typeInput, $lower) {
                if (str_contains($lower, 'break')) {
                    $q->where('type', 'Breakfast')->orWhere('type', 'like', '%breakfast%');
                } elseif (str_contains($lower, 'lunch')) {
                    $q->where('type', 'Lunch')->orWhere('type', 'like', '%lunch%');
                } elseif (str_contains($lower, 'din')) {
                    $q->where('type', 'Dinner')->orWhere('type', 'like', '%dinner%');
                } elseif (str_contains($lower, 'bev') || str_contains($lower, 'drink')) {
                    $q->where('type', 'Beverage')->orWhere('type', 'like', '%beverage%');
                } elseif (str_contains($lower, 'rice') || str_contains($lower, 'carb')) {
                    $q->where('type', 'Rice & Carbs')->orWhere('type', 'like', '%rice%')->orWhere('type', 'like', '%carb%');
                } elseif (str_contains($lower, 'curr') || str_contains($lower, 'meat') || str_contains($lower, 'fish')) {
                    $q->where('type', 'Curries & Meats')->orWhere('type', 'like', '%curr%')->orWhere('type', 'like', '%meat%')->orWhere('type', 'like', '%fish%');
                } elseif (str_contains($lower, 'veg') || str_contains($lower, 'sambol')) {
                    $q->where('type', 'Veggies & Sambols')->orWhere('type', 'like', '%veg%')->orWhere('type', 'like', '%sambol%');
                } else {
                    $q->where('type', $typeInput)->orWhere('type', 'like', "%{$lower}%");
                }
            });
        }

        // Sort application
        switch ($this->sort) {
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'price_asc':
                $query->orderBy('price', 'asc')->orderBy('name', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc')->orderBy('name', 'asc');
                break;
            case 'category':
                $query->orderBy('type', 'asc')->orderBy('name', 'asc');
                break;
            case 'channel':
                $query->orderByRaw("CASE WHEN channel = 'pos' THEN 1 WHEN channel = 'all' THEN 2 ELSE 3 END")->orderBy('name', 'asc');
                break;
            case 'newest':
                $query->latest('id');
                break;
            case 'oldest':
                $query->oldest('id');
                break;
            case 'calories_asc':
                $query->orderBy('calories', 'asc')->orderBy('name', 'asc');
                break;
            case 'calories_desc':
                $query->orderBy('calories', 'desc')->orderBy('name', 'asc');
                break;
            case 'rating':
                $query->orderByDesc('rating')->orderBy('name', 'asc');
                break;
            case 'name_asc':
            case 'name':
            default:
                $query->orderBy('name', 'asc');
                break;
        }

        $items = $query->get();

        // Sort label
        $sortLabels = [
            'name'          => 'Name (A to Z)',
            'name_asc'      => 'Name (A to Z)',
            'name_desc'     => 'Name (Z to A)',
            'price_asc'     => 'Price: Low to High',
            'price_desc'    => 'Price: High to Low',
            'category'      => 'Category / Menu Type',
            'channel'       => 'Channel (POS Buffet First)',
            'newest'        => 'Newest Added First',
            'oldest'        => 'Oldest First',
            'calories_asc'  => 'Calories: Low to High',
            'calories_desc' => 'Calories: High to Low',
            'rating'        => 'Customer Rating',
        ];
        $activeSortLabel = $sortLabels[$this->sort] ?? 'Alphabetical';

        $channelLabels = [
            'pos'      => 'POS Terminal & Buffet Counter Only',
            'app'      => 'Customer Mobile App Only',
            'beverage' => 'Beverages & Drinks Only',
            'all'      => 'All Sales Channels (POS & Mobile App)',
        ];
        $activeChannelLabel = $channelLabels[$this->channel] ?? 'All Channels';

        $rows = [];

        // Row 1: Header Banner
        $rows[] = ['MENU — CULINARY & BEVERAGE CATALOG INVENTORY'];
        // Row 2: Subtitle Scope
        $rows[] = ["Scope: {$activeChannelLabel}   |   Sorting Method: {$activeSortLabel}   |   Total Items: " . $items->count()];
        // Row 3: Metadata
        $rows[] = ['Date Generated: ' . Carbon::now()->format('F d, Y (l) \a\t H:i') . '   |   Brand: MENU Luxury Management'];
        // Row 4: Spacer
        $rows[] = [];

        // Row 5: Column Headers
        $rows[] = [
            '#',
            'Item ID',
            'Item / Dish Name',
            'Category / Type',
            'Sales Channel',
            'Portion Size',
            'Brand / Supplier',
            'Volume (ml)',
            'Price (LKR)',
            'Calories (kcal)',
            'Prep Time',
            'Difficulty',
            'Rating',
            'Available Service Days',
            'Ingredients List',
            'Description',
        ];

        $this->headerRow = count($rows);

        $totalPrice = 0.0;
        $totalCalories = 0;

        foreach ($items as $idx => $f) {
            $price = (float) $f->price;
            $calories = (int) $f->calories;
            $totalPrice += $price;
            $totalCalories += $calories;

            $channelText = match ($f->channel) {
                'pos' => 'POS Only',
                'app' => 'App Only',
                default => 'Everywhere (POS & App)',
            };

            $daysText = 'All Days';
            if (!empty($f->available_days)) {
                $days = is_array($f->available_days) ? $f->available_days : json_decode($f->available_days, true);
                if (is_array($days) && count($days) > 0) {
                    $daysText = implode(', ', $days);
                }
            }

            $ingredientsText = '';
            if ($f->relationLoaded('ingredients') && $f->ingredients->count() > 0) {
                $ingredientsText = $f->ingredients->pluck('name')->implode(', ');
            }

            $rows[] = [
                $idx + 1,
                'MENU-ITM-' . str_pad($f->id, 4, '0', STR_PAD_LEFT),
                $f->name,
                $f->type ?: 'Standard',
                $channelText,
                $f->portion_size ?: ($f->servings ?: '1 Portion'),
                $f->brand ?: ($f->type === 'Beverage' ? 'In-House' : 'Kitchen'),
                $f->volume_ml ? $f->volume_ml . ' ml' : '—',
                $price,
                $calories > 0 ? $calories : '—',
                $f->prep_time ?: '15 min',
                $f->difficulty ?: 'Easy',
                $f->rating ? number_format($f->rating, 1) . ' / 5.0' : '—',
                $daysText,
                $ingredientsText,
                $f->description ?: '',
            ];
        }

        $this->lastDataRow = count($rows);

        // Summary Row
        $avgPrice = $items->count() > 0 ? round($totalPrice / $items->count(), 2) : 0.0;
        $avgCalories = $items->count() > 0 ? round($totalCalories / $items->count()) : 0;

        $rows[] = []; // Spacer
        $rows[] = [
            'TOTALS / AVERAGES',
            'Total Items: ' . $items->count(),
            '—',
            '—',
            '—',
            '—',
            '—',
            'Avg Price:',
            $avgPrice,
            'Avg Kcal: ' . $avgCalories,
            '—',
            '—',
            '—',
            '—',
            '—',
            'Inventory Audit Complete',
        ];
        $this->summaryRow = count($rows);

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $hRow = $this->headerRow;
        $lastRow = $this->lastDataRow;
        $sumRow = $this->summaryRow;

        // Merge Row 1 to Row 3 for Title Banner
        $sheet->mergeCells("A1:P1");
        $sheet->mergeCells("A2:P2");
        $sheet->mergeCells("A3:P3");

        $styles = [
            // Row 1: Brand Title Banner
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 15,
                    'color' => ['argb' => 'FFC9A050'], // Gold
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF0A2E2A'], // Dark Emerald
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'indent' => 1,
                ],
            ],

            // Row 2: Subtitle
            2 => [
                'font' => [
                    'bold' => true,
                    'size' => 10,
                    'color' => ['argb' => 'FFE6F2F0'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF0D3A35'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'indent' => 1,
                ],
            ],

            // Row 3: Generation Date
            3 => [
                'font' => [
                    'size' => 9,
                    'italic' => true,
                    'color' => ['argb' => 'FFA3C2BD'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF0D3A35'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'indent' => 1,
                ],
            ],

            // Row 5: Table Column Headers
            $hRow => [
                'font' => [
                    'bold' => true,
                    'size' => 10,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF0A2E2A'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'bottom' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['argb' => 'FFC9A050'],
                    ],
                ],
            ],
        ];

        // Format data rows
        if ($lastRow >= $hRow + 1) {
            // Numbers alignment
            $sheet->getStyle("A" . ($hRow + 1) . ":B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E" . ($hRow + 1) . ":H{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("J" . ($hRow + 1) . ":M{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Currency formatting for Price column (I)
            $sheet->getStyle("I" . ($hRow + 1) . ":I{$lastRow}")->getNumberFormat()->setFormatCode('[$Rs. ]#,##0.00');
            $sheet->getStyle("I" . ($hRow + 1) . ":I{$lastRow}")->getFont()->setBold(true)->getColor()->setARGB('FF0A2E2A');

            // Zebra Striping & Thin Borders
            for ($r = $hRow + 1; $r <= $lastRow; $r++) {
                if ($r % 2 === 0) {
                    $sheet->getStyle("A{$r}:P{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFBFBF9');
                }
                $sheet->getStyle("A{$r}:P{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE5E7EB');
            }
        }

        // Summary Row
        if ($sumRow > $lastRow) {
            $sheet->getStyle("A{$sumRow}:P{$sumRow}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 10,
                    'color' => ['argb' => 'FF0A2E2A'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFFBF5E8'], // Soft Gold Tint
                ],
                'borders' => [
                    'top' => [
                        'borderStyle' => Border::BORDER_DOUBLE,
                        'color' => ['argb' => 'FFC9A050'],
                    ],
                    'bottom' => [
                        'borderStyle' => Border::BORDER_DOUBLE,
                        'color' => ['argb' => 'FFC9A050'],
                    ],
                ],
            ]);

            $sheet->getStyle("I{$sumRow}")->getNumberFormat()->setFormatCode('[$Rs. ]#,##0.00');
            $sheet->getStyle("I{$sumRow}")->getFont()->getColor()->setARGB('FFC9A050');
        }

        // Freeze panes so header row stays pinned
        $sheet->freezePane('A' . ($hRow + 1));

        return $styles;
    }
}
