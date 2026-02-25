<?php

declare(strict_types=1);

namespace Modules\Address\DTO;

use Illuminate\Http\Request;

final readonly class AddressPaginationData
{
    public function __construct(
        public int $page,
        public int $perPage,
        public string $sortBy,
        public bool $desc,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            page: (int) $request->query('page', 1),
            perPage: (int) $request->query('per_page', 15),
            sortBy: (string) $request->query('sort_by', 'created_at'),
            desc: filter_var($request->query('desc', true), FILTER_VALIDATE_BOOLEAN),
        );
    }
}
