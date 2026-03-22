<?php

declare(strict_types=1);

namespace Modules\Address\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Models\Address;
use Modules\Address\Repositories\Contracts\AddressRepositoryInterface;
use Modules\Address\Repositories\Contracts\UserAddressRepositoryInterface;

class DeleteAddress
{
    use AsAction;

    public function __construct(
        private AddressRepositoryInterface $addressRepository,
        private UserAddressRepositoryInterface $userAddressRepository,
    ) {}

    public function handle(int $userId, Address $address): void
    {
        DB::transaction(function () use ($userId, $address) {
            $this->userAddressRepository->detach($userId, $address->getKey());
            $this->addressRepository->delete($address);
        });
    }

    public function asController(Request $request, string $address): RedirectResponse
    {
        $userId = (int) $request->user()->getKey();
        $addressModel = GetUserAddress::run($userId, (int) $address);
        $this->handle($userId, $addressModel);

        return redirect()->route('addresses.index')->with('success', 'Адресу видалено');
    }
}
