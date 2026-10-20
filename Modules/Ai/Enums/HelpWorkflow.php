<?php

declare(strict_types=1);

namespace Modules\Ai\Enums;

enum HelpWorkflow: string
{
    case MeterReading = 'meter_reading';
    case PropertySetup = 'property_setup';
    case ProductSupport = 'product_support';
}
