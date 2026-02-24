<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('first_name')->nullable()->after('username');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone_number')->nullable()->after('last_name');
            $table->string('role')->default('user')->after('phone_number');
            $table->string('auth_provider')->default('local')->after('role');
            $table->string('external_id')->nullable()->after('auth_provider');
            $table->boolean('email_verified')->default(false)->after('external_id');
            $table->timestamp('last_login_at')->nullable()->after('email_verified');
            $table->string('password')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique(['auth_provider', 'external_id'], 'users_auth_provider_external_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_auth_provider_external_id_unique');
            $table->dropColumn([
                'username',
                'first_name',
                'last_name',
                'phone_number',
                'role',
                'auth_provider',
                'external_id',
                'email_verified',
                'last_login_at',
            ]);
            $table->string('password')->nullable(false)->change();
        });
    }
};
