<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('platform_records', function (Blueprint $table) {
            $table->id();
            $table->string('section')->index();
            $table->string('record_id')->index();
            $table->string('store_id')->nullable()->index();
            $table->string('partner_id')->nullable()->index();
            $table->string('status')->nullable()->index();
            $table->json('payload');
            $table->timestamps();
            $table->unique(['section', 'record_id']);
        });

        Schema::create('platform_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('actor_type')->default('system');
            $table->string('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->string('role')->nullable()->index();
            $table->string('store_id')->nullable()->index();
            $table->string('partner_id')->nullable()->index();
            $table->string('action')->index();
            $table->string('subject_type')->nullable()->index();
            $table->string('subject_id')->nullable()->index();
            $table->json('properties')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('store_id')->nullable()->index();
            $table->string('partner_id')->nullable()->index();
            $table->string('severity')->default('info')->index();
            $table->string('url')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('store_onboarding_steps', function (Blueprint $table) {
            $table->id();
            $table->string('store_id')->index();
            $table->string('step_key');
            $table->string('title');
            $table->string('status')->default('pending')->index();
            $table->json('payload')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'step_key']);
        });

        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('store_id')->unique();
            $table->json('identity')->nullable();
            $table->json('branding')->nullable();
            $table->json('payments')->nullable();
            $table->json('shipping')->nullable();
            $table->json('taxes')->nullable();
            $table->json('invoices')->nullable();
            $table->timestamps();
        });

        Schema::create('marketplace_apps', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->index();
            $table->string('provider')->nullable();
            $table->string('status')->default('available')->index();
            $table->text('description')->nullable();
            $table->json('configuration')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_apps');
        Schema::dropIfExists('store_settings');
        Schema::dropIfExists('store_onboarding_steps');
        Schema::dropIfExists('platform_notifications');
        Schema::dropIfExists('platform_activity_logs');
        Schema::dropIfExists('platform_records');
    }
};
