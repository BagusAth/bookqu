<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->enum('event', [
                'created',
                'payment_pending',
                'payment_success',
                'payment_failed',
                'cancelled',
                'rescheduled',
                'viewed',
            ]);
            $table->string('description', 500)->nullable();
            $table->json('metadata')->nullable(); // extra context (old date, ip, etc.)
            $table->timestamp('created_at')->useCurrent();

            $table->index(['booking_id', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_logs');
    }
};
