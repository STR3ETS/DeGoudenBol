<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pers
        Schema::create('press_releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('province_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('milestone', 32);
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->longText('body');
            $table->dateTime('embargo_until')->nullable();
            $table->dateTime('published_at')->nullable()->index();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('generated_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['edition_id', 'province_id', 'milestone']);
        });

        Schema::create('media_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('outlet', 120);
            $table->string('email', 190)->unique();
            $table->foreignId('province_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Sponsoring (docs/04 §9, besluit 18)
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32);
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->unsignedInteger('price_cents');
            $table->unsignedInteger('extra_link_price_cents')->nullable();
            $table->decimal('vat_rate', 5, 2)->default(21);
            $table->string('available_from_phase', 32)->default('always');
            $table->boolean('is_custom')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['edition_id', 'code']);
        });

        Schema::create('sponsors', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('name', 160);
            $table->string('slug', 180)->unique();
            $table->string('url')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('contact_name', 120)->nullable();
            $table->string('contact_email', 190)->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->string('kvk_number', 20)->nullable();
            $table->json('billing_address')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 16)->default('active');
            $table->timestamps();
        });

        Schema::create('placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sponsor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('location', 40)->index();
            $table->foreignId('province_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('entry_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label', 160)->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('is_exclusive')->default(false);
            $table->string('status', 16)->index();
            $table->unsignedInteger('price_cents');
            $table->foreignId('order_line_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sponsor_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sponsor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('placement_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('requested_at');
            $table->dateTime('confirmed_by_company_at')->nullable();
            $table->dateTime('declined_at')->nullable();
            $table->timestamps();

            $table->unique(['edition_id', 'sponsor_id', 'company_id']);
        });

        // Goede doelen (docs/04 §9, besluit 8)
        Schema::create('charities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('slug', 180)->unique();
            $table->string('kvk_or_rsin', 20)->nullable();
            $table->boolean('is_anbi')->default(false);
            $table->foreignId('province_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 40)->nullable();
            $table->text('motivation')->nullable();
            $table->string('website')->nullable();
            $table->string('status', 16)->index();
            $table->json('review_checklist')->nullable();
            $table->text('review_note')->nullable();
            $table->nullableMorphs('nominated_by');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('charity_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_line_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('charity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('regional_pot_province_id')->nullable()->constrained('provinces')->nullOnDelete();
            $table->nullableMorphs('payer');
            $table->unsignedInteger('basis_cents');
            $table->unsignedInteger('amount_cents');
            $table->string('basis', 16);
            $table->timestamps();
        });

        Schema::create('charity_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount_cents');
            $table->date('paid_at');
            $table->string('reference', 120)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Nieuwsbrief-opt-ins (dubbele opt-in)
        Schema::create('newsletter_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('email', 190)->unique();
            $table->string('source', 24)->default('site');
            $table->dateTime('confirmed_at')->nullable()->index();
            $table->dateTime('unsubscribed_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscriptions');
        Schema::dropIfExists('charity_payouts');
        Schema::dropIfExists('charity_reservations');
        Schema::dropIfExists('charities');
        Schema::dropIfExists('sponsor_links');
        Schema::dropIfExists('placements');
        Schema::dropIfExists('sponsors');
        Schema::dropIfExists('products');
        Schema::dropIfExists('media_contacts');
        Schema::dropIfExists('press_releases');
    }
};
