<?php

namespace App\Http\Controllers;

use App\Enums\CertificationType;
use App\Enums\EventType;
use App\Enums\Material;
use App\Enums\PackagingMaterial;
use App\Enums\ProductionProcess;
use App\Enums\ProductType;
use App\Enums\TransportMode;
use App\Enums\TrustLevel;

class EnumController extends Controller
{
    /**
     * Get all enums for frontend forms
     */
    public function index()
    {
        return response()->json([
            'event_types' => EventType::toArray(),
            'trust_levels' => TrustLevel::toArray(),
            'product_types' => ProductType::toArray()
        ]);
    }

    /**
     * Get event types only
     */
    public function eventTypes()
    {
        return response()->json(EventType::toArray());
    }

    /**
     * Get trust levels only
     */
    public function trustLevels()
    {
        return response()->json(TrustLevel::toArray());
    }

    public function eventEnums()
    {
        return response()->json([
            'materials' => Material::toArray(),
            'processes' => ProductionProcess::toArray(),
            'transport_modes' => TransportMode::toArray(),
            'packaging_materials' => PackagingMaterial::toArray(),
            'certification_types' => CertificationType::toArray(),
        ]);
    }
}