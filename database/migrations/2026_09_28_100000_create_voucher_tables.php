<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_campaigns', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('province_id')->constrained()->restrictOnDelete();
            $table->foreignId('terms_version_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('starts_at')->index();
            $table->dateTime('winners_deadline_at');
            $table->date('last_redeem_day');
            $table->string('selection_method', 40)->default('social_media');
            $table->unsignedSmallInteger('winner_count');
            $table->unsignedInteger('voucher_value_cents');
            $table->string('status', 16)->index();
            $table->dateTime('opened_notified_at')->nullable();
            $table->timestamps();

            $table->unique(['edition_id', 'company_id']);
        });

        Schema::create('voucher_winners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_campaign_id')->constrained()->cascadeOnDelete();
            $table->string('first_name', 60);
            $table->string('last_initial', 4);
            $table->string('email', 190);
            $table->char('claim_token_hash', 64)->unique();
            $table->dateTime('claimed_at')->nullable();
            $table->foreignId('terms_version_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('consent_public_name')->default(false);
            $table->boolean('consent_photo')->default(false);
            $table->dateTime('anonymized_at')->nullable();
            $table->timestamps();

            $table->unique(['voucher_campaign_id', 'email']);
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            // 128-bit token uit de QR, alleen gehasht opgeslagen.
            $table->char('qr_token_hash', 64)->unique();
            $table->foreignId('voucher_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voucher_winner_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('value_cents');
            $table->string('status', 16)->index();
            $table->dateTime('issued_at');
            $table->dateTime('expires_at')->index();
            $table->dateTime('redeemed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('voucher_campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('participant_user_id')->nullable()->constrained('participant_users')->nullOnDelete();
            $table->string('result', 16)->index();
            $table->string('method', 8);
            $table->string('refusal_reason', 32)->nullable();
            $table->string('photo_path')->nullable();
            $table->dateTime('created_at')->index();
        });

        Schema::create('voucher_shortfalls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_campaign_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('count');
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('invoiced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_shortfalls');
        Schema::dropIfExists('redemptions');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('voucher_winners');
        Schema::dropIfExists('voucher_campaigns');
    }
};
