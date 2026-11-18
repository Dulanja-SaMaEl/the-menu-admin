<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FoodItemsTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function headings(): array
    {
        return [
            'Name',
            'Category',
            'Price_LKR',
            'Portion_Size',
            'Brand',
            'Volume_ML',
            'Channel',
            'Description',
            'Calories',
        ];
    }

    public function array(): array
    {
        return [
            ['Basmati Yellow Rice', 'Rice & Carbs', 180.00, '1 Portion', '', '', 'pos', 'Fragrant saffron tempered basmati rice', 220],
            ['Red Raw Rice', 'Rice & Carbs', 120.00, '1 Portion', '', '', 'pos', 'Healthy steamed red rice', 180],
            ['String Hoppers (Red/White)', 'Rice & Carbs', 150.00, '10 Pieces', '', '', 'pos', 'Fresh steamed string hoppers', 250],
            ['Egg Rotti', 'Rice & Carbs', 120.00, '1 Piece', '', '', 'pos', 'Fresh griddled egg rotti', 280],
            ['Plain Hopper', 'Rice & Carbs', 60.00, '1 Piece', '', '', 'pos', 'Crispy edged traditional hopper', 120],
            ['Egg Hopper', 'Rice & Carbs', 120.00, '1 Piece', '', '', 'pos', 'Crispy hopper with soft egg center', 190],
            ['Kaju Maluwa (Cashew Curry)', 'Curries & Meats', 350.00, '1 Portion', '', '', 'pos', 'Rich and creamy slow-cooked raw cashew nut curry', 320],
            ['Ceylon Chicken Curry', 'Curries & Meats', 260.00, '1 Portion', '', '', 'pos', 'Traditional roasted spice chicken curry with gravy', 280],
            ['Spicy Black Pork Curry', 'Curries & Meats', 320.00, '1 Portion', '', '', 'pos', 'Goraka black pepper slow-cooked pork', 350],
            ['Fish Ambulthiyal', 'Curries & Meats', 250.00, '1 Portion', '', '', 'pos', 'Southern sour fish dry curry', 210],
            ['Red Dhal Curry', 'Curries & Meats', 80.00, '1 Portion', '', '', 'pos', 'Creamy coconut milk lentil curry', 160],
            ['Polos Curry (Baby Jackfruit)', 'Curries & Meats', 140.00, '1 Portion', '', '', 'pos', 'Tender young jackfruit curry', 150],
            ['Fresh Coconut Pol Sambol', 'Veggies & Sambols', 50.00, '1 Portion', '', '', 'pos', 'Fresh grated coconut, red chili, shallots, and lime', 110],
            ['Katta Sambol', 'Veggies & Sambols', 50.00, '1 Portion', '', '', 'pos', 'Crushed red chili, Maldive fish, and lime', 70],
            ['Brinjal Moju (Wambatu Moju)', 'Veggies & Sambols', 100.00, '1 Portion', '', '', 'pos', 'Caramelized sweet and sour fried eggplant', 180],
            ['Gotukola Sambol', 'Veggies & Sambols', 70.00, '1 Portion', '', '', 'pos', 'Fresh pennywort salad with grated coconut', 60],
            ['Coca-Cola (330ml Can)', 'Beverages', 250.00, 'Can', 'Coca-Cola', 330, 'all', 'Chilled sparkling soft drink can', 140],
            ['Coca-Cola (750ml PET)', 'Beverages', 380.00, 'PET Bottle', 'Coca-Cola', 750, 'pos', 'Chilled PET bottle for sharing', 310],
            ['Sprite (330ml Can)', 'Beverages', 250.00, 'Can', 'Coca-Cola', 330, 'all', 'Crisp lemon-lime soda can', 140],
            ['Elephant House Ginger Beer (EGB)', 'Beverages', 220.00, 'Glass Bottle', 'Elephant House', 400, 'all', 'Natural ginger brew cold beverage', 160],
            ['Mineral Water (500ml)', 'Beverages', 100.00, 'Bottle', 'Highland', 500, 'all', 'Pure natural highland spring water', 0],
            ['Mineral Water (1000ml)', 'Beverages', 160.00, 'Bottle', 'Highland', 1000, 'all', '1 Litre highland spring water bottle', 0],
            ['Fresh Lime Juice (350ml)', 'Beverages', 300.00, 'Glass', 'In-House', 350, 'all', 'Freshly squeezed lime juice with mint', 90],
        ];
    }

    public function styles(Worksheet $sheet): ?array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF0A2E2A'],
                ],
            ],
        ];
    }
}
