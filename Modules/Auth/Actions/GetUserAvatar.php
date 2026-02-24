<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\Models\User;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class GetUserAvatar
{
    use AsAction;

    public function handle(User $user, string $conversion): BinaryFileResponse
    {
        $media = $user->getFirstMedia('avatar');

        abort_if($media === null, Response::HTTP_NOT_FOUND);

        return response()->file($media->getPath($conversion));
    }
}
