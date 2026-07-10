<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

describe('Public storage symlink retrievability', function (): void {
    it('resolves public/storage as a symlink pointing at storage/app/public', function (): void {
        $symlinkPath = public_path('storage');

        expect(File::exists($symlinkPath))
            ->toBeTrue('public/storage does not exist — storage:link was not run for this environment. '
                .'This mirrors the shipped bug: without the entrypoint fix, uploaded media 404s in production.')
            ->and(is_link($symlinkPath))->toBeTrue()
            ->and(realpath($symlinkPath))->toBe(realpath(storage_path('app/public')));
    });

    it('serves real bytes written to the public disk through the storage symlink, without Storage::fake', function (): void {
        $relativePath = 'qa-retrievability-check/'.uniqid('marker_', true).'.txt';
        $contents = 'qa-retrievability-marker';

        Storage::disk('public')->put($relativePath, $contents);

        $pathThroughSymlink = public_path('storage/'.$relativePath);

        expect(File::exists($pathThroughSymlink))
            ->toBeTrue('File written to the public disk is not reachable through public/storage — '
                .'the symlink is missing or stale, which is exactly the root cause this fix addresses.')
            ->and(File::get($pathThroughSymlink))->toBe($contents);

        Storage::disk('public')->delete($relativePath);
    });
});
