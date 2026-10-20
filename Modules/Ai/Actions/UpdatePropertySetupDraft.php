<?php

declare(strict_types=1);

namespace Modules\Ai\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Ai\Enums\HelpApprovalStatus;
use Modules\Ai\Enums\HelpWorkflow;
use Modules\Ai\Models\HelpApproval;
use Modules\Ai\Models\HelpConversation;
use Modules\Auth\Models\User;

class UpdatePropertySetupDraft
{
    private const array FIELDS = [
        'address_id' => null,
        'address' => ['region_id', 'address_type_id', 'city', 'street', 'building_number', 'apartment_number', 'zip_code', 'notes', 'is_primary'],
        'provider' => ['utility_type_id', 'name', 'description', 'phone', 'email', 'website', 'tariffs'],
        'meter' => ['utility_type_id', 'service_provider_id', 'serial_number', 'name', 'description', 'model_name', 'location', 'installation_date', 'initial_reading', 'notes'],
    ];

    /** @param array<string, mixed> $patch */
    public function handle(User $user, HelpConversation $conversation, array $patch, ?HelpApproval $approval = null, ?int $version = null): HelpConversation
    {
        abort_unless($conversation->user_id === $user->getKey(), 403);
        if ($approval !== null) {
            abort_unless($approval->user_id === $user->getKey() && $approval->conversation_id === $conversation->id
                && $approval->workflow === HelpWorkflow::PropertySetup, 403);
        }

        if (strlen(json_encode($patch, JSON_THROW_ON_ERROR)) > 10000) {
            throw ValidationException::withMessages(['draft' => 'Чернетка завелика.']);
        }

        return DB::transaction(function () use ($user, $conversation, $patch, $approval, $version): HelpConversation {
            DB::table('users')->where('id', $user->getKey())->lockForUpdate()->first();
            $locked = HelpConversation::query()->whereKey($conversation->id)->where('user_id', $user->getKey())->lockForUpdate()->firstOrFail();
            if ($approval !== null) {
                $currentApproval = HelpApproval::query()->whereKey($approval->id)->lockForUpdate()->firstOrFail();
                abort_if($currentApproval->status !== HelpApprovalStatus::Pending || $currentApproval->expires_at->isPast()
                    || $currentApproval->draft_version !== $version || $locked->draft_version !== $version, 409);
            }
            $draft = $locked->draft ?? [];
            foreach (self::FIELDS as $section => $fields) {
                if (! array_key_exists($section, $patch)) {
                    continue;
                }

                if ($fields === null) {
                    $draft[$section] = $patch[$section];
                } elseif (is_array($patch[$section])) {
                    $clean = array_intersect_key($patch[$section], array_flip($fields));
                    $draft[$section] = array_replace($draft[$section] ?? [], $clean);
                }
            }

            if ($draft === ($locked->draft ?? [])) {
                return $locked;
            }

            HelpApproval::query()->where('conversation_id', $locked->id)
                ->where('status', HelpApprovalStatus::Pending)
                ->update(['status' => HelpApprovalStatus::Cancelled, 'cancelled_at' => now()]);

            $locked->update([
                'draft' => $draft,
                'draft_version' => $locked->draft_version + 1,
                'active_workflow' => HelpWorkflow::PropertySetup,
                'active_agent' => HelpWorkflow::PropertySetup,
                'expires_at' => now()->addDay(),
            ]);

            return $locked;
        });
    }
}
