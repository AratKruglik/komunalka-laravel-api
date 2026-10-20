<?php

declare(strict_types=1);

namespace Modules\Ai\Enums;

enum HelpIntent: string
{
    case MeterReading = 'meter_reading';
    case PropertySetup = 'property_setup';
    case ProductSupport = 'product_support';
    case Clarify = 'clarify';
}
