<?php

declare(strict_types=1);

namespace Modules\Billing\Actions\Pages;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Billing\Actions\GetUserServiceProviders;
use Modules\Billing\Http\Resources\ServiceProviderResource;

class ProviderIndexPage
{
    use AsAction;

    public function handle(int $userId): Response
    {
        $providers = GetUserServiceProviders::run($userId);

        return Inertia::render('Providers/Index', [
            'providers' => ServiceProviderResource::collection($providers),
        ]);
    }

    public function asController(Request $request): Response
    {
        return $this->handle((int) $request->user()->getKey());
    }
}
