<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Shared\Actions\GetUtilityType;
use Modules\Shared\Models\UtilityType;

it('returns a utility type by id', function () {
    $utilityType = UtilityType::factory()->create();

    $result = app(GetUtilityType::class)->handle($utilityType->id);

    expect($result->id)->toBe($utilityType->id);
});

it('throws exception for non-existent id', function () {
    app(GetUtilityType::class)->handle(999);
})->throws(ModelNotFoundException::class);
