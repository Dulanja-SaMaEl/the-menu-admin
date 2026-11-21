<?php

namespace App\Exports\Sheets;

use App\Models\OrderItem;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TopDishesPerformanceSheet implements FromArray, WithTitle, WithStyles, ShouldAutoSize
{
    protected ?Carbon $startDate;
    protected ?Carbon $endDate;
    protected string $channel;
    protected int $headerRow = 4;
    protected int $lastDataRow = 4;
    protected int $grandTotalRow = 5;

    public function __construct(?Carbon $startDate, ?Carbon $endDate, string $channel = 'all')
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->channel = $channel;
    }

    public function title(): string
    {
        return '🏆 Top Dishes Ranking';
    }

    public function array(): array
    {
        $applyDate = function ($q, $col = 'created_at') {
            if ($this->startDate && $this->endDate) {
                $q->whereBetween($col, [$this->startDate, $this->endDate]);
            } elseif ($this->startDate) {
                $q->where($col, '>=', $this->startDate);
            } elseif ($this->endDate) {
                $q->where($col, '<=', $this->endDate);
            }
        };

        $dateLabel = 'All Dates';
        if ($this->startDate && $this->endDate) {
            $dateLabel = $this->startDate->format('Y-m-d') . ' to ' . $this->endDate->format('Y-m-d');
        } elseif ($this->startDate) {
            $dateLabel = 'From ' . $this->startDate->format('Y-m-d');
        }

        $itemsQuery = OrderItem::with(['food', 'order'])
            ->whereHas('order', function ($q) use ($applyDate) {
                if ($this->channel === 'pos') {
                    $q->where('channel', 'pos');
                } elseif ($this->channel === 'app') {
                    $q->where(function ($sub) {
                        $sub->where('channel', 'app')->orWhereNull('channel');
                    });
                }
                $applyDate($q, 'created_at');
                $q->whereNotIn('status', ['cancelled']);
            });

        $items = $itemsQuery->get();

        $dishMap = [];
        $totalRev = 0.0;
        $totalQty = 0;

        foreach ($items as $it) {
            $f = $it->food;
            $fId = $it->food_id ?: ($f?->name ?? 'Custom Item');
            $fName = $f?->name ?: ('Dish #' . $it->food_id);
            $cat = $f?->type ?: 'Culinary Preparation';
            $qty = (int) $it->quantity;
            $rev = (float) ($it->price * $qty);
            $orderChan = strtolower($it->order?->channel ?? 'app') === 'pos' ? 'pos' : 'app';

            $totalRev += $rev;
            $totalQty += $qty;

            if (!isset($dishMap[$fId])) {
                $dishMap[$fId] = [
                    'name' => $fName,
                    'category' => $cat,
                    'pos_qty' => 0,
                    'app_qty' => 0,
                    'total_qty' => 0,
                    'total_rev' => 0.0,
                ];
            }

            if ($orderChan === 'pos') {
                $dishMap[$fId]['pos_qty'] += $qty;
            } else {
                $dishMap[$fId]['app_qty'] += $qty;
            }

            $dishMap[$fId]['total_qty'] += $qty;
            $dishMap[$fId]['total_rev'] += $rev;
        }

        uasort($dishMap, fn($a, $b) => $b['total_rev'] <=> $a['total_rev']);

        $rows = [];

        // Title Block
        $rows[] = ['MENU — DISH & BEVERAGE CULINARY PERFORMANCE RANKINGS'];
        $rows[] = ['All Dishes, Portions, and Beverages Ranked by Volume & Revenue Contribution'];
        $rows[] = ['Date Window: ' . $dateLabel . '   |   Total Portions Sold: ' . $totalQty . '   |   Gross Culinary Inflow: Rs. ' . number_format($totalRev, 2)];

        // Header Row
        $rows[] = [
            'Rank',
            'Dish / Item Name',
            'Primary Channel',
            'Category',
            'Portions Sold',
            'Gross Revenue (Rs.)',
            'Average Price (Rs.)',
            'Revenue Contribution %',
        ];

        if (empty($dishMap)) {
            $rows[] = ['1', 'No meal data available for this range', '—', '—', 0, 0, 0, '0.0%'];
            $this->lastDataRow = count($rows);
        } else {
            $rank = 1;
            foreach ($dishMap as $d) {
                $share = $totalRev > 0 ? round(($d['total_rev'] / $totalRev) * 100, 1) : 0.0;
                $avgP = $d['total_qty'] > 0 ? round($d['total_rev'] / $d['total_qty'], 2) : 0.0;

                $chanLabel = 'Both Channels';
                if ($d['pos_qty'] > 0 && $d['app_qty'] === 0) {
                    $chanLabel = '🛒 POS Counter Only';
                } elseif ($d['app_qty'] > 0 && $d['pos_qty'] === 0) {
                    $chanLabel = '📱 Mobile App Only';
                }

                $rows[] = [
                    $rank,
                    $d['name'],
                    $chanLabel,
                    $d['category'],
                    $d['total_qty'],
                    $d['total_rev'],
                    $avgP,
                    $share . '%',
                ];
                $rank++;
            }
            $this->lastDataRow = count($rows);
        }

        // Grand Total row
        $rows[] = [
            'TOTALS',
            'Consolidated All Dishes & Items',
            '',
            '',
            $totalQty,
            $totalRev,
            $totalQty > 0 ? round($totalRev / $totalQty, 2) : 0.0,
            '100.0%',
        ];
        $this->grandTotalRow = count($rows);

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        // Title Block
        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');
        $sheet->mergeCells('A3:H3');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FF0A2E2A');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB('FF4B5563');
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(9)->getColor()->setARGB('FF0D5C4E');

        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension($this->headerRow)->setRowHeight(24);

        // Header Styling
        $headerRange = 'A' . $this->headerRow . ':H' . $this->headerRow;
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0A2E2A']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('B' . $this->headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Data Rows Styling
        $firstDataRow = $this->headerRow + 1;
        if ($this->lastDataRow >= $firstDataRow) {
            $dataRange = 'A' . $firstDataRow . ':H' . $this->lastDataRow;
            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']]],
            ]);

            $sheet->getStyle('A' . $firstDataRow . ':A' . $this->lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $firstDataRow . ':C' . $this->lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $firstDataRow . ':E' . $this->lastDataRow)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('F' . $firstDataRow . ':G' . $this->lastDataRow)->getNumberFormat()->setFormatCode('[$Rs. ]#,##0.00');
            $sheet->getStyle('H' . $firstDataRow . ':H' . $this->lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Highlight top 3 dishes
            if ($this->lastDataRow >= $firstDataRow) {
                $sheet->getStyle('A' . $firstDataRow . ':H' . $firstDataRow)->getFont()->setBold(true);
                $sheet->getStyle('A' . $firstDataRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFEF3C7'); // Gold for #1
            }

            // Zebra striping
            for ($r = $firstDataRow; $r <= $this->lastDataRow; $r++) {
                if ($r % 2 === 0 && $r !== $firstDataRow) {
                    $sheet->getStyle('A' . $r . ':H' . $r)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9FAF8');
                }
            }
        }

        // Grand Total row styling
        $gtRange = 'A' . $this->grandTotalRow . ':H' . $this->grandTotalRow;
        $sheet->getStyle($gtRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FF0A2E2A']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFEF3C7']],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFC9A050']],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['argb' => 'FF0A2E2A']],
            ],
        ]);
        $sheet->getStyle('E' . $this->grandTotalRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('F' . $this->grandTotalRow . ':G' . $this->grandTotalRow)->getNumberFormat()->setFormatCode('[$Rs. ]#,##0.00');

        $sheet->freezePane('A' . ($this->headerRow + 1));

        return [];
    }
}
