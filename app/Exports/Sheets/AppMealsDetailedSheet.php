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

class AppMealsDetailedSheet implements FromArray, WithTitle, WithStyles, ShouldAutoSize
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
        return '📱 Mobile App Meals';
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

        $items = OrderItem::with(['food', 'order.user', 'order.driver'])
            ->whereHas('order', function ($q) use ($applyDate) {
                $q->where(function ($sub) {
                    $sub->where('channel', 'app')->orWhereNull('channel');
                });
                $applyDate($q, 'created_at');
                $q->whereNotIn('status', ['cancelled']);
            })
            ->latest('id')
            ->get();

        $rows = [];

        // Title Block
        $rows[] = ['MENU — MOBILE APP MEAL DELIVERIES & SUBSCRIPTION AUDIT'];
        $rows[] = ['Detailed Breakdown of Scheduled Meal Plans, Customer Deliveries & Dish Dispatches'];
        $rows[] = ['Active Date Window: ' . $dateLabel . '   |   Total Delivered Portions: ' . $items->sum('quantity') . '   |   Exported: ' . now()->format('Y-m-d H:i')];

        // Table Header
        $rows[] = [
            '#',
            'Order #',
            'Scheduled Date',
            'Meal Session',
            'Menu / Dish Name',
            'Customer Name',
            'Customer Phone',
            'Delivery Address / Area',
            'Portions',
            'Unit Price (Rs.)',
            'Total Price (Rs.)',
            'Order Status',
            'Payment Method',
            'Assigned Driver',
        ];

        $totQty = 0;
        $totRev = 0.0;

        if ($items->isEmpty()) {
            $rows[] = ['1', '—', '—', '—', 'No Mobile App meal deliveries recorded within this date range', '—', '—', '—', 0, 0, 0, '—', '—', '—'];
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

                $orderCode = $order?->order_number ?: ('ORD-' . str_pad($order?->id ?? 0, 4, '0', STR_PAD_LEFT));
                $schedDate = $it->scheduled_date 
                    ? Carbon::parse($it->scheduled_date)->format('Y-m-d') 
                    : ($order?->created_at ? $order->created_at->format('Y-m-d') : 'Immediate');

                $session = \App\Http\Controllers\OrderController::normalizeMealType($it->meal_type, $food?->type);
                $sessionLabel = match(strtolower($session)) {
                    'breakfast' => '🍳 Breakfast',
                    'dinner' => '🌙 Dinner',
                    default => '☀️ Lunch',
                };

                $dishName = $food?->name ?: ($it->food_id ? ('Dish #' . $it->food_id) : 'Special Scheduled Dish');
                $cName = $order?->customer_name ?: ($order?->user?->name ?? 'Mobile Client');
                $cPhone = $order?->customer_phone ?: ($order?->user?->phone ?? '—');
                $cAddress = $order?->delivery_address ?: ($order?->address ?: 'Colombo');
                $status = ucfirst($order?->status ?: 'Completed');
                $payMethod = $order?->payment_method ?: 'Bank Transfer';
                $driverName = $order?->driver?->name ?: 'Unassigned';

                $rows[] = [
                    $idx + 1,
                    $orderCode,
                    $schedDate,
                    $sessionLabel,
                    $dishName,
                    $cName,
                    $cPhone,
                    $cAddress,
                    $qty,
                    $price,
                    $lineTotal,
                    $status,
                    $payMethod,
                    $driverName,
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
            'All Mobile App Meal Dispatches Combined',
            '',
            '',
            '',
            $totQty,
            '',
            $totRev,
            '',
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
            $sheet->getStyle('I' . $firstDataRow . ':I' . $this->lastDataRow)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('J' . $firstDataRow . ':K' . $this->lastDataRow)->getNumberFormat()->setFormatCode('[$Rs. ]#,##0.00');
            $sheet->getStyle('L' . $firstDataRow . ':L' . $this->lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

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
        $sheet->getStyle('I' . $this->grandTotalRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('K' . $this->grandTotalRow)->getNumberFormat()->setFormatCode('[$Rs. ]#,##0.00');

        // Freeze panes
        $sheet->freezePane('A' . ($this->headerRow + 1));

        return [];
    }
}
