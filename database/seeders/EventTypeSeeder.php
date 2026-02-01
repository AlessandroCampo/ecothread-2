<?php

namespace Database\Seeders;

use App\Enums\EventType;
use App\Models\EventType as EventTypeModel;
use Illuminate\Database\Seeder;

class EventTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (EventType::cases() as $type) {
            EventTypeModel::updateOrCreate(
                ['code' => $type->value],
                [
                    'label' => $type->label(),
                    'icon' => $type->icon(),
                    'description' => $type->description(),
                    'is_required' => $type->isRequired(),
                    'sort_order' => $type->sortOrder(),
                ]
            );
        }
    }
}