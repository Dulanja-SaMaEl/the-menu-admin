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

class PosMealsDetailedSheet implements FromArray, WithTitle, WithStyles, ShouldAutoSize
{
    protected ?Carbon $startDate;
    protected ?Carbon $endDate;
    protected int $headerRow = 4;
    protected int $lastDataRow = 4;
    protected int $grandTotalRow = 5;

    public function __construct(?Carbon $startDate, ?Carbon $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function title(): string
    {
        return '🛒 POS Terminal Items';
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
            if ($this->startDate->toDateString() === $this->endDate->toDateString()) {
                $dayLabel = $this->startDate->isToday() ? 'Today' : ($this->startDate->isYesterday() ? 'Yesterday' : $this->startDate->format('l'));
                $dateLabel = $this->startDate->format('F d, Y') . " ({$dayLabel})";
            } else {
                $dateLabel = $this->startDate->format('M d, Y') . ' to ' . $this->endDate->format('M d, Y');
            }
        } elseif ($this->startDate) {
            $dateLabel = 'From ' . $this->startDate->format('M d, Y');
        }

        $items = OrderItem::with(['food', 'order.user'])
            ->whereHas('order', function ($q) use ($applyDate) {
                $q->where('channel', 'pos');
                $applyDate($q, 'created_at');
                $q->whereNotIn('status', ['cancelled']);
            })
            ->latest('id')
            ->get();

        $rows = [];

        // Title Block
        $rows[] = ['MENU — POS COUNTER SALES & BUFFET ITEMS AUDIT'];
        $rows[] = ['Detailed Item-by-Item Breakdown of In-Store Sales (Dine-In & Takeaway)'];
        $rows[] = ['Active Date Window: ' . $dateLabel . '   |   Total Sold Lines: ' . $items->count() . '   |   Exported: ' . now()->format('Y-m-d H:i')];

        // Table Header
        $rows[] = [
            '#',
            'Receipt #',
            'Date',
            'Time',
            'Item / Beverage Name',
            'Category',
            'Portion Size',
            'Volume (ml)',
            'Order Mode',
            'Qty Sold',
            'Unit Price (Rs.)',
            'Line Total (Rs.)',
            'Payment Method',
            'Cashier / Staff',
        ];

        $totQty = 0;
        $totRev = 0.0;

        if ($items->isEmpty()) {
            $rows[] = ['1', '—', '—', '—', 'No POS sales recorded within this date range', '—', '—', '—', '—', 0, 0, 0, '—', '—'];
            $this->lastDataRow = count($rows);
        } else {
            foreach ($items as $idx => $it) {
                $order = $it->order;
                $food = $it->food;
                $qty = (int) $it->quantity;
                $price = (float) $it->price;
                $lineTotal = round($qty * $price, 2);

                $totQty += $qty;
                $totRev += $lineTotal;

                $orderCode = $order?->order_number ?: ('ORD-' . str_pad($order?->id ?? 0, 5, '0', STR_PAD_LEFT));
                $dateStr = $order?->created_at ? $order->created_at->format('Y-m-d') : '—';
                $timeStr = $order?->created_at ? $order->created_at->format('h:i A') : '—';

                $itemName = $food?->name ?: ('POS Item #' . $it->food_id);
                $category = $food?->type ?: 'Buffet / Retail';
                $portion = $food?->portion_size ?: ($food?->servings ?: '1 Portion');
                $volume = $food?->volume_ml ? ($food->volume_ml . ' ml') : '—';

                // Order mode
                $mode = $order?->order_mode ?: '';
                if (!$mode && $order?->address) {
                    $mode = str_contains($order->address, 'Dine-In') ? 'dine_in' : 'takeaway';
                }
                $modeLabel = match(strtolower($mode)) {
                    'dine_in' => '🍽️ Dine-In',
                    'takeaway' => '🥡 Take Away',
                    default => 'Take Away',
                };

                $payMethod = $order?->payment_method ?: 'Cash';
                $cashier = $order?->user?->name ?: 'POS Terminal';

                $rows[] = [
                    $idx + 1,
                    $orderCode,
                    $dateStr,
                    $timeStr,
                    $itemName,
                    $category,
                    $portion,
                    $volume,
                    $modeLabel,
                    $qty,
                    $price,
                    $lineTotal,
                    $payMethod,
                    $cashier,
                ];
            }
            $this->lastDataRow = count($rows);
        }

        // Grand Total row
        $rows[] = [
            'TOTALS',
            '',
            '',
            '',
            'All POS Items & Beverages Combined',
            '',
            '',
            '',
            '',
            $totQty,
            '',
            $totRev,
            '',
            '',
        ];
        $this->grandTotalRow = count($rows);

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        // Title Block
        $sheet->mergeCells('A1:N1');
        $sheet->mergeCells('A2:N2');
        $sheet->mergeCells('A3:N3');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FF0A2E2A');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB('FF4B5563');
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(9)->getColor()->setARGB('FF0D5C4E');

        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension($this->headerRow)->setRowHeight(24);

        // Header Styling
        $headerRange = 'A' . $this->headerRow . ':N' . $this->headerRow;
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0A2E2A']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('E' . $this->headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Data Rows Styling
        $firstDataRow = $this->headerRow + 1;
        if ($this->lastDataRow >= $firstDataRow) {
            $dataRange = 'A' . $firstDataRow . ':N' . $this->lastDataRow;
            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']]],
            ]);

            $sheet->getStyle('A' . $firstDataRow . ':A' . $this->lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $firstDataRow . ':D' . $this->lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H' . $firstDataRow . ':I' . $this->lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('J' . $firstDataRow . ':J' . $this->lastDataRow)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('K' . $firstDataRow . ':L' . $this->lastDataRow)->getNumberFormat()->setFormatCode('[$Rs. ]#,##0.00');

            // Zebra striping
            for ($r = $firstDataRow; $r <= $this->lastDataRow; $r++) {
                if ($r % 2 === 0) {
                    $sheet->getStyle('A' . $r . ':N' . $r)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9FAF8');
                }
            }
        }

        // Grand Total row styling
        $gtRange = 'A' . $this->grandTotalRow . ':N' . $this->grandTotalRow;
        $sheet->getStyle($gtRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FF0A2E2A']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFEF3C7']],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFC9A050']],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['argb' => 'FF0A2E2A']],
            ],
        ]);
        $sheet->getStyle('J' . $this->grandTotalRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('L' . $this->grandTotalRow)->getNumberFormat()->setFormatCode('[$Rs. ]#,##0.00');

        // Freeze panes so header row stays pinned
        $sheet->freezePane('A' . ($this->headerRow + 1));

        return [];
    }
}
