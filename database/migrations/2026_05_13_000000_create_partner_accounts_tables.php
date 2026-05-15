<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('partner_stores', function (Blueprint $table) {
            $table->id();
            $table->string('partner_id')->unique();
            $table->string('store_id')->unique();
            $table->string('name');
            $table->string('brand_name')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('owner_email')->nullable()->index();
            $table->string('owner_phone')->nullable();
            $table->string('status')->default('review')->index();
            $table->string('plan')->default('Starter')->index();
            $table->string('domain')->nullable()->index();
            $table->string('store_url')->nullable();
            $table->string('logo')->nullable();
            $table->string('payment_status')->nullable();
            $table->date('subscription_started_at')->nullable();
            $table->date('subscription_renews_at')->nullable();
            $table->string('payment_provider')->nullable();
            $table->string('shipping_provider')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('partner_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_store_id')->constrained('partner_stores')->cascadeOnDelete();
            $table->string('store_id')->index();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->nullable()->index();
            $table->string('password_hash');
            $table->string('role')->index();
            $table->string('status')->default('invited')->index();
            $table->json('abilities')->nullable();
            $table->string('invite_token')->nullable()->unique();
            $table->timestamp('invite_expires_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_users');
        Schema::dropIfExists('partner_stores');
    }
};
