<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Capacity, participants, seats and the no_show status (ADR-0017 decisions 1-3, ADR-0018).
 *
 * The seat index replaces `unique_active_booking_slot` in this one migration, after every
 * existing booking received its seat 0, so the slot never runs without a database guard.
 */
return new class extends Migration
{
    private const OCCUPYING = "'pending', 'paid', 'completed', 'no_show'";

    public function up(): void
    {
        // `kapasitas` used to be a display-only "Maks N orang". It becomes the capacity per slot,
        // so every existing service starts at 1 and keeps one booking per slot (FR-BOOKING-021).
        DB::table('services')->update(['kapasitas' => 1]);
        Schema::table('services', function (Blueprint $table) {
            $table->unsignedInteger('kapasitas')->default(1)->nullable(false)->change();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedSmallInteger('peserta')->default(1)->after('jam');
        });

        Schema::create('booking_seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idbooking')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('idschedule')->constrained('schedules')->cascadeOnDelete();
            $table->unsignedSmallInteger('seat_no');
            // Copy of the booking status, kept in step by the Booking model (BookingSeats::sync).
            $table->string('status', 20);
            $table->unsignedBigInteger('active_idschedule')
                ->nullable()
                ->virtualAs('CASE WHEN status IN ('.self::OCCUPYING.') THEN idschedule ELSE NULL END');

            $table->unique(['active_idschedule', 'seat_no'], 'booking_seats_active_seat_unique');
            $table->index('idbooking');
        });

        DB::statement('INSERT INTO booking_seats (idbooking, idschedule, seat_no, status) SELECT id, idschedule, 0, status FROM bookings');

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique('unique_active_booking_slot');
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('active_idschedule');
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('status', ['pending', 'paid', 'cancelled', 'completed', 'no_show'])->change();
        });
    }

    public function down(): void
    {
        DB::table('bookings')->where('status', 'no_show')->update(['status' => 'completed']);
        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('status', ['pending', 'paid', 'cancelled', 'completed'])->change();
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('active_idschedule')
                ->nullable()
                ->virtualAs("CASE WHEN status IN ('pending', 'paid', 'completed') THEN idschedule ELSE NULL END");
            $table->unique(['active_idschedule'], 'unique_active_booking_slot');
        });
        Schema::dropIfExists('booking_seats');
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('peserta');
        });
        Schema::table('services', function (Blueprint $table) {
            $table->unsignedInteger('kapasitas')->nullable()->default(null)->change();
        });
    }
};
