<?php

declare(strict_types=1);

namespace App\Actions\Service;

use App\Actions\Schedule\GetSlotRunway;
use App\Domain\Booking\BookingState;
use App\Domain\Modules\ModuleRelease;
use App\Domain\Subscription\EntitlementRules;
use App\Models\AdditionalItem;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\Unit;
use App\Support\CategoryColor;
use App\Support\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * Prepares the Layanan page (owner-portal.md §8.6): the service list with its usage meter and the
 * category list. The view only renders what this returns.
 */
class GetLayananPage
{
    public const TAB_SERVICES = 'layanan';

    public const TAB_CATEGORIES = 'kategori';

    public function __construct(private GetSlotRunway $slotRunway) {}

    /**
     * @param  array{katakunci?: string, category?: int|string|null, search?: string, status?: string, sort?: string}  $filters
     * @return array{
     *     services: LengthAwarePaginator<int, Service>,
     *     serviceCards: list<array<string, mixed>>,
     *     slotRunway: array<int, string|null>,
     *     commonSlotDate: string|null,
     *     unitOptions: list<array{id: string, name: string}>|null,
     *     pickerImages: list<array{id: int, url: string, alt: string}>,
     *     galleryUrl: string|null,
     *     categoryOptions: list<array{id: int, name: string}>,
     *     categories: list<array<string, mixed>>,
     *     usage: array{used: int, max: int, unlimited: bool, canCreate: bool, message: string|null},
     *     filters: array{katakunci: string, category: int|null, search: string, status: string, sort: string},
     *     colors: array<string, array{label: string, hex: string}>,
     * }
     */
    public function execute(Tenant $tenant, array $filters): array
    {
        app(TenantContext::class)->setTenantId($tenant->id);

        $katakunci = trim((string) ($filters['katakunci'] ?? ''));
        $categoryFilter = isset($filters['category']) && is_numeric($filters['category']) ? (int) $filters['category'] : null;
        $search = trim((string) ($filters['search'] ?? ''));
        $status = in_array($filters['status'] ?? 'all', ['all', 'active', 'inactive'], true) ? (string) ($filters['status'] ?? 'all') : 'all';
        $sort = in_array($filters['sort'] ?? 'order', ['order', 'newest', 'name_asc', 'name_desc', 'services'], true) ? (string) ($filters['sort'] ?? 'order') : 'order';
        $now = now();
        $showAddOns = ModuleRelease::state('add-ons') === 'released'
            && EntitlementRules::canAccessFeature($tenant->subscription, 'add-ons')['allowed'];

        $services = Service::where('idtenant', $tenant->id)
            ->with(['category', 'units:units.id,units.name'])
            ->when($showAddOns, fn ($query) => $query->with('additionalItems:additional_items.id'))
            ->withCount(['bookings', 'bookings as future_booking_count' => fn ($query) => $query
                ->whereIn('status', [BookingState::STATUS_PAID, BookingState::STATUS_PENDING])
                ->where(fn ($dates) => $dates->whereDate('tanggalbooking', '>', $now->toDateString())
                    ->orWhere(fn ($today) => $today->whereDate('tanggalbooking', $now->toDateString())->where('jam', '>=', $now->format('H:i:s'))))])
            ->when($katakunci !== '', fn ($query) => $query->where('namalayanan', 'like', '%'.$katakunci.'%'))
            ->when($categoryFilter !== null, fn ($query) => $query->where('idcategory', $categoryFilter))
            ->orderByDesc('is_active')
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        $allCategories = Category::where('idtenant', $tenant->id)->orderBy('sort_order')->orderBy('name')->get();
        // BQ-142 (FR-ASSET-005): the service image picks from this business's gallery.
        $gallery = Asset::where('idtenant', $tenant->id)->latest()->latest('id')->get();
        $assetIdsByPath = $gallery->pluck('id', 'file_path');

        $categories = Category::where('idtenant', $tenant->id)
            ->withCount('services')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', '%'.$search.'%')->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($sort === 'order', fn ($query) => $query->orderBy('sort_order')->orderBy('name'))
            ->when($sort === 'newest', fn ($query) => $query->orderByDesc('created_at'))
            ->when($sort === 'name_asc', fn ($query) => $query->orderBy('name'))
            ->when($sort === 'name_desc', fn ($query) => $query->orderByDesc('name'))
            ->when($sort === 'services', fn ($query) => $query->orderByDesc('services_count'))
            ->get();

        $unitOptions = null;
        if (ModuleRelease::state('units') === 'released'
            && EntitlementRules::canAccessFeature($tenant->subscription()->with('plan')->first(), 'units')['allowed']) {
            $unitOptions = Unit::where('idtenant', $tenant->id)->orderBy('name')->get()
                ->map(fn (Unit $unit): array => ['id' => (string) $unit->id, 'name' => $unit->name])->all();
        }

        $slotRunway = collect($this->slotRunway->execute($tenant))->pluck('open_until', 'service_id');
        $commonSlotDate = $slotRunway->filter()->countBy()->sortKeysDesc()->sortDesc()->keys()->first();

        return [
            'slotRunway' => $slotRunway->all(),
            'commonSlotDate' => $commonSlotDate,
            'unitOptions' => $unitOptions,
            'addOnOptions' => $showAddOns ? AdditionalItem::where('idtenant', $tenant->id)->orderBy('name')->get(['id', 'name']) : null,
            'services' => $services,
            'serviceCards' => $services->getCollection()->map(fn (Service $service): array => $this->serviceCard($service) + ['imageAssetId' => $assetIdsByPath[(string) $service->image_url] ?? null])->values()->all(),
            'galleryUrl' => Route::has('owner.booking-page') && ModuleRelease::state('booking-page') !== 'hidden' ? route('owner.booking-page', ['section' => 'galeri']) : null,
            'pickerImages' => $gallery->map(fn (Asset $asset): array => ['id' => (int) $asset->id, 'url' => $asset->url, 'alt' => (string) $asset->title])->values()->all(),
            'categoryOptions' => $allCategories->map(fn (Category $category): array => ['id' => (int) $category->id, 'name' => (string) $category->name])->values()->all(),
            'categories' => $this->categoryRows($categories),
            'usage' => $this->usage($tenant),
            'filters' => ['katakunci' => $katakunci, 'category' => $categoryFilter, 'search' => $search, 'status' => $status, 'sort' => $sort],
            'colors' => CategoryColor::PALETTE,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serviceCard(Service $service): array
    {
        $hasHistory = (int) $service->bookings_count > 0;

        return [
            'id' => (int) $service->id,
            'name' => (string) $service->namalayanan,
            'description' => (string) ($service->deskripsi ?? ''),
            'price' => (float) $service->harga,
            'priceLabel' => 'Rp '.number_format((float) $service->harga, 0, ',', '.'),
            'priceUnit' => (string) ($service->satuan_harga ?: 'sesi'),
            'duration' => (int) $service->durasi,
            'durationUnit' => (string) ($service->satuan_durasi ?: 'menit'),
            'categoryId' => $service->idcategory !== null ? (int) $service->idcategory : null,
            'categoryName' => $service->category?->name,
            'categoryColor' => CategoryColor::hex($service->category?->color),
            'imageUrl' => $service->cover_image_url,
            'isActive' => (bool) $service->is_active,
            'isPopular' => (bool) $service->is_popular,
            'bookingCount' => (int) $service->bookings_count,
            'futureBookingCount' => (int) $service->getAttribute('future_booking_count'),
            'addOnIds' => $service->relationLoaded('additionalItems') ? $service->additionalItems->pluck('id')->map(fn ($id): string => (string) $id)->all() : [],
            'capacity' => (int) $service->kapasitas,
            'unitIds' => $service->units->pluck('id')->map(fn ($id): string => (string) $id)->all(),
            'unitNames' => $service->units->pluck('name')->all(),
            // AC-LYN-02: a service with any booking can only be deactivated.
            'canDelete' => ! $hasHistory,
        ];
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @return list<array<string, mixed>>
     */
    private function categoryRows(Collection $categories): array
    {
        return $categories->map(fn (Category $category): array => [
            'id' => (int) $category->id,
            'name' => (string) $category->name,
            'description' => (string) ($category->description ?? ''),
            'color' => array_key_exists((string) $category->color, CategoryColor::PALETTE) ? (string) $category->color : CategoryColor::DEFAULT,
            'colorHex' => CategoryColor::hex($category->color),
            'imageUrl' => $category->image_src,
            'sortOrder' => (int) $category->sort_order,
            'serviceCount' => (int) $category->services_count,
            'isActive' => (bool) $category->is_active,
            'canDelete' => (int) $category->services_count === 0,
        ])->values()->all();
    }

    /**
     * @return array{used: int, max: int, unlimited: bool, canCreate: bool, message: string|null}
     */
    private function usage(Tenant $tenant): array
    {
        $subscription = Subscription::with('plan')->where('idtenant', $tenant->id)->latest('id')->first();
        $used = Service::where('idtenant', $tenant->id)->count();
        $quota = EntitlementRules::canCreateService($subscription, $used);

        return [
            'used' => $used,
            'max' => (int) $quota['maxServices'],
            'unlimited' => (bool) $quota['isUnlimited'],
            'canCreate' => (bool) $quota['allowed'],
            'message' => $quota['message'],
        ];
    }
}
