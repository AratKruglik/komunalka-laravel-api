<?php

declare(strict_types=1);

use Modules\Shared\Actions\GetActiveUtilityTypes;
use Modules\Shared\Models\UtilityType;

it('returns only active utility types', function () {
    UtilityType::factory()->count(2)->create();
    UtilityType::factory()->inactive()->create();

    $result = app(GetActiveUtilityTypes::class)->handle();

    expect($result)->toHaveCount(2)
        ->each(fn ($item) => $item->is_active->toBeTrue());
});
