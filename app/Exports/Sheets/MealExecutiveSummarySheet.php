<?php

namespace App\Exports\Sheets;

use App\Models\Order;
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

class MealExecutiveSummarySheet implements FromArray, WithTitle, WithStyles, ShouldAutoSize
{
    protected ?Carbon $startDate;
    protected ?Carbon $endDate;
    protected string $range;
    protected string $channel;

    protected int $channelTableHeaderRow = 5;
    protected int $channelTableEndRow = 10;
    protected int $catTableHeaderRow = 13;
    protected int $catTableEndRow = 13;
    protected int $grandTotalRow = 14;

    public function __construct(?Carbon $startDate, ?Carbon $endDate, string $range = 'all', string $channel = 'all')
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->range = $range;
        $this->channel = $channel;
    }

    public function title(): string
    {
        return 'Executive Summary';
    }

    public function array(): array
    {
        // Date scope closure
        $applyDate = function ($q, $col = 'created_at') {
            if ($this->startDate && $this->endDate) {
                $q->whereBetween($col, [$this->startDate, $this->endDate]);
            } elseif ($this->startDate) {
                $q->where($col, '>=', $this->startDate);
            } elseif ($this->endDate) {
                $q->where($col, '<=', $this->endDate);
            }
        };

        // Date Label Description
        $dateLabel = 'All Historical Records';
        if ($this->startDate && $this->endDate) {
            $dateLabel = $this->startDate->format('M d, Y') . ' to ' . $this->endDate->format('M d, Y');
        } elseif ($this->startDate) {
            $dateLabel = 'From ' . $this->startDate->format('M d, Y') . ' onwards';
        }

        // 1. Fetch all items in scope
        $posItemsQuery = OrderItem::with(['food', 'order'])
            ->whereHas('order', function ($q) use ($applyDate) {
                $q->where('channel', 'pos');
                $applyDate($q, 'created_at');
                $q->whereNotIn('status', ['cancelled']);
            });
        $posItems = $posItemsQuery->get();

        $appItemsQuery = OrderItem::with(['food', 'order'])
            ->whereHas('order', function ($q) use ($applyDate) {
                $q->where(function ($sub) {
                    $sub->where('channel', 'app')->orWhereNull('channel');
                });
                $applyDate($q, 'created_at');
                $q->whereNotIn('status', ['cancelled']);
            });
        $appItems = $appItemsQuery->get();

        // High-level aggregates
        $posRevenue = (float) $posItems->sum(fn($it) => $it->price * $it->quantity);
        $posQuantity = (int) $posItems->sum('quantity');
        $posOrdersCount = $posItems->pluck('order_id')->unique()->count();

        $appRevenue = (float) $appItems->sum(fn($it) => $it->price * $it->quantity);
        $appQuantity = (int) $appItems->sum('quantity');
        $appOrdersCount = $appItems->pluck('order_id')->unique()->count();

        $totalRevenue = $posRevenue + $appRevenue;
        $totalQuantity = $posQuantity + $appQuantity;
        $totalOrdersCount = $posOrdersCount + $appOrdersCount;

        $posRevPct = $totalRevenue > 0 ? round(($posRevenue / $totalRevenue) * 100, 1) : 0.0;
        $appRevPct = $totalRevenue > 0 ? round(($appRevenue / $totalRevenue) * 100, 1) : 0.0;
        $posQtyPct = $totalQuantity > 0 ? round(($posQuantity / $totalQuantity) * 100, 1) : 0.0;
        $appQtyPct = $totalQuantity > 0 ? round(($appQuantity / $totalQuantity) * 100, 1) : 0.0;
        $posOrdPct = $totalOrdersCount > 0 ? round(($posOrdersCount / $totalOrdersCount) * 100, 1) : 0.0;
        $appOrdPct = $totalOrdersCount > 0 ? round(($appOrdersCount / $totalOrdersCount) * 100, 1) : 0.0;

        $avgPosTicket = $posOrdersCount > 0 ? round($posRevenue / $posOrdersCount, 2) : 0.0;
        $avgAppTicket = $appOrdersCount > 0 ? round($appRevenue / $appOrdersCount, 2) : 0.0;
        $avgTicket = $totalOrdersCount > 0 ? round($totalRevenue / $totalOrdersCount, 2) : 0.0;

        $avgPosPortion = $posQuantity > 0 ? round($posRevenue / $posQuantity, 2) : 0.0;
        $avgAppPortion = $appQuantity > 0 ? round($appRevenue / $appQuantity, 2) : 0.0;
        $avgPortion = $totalQuantity > 0 ? round($totalRevenue / $totalQuantity, 2) : 0.0;

        // Category breakdown aggregation
        $categoryData = [];

        // Aggregate POS categories
        foreach ($posItems as $it) {
            $cat = $it->food?->type ?: 'Buffet / Retail';
            if (!isset($categoryData[$cat])) {
                $categoryData[$cat] = ['channel' => 'POS Terminal', 'quantity' => 0, 'revenue' => 0.0];
            }
            $categoryData[$cat]['quantity'] += (int) $it->quantity;
            $categoryData[$cat]['revenue'] += (float) ($it->price * $it->quantity);
        }

        // Aggregate App categories
        foreach ($appItems as $it) {
            $cat = $it->food?->type ?: $it->meal_type;
            if (!$cat || str_contains(strtolower($cat), 'lunch')) {
                $cat = 'Sri Lankan Style Lunch Menu';
            } elseif (str_contains(strtolower($cat), 'dinner')) {
                $cat = 'Sri Lankan Style Dinner Menu';
            } elseif (str_contains(strtolower($cat), 'break') || str_contains(strtolower($cat), 'breakfast')) {
                $cat = 'Sri Lankan Style Breakfast Menu';
            }

            if (!isset($categoryData[$cat])) {
                $categoryData[$cat] = ['channel' => 'Mobile App', 'quantity' => 0, 'revenue' => 0.0];
            } else {
                if ($categoryData[$cat]['channel'] === 'POS Terminal') {
                    $categoryData[$cat]['channel'] = 'Both Channels';
                }
            }
            $categoryData[$cat]['quantity'] += (int) $it->quantity;
            $categoryData[$cat]['revenue'] += (float) ($it->price * $it->quantity);
        }

        // Sort categories by revenue desc
        uasort($categoryData, fn($a, $b) => $b['revenue'] <=> $a['revenue']);

        $rows = [];

        // Header Title Block
        $rows[] = ['MENU — EXECUTIVE MEAL INTELLIGENCE & TELEMETRY REPORT'];
        $rows[] = ['Consolidated Audit: POS Terminal Items vs. Mobile App Scheduled Deliveries'];
        $rows[] = ['Date Window: ' . $dateLabel . '   |   Filter Preset: ' . ucfirst(str_replace('_', ' ', $this->range)) . '   |   Generated: ' . now()->format('Y-m-d H:i')];
        $rows[] = []; // Row 4 spacer

        // Section 1: Channel Comparison Table
        $rows[] = ['Operational Performance Metric', 'Consolidated (All Channels)', '🛒 POS Terminal Counter', '📱 Mobile App Deliveries', 'POS Share %', 'App Share %'];
        $this->channelTableHeaderRow = count($rows);

        $rows[] = ['Total Food Revenue (Rs.)', $totalRevenue, $posRevenue, $appRevenue, $posRevPct . '%', $appRevPct . '%'];
        $rows[] = ['Total Portions / Units Sold', $totalQuantity, $posQuantity, $appQuantity, $posQtyPct . '%', $appQtyPct . '%'];
        $rows[] = ['Total Orders / Transactions', $totalOrdersCount, $posOrdersCount, $appOrdersCount, $posOrdPct . '%', $appOrdPct . '%'];
        $rows[] = ['Average Ticket / Order Value (Rs.)', $avgTicket, $avgPosTicket, $avgAppTicket, '—', '—'];
        $rows[] = ['Average Price per Portion (Rs.)', $avgPortion, $avgPosPortion, $avgAppPortion, '—', '—'];
        $this->channelTableEndRow = count($rows);

        $rows[] = []; // Row 11 spacer
        $rows[] = ['2. MEAL CATEGORY PERFORMANCE & POPULARITY BREAKDOWN']; // Row 12
        $rows[] = ['Category / Menu Type', 'Sales Channel', 'Units Sold', 'Gross Revenue (Rs.)', 'Average Price (Rs.)', 'Revenue Share %'];
        $this->catTableHeaderRow = count($rows);

        foreach ($categoryData as $catName => $cData) {
            $share = $totalRevenue > 0 ? round(($cData['revenue'] / $totalRevenue) * 100, 1) : 0.0;
            $avgP = $cData['quantity'] > 0 ? round($cData['revenue'] / $cData['quantity'], 2) : 0.0;

            $rows[] = [
                $catName,
                $cData['channel'],
                $cData['quantity'],
                $cData['revenue'],
                $avgP,
                $share . '%',
            ];
        }

        $this->catTableEndRow = count($rows);

        // Grand Total row
        $rows[] = [
            'GRAND TOTAL (ALL CATEGORIES)',
            'Consolidated',
            $totalQuantity,
            $totalRevenue,
            $avgPortion,
            '100.0%',
        ];
        $this->grandTotalRow = count($rows);

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        // 1. Title block styling
        $sheet->mergeCells('A1:F1');
        $sheet->mergeCells('A2:F2');
        $sheet->mergeCells('A3:F3');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15)->getColor()->setARGB('FF0A2E2A');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB('FF4B5563');
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(9)->getColor()->setARGB('FF0D5C4E');

        // Row heights
        $sheet->getRowDimension(1)->setRowHeight(26);
        $sheet->getRowDimension(5)->setRowHeight(24);
        $sheet->getRowDimension($this->catTableHeaderRow)->setRowHeight(24);

        // 2. Channel Comparison Table styling
        $sheet->getStyle('A5:F5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0A2E2A']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet->getStyle('A6:A' . $this->channelTableEndRow)->getFont()->setBold(true);
        $sheet->getStyle('B6:D6')->getNumberFormat()->setFormatCode('[$Rs. ]#,##0.00');
        $sheet->getStyle('B7:D8')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('B9:D10')->getNumberFormat()->setFormatCode('[$Rs. ]#,##0.00');

        $sheet->getStyle('A6:F' . $this->channelTableEndRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']]],
        ]);

        // 3. Category Breakdown Banner & Table
        $sheet->getStyle('A12:F12')->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FF0A2E2A');

        $catHeader = 'A' . $this->catTableHeaderRow . ':F' . $this->catTableHeaderRow;
        $sheet->getStyle($catHeader)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0A2E2A']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Category data rows
        $firstCatDataRow = $this->catTableHeaderRow + 1;
        if ($this->catTableEndRow >= $firstCatDataRow) {
            $sheet->getStyle('C' . $firstCatDataRow . ':C' . $this->catTableEndRow)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('D' . $firstCatDataRow . ':E' . $this->catTableEndRow)->getNumberFormat()->setFormatCode('[$Rs. ]#,##0.00');
            $sheet->getStyle('F' . $firstCatDataRow . ':F' . $this->catTableEndRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->getStyle('A' . $firstCatDataRow . ':F' . $this->catTableEndRow)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']]],
            ]);

            // Subtle zebra striping
            for ($r = $firstCatDataRow; $r <= $this->catTableEndRow; $r++) {
                if ($r % 2 === 0) {
                    $sheet->getStyle('A' . $r . ':F' . $r)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9FAF8');
                }
            }
        }

        // Grand Total row styling
        $gtRange = 'A' . $this->grandTotalRow . ':F' . $this->grandTotalRow;
        $sheet->getStyle($gtRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FF0A2E2A']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFEF3C7']], // Soft luxury amber
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFC9A050']],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['argb' => 'FF0A2E2A']],
            ],
        ]);
        $sheet->getStyle('C' . $this->grandTotalRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('D' . $this->grandTotalRow . ':E' . $this->grandTotalRow)->getNumberFormat()->setFormatCode('[$Rs. ]#,##0.00');

        return [];
    }
}
