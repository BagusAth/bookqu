<?php

declare(strict_types=1);

namespace App\Actions\Service;

use App\Domain\Subscription\EntitlementRules;
use App\Models\Category;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Support\CategoryColor;
use App\Support\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Prepares the Layanan page (owner-portal.md §8.6): the service list with its usage meter and the
 * category list. The view only renders what this returns.
 */
class GetLayananPage
{
    public const TAB_SERVICES = 'layanan';

    public const TAB_CATEGORIES = 'kategori';

    /**
     * @param  array{katakunci?: string, category?: int|string|null, search?: string, status?: string, sort?: string}  $filters
     * @return array{
     *     services: LengthAwarePaginator<int, Service>,
     *     serviceCards: list<array<string, mixed>>,
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

        $services = Service::where('idtenant', $tenant->id)
            ->with(['category', 'resources:resources.id'])
            ->withCount('bookings')
            ->when($katakunci !== '', fn ($query) => $query->where('namalayanan', 'like', '%'.$katakunci.'%'))
            ->when($categoryFilter !== null, fn ($query) => $query->where('idcategory', $categoryFilter))
            ->orderByDesc('is_active')
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        $allCategories = Category::where('idtenant', $tenant->id)->orderBy('sort_order')->orderBy('name')->get();

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

        return [
            'services' => $services,
            'serviceCards' => $services->getCollection()->map(fn (Service $service): array => $this->serviceCard($service))->values()->all(),
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
            // Unit links are edited on the Unit page (BQ-152); the form sends them back unchanged because
            // a save without `resource_ids` detaches every unit (BQ-045).
            'resourceIds' => $service->resources->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
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
