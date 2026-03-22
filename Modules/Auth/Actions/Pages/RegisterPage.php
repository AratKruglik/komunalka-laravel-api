<?php

declare(strict_types=1);

namespace Modules\Auth\Actions\Pages;

use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsAction;

class RegisterPage
{
    use AsAction;

    public function handle(): Response
    {
        return Inertia::render('Auth/Register');
    }
}
