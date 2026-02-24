<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Models\User;

beforeEach(function () {
    Route::middleware('api')->prefix('api')->group(function () {
        Route::get('test/not-found', fn () => abort(404));
        Route::get('test/forbidden', fn () => throw new \Illuminate\Auth\Access\AuthorizationException('Access denied'));
        Route::get('test/server-error', fn () => throw new \RuntimeException('Something broke'));
        Route::post('test/validation', function (\Illuminate\Http\Request $request) {
            $request->validate(['email' => 'required|email']);
        });
    });
});

describe('Exception handling', function () {
    it('returns PRD format for 404', function () {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/address/99999')
            ->assertNotFound()
            ->assertJsonStructure([
                'statusCode',
                'message',
                'details',
                'timestamp',
                'path',
            ])
            ->assertJson([
                'statusCode' => 404,
                'message' => 'Not found',
                'details' => null,
            ]);
    });

    it('returns PRD format for 401', function () {
        $this->getJson('/api/v1/address')
            ->assertUnauthorized()
            ->assertJsonStructure([
                'statusCode',
                'message',
                'timestamp',
                'path',
            ])
            ->assertJson([
                'statusCode' => 401,
            ]);
    });

    it('returns PRD format for 403', function () {
        $this->getJson('/api/test/forbidden')
            ->assertForbidden()
            ->assertJsonStructure([
                'statusCode',
                'message',
                'details',
                'timestamp',
                'path',
            ])
            ->assertJson([
                'statusCode' => 403,
                'message' => 'Access denied',
            ]);
    });

    it('returns PRD format for 422 validation errors', function () {
        $this->postJson('/api/test/validation', [])
            ->assertUnprocessable()
            ->assertJsonStructure([
                'statusCode',
                'message',
                'errors' => ['email'],
            ])
            ->assertJson([
                'statusCode' => 422,
                'message' => 'Data validation error',
            ]);
    });

    it('hides error details in production', function () {
        config(['app.debug' => false]);

        $this->getJson('/api/test/server-error')
            ->assertStatus(500)
            ->assertJson([
                'statusCode' => 500,
                'message' => 'Internal server error',
                'details' => null,
            ]);
    });

    it('shows error details when debug is enabled', function () {
        config(['app.debug' => true]);

        $this->getJson('/api/test/server-error')
            ->assertStatus(500)
            ->assertJson([
                'statusCode' => 500,
                'message' => 'Something broke',
            ])
            ->assertJsonMissing(['details' => null]);
    });

    it('does not transform success responses', function () {
        Route::middleware('api')->prefix('api')->group(function () {
            Route::get('test/success', fn () => response()->json(['data' => 'ok']));
        });

        $this->getJson('/api/test/success')
            ->assertSuccessful()
            ->assertExactJson(['data' => 'ok']);
    });
});
