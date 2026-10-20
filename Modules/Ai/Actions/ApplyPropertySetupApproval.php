<?php

declare(strict_types=1);

namespace Modules\Ai\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Address\Actions\CreateAddress;
use Modules\Address\DTO\CreateAddressData;
use Modules\Address\Models\Address;
use Modules\Ai\Enums\HelpApprovalStatus;
use Modules\Ai\Enums\HelpWorkflow;
use Modules\Ai\Models\HelpApproval;
use Modules\Ai\Models\HelpConversation;
use Modules\Auth\Models\User;
use Modules\Billing\Actions\CreateServiceProvider;
use Modules\Billing\DTO\CreateServiceProviderData;
use Modules\Billing\DTO\CreateTariffData;
use Modules\Billing\Models\ServiceProvider;
use Modules\Meter\Actions\CreateMeter;
use Modules\Meter\DTO\CreateMeterData;
use Modules\Meter\Models\Meter;

class ApplyPropertySetupApproval
{
    public function __construct(
        private readonly ValidatePropertySetupDraft $validate,
        private readonly CreatePropertySetupApproval $proposals,
        private readonly CreateAddress $createAddress,
        private readonly CreateServiceProvider $createProvider,
        private readonly CreateMeter $createMeter,
    ) {}

    /** @return array<string, mixed> */
    public function handle(User $user, HelpConversation $conversation, HelpApproval $approval, int $version, string $action): array
    {
        abort_unless($conversation->user_id === $user->getKey() && $approval->user_id === $user->getKey()
            && $approval->conversation_id === $conversation->id && $approval->workflow === HelpWorkflow::PropertySetup, 403);

        return DB::transaction(function () use ($user, $conversation, $approval, $version, $action): array {
            DB::table('users')->where('id', $user->getKey())->lockForUpdate()->first();
            $locked = HelpApproval::query()->whereKey($approval->id)->lockForUpdate()->firstOrFail();
            $currentConversation = HelpConversation::query()->whereKey($conversation->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === HelpApprovalStatus::Applied && $action === 'approve') {
                return $locked->result;
            }

            abort_if($locked->status !== HelpApprovalStatus::Pending || $locked->expires_at->isPast()
                || $locked->draft_version !== $version || $currentConversation->draft_version !== $version, 409,
                'Ця пропозиція вже неактуальна.');

            if ($action === 'reject') {
                $locked->update(['status' => HelpApprovalStatus::Cancelled, 'cancelled_at' => now()]);
                $currentConversation->update(['draft' => null, 'next_field' => null, 'expires_at' => null, 'active_workflow' => null, 'active_agent' => null]);

                return ['status' => 'rejected', 'message' => 'Створення скасовано. Нічого не збережено.', 'address' => null, 'provider' => null, 'meter' => null];
            }

            abort_unless($action === 'approve', 422);
            $payload = $this->validate->handle($user, $locked->payload);
            abort_if($this->proposals->payloadHash($payload) !== $locked->payload_hash, 409);

            $addressId = $payload['address_id'] ?? null;
            $address = null;
            if (isset($payload['address'])) {
                $data = $payload['address'];
                $this->rejectDuplicateAddress($user, $data);
                $address = $this->createAddress->handle((int) $user->getKey(), new CreateAddressData(
                    (int) $data['region_id'], (int) $data['address_type_id'], $data['city'], $data['street'],
                    $data['building_number'], $data['apartment_number'] ?? null, $data['zip_code'] ?? null,
                    $data['notes'] ?? null, (bool) ($data['is_primary'] ?? false),
                ));
                $addressId = $address->id;
            }

            if ($addressId !== null) {
                Address::query()->whereKey($addressId)->lockForUpdate()->firstOrFail();
            }

            $provider = null;
            if (isset($payload['provider'])) {
                $data = $payload['provider'];
                $this->rejectDuplicateProvider((int) $addressId, $data);
                $provider = $this->createProvider->handle((int) $user->getKey(), new CreateServiceProviderData(
                    (int) $addressId, (int) $data['utility_type_id'], $data['name'],
                    $data['description'] ?? null, $data['phone'] ?? null, $data['email'] ?? null,
                    $data['website'] ?? null, true, array_map(
                        fn (array $tariff): CreateTariffData => new CreateTariffData(
                            (int) $tariff['utility_type_id'], (int) $tariff['currency_id'], $tariff['name'],
                            (string) $tariff['base_rate'], (string) ($tariff['service_fee'] ?? '0'),
                            $tariff['effective_from'], $tariff['effective_to'] ?? null, $tariff['notes'] ?? null,
                        ),
                        $data['tariffs'] ?? [],
                    ),
                ));
            }

            $meter = null;
            if (isset($payload['meter'])) {
                $data = $payload['meter'];
                $this->rejectDuplicateMeter((int) $addressId, $data);
                $meter = $this->createMeter->handle((int) $user->getKey(), new CreateMeterData(
                    (int) $addressId, (int) $data['utility_type_id'],
                    $provider !== null ? $provider->id : ($data['service_provider_id'] ?? null),
                    $data['serial_number'], $data['name'], $data['description'] ?? null,
                    $data['model_name'] ?? null, $data['location'] ?? null,
                    $data['installation_date'] ?? null, (float) $data['initial_reading'],
                    $data['notes'] ?? null, true,
                ));
            }

            $result = [
                'status' => 'applied',
                'message' => 'Налаштування збережено.',
                'address' => $address === null ? null : ['id' => $address->id],
                'provider' => $provider === null ? null : ['id' => $provider->id],
                'meter' => $meter === null ? null : ['id' => $meter->id],
            ];
            $locked->update(['status' => HelpApprovalStatus::Applied, 'result' => $result, 'executed_at' => now()]);
            $currentConversation->update(['draft' => null, 'next_field' => null, 'expires_at' => null]);

            return $result;
        });
    }

