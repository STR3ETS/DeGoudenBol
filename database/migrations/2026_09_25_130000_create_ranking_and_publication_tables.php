<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->dateTime('linked_at')->nullable()->after('scheduled_at');
            $table->dateTime('published_at')->nullable()->after('linked_at');
        });

        Schema::create('publication_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->dateTime('scheduled_at')->index();
            $table->string('status', 24)->index();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('publication_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publication_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('province_id')->constrained()->restrictOnDelete();
            // Kopie van de uitslag (cijfer, deelscores, aantal kaarten); nooit het testnummer.
            $table->json('result_snapshot');
            $table->string('visibility', 16);
            $table->timestamps();

            $table->unique(['publication_batch_id', 'entry_id']);
        });

        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->morphs('approvable');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('approved_at');

            $table->unique(['approvable_type', 'approvable_id', 'user_id']);
        });

        Schema::create('ranking_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('province_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('scope', 32)->index();
            $table->string('round', 16);
            $table->string('engine_version', 16);
            $table->string('status', 16)->index();
            $table->char('input_hash', 64);
            $table->foreignId('publication_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('computed_at');
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ranking_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ranking_snapshot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entry_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->decimal('total', 4, 1);
            $table->unsignedSmallInteger('tie_group')->nullable();
            $table->string('label', 8);
            $table->boolean('needs_tie_break')->default(false);

            $table->unique(['ranking_snapshot_id', 'entry_id']);
            $table->index(['ranking_snapshot_id', 'position']);
        });

        Schema::create('confidential_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('strengths')->nullable();
            $table->text('opportunities')->nullable();
            $table->text('course_suggestion')->nullable();
            $table->string('status', 16)->index();
            $table->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('objections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_user_id')->constrained('participant_users')->cascadeOnDelete();
            $table->text('reason');
            $table->string('status', 16)->index();
            $table->text('decision')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('submitted_at');
            $table->dateTime('decided_at')->nullable();
            $table->timestamps();
        });

        Schema::create('recognitions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->foreignId('entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('province_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('type', 24)->index();
            $table->string('status', 16)->index();
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->dateTime('embargo_until')->nullable();
            $table->timestamps();

            $table->unique(['entry_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recognitions');
        Schema::dropIfExists('objections');
        Schema::dropIfExists('confidential_reports');
        Schema::dropIfExists('ranking_positions');
        Schema::dropIfExists('ranking_snapshots');
        Schema::dropIfExists('approvals');
        Schema::dropIfExists('publication_items');
        Schema::dropIfExists('publication_batches');

        Schema::table('entries', function (Blueprint $table) {
            $table->dropColumn(['linked_at', 'published_at']);
        });
    }
};
