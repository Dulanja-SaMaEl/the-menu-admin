<?php

namespace App\Imports;

use App\Models\Food;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class FoodItemsImport implements ToCollection, WithHeadingRow
{
    public int $createdCount = 0;
    public int $updatedCount = 0;
    public int $skippedCount = 0;

    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? $row['item_name'] ?? ''));
            if (empty($name)) {
                $this->skippedCount++;
                continue;
            }

            $price = (float) ($row['price_lkr'] ?? $row['price'] ?? 0);
            if ($price < 0) {
                $price = 0;
            }

            $rawCategory = trim((string) ($row['category'] ?? $row['type'] ?? ''));
            $category = $this->normalizeCategory($rawCategory, $name);

            $portionSize = trim((string) ($row['portion_size'] ?? $row['portion'] ?? ''));
            $brand = trim((string) ($row['brand'] ?? ''));
            $volumeMl = !empty($row['volume_ml']) ? (int) $row['volume_ml'] : null;

            $rawChannel = strtolower(trim((string) ($row['channel'] ?? 'pos')));
            $channel = in_array($rawChannel, ['pos', 'app', 'all']) ? $rawChannel : 'all';

            $description = trim((string) ($row['description'] ?? ''));
            $calories = !empty($row['calories']) ? (int) $row['calories'] : null;

            // Pick a matching representative placeholder image
            $imageUrl = $this->pickImage($category, $name);

            $existing = Food::where('name', $name)->first();

            $data = [
                'name' => $name,
                'type' => $category,
                'price' => $price,
                'portion_size' => $portionSize ?: null,
                'brand' => $brand ?: null,
                'volume_ml' => $volumeMl,
                'channel' => $channel,
                'description' => $description ?: null,
                'calories' => $calories,
                'available_days' => ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
                'difficulty' => str_contains(strtolower($category), 'bev') ? 'Chilled' : 'Fresh Kitchen',
                'prep_time' => str_contains(strtolower($category), 'bev') ? 'Ready to Serve' : '10-15 min',
                'servings' => $portionSize ?: '1 portion',
                'rating' => 4.8,
            ];

            if ($existing) {
                // Keep existing custom uploaded image if available
                if (!empty($existing->getRawOriginal('image_url'))) {
                    $data['image_url'] = $existing->getRawOriginal('image_url');
                } else {
                    $data['image_url'] = $imageUrl;
                }
                $existing->update($data);
                $this->updatedCount++;
            } else {
                $data['image_url'] = $imageUrl;
                Food::create($data);
                $this->createdCount++;
            }
        }
    }

    protected function normalizeCategory(string $raw, string $name): string
    {
        $lower = strtolower($raw . ' ' . $name);
        if (str_contains($lower, 'beverage') || str_contains($lower, 'drink') || str_contains($lower, 'cola') || str_contains($lower, 'water') || str_contains($lower, 'juice') || str_contains($lower, 'beer')) {
            return 'Beverages';
        }
        if (str_contains($lower, 'rice') || str_contains($lower, 'carb') || str_contains($lower, 'hopper') || str_contains($lower, 'rotti') || str_contains($lower, 'bread') || str_contains($lower, 'pittu')) {
            return 'Rice & Carbs';
        }
        if (str_contains($lower, 'curry') || str_contains($lower, 'chicken') || str_contains($lower, 'pork') || str_contains($lower, 'fish') || str_contains($lower, 'beef') || str_contains($lower, 'dhal') || str_contains($lower, 'kaju') || str_contains($lower, 'meat')) {
            return 'Curries & Meats';
        }
        if (str_contains($lower, 'sambol') || str_contains($lower, 'moju') || str_contains($lower, 'salad') || str_contains($lower, 'veggie') || str_contains($lower, 'gotukola')) {
            return 'Veggies & Sambols';
        }
        if (str_contains($lower, 'breakfast')) return 'Breakfast';
        if (str_contains($lower, 'dinner')) return 'Dinner';
        if (str_contains($lower, 'lunch')) return 'Lunch';

        return !empty($raw) ? $raw : 'Curries & Meats';
    }

    protected function pickImage(string $category, string $name): string
    {
        $lower = strtolower($category . ' ' . $name);
        if (str_contains($lower, 'cola') || str_contains($lower, 'soft drink')) {
            return 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?auto=format&fit=crop&w=800&q=80';
        }
        if (str_contains($lower, 'water')) {
            return 'https://images.unsplash.com/photo-1548839140-29a749e1bc4e?auto=format&fit=crop&w=800&q=80';
        }
        if (str_contains($lower, 'juice') || str_contains($lower, 'lime')) {
            return 'https://images.unsplash.com/photo-1613478223719-2ab802602423?auto=format&fit=crop&w=800&q=80';
        }
        if (str_contains($lower, 'beer') || str_contains($lower, 'egb')) {
            return 'https://images.unsplash.com/photo-1625772299848-391b6a87d7b3?auto=format&fit=crop&w=800&q=80';
        }
        if (str_contains($lower, 'rice')) {
            return 'https://images.unsplash.com/photo-1512058564366-18510be2db19?auto=format&fit=crop&w=800&q=80';
        }
        if (str_contains($lower, 'chicken')) {
            return 'https://images.unsplash.com/photo-1604908176997-125f25cc6f3d?auto=format&fit=crop&w=800&q=80';
        }
        if (str_contains($lower, 'kaju') || str_contains($lower, 'cashew')) {
            return 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=800&q=80';
        }
        if (str_contains($lower, 'sambol')) {
            return 'https://images.unsplash.com/photo-1540420773420-3366772f4999?auto=format&fit=crop&w=800&q=80';
        }

        return 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=800&q=80';
    }
}