    /** @param array<string, mixed> $data */
    private function rejectDuplicateAddress(User $user, array $data): void
    {
        $duplicate = $user->addresses()->where('region_id', $data['region_id'])
            ->where('address_type_id', $data['address_type_id'])->get()
            ->contains(fn (Address $address): bool => $this->key((string) $address->getAttribute('city'), (string) $address->getAttribute('street'), (string) $address->getAttribute('building_number'), (string) ($address->getAttribute('apartment_number') ?? ''))
                === $this->key($data['city'], $data['street'], $data['building_number'], $data['apartment_number'] ?? ''));
        if ($duplicate) {
            throw ValidationException::withMessages(['address' => 'Така адреса вже існує.']);
        }
    }

    /** @param array<string, mixed> $data */
    private function rejectDuplicateProvider(int $addressId, array $data): void
    {
        $duplicate = ServiceProvider::query()->where('address_id', $addressId)
            ->where('utility_type_id', $data['utility_type_id'])->get()
            ->contains(fn (ServiceProvider $provider): bool => $this->key((string) $provider->getAttribute('name')) === $this->key($data['name']));
        if ($duplicate) {
            throw ValidationException::withMessages(['provider' => 'Такий провайдер уже існує.']);
        }
    }

    /** @param array<string, mixed> $data */
    private function rejectDuplicateMeter(int $addressId, array $data): void
    {
        $duplicate = Meter::query()->where('address_id', $addressId)
            ->where('utility_type_id', $data['utility_type_id'])->get()
            ->contains(fn (Meter $meter): bool => $this->serial((string) $meter->getAttribute('serial_number')) === $this->serial($data['serial_number']));
        if ($duplicate) {
            throw ValidationException::withMessages(['meter' => 'Лічильник із таким серійним номером уже існує.']);
        }
    }

    private function key(string ...$parts): string
    {
        return implode('|', array_map(fn (string $value): string => mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value) ?? $value)), $parts));
    }

    private function serial(string $value): string
    {
        return mb_strtoupper(preg_replace('/\s+/u', '', trim($value)) ?? $value);
    }
}
