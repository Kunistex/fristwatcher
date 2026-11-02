<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('slug')->nullable();
            $table->string('name');
            $table->string('icon', 40)->default('folder');
            $table->boolean('tracks_usage')->default(false);
            $table->unsignedSmallInteger('sort')->default(100);
            $table->timestamps();
            $table->unique(['user_id', 'slug']);
        });

        Schema::create('contract_templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->json('defaults');
            $table->text('help_text')->nullable();
            $table->string('compare_url')->nullable();
            $table->unsignedSmallInteger('sort')->default(100);
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('contract_templates')->nullOnDelete();

            $table->string('name');
            $table->string('provider');
            $table->string('customer_number')->nullable();

            $table->unsignedInteger('cost_amount')->default(0); // Rappen
            $table->string('cost_interval', 20)->default('monthly');

            $table->date('start_date');
            $table->unsignedSmallInteger('min_term_months')->nullable();
            $table->unsignedSmallInteger('renewal_months')->nullable();
            $table->string('notice_anchor', 20)->default('term_end'); // term_end | fixed_dates | anytime
            $table->json('fixed_end_dates')->nullable(); // ["12-31", "06-30"]
            $table->unsignedSmallInteger('notice_value')->default(0);
            $table->string('notice_unit', 10)->default('months');
            $table->unsignedTinyInteger('send_buffer_days')->nullable();
            $table->json('reminder_offsets')->nullable();

            $table->text('cancellation_address')->nullable();
            $table->string('cancellation_email')->nullable();

            $table->string('status', 30)->default('active');
            $table->date('cancellation_sent_on')->nullable();
            $table->date('cancellation_effective_on')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->text('notes')->nullable();

            // Berechnet durch DeadlineCalculator, gespeichert für Sortierung und Abfragen.
            $table->date('next_term_end')->nullable();
            $table->date('receipt_deadline')->nullable();
            $table->date('send_by')->nullable();

            $table->timestamps();
            $table->index(['user_id', 'status', 'send_by']);
        });

        Schema::create('price_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('old_amount');
            $table->unsignedInteger('new_amount');
            $table->date('effective_date');
            $table->date('announced_at')->nullable();
            $table->date('special_termination_deadline')->nullable();
            $table->string('note')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
        });

        Schema::create('expiry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30)->default('other');
            $table->string('name');
            $table->date('expires_at');
            $table->string('reference_last4', 4)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'expires_at']);
        });

        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('remindable');
            $table->string('kind', 30); // deadline | expiry | price_special | confirmation
            $table->date('target_date');
            $table->date('due_on');
            $table->smallInteger('offset_days')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['due_on', 'sent_at']);
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('documentable');
            $table->string('kind', 30)->default('other'); // policy | letter | confirmation | other
            $table->string('path');
            $table->string('original_name');
            $table->string('mime')->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('reminders');
        Schema::dropIfExists('expiry_items');
        Schema::dropIfExists('price_changes');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('contract_templates');
        Schema::dropIfExists('categories');
    }
};
