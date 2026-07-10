<?php

declare(strict_types=1);

function qaEntrypointBasePath(string $relative = ''): string
{
    $root = dirname(__DIR__, 2);

    return $relative === '' ? $root : $root.'/'.ltrim($relative, '/');
}

describe('docker/php/entrypoint.sh structural checks', function (): void {
    beforeEach(function (): void {
        $this->script = file_get_contents(qaEntrypointBasePath('docker/php/entrypoint.sh'));
    });

    it('exists and is executable on disk', function (): void {
        $path = qaEntrypointBasePath('docker/php/entrypoint.sh');

        expect(file_exists($path))->toBeTrue()
            ->and(is_executable($path))->toBeTrue();
    });

    it('runs storage:link idempotently, only for the app process', function (): void {
        expect($this->script)
            ->toContain('PROCESS')
            ->toContain('storage:link')
            ->toContain('exec "$@"');
    });

    it('guards storage:link with an existence check so it is idempotent across restarts', function (): void {
        expect($this->script)->toMatch('/\[\s*!\s*-e\s+\S*public\/storage\s*\]/');
    });

    it('never fails container startup if storage:link errors', function (): void {
        expect($this->script)->toMatch('/storage:link.*\|\|\s*true/');
    });
});

describe('Dockerfiles wire the entrypoint script correctly', function (): void {
    it('copies, chmods and sets the entrypoint script in both Dockerfiles', function (string $dockerfile): void {
        $contents = file_get_contents(qaEntrypointBasePath($dockerfile));

        expect($contents)
            ->toContain('entrypoint.sh')
            ->toMatch('/chmod\s+\+x\s+\S*entrypoint\.sh/')
            ->toMatch('/ENTRYPOINT\s+\[".*entrypoint\.sh"\]/');
    })->with([
        'docker/php/Dockerfile',
        'docker/php/Dockerfile-dev',
    ]);

    it('keeps supervisord as the CMD so signal handling is unchanged after exec', function (string $dockerfile): void {
        $contents = file_get_contents(qaEntrypointBasePath($dockerfile));

        expect($contents)->toMatch('/CMD\s+\[".*supervisord.*"\]/s');
    })->with([
        'docker/php/Dockerfile',
        'docker/php/Dockerfile-dev',
    ]);
});
