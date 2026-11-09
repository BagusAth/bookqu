<?php

declare(strict_types=1);

namespace Tests\Feature\Owner;

use App\Actions\Subscription\CreateTrialSubscription;
use App\Models\Booking;
use App\Models\Review;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BQ-143 (owner-portal.md §8.11): the Ulasan page with tabs and suggested replies.
 */
class UlasanPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Tenant $tenant;

    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-20 10:00:00', 'Asia/Jakarta'));

        $this->owner = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        $this->tenant = Tenant::factory()->create(['iduser' => $this->owner->id, 'slug' => 'studio-ulasan']);
        app(CreateTrialSubscription::class)->execute($this->tenant);
        $this->service = Service::factory()->create(['idtenant' => $this->tenant->id, 'namalayanan' => 'Foto Studio', 'is_active' => true]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function review(array $attributes = [], ?Tenant $tenant = null, string $customer = 'Rani'): Review
    {
        $tenant ??= $this->tenant;
        $service = $tenant->is($this->tenant) ? $this->service : Service::factory()->create(['idtenant' => $tenant->id]);
        $slot = Schedule::factory()->create(['idtenant' => $tenant->id, 'idlayanan' => $service->id]);
        $booking = Booking::factory()->create([
            'idtenant' => $tenant->id, 'idlayanan' => $service->id, 'idschedule' => $slot->id,
            'namapelanggan' => $customer, 'status' => 'completed', 'booking_code' => 'BKQ-'.strtoupper(substr(md5((string) $slot->id), 0, 6)),
        ]);

        return Review::create(array_merge([
            'idtenant' => $tenant->id, 'idbooking' => $booking->id, 'rating' => 5, 'komentar' => 'Bagus sekali', 'is_hidden' => false,
        ], $attributes));
    }

    public function test_page_shows_the_average_distribution_tabs_and_search(): void
    {
        $this->review(['rating' => 5]);
        $this->review(['rating' => 4]);
        $this->review(['rating' => 2, 'balasan' => 'Maaf', 'dibalas_pada' => now()]);

        $this->actingAs($this->owner)->get(route('owner.reviews'))
            ->assertOk()
            ->assertSee('role="tablist"', false)
            ->assertSee('Belum dibalas')
            ->assertSee('Disembunyikan')
            ->assertSee('3,7')
            ->assertSee('3 ulasan')
            ->assertSee('name="search"', false)
            ->assertViewHas('summary', fn (array $summary): bool => $summary['distribution'] === [5 => 1, 4 => 1, 3 => 0, 2 => 1, 1 => 0]);
    }

    public function test_ac_uls_02_belum_dibalas_lists_exactly_the_reviews_without_a_reply(): void
    {
        $this->review(['komentar' => 'Belum dijawab satu']);
        $this->review(['komentar' => 'Belum dijawab tersembunyi', 'is_hidden' => true]);
        $this->review(['komentar' => 'Balasan kosong', 'balasan' => '']);
        $this->review(['komentar' => 'Sudah dijawab', 'balasan' => 'Terima kasih', 'dibalas_pada' => now()]);

        $response = $this->actingAs($this->owner)->get(route('owner.reviews'))->assertOk();
        $shown = collect($response->viewData('reviews')->items())->pluck('comment')->sort()->values()->all();

        $this->assertSame(['Balasan kosong', 'Belum dijawab satu', 'Belum dijawab tersembunyi'], $shown);
        $response->assertDontSee('Sudah dijawab');
    }

    public function test_the_tabs_all_and_hidden(): void
    {
        $this->review(['komentar' => 'Terlihat', 'balasan' => 'Ok', 'dibalas_pada' => now()]);
        $this->review(['komentar' => 'Disembunyikan pemilik', 'is_hidden' => true, 'balasan' => 'Ok', 'dibalas_pada' => now()]);

        $this->actingAs($this->owner)->get(route('owner.reviews', ['tab' => 'semua']))
            ->assertSee('Terlihat')->assertSee('Disembunyikan pemilik');
        $this->get(route('owner.reviews', ['tab' => 'disembunyikan']))
            ->assertSee('Disembunyikan pemilik')->assertDontSee('Terlihat');
    }

    public function test_ac_uls_01_a_hidden_review_does_not_appear_on_the_booking_page(): void
    {
        $review = $this->review(['komentar' => 'Rahasia jangan tampil']);
        $this->actingAs($this->owner)->post(route('owner.reviews.toggle', $review->id))->assertRedirect();
        $this->assertTrue($review->fresh()->is_hidden);

        auth()->logout();
        $this->get('/'.$this->tenant->slug)->assertOk()->assertDontSee('Rahasia jangan tampil');
    }

    public function test_reply_is_saved_up_to_1000_characters_and_can_be_edited(): void
    {
        $review = $this->review();

        $this->actingAs($this->owner)->post(route('owner.reviews.reply', $review->id), ['balasan' => str_repeat('a', 1001)])
            ->assertSessionHasErrors('balasan');
        $this->assertNull($review->fresh()->balasan);

        $this->post(route('owner.reviews.reply', $review->id), ['balasan' => 'Terima kasih, Rani!'])->assertRedirect(route('owner.reviews'));
        $this->assertSame('Terima kasih, Rani!', $review->fresh()->balasan);

        $this->post(route('owner.reviews.reply', $review->id), ['balasan' => 'Terima kasih banyak, Rani!']);
        $this->assertSame('Terima kasih banyak, Rani!', $review->fresh()->balasan);
    }

    public function test_reply_dialog_offers_three_suggested_sentences_and_a_named_route_template(): void
    {
        $this->review(['rating' => 5]);

        $response = $this->actingAs($this->owner)->get(route('owner.reviews'))->assertOk();
        $this->assertCount(3, $response->viewData('suggestions')['positive']);
        $this->assertCount(3, $response->viewData('suggestions')['negative']);
        $response->assertSee('id="review-reply-dialog"', false)
            ->assertSee('reviews\/__ID__\/reply', false)
            ->assertDontSee('/owner/reviews/${', false);
    }

    public function test_each_review_links_to_its_booking(): void
    {
        $review = $this->review();
        $code = Booking::withoutGlobalScopes()->findOrFail($review->idbooking)->booking_code;

        $this->actingAs($this->owner)->get(route('owner.reviews'))
            ->assertSee($code)
            ->assertSee(route('owner.bookings', ['katakunci' => $code]));
    }

    public function test_list_is_paginated_and_keeps_the_search(): void
    {
        foreach (range(1, 17) as $i) {
            $this->review(['komentar' => 'Ulasan foto '.$i]);
        }

        $page = $this->actingAs($this->owner)->get(route('owner.reviews', ['tab' => 'semua', 'search' => 'foto']))->assertOk();
        $this->assertSame(15, count($page->viewData('reviews')->items()));
        $this->assertSame(17, $page->viewData('reviews')->total());
        $page->assertSee('search=foto', false);
    }

    public function test_search_is_escaped_and_the_page_is_tenant_scoped(): void
    {
        $this->review(['komentar' => 'Punya kami']);
        $other = Tenant::factory()->create();
        $foreign = $this->review(['komentar' => 'Punya bisnis lain'], $other);

        $html = (string) $this->actingAs($this->owner)
            ->get(route('owner.reviews', ['tab' => 'semua', 'search' => "'\"><script>alert(1)</script>"]))
            ->assertOk()->getContent();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('addslashes', $html);

        $this->get(route('owner.reviews', ['tab' => 'semua']))->assertSee('Punya kami')->assertDontSee('Punya bisnis lain');
        $this->post(route('owner.reviews.reply', $foreign->id), ['balasan' => 'Bukan milik kami'])->assertNotFound();
        $this->post(route('owner.reviews.toggle', $foreign->id))->assertNotFound();
    }

    public function test_empty_state_explains_how_customers_review(): void
    {
        $this->actingAs($this->owner)->get(route('owner.reviews'))
            ->assertOk()
            ->assertSee('link akses');
    }

    public function test_kasir_cannot_open_the_page(): void
    {
        $kasir = User::factory()->create(['role' => 'owner', 'email_verified_at' => now()]);
        TenantMember::create(['idtenant' => $this->tenant->id, 'iduser' => $kasir->id, 'role' => 'kasir', 'accepted_at' => now()]);

        $this->actingAs($kasir)->get(route('owner.reviews'))->assertForbidden();
    }
}
