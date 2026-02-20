<?php

declare(strict_types=1);

use Modules\Shared\Actions\DeleteCurrency;
use Modules\Shared\Models\Currency;

it('deletes a currency', function () {
    $currency = Currency::factory()->create();

    $result = app(DeleteCurrency::class)->handle($currency);

    expect($result)->toBeTrue();
    $this->assertDatabaseMissing('currencies', ['id' => $currency->id]);
});
