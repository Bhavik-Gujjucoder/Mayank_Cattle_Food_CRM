<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date');
            $table->decimal('already_produced', 12, 2)->default(0)
                ->comment('Ready stock / already produced (bag-equivalent)');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // App enforces one active report per date (soft deletes keep history).
            $table->index('report_date');
        });

        Schema::create('weekly_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weekly_report_id')->constrained('weekly_reports')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('order_id')->constrained('order_management')->restrictOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->foreignId('transport_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('truck_number', 100)->nullable();
            $table->string('driver_contact', 20)->nullable();
            $table->text('note')->nullable();
            $table->string('status', 20)->default('pending')
                ->comment('pending | confirmed');
            $table->foreignId('dispatch_id')->nullable()
                ->constrained('dispatch_management')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['weekly_report_id', 'sort_order']);
            $table->index(['weekly_report_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_report_items');
        Schema::dropIfExists('weekly_reports');
    }
};
