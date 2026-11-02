<?php

declare(strict_types=1);

use Modules\Auth\Models\User;

describe('Dashboard realtime widgets', function (): void {
    it('renders the dashboard widgets without JavaScript errors', function (): void {
        $this->actingAs(User::factory()->create());

        visit(route('dashboard'))
            ->assertSee('Графік споживання')
            ->assertSee('Останні показання')
            ->assertSee('Розподіл витрат')
            ->assertNoJavaScriptErrors();
    });
});
