<?php

declare(strict_types=1);

namespace Modules\Billing\DTOs;

use Modules\Billing\Http\Requests\UpdateServiceProviderRequest;

final readonly class UpdateServiceProviderData
{
    public function __construct(
        public string $name,
        public ?string $description,
        public ?string $phone,
        public ?string $email,
        public ?string $website,
        public bool $isActive,
        public int $utilityTypeId,
    ) {}

    public static function fromRequest(UpdateServiceProviderRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            description: $request->validated('description'),
            phone: $request->validated('phone'),
            email: $request->validated('email'),
            website: $request->validated('website'),
            isActive: (bool) $request->validated('is_active', true),
            utilityTypeId: (int) $request->validated('utility_type_id'),
        );
    }
}
