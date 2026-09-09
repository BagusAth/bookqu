<?php

namespace Database\Seeders;

use App\Models\AdditionalItem;
use App\Models\Asset;
use App\Models\Booking;
use App\Models\Category;
use App\Models\CustomerNote;
use App\Models\OwnerBlockedDate;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Resource;
use App\Models\Review;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BramaStudioSeeder extends Seeder
{
    /**
     * Seed comprehensive dummy data for Brama Digital & its owner modules.
     */
    public function run(): void
    {
        // ── 1. Ensure Subscription Plans ──────────────────────────────────────
        $planSmall = Plan::updateOrCreate(
            ['namapaket' => 'small'],
            ['hargabulanan' => 149000, 'maxlayanan' => 5, 'maxbooking' => 300, 'isunlimited' => false]
        );

        $planMedium = Plan::updateOrCreate(
            ['namapaket' => 'medium'],
            ['hargabulanan' => 299000, 'maxlayanan' => 0, 'maxbooking' => 500, 'isunlimited' => false]
        );

        $planPro = Plan::updateOrCreate(
            ['namapaket' => 'pro'],
            ['hargabulanan' => 499000, 'maxlayanan' => 0, 'maxbooking' => 0, 'isunlimited' => true]
        );

        // ── 2. Create / Ensure Owner Users ───────────────────────────────────
        $ownerBramantyo = User::updateOrCreate(
            ['email' => 'bramantyo989@gmail.com'],
            [
                'namalengkap'       => 'Bramantyo (Brama Digital)',
                'password'          => Hash::make('password'),
                'nomorhp'           => '081299887766',
                'role'              => 'owner',
                'email_verified_at' => now(),
            ]
        );

        $ownerBramaTest = User::updateOrCreate(
            ['email' => 'brama@bookqu.test'],
            [
                'namalengkap'       => 'Brama Digital Owner',
                'password'          => Hash::make('password'),
                'nomorhp'           => '081299887766',
                'role'              => 'owner',
                'email_verified_at' => now(),
            ]
        );

        $ownerSilver = User::updateOrCreate(
            ['email' => 'silverdrgn6661@gmail.com'],
            [
                'namalengkap'       => 'Brama Digital Admin',
                'password'          => Hash::make('password'),
                'nomorhp'           => '081299887766',
                'role'              => 'owner',
                'email_verified_at' => now(),
            ]
        );

        $adminUser = User::updateOrCreate(
            ['email' => 'admin@bookqu.com'],
            [
                'namalengkap'       => 'Admin BookQu',
                'password'          => Hash::make('password'),
                'nomorhp'           => '080000000000',
                'role'              => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // ── 3. Create Tenants ────────────────────────────────────────────────
        // Master Tenant: Brama Digital Studio
        $tenantBrama = Tenant::updateOrCreate(
            ['slug' => 'brama-digital'],
            [
                'iduser'                 => $ownerBramantyo->id,
                'namabisnis'             => 'Brama Digital Studio',
                'jenisbisnis'            => 'Creative Studio & Production',
                'alamat'                 => 'Jl. Senopati No. 88, Kebayoran Baru, Jakarta Selatan',
                'deskripsi'              => 'Studio kreatif terpadu untuk fotografi profesional, rekaman podcast, videografi 4K green screen, dan live streaming broadcast.',
                'nomorhp'                => '081299887766',
                'payment_mode'           => 'platform',
                'midtrans_status'        => 'approved',
                'midtrans_environment'   => 'sandbox',
                'saldo_platform'         => 34500000,
                'weekend_price_type'     => 'multiplier',
                'weekend_price_value'    => 1.10,
                'cancel_before_hours'    => 24,
                'reschedule_before_hours'=> 12,
                'theme_color'            => 'purple',
                'button_style'           => 'rounded-xl',
                'card_style'             => 'modern',
            ]
        );

        // Also ensure brama@bookqu.test has its own active tenant so logging in with either email works
        $tenantStudio = Tenant::updateOrCreate(
            ['slug' => 'brama-studio'],
            [
                'iduser'                 => $ownerBramaTest->id,
                'namabisnis'             => 'Brama Studio Creative',
                'jenisbisnis'            => 'Creative Studio',
                'alamat'                 => 'Jl. Senopati No. 88, Jakarta Selatan',
                'deskripsi'              => 'Studio kreatif foto dan video profesional.',
                'nomorhp'                => '081299887766',
                'payment_mode'           => 'platform',
                'saldo_platform'         => 18500000,
                'weekend_price_type'     => 'multiplier',
                'weekend_price_value'    => 1.10,
                'cancel_before_hours'    => 24,
                'reschedule_before_hours'=> 12,
                'theme_color'            => 'purple',
                'button_style'           => 'rounded-xl',
                'card_style'             => 'modern',
            ]
        );

        // Also ensure silverdrgn6661@gmail.com has its own active tenant
        $tenantSilver = Tenant::updateOrCreate(
            ['slug' => 'brama-agency'],
            [
                'iduser'                 => $ownerSilver->id,
                'namabisnis'             => 'Brama Creative Agency',
                'jenisbisnis'            => 'Creative Studio & Production',
                'alamat'                 => 'Jl. Senopati No. 88, Kebayoran Baru, Jakarta Selatan',
                'deskripsi'              => 'Studio kreatif terpadu untuk fotografi komersial, rekaman podcast, dan live streaming broadcast.',
                'nomorhp'                => '081299887766',
                'payment_mode'           => 'platform',
                'midtrans_status'        => 'approved',
                'midtrans_environment'   => 'sandbox',
                'saldo_platform'         => 28500000,
                'weekend_price_type'     => 'multiplier',
                'weekend_price_value'    => 1.10,
                'cancel_before_hours'    => 24,
                'reschedule_before_hours'=> 12,
                'theme_color'            => 'purple',
                'button_style'           => 'rounded-xl',
                'card_style'             => 'modern',
            ]
        );

        // Seed full rich suite for Brama Digital
        $this->seedTenantFullSuite($tenantBrama, $planPro);

        // Also seed Brama Studio with standard data
        $this->seedTenantFullSuite($tenantStudio, $planPro);

        // Also seed Brama Creative Agency with standard data
        $this->seedTenantFullSuite($tenantSilver, $planPro);

        $this->command->info('Brama Digital comprehensive dataset successfully seeded!');
    }

    /**
     * Populate full suite of business data for a tenant.
     */
    protected function seedTenantFullSuite(Tenant $tenant, Plan $plan): void
    {
        // Set TenantContext so models using BelongsToTenant scope properly during seeding
        app(\App\Support\TenantContext::class)->setTenantId($tenant->id);

        // Active Pro Subscription
        Subscription::updateOrCreate(
            ['idtenant' => $tenant->id],
            [
                'idplan'             => $plan->id,
                'status'             => 'active',
                'langganan_mulai'    => Carbon::now()->subMonths(6),
                'langganan_berakhir' => Carbon::now()->addMonths(6),
                'trial_berakhir'     => null,
            ]
        );

        // ── 4. Categories ────────────────────────────────────────────────────
        $categoriesData = [
            [
                'name'        => 'Fotografi & Portrait Studio',
                'slug'        => 'fotografi-portrait-studio',
                'description' => 'Sesi foto personal, wisuda, keluarga, dan photoshoot tematik dengan lighting studio lengkap.',
                'color'       => 'purple',
                'is_active'   => true,
            ],
            [
                'name'        => 'Podcast & Audio Production',
                'slug'        => 'podcast-audio-production',
                'description' => 'Ruang kedap suara akustik dengan perlengkapan mic Shure & Rode, mixer, dan multi-kamera podcast.',
                'color'       => 'indigo',
                'is_active'   => true,
            ],
            [
                'name'        => 'Videografi & Green Screen',
                'slug'        => 'videografi-green-screen',
                'description' => 'Studio video luas dengan background green screen full-body 4K, lighting video profesional, dan teleprompter.',
                'color'       => 'emerald',
                'is_active'   => true,
            ],
            [
                'name'        => 'Commercial & Product Shoot',
                'slug'        => 'commercial-product-shoot',
                'description' => 'Pemotretan produk katalog, lookbook fashion, dan commercial branding brand Anda.',
                'color'       => 'amber',
                'is_active'   => true,
            ],
            [
                'name'        => 'Live Streaming & Broadcast',
                'slug'        => 'live-streaming-broadcast',
                'description' => 'Hub penyiaran live streaming YouTube, TikTok, dan webinar interaktif dengan internet dedicated 200 Mbps.',
                'color'       => 'rose',
                'is_active'   => true,
            ],
        ];

        $categories = [];
        foreach ($categoriesData as $cData) {
            $cat = Category::updateOrCreate(
                ['idtenant' => $tenant->id, 'slug' => $cData['slug']],
                array_merge($cData, ['idtenant' => $tenant->id])
            );
            $categories[$cData['slug']] = $cat;
        }

        // ── 5. Services ──────────────────────────────────────────────────────
        $servicesData = [
            [
                'namalayanan'   => 'Studio Foto Basic (1 Jam)',
                'category_slug' => 'fotografi-portrait-studio',
                'harga'         => 150000,
                'durasi'        => 60,
                'deskripsi'     => 'Sewa studio foto dengan background polos standar (putih/hitam/abu-abu). Include 2 lighting Godox dan trigger.',
                'satuan_harga'  => 'jam',
                'satuan_durasi' => 'menit',
                'is_active'     => true,
                'is_popular'    => true,
                'kapasitas'     => 5,
                'image_url'     => 'https://placehold.co/1200x675/EEF2FF/382186?text=Studio+Foto+Basic',
            ],
            [
                'namalayanan'   => 'Studio Foto Tematik VIP (2 Jam)',
                'category_slug' => 'fotografi-portrait-studio',
                'harga'         => 350000,
                'durasi'        => 120,
                'deskripsi'     => 'Sesi foto background tematik vintage & loft, softbox lighting premium, sofa set, dan ruang ganti privat.',
                'satuan_harga'  => 'sesi',
                'satuan_durasi' => 'menit',
                'is_active'     => true,
                'is_popular'    => true,
                'kapasitas'     => 8,
                'image_url'     => 'https://placehold.co/1200x675/EEF2FF/382186?text=Studio+Tematik+VIP',
            ],
            [
                'namalayanan'   => 'Graduation & Family Photoshoot',
                'category_slug' => 'fotografi-portrait-studio',
                'harga'         => 500000,
                'durasi'        => 90,
                'deskripsi'     => 'Paket lengkap foto keluarga atau wisuda termasuk fotografer pendamping profesional dan 15 foto hasil edit warna.',
                'satuan_harga'  => 'sesi',
                'satuan_durasi' => 'menit',
                'is_active'     => true,
                'is_popular'    => true,
                'kapasitas'     => 10,
                'image_url'     => 'https://placehold.co/1200x675/EEF2FF/382186?text=Graduation+Family',
            ],
            [
                'namalayanan'   => 'Studio Podcast Multi-Mic 4 Orang (2 Jam)',
                'category_slug' => 'podcast-audio-production',
                'harga'         => 300000,
                'durasi'        => 120,
                'deskripsi'     => 'Studio podcast kedap suara akustik dengan 4 mic Shure SM7B, mixer Rodecaster Pro, dan multi-kamera Sony 4K.',
                'satuan_harga'  => 'sesi',
                'satuan_durasi' => 'menit',
                'is_active'     => true,
                'is_popular'    => true,
                'kapasitas'     => 4,
                'image_url'     => 'https://placehold.co/1200x675/EEF2FF/382186?text=Studio+Podcast',
            ],
            [
                'namalayanan'   => 'Audio Voice Over & Dubbing Session (1 Jam)',
                'category_slug' => 'podcast-audio-production',
                'harga'         => 200000,
                'durasi'        => 60,
                'deskripsi'     => 'Booth vokal akustik terisolasi untuk rekaman VO iklan, audiobook, dubbing video, dan podcast solo.',
                'satuan_harga'  => 'jam',
                'satuan_durasi' => 'menit',
                'is_active'     => true,
                'is_popular'    => false,
                'kapasitas'     => 2,
                'image_url'     => 'https://placehold.co/1200x675/EEF2FF/382186?text=Voice+Over',
            ],
            [
                'namalayanan'   => 'Studio Video Green Screen 4K (2 Jam)',
                'category_slug' => 'videografi-green-screen',
                'harga'         => 450000,
                'durasi'        => 120,
                'deskripsi'     => 'Studio video khusus dengan green screen lantai-dinding tanpa bayangan, lampu continuous Aputure, dan teleprompter.',
                'satuan_harga'  => 'sesi',
                'satuan_durasi' => 'menit',
                'is_active'     => true,
                'is_popular'    => false,
                'kapasitas'     => 8,
                'image_url'     => 'https://placehold.co/1200x675/EEF2FF/382186?text=Green+Screen+4K',
            ],
            [
                'namalayanan'   => 'Creative Music Video Production (Half Day)',
                'category_slug' => 'videografi-green-screen',
                'harga'         => 1500000,
                'durasi'        => 240,
                'deskripsi'     => 'Akses studio lengkap 4 jam untuk produksi video musik indie, creative dance cover, dan video klip sinematik.',
                'satuan_harga'  => 'sesi',
                'satuan_durasi' => 'menit',
                'is_active'     => true,
                'is_popular'    => true,
                'kapasitas'     => 15,
                'image_url'     => 'https://placehold.co/1200x675/EEF2FF/382186?text=Music+Video+Set',
            ],
            [
                'namalayanan'   => 'Product & Catalog Commercial Shoot',
                'category_slug' => 'commercial-product-shoot',
                'harga'         => 600000,
                'durasi'        => 120,
                'deskripsi'     => 'Pemotretan produk fashion, kosmetik, atau makanan dengan meja turntable 360°, macro lens, dan lighting presisi.',
                'satuan_harga'  => 'sesi',
                'satuan_durasi' => 'menit',
                'is_active'     => true,
                'is_popular'    => false,
                'kapasitas'     => 6,
                'image_url'     => 'https://placehold.co/1200x675/EEF2FF/382186?text=Product+Shoot',
            ],
            [
                'namalayanan'   => 'Live Streaming Broadcast Hub (3 Jam)',
                'category_slug' => 'live-streaming-broadcast',
                'harga'         => 850000,
                'durasi'        => 180,
                'deskripsi'     => 'Studio live streaming siap pakai dengan ATEM Mini Pro switcher, audio mixer, multi-lighting, dan koneksi internet redundant.',
                'satuan_harga'  => 'sesi',
                'satuan_durasi' => 'menit',
                'is_active'     => true,
                'is_popular'    => true,
                'kapasitas'     => 6,
                'image_url'     => 'https://placehold.co/1200x675/EEF2FF/382186?text=Live+Streaming+Hub',
            ],
            [
                'namalayanan'   => 'Webinar & Corporate Broadcast Room (2 Jam)',
                'category_slug' => 'live-streaming-broadcast',
                'harga'         => 750000,
                'durasi'        => 120,
                'deskripsi'     => 'Studio broadcast profesional untuk presentasi webinar korporat, training daring, dan townhall meeting perusahaan.',
                'satuan_harga'  => 'sesi',
                'satuan_durasi' => 'menit',
                'is_active'     => true,
                'is_popular'    => false,
                'kapasitas'     => 10,
                'image_url'     => 'https://placehold.co/1200x675/EEF2FF/382186?text=Corporate+Webinar',
            ],
        ];

        $services = [];
        foreach ($servicesData as $sData) {
            $catSlug = $sData['category_slug'];
            unset($sData['category_slug']);
            $sData['idcategory'] = $categories[$catSlug]->id ?? null;

            $service = Service::updateOrCreate(
                ['idtenant' => $tenant->id, 'namalayanan' => $sData['namalayanan']],
                array_merge($sData, ['idtenant' => $tenant->id])
            );
            $services[$sData['namalayanan']] = $service;
        }

        // ── 6. Staff & Pivots ────────────────────────────────────────────────
        $staffData = [
            [
                'name'                  => 'Bramantyo',
                'role'                  => 'Lead Creative Director',
                'email'                 => 'bramantyo@bramadigital.com',
                'phone'                 => '081299887766',
                'availability_schedule' => 'Shift Pagi & Siang (08:00 - 17:00)',
                'is_active'             => true,
                'service_names'         => ['Studio Foto Tematik VIP (2 Jam)', 'Creative Music Video Production (Half Day)', 'Product & Catalog Commercial Shoot'],
            ],
            [
                'name'                  => 'Bella Kartika',
                'role'                  => 'Senior Studio Photographer',
                'email'                 => 'bella@bramadigital.com',
                'phone'                 => '081299887711',
                'availability_schedule' => 'Shift Fleksibel (09:00 - 18:00)',
                'is_active'             => true,
                'service_names'         => ['Studio Foto Basic (1 Jam)', 'Studio Foto Tematik VIP (2 Jam)', 'Graduation & Family Photoshoot'],
            ],
            [
                'name'                  => 'Dimas Audio',
                'role'                  => 'Sound Engineer & Podcast Specialist',
                'email'                 => 'dimas@bramadigital.com',
                'phone'                 => '081299887722',
                'availability_schedule' => 'Shift Siang & Malam (13:00 - 21:00)',
                'is_active'             => true,
                'service_names'         => ['Studio Podcast Multi-Mic 4 Orang (2 Jam)', 'Audio Voice Over & Dubbing Session (1 Jam)', 'Webinar & Corporate Broadcast Room (2 Jam)'],
            ],
            [
                'name'                  => 'Rian Videographer',
                'role'                  => 'Video Production Specialist',
                'email'                 => 'rian@bramadigital.com',
                'phone'                 => '081299887733',
                'availability_schedule' => 'Shift Pagi & Siang (08:00 - 17:00)',
                'is_active'             => true,
                'service_names'         => ['Studio Video Green Screen 4K (2 Jam)', 'Creative Music Video Production (Half Day)', 'Live Streaming Broadcast Hub (3 Jam)'],
            ],
            [
                'name'                  => 'Siti Wardrobe & MUA',
                'role'                  => 'Stylist & Makeup Artist',
                'email'                 => 'siti@bramadigital.com',
                'phone'                 => '081299887744',
                'availability_schedule' => 'Shift Penuh (08:00 - 18:00)',
                'is_active'             => true,
                'service_names'         => ['Studio Foto Tematik VIP (2 Jam)', 'Graduation & Family Photoshoot'],
            ],
            [
                'name'                  => 'Fajar Broadcast Tech',
                'role'                  => 'Streaming & Broadcast Operator',
                'email'                 => 'fajar@bramadigital.com',
                'phone'                 => '081299887755',
                'availability_schedule' => 'Shift Malam (15:00 - 22:00)',
                'is_active'             => true,
                'service_names'         => ['Live Streaming Broadcast Hub (3 Jam)', 'Webinar & Corporate Broadcast Room (2 Jam)'],
            ],
        ];

        foreach ($staffData as $st) {
            $sNames = $st['service_names'];
            unset($st['service_names']);

            $staffObj = Staff::updateOrCreate(
                ['idtenant' => $tenant->id, 'name' => $st['name']],
                array_merge($st, ['idtenant' => $tenant->id])
            );

            $targetServiceIds = [];
            foreach ($sNames as $sName) {
                if (isset($services[$sName])) {
                    $targetServiceIds[] = $services[$sName]->id;
                }
            }
            $staffObj->services()->sync($targetServiceIds);
        }

        // ── 7. Physical Resources & Rooms ────────────────────────────────────
        $resourcesData = [
            [
                'name'                => 'Studio Foto A (White Cyclorama)',
                'type'                => 'Cyclorama Studio',
                'capacity'            => 10,
                'availability_status' => 'available',
                'is_active'           => true,
                'service_names'       => ['Studio Foto Basic (1 Jam)', 'Studio Foto Tematik VIP (2 Jam)', 'Product & Catalog Commercial Shoot'],
            ],
            [
                'name'                => 'Studio Foto B (Thematic Vintage Loft)',
                'type'                => 'Thematic Room',
                'capacity'            => 8,
                'availability_status' => 'available',
                'is_active'           => true,
                'service_names'       => ['Studio Foto Tematik VIP (2 Jam)', 'Graduation & Family Photoshoot'],
            ],
            [
                'name'                => 'Podcast Suite 1 (Acoustic Treated)',
                'type'                => 'Soundproof Room',
                'capacity'            => 4,
                'availability_status' => 'available',
                'is_active'           => true,
                'service_names'       => ['Studio Podcast Multi-Mic 4 Orang (2 Jam)', 'Audio Voice Over & Dubbing Session (1 Jam)'],
            ],
            [
                'name'                => 'Green Screen Stage 4K',
                'type'                => 'Video Production Stage',
                'capacity'            => 12,
                'availability_status' => 'available',
                'is_active'           => true,
                'service_names'       => ['Studio Video Green Screen 4K (2 Jam)', 'Creative Music Video Production (Half Day)'],
            ],
            [
                'name'                => 'Live Streaming Control Room',
                'type'                => 'Broadcast Hub',
                'capacity'            => 6,
                'availability_status' => 'available',
                'is_active'           => true,
                'service_names'       => ['Live Streaming Broadcast Hub (3 Jam)', 'Webinar & Corporate Broadcast Room (2 Jam)'],
            ],
            [
                'name'                => 'VIP Lounge & Private Dressing Room',
                'type'                => 'Hospitality Area',
                'capacity'            => 6,
                'availability_status' => 'available',
                'is_active'           => true,
                'service_names'       => ['Studio Foto Tematik VIP (2 Jam)', 'Graduation & Family Photoshoot', 'Creative Music Video Production (Half Day)'],
            ],
        ];

        foreach ($resourcesData as $rd) {
            $rNames = $rd['service_names'];
            unset($rd['service_names']);

            $resObj = Resource::updateOrCreate(
                ['idtenant' => $tenant->id, 'name' => $rd['name']],
                array_merge($rd, ['idtenant' => $tenant->id])
            );

            $targetServiceIds = [];
            foreach ($rNames as $rName) {
                if (isset($services[$rName])) {
                    $targetServiceIds[] = $services[$rName]->id;
                }
            }
            $resObj->services()->sync($targetServiceIds);
        }

        // ── 8. Additional Items / Add-ons ────────────────────────────────────
        $addonsData = [
            [
                'name'          => 'Extra Lighting Godox SL-60W Set',
                'description'   => 'Tambahan 2 lampu continuous Godox dengan softbox octagon 90cm.',
                'price'         => 50000,
                'stock'         => 5,
                'is_unlimited'  => false,
                'is_active'     => true,
                'service_names' => ['Studio Foto Basic (1 Jam)', 'Studio Foto Tematik VIP (2 Jam)', 'Studio Video Green Screen 4K (2 Jam)'],
            ],
            [
                'name'          => 'Wireless Mic Rode Wireless GO II (Dual Kit)',
                'description'   => 'Set mic nirkabel dual channel untuk rekaman wawancara atau vlog bergerak.',
                'price'         => 75000,
                'stock'         => 2, // Kritis (2 unit)
                'is_unlimited'  => false,
                'is_active'     => true,
                'service_names' => ['Studio Podcast Multi-Mic 4 Orang (2 Jam)', 'Studio Video Green Screen 4K (2 Jam)', 'Live Streaming Broadcast Hub (3 Jam)'],
            ],
            [
                'name'          => 'Lensa Sony G-Master 24-70mm f/2.8',
                'description'   => 'Sewa lensa zoom profesional ketajaman tinggi untuk kamera mirrorless Sony E-Mount.',
                'price'         => 120000,
                'stock'         => 0, // Habis (0 unit)
                'is_unlimited'  => false,
                'is_active'     => true,
                'service_names' => ['Studio Foto Basic (1 Jam)', 'Studio Foto Tematik VIP (2 Jam)', 'Graduation & Family Photoshoot'],
            ],
            [
                'name'          => 'Smoke Machine Atmospheric FX',
                'description'   => 'Efek kabut panggung sinematik untuk photoshoot dramatis atau video klip musik.',
                'price'         => 65000,
                'stock'         => 3, // Kritis (3 unit)
                'is_unlimited'  => false,
                'is_active'     => true,
                'service_names' => ['Studio Foto Tematik VIP (2 Jam)', 'Creative Music Video Production (Half Day)'],
            ],
            [
                'name'          => 'Properti & Jubah Wisuda Lengkap',
                'description'   => 'Peminjaman jubah wisuda standar universitas, topi toga, map ijazah, dan buket bunga.',
                'price'         => 80000,
                'stock'         => 10, // Tersedia normal (10 unit)
                'is_unlimited'  => false,
                'is_active'     => true,
                'service_names' => ['Graduation & Family Photoshoot'],
            ],
            [
                'name'          => 'Asisten Studio Tambahan (Per Sesi)',
                'description'   => 'Bantuan kru studio untuk lighting stand, penataan wardrobe, dan kontrol teleprompter.',
                'price'         => 100000,
                'stock'         => null, // Unlimited
                'is_unlimited'  => true,
                'is_active'     => true,
                'service_names' => array_keys($services),
            ],
            [
                'name'          => 'Instant RAW to JPEG Cloud Export',
                'description'   => 'Seluruh file foto langsung dikonversi dan diunggah ke Google Drive private dalam 15 menit.',
                'price'         => 35000,
                'stock'         => null, // Unlimited
                'is_unlimited'  => true,
                'is_active'     => true,
                'service_names' => array_keys($services),
            ],
            [
                'name'          => 'Fast Delivery Retouch Foto (1x24 Jam)',
                'description'   => 'Layanan kilat editing warna, penghalusan kulit, dan penyesuaian framing selesai besoknya.',
                'price'         => 150000,
                'stock'         => null, // Unlimited
                'is_unlimited'  => true,
                'is_active'     => true,
                'service_names' => ['Studio Foto Basic (1 Jam)', 'Studio Foto Tematik VIP (2 Jam)', 'Graduation & Family Photoshoot'],
            ],
        ];

        foreach ($addonsData as $ad) {
            $aNames = $ad['service_names'];
            unset($ad['service_names']);

            $addonObj = AdditionalItem::updateOrCreate(
                ['idtenant' => $tenant->id, 'name' => $ad['name']],
                array_merge($ad, ['idtenant' => $tenant->id])
            );

            $targetServiceIds = [];
            foreach ($aNames as $aName) {
                if (isset($services[$aName])) {
                    $targetServiceIds[] = $services[$aName]->id;
                }
            }
            $addonObj->services()->sync($targetServiceIds);
        }

        // ── 9. Blocked Dates ─────────────────────────────────────────────────
        OwnerBlockedDate::updateOrCreate(
            ['idtenant' => $tenant->id, 'tanggal' => Carbon::today()->addDays(3)->toDateString()],
            ['alasan' => 'Maintenance Peralatan & Kalibrasi Lighting Studio']
        );
        OwnerBlockedDate::updateOrCreate(
            ['idtenant' => $tenant->id, 'tanggal' => Carbon::today()->subDays(10)->toDateString()],
            ['alasan' => 'Libur Nasional / Cuti Bersama Operasional']
        );

        // ── 10. Vouchers ─────────────────────────────────────────────────────
        $vouchers = [
            [
                'code'                => 'HAPPYHOUR',
                'discount_type'       => 'percentage',
                'discount_value'      => 20,
                'min_spending'        => 200000,
                'max_discount'        => 75000,
                'usage_limit'         => 100,
                'used_count'          => 14,
                'start_date'          => Carbon::now()->subDays(15)->toDateString(),
                'end_date'            => Carbon::now()->addDays(45)->toDateString(),
                'applicable_services' => 'all',
                'is_active'           => true,
            ],
            [
                'code'                => 'BRAMA15',
                'discount_type'       => 'percentage',
                'discount_value'      => 15,
                'min_spending'        => 150000,
                'max_discount'        => 50000,
                'usage_limit'         => 200,
                'used_count'          => 38,
                'start_date'          => Carbon::now()->subDays(30)->toDateString(),
                'end_date'            => Carbon::now()->addDays(60)->toDateString(),
                'applicable_services' => 'all',
                'is_active'           => true,
            ],
            [
                'code'                => 'POTONGAN50RB',
                'discount_type'       => 'fixed',
                'discount_value'      => 50000,
                'min_spending'        => 300000,
                'max_discount'        => 50000,
                'usage_limit'         => 50,
                'used_count'          => 9,
                'start_date'          => Carbon::now()->subDays(10)->toDateString(),
                'end_date'            => Carbon::now()->addDays(30)->toDateString(),
                'applicable_services' => 'all',
                'is_active'           => true,
            ],
        ];

        foreach ($vouchers as $v) {
            Voucher::updateOrCreate(
                ['idtenant' => $tenant->id, 'code' => $v['code']],
                array_merge($v, ['idtenant' => $tenant->id])
            );
        }

        // ── 11. Customer CRM Notes ───────────────────────────────────────────
        $crmNotes = [
            ['customer_identifier' => '081234567801', 'notes' => 'Klien langganan podcast mingguan segmen teknologi. Minta disiapkan 3 mic.'],
            ['customer_identifier' => '081234567802', 'notes' => 'Suka background tematik warm/beige. Lebih nyaman dengan fotografer Bella.'],
            ['customer_identifier' => '081234567803', 'notes' => 'Produksi video klip indie. Selalu pesan ekstra fog smoke machine.'],
        ];
        foreach ($crmNotes as $cn) {
            CustomerNote::updateOrCreate(
                ['idtenant' => $tenant->id, 'customer_identifier' => $cn['customer_identifier']],
                array_merge($cn, ['idtenant' => $tenant->id])
            );
        }

        // ── 12. Dummy Assets ─────────────────────────────────────────────────
        $dummyAssets = [
            ['title' => 'Logo Brama Digital Studio HD', 'category' => 'logo', 'file_path' => 'assets/brama-logo.png', 'mime_type' => 'image/png', 'file_size' => 245000, 'dimensions' => '512x512'],
            ['title' => 'Brosur & Ratecard Studio 2026', 'category' => 'general', 'file_path' => 'assets/ratecard-2026.pdf', 'mime_type' => 'application/pdf', 'file_size' => 1250000, 'dimensions' => null],
            ['title' => 'Banner Promo Happy Hour.jpg', 'category' => 'cover', 'file_path' => 'assets/banner-promo.jpg', 'mime_type' => 'image/jpeg', 'file_size' => 450000, 'dimensions' => '1200x630'],
        ];
        foreach ($dummyAssets as $da) {
            Asset::updateOrCreate(
                ['idtenant' => $tenant->id, 'title' => $da['title']],
                array_merge($da, ['idtenant' => $tenant->id])
            );
        }

        // ── 13. Comprehensive Schedules, Bookings & Payments ─────────────────
        // Customer pool
        $customers = [
            ['name' => 'Arif Hakim', 'phone' => '081234567801', 'email' => 'arif.hakim@gmail.com'],
            ['name' => 'Bunga Citra', 'phone' => '081234567802', 'email' => 'bunga.citra@gmail.com'],
            ['name' => 'Caca Marica', 'phone' => '081234567803', 'email' => 'caca.marica@gmail.com'],
            ['name' => 'Deni Setiawan', 'phone' => '081234567804', 'email' => 'deni.setiawan@gmail.com'],
            ['name' => 'Eka Saputri', 'phone' => '081234567805', 'email' => 'eka.saputri@gmail.com'],
            ['name' => 'Fajar Ramadhan', 'phone' => '081234567806', 'email' => 'fajar.ramadhan@gmail.com'],
            ['name' => 'Gita Gutawa', 'phone' => '081234567807', 'email' => 'gita.gutawa@gmail.com'],
            ['name' => 'Hendi Hermawan', 'phone' => '081234567808', 'email' => 'hendi.hermawan@gmail.com'],
            ['name' => 'Irma Suryani', 'phone' => '081234567809', 'email' => 'irma.suryani@gmail.com'],
            ['name' => 'Joko Susilo', 'phone' => '081234567810', 'email' => 'joko.susilo@gmail.com'],
            ['name' => 'Kiki Amalia', 'phone' => '081234567811', 'email' => 'kiki.amalia@gmail.com'],
            ['name' => 'Lukman Hakim', 'phone' => '081234567812', 'email' => 'lukman.hakim@gmail.com'],
            ['name' => 'Maya Wulan', 'phone' => '081234567813', 'email' => 'maya.wulan@gmail.com'],
            ['name' => 'Nadia Safira', 'phone' => '081234567814', 'email' => 'nadia.safira@gmail.com'],
            ['name' => 'Oki Setiana', 'phone' => '081234567815', 'email' => 'oki.setiana@gmail.com'],
            ['name' => 'Putra Permana', 'phone' => '081234567816', 'email' => 'putra.permana@gmail.com'],
            ['name' => 'Rini Yulianti', 'phone' => '081234567817', 'email' => 'rini.yulianti@gmail.com'],
            ['name' => 'Sandi Nugraha', 'phone' => '081234567818', 'email' => 'sandi.nugraha@gmail.com'],
            ['name' => 'Tari Lestari', 'phone' => '081234567819', 'email' => 'tari.lestari@gmail.com'],
            ['name' => 'Umar Wirahadi', 'phone' => '081234567820', 'email' => 'umar.wirahadi@gmail.com'],
        ];

        // Clean up old tenant schedules and bookings to prevent duplicates
        $oldScheds = Schedule::where('idtenant', $tenant->id)->pluck('id');
        if ($oldScheds->isNotEmpty()) {
            Booking::where('idtenant', $tenant->id)->delete();
            Payment::where('idtenant', $tenant->id)->delete();
            Schedule::where('idtenant', $tenant->id)->delete();
            Review::where('idtenant', $tenant->id)->delete();
        }

        $allServiceList = array_values($services);
        $paymentMethods = ['qris', 'transfer_bank', 'ewallet', 'kartu_kredit'];

        // A. Seed TODAY's precise Schedule & Bookings
        $today = Carbon::today();
        $todaySlots = [
            ['hour' => '08:00', 'status' => 'paid',      'cust' => $customers[0], 'svc' => $allServiceList[0]], // Foto Basic
            ['hour' => '09:00', 'status' => 'paid',      'cust' => $customers[1], 'svc' => $allServiceList[3]], // Podcast
            ['hour' => '11:00', 'status' => 'available', 'cust' => null,          'svc' => $allServiceList[1]], // AVAILABLE!
            ['hour' => '13:00', 'status' => 'paid',      'cust' => $customers[2], 'svc' => $allServiceList[5]], // Green Screen
            ['hour' => '15:00', 'status' => 'pending',   'cust' => $customers[3], 'svc' => $allServiceList[2]], // Graduation (Pending)
            ['hour' => '16:30', 'status' => 'available', 'cust' => null,          'svc' => $allServiceList[7]], // AVAILABLE!
            ['hour' => '18:00', 'status' => 'completed', 'cust' => $customers[4], 'svc' => $allServiceList[8]], // Live Streaming
            ['hour' => '20:00', 'status' => 'available', 'cust' => null,          'svc' => $allServiceList[4]], // AVAILABLE!
        ];

        foreach ($todaySlots as $ts) {
            $durHours = max(1, (int) ceil($ts['svc']->durasi / 60));
            $startH = (int) substr($ts['hour'], 0, 2);
            $startM = substr($ts['hour'], 3, 2);
            $endH = sprintf('%02d:%s', min(23, $startH + $durHours), $startM);

            $sched = Schedule::create([
                'idtenant'       => $tenant->id,
                'idlayanan'      => $ts['svc']->id,
                'tanggal'        => $today->toDateString(),
                'jam_mulai'      => $ts['hour'],
                'jam_selesai'    => $endH,
                'status'         => 'tersedia',
                'harga_override' => $ts['svc']->harga,
            ]);

            if ($ts['status'] !== 'available') {
                $payId = null;
                if (in_array($ts['status'], ['paid', 'completed'])) {
                    $payment = Payment::create([
                        'idtenant'       => $tenant->id,
                        'tipe'           => 'booking',
                        'jumlah'         => $ts['svc']->harga,
                        'status'         => 'sukses',
                        'metode'         => $paymentMethods[array_rand($paymentMethods)],
                        'external_id'    => 'PAY-' . strtoupper(Str::random(10)),
                        'nama_pembayar'  => $ts['cust']['name'],
                        'email_pembayar' => $ts['cust']['email'],
                        'hp_pembayar'    => $ts['cust']['phone'],
                    ]);
                    $payId = $payment->id;
                }

                $booking = Booking::create([
                    'idtenant'           => $tenant->id,
                    'idlayanan'          => $ts['svc']->id,
                    'idschedule'         => $sched->id,
                    'namapelanggan'      => $ts['cust']['name'],
                    'nomorhp'            => $ts['cust']['phone'],
                    'email'              => $ts['cust']['email'],
                    'tanggalbooking'     => $today->toDateString(),
                    'jam'                => $ts['hour'],
                    'status'             => $ts['status'],
                    'idpayment'          => $payId,
                    'catatan'            => 'Booking operasional hari ini.',
                    'booking_code'       => 'BKG-' . strtoupper(Str::random(8)),
                    'cancellation_token' => Str::random(32),
                    'reschedule_token'   => Str::random(32),
                ]);

                if ($payId) {
                    Payment::where('id', $payId)->update(['idbooking' => $booking->id]);
                }
            }
        }

        // B. Seed THIS CURRENT WEEK (Monday to Sunday)
        $startOfWeek = Carbon::now()->startOfWeek();
        for ($dayIndex = 0; $dayIndex < 7; $dayIndex++) {
            $currentDay = $startOfWeek->copy()->addDays($dayIndex);
            // Skip today because it's already seeded above
            if ($currentDay->isSameDay($today)) {
                continue;
            }

            // Saturday is Peak Day (8-10 bookings), weekdays have 4-6
            $daySlotsCount = $currentDay->isSaturday() ? 8 : ($currentDay->isSunday() ? 6 : 5);
            $hoursList = ['08:00', '09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00'];
            shuffle($hoursList);
            $chosenHours = array_slice($hoursList, 0, $daySlotsCount);
            sort($chosenHours);

            foreach ($chosenHours as $idx => $hour) {
                $service = $allServiceList[array_rand($allServiceList)];
                $durHours = max(1, (int) ceil($service->durasi / 60));
                $startH = (int) substr($hour, 0, 2);
                $endH = sprintf('%02d:00', min(23, $startH + $durHours));

                $sched = Schedule::create([
                    'idtenant'       => $tenant->id,
                    'idlayanan'      => $service->id,
                    'tanggal'        => $currentDay->toDateString(),
                    'jam_mulai'      => $hour,
                    'jam_selesai'    => $endH,
                    'status'         => 'tersedia',
                    'harga_override' => $service->harga,
                ]);

                // 1 slot per day is left AVAILABLE for testing walk-in
                if ($idx === 0 && $currentDay->greaterThanOrEqualTo($today)) {
                    continue; // Leave available!
                }

                $cust = $customers[array_rand($customers)];
                $statusChoices = $currentDay->isPast()
                    ? ['completed', 'completed', 'paid', 'cancelled']
                    : ['paid', 'paid', 'pending'];
                $status = $statusChoices[array_rand($statusChoices)];

                $payId = null;
                if (in_array($status, ['paid', 'completed'])) {
                    $payment = Payment::create([
                        'idtenant'       => $tenant->id,
                        'tipe'           => 'booking',
                        'jumlah'         => $service->harga,
                        'status'         => 'sukses',
                        'metode'         => $paymentMethods[array_rand($paymentMethods)],
                        'external_id'    => 'PAY-' . strtoupper(Str::random(10)),
                        'nama_pembayar'  => $cust['name'],
                        'email_pembayar' => $cust['email'],
                        'hp_pembayar'    => $cust['phone'],
                    ]);
                    $payment->created_at = $currentDay;
                    $payment->save();
                    $payId = $payment->id;
                }

                $booking = Booking::create([
                    'idtenant'           => $tenant->id,
                    'idlayanan'          => $service->id,
                    'idschedule'         => $sched->id,
                    'namapelanggan'      => $cust['name'],
                    'nomorhp'            => $cust['phone'],
                    'email'              => $cust['email'],
                    'tanggalbooking'     => $currentDay->toDateString(),
                    'jam'                => $hour,
                    'status'             => $status,
                    'idpayment'          => $payId,
                    'catatan'            => null,
                    'booking_code'       => 'BKG-' . strtoupper(Str::random(8)),
                    'cancellation_token' => Str::random(32),
                    'reschedule_token'   => Str::random(32),
                ]);
                $booking->created_at = $currentDay;
                $booking->save();

                if ($payId) {
                    Payment::where('id', $payId)->update(['idbooking' => $booking->id]);
                }
            }
        }

        // C. Seed NEXT 14 Days (Upcoming calendar schedules & available slots)
        for ($i = 1; $i <= 14; $i++) {
            $futureDate = Carbon::today()->addDays($i);
            // If it's already within this week, skip
            if ($futureDate->lessThanOrEqualTo($startOfWeek->copy()->endOfWeek())) {
                continue;
            }

            $futureHours = ['09:00', '11:00', '14:00', '16:00', '18:00'];
            foreach ($futureHours as $fIdx => $fHour) {
                $service = $allServiceList[array_rand($allServiceList)];
                $durHours = max(1, (int) ceil($service->durasi / 60));
                $startH = (int) substr($fHour, 0, 2);
                $endH = sprintf('%02d:00', min(23, $startH + $durHours));

                $sched = Schedule::create([
                    'idtenant'       => $tenant->id,
                    'idlayanan'      => $service->id,
                    'tanggal'        => $futureDate->toDateString(),
                    'jam_mulai'      => $fHour,
                    'jam_selesai'    => $endH,
                    'status'         => 'tersedia',
                    'harga_override' => $service->harga,
                ]);

                // Half are booked in advance, half are AVAILABLE for reservations
                if ($fIdx % 2 === 0) {
                    $cust = $customers[array_rand($customers)];
                    $status = $fIdx === 0 ? 'paid' : 'pending';

                    $payId = null;
                    if ($status === 'paid') {
                        $payment = Payment::create([
                            'idtenant'       => $tenant->id,
                            'tipe'           => 'booking',
                            'jumlah'         => $service->harga,
                            'status'         => 'sukses',
                            'metode'         => $paymentMethods[array_rand($paymentMethods)],
                            'external_id'    => 'PAY-' . strtoupper(Str::random(10)),
                            'nama_pembayar'  => $cust['name'],
                            'email_pembayar' => $cust['email'],
                            'hp_pembayar'    => $cust['phone'],
                        ]);
                        $payId = $payment->id;
                    }

                    $booking = Booking::create([
                        'idtenant'           => $tenant->id,
                        'idlayanan'          => $service->id,
                        'idschedule'         => $sched->id,
                        'namapelanggan'      => $cust['name'],
                        'nomorhp'            => $cust['phone'],
                        'email'              => $cust['email'],
                        'tanggalbooking'     => $futureDate->toDateString(),
                        'jam'                => $fHour,
                        'status'             => $status,
                        'idpayment'          => $payId,
                        'catatan'            => 'Pemesanan di muka (advance booking).',
                        'booking_code'       => 'BKG-' . strtoupper(Str::random(8)),
                        'cancellation_token' => Str::random(32),
                        'reschedule_token'   => Str::random(32),
                    ]);

                    if ($payId) {
                        Payment::where('id', $payId)->update(['idbooking' => $booking->id]);
                    }
                }
            }
        }

        // D. Seed Historical Data (Past 6 Months) for Charts, Reports & Analytics
        for ($monthOffset = 5; $monthOffset >= 1; $monthOffset--) {
            $monthStart = Carbon::now()->subMonths($monthOffset)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($monthOffset)->endOfMonth();
            $targetBookingsCount = rand(30, 50);

            for ($k = 0; $k < $targetBookingsCount; $k++) {
                $randTimestamp = rand($monthStart->timestamp, $monthEnd->timestamp);
                $randDate = Carbon::createFromTimestamp($randTimestamp);

                $service = $allServiceList[array_rand($allServiceList)];
                $durHours = max(1, (int) ceil($service->durasi / 60));
                $hourInt = rand(8, 20);
                $hourStr = sprintf('%02d:00', $hourInt);
                $endHourStr = sprintf('%02d:00', min(23, $hourInt + $durHours));

                $sched = Schedule::create([
                    'idtenant'       => $tenant->id,
                    'idlayanan'      => $service->id,
                    'tanggal'        => $randDate->toDateString(),
                    'jam_mulai'      => $hourStr,
                    'jam_selesai'    => $endHourStr,
                    'status'         => 'tersedia',
                    'harga_override' => $service->harga,
                    'created_at'     => $randDate,
                    'updated_at'     => $randDate,
                ]);

                $statusChoice = ['completed', 'completed', 'completed', 'paid', 'cancelled'];
                $status = $statusChoice[array_rand($statusChoice)];
                $cust = $customers[array_rand($customers)];

                $payId = null;
                if (in_array($status, ['completed', 'paid'])) {
                    $payment = Payment::create([
                        'idtenant'       => $tenant->id,
                        'tipe'           => 'booking',
                        'jumlah'         => $service->harga,
                        'status'         => 'sukses',
                        'metode'         => $paymentMethods[array_rand($paymentMethods)],
                        'external_id'    => 'PAY-' . strtoupper(Str::random(10)),
                        'nama_pembayar'  => $cust['name'],
                        'email_pembayar' => $cust['email'],
                        'hp_pembayar'    => $cust['phone'],
                        'created_at'     => $randDate,
                        'updated_at'     => $randDate,
                    ]);
                    $payId = $payment->id;
                }

                $booking = Booking::create([
                    'idtenant'           => $tenant->id,
                    'idlayanan'          => $service->id,
                    'idschedule'         => $sched->id,
                    'namapelanggan'      => $cust['name'],
                    'nomorhp'            => $cust['phone'],
                    'email'              => $cust['email'],
                    'tanggalbooking'     => $randDate->toDateString(),
                    'jam'                => $hourStr,
                    'status'             => $status,
                    'idpayment'          => $payId,
                    'catatan'            => null,
                    'booking_code'       => 'BKG-' . strtoupper(Str::random(8)),
                    'cancellation_token' => Str::random(32),
                    'reschedule_token'   => Str::random(32),
                    'created_at'         => $randDate,
                    'updated_at'         => $randDate,
                ]);

                if ($payId) {
                    Payment::where('id', $payId)->update(['idbooking' => $booking->id]);
                }
            }
        }

        // ── 14. Customer Reviews ─────────────────────────────────────────────
        $reviewSamples = [
            [
                'rating'       => 5,
                'komentar'     => 'Studio podcastnya mantap sekali! Audio treatmennya kedap sempurna dan mic Shure SM7B suaranya renyah. Sangat recommended!',
                'balasan'      => 'Terima kasih banyak Kak Arif! Senang bisa mendukung produksi konten podcast Kakak. Ditunggu sesi berikutnya!',
                'dibalas_pada' => Carbon::now()->subDays(2),
                'is_hidden'    => false,
            ],
            [
                'rating'       => 5,
                'komentar'     => 'Photoshoot wisuda keluarga hasilnya luar biasa bagus. Kak Bella fotografernya sangat sabar dan mengarahkan pose dengan ramah.',
                'balasan'      => 'Terima kasih Kak Bunga! Selamat atas wisudanya ya, semoga sukses selalu untuk keluarga tercinta!',
                'dibalas_pada' => Carbon::now()->subDays(3),
                'is_hidden'    => false,
            ],
            [
                'rating'       => 5,
                'komentar'     => 'Green screen 4K sangat rapi tanpa lipatan. Lighting Aputure merata bikin keying di Premiere Pro langsung bersih.',
                'balasan'      => 'Mantap Kak Deni! Tim kami selalu memastikan green screen terbentang presisi dan terkalibrasi dengan baik.',
                'dibalas_pada' => Carbon::now()->subDays(1),
                'is_hidden'    => false,
            ],
            [
                'rating'       => 4,
                'komentar'     => 'Ruangannya sejuk, tempat parkir luas, dan staf standby sigap bantu setup kamera.',
                'balasan'      => 'Terima kasih reviewnya Kak Gita! Kami akan terus menjaga kenyamanan dan fasilitas terbaik untuk customer.',
                'dibalas_pada' => Carbon::now()->subHours(12),
                'is_hidden'    => false,
            ],
        ];

        $firstFewBookings = Booking::where('idtenant', $tenant->id)->where('status', 'completed')->take(4)->get();
        foreach ($firstFewBookings as $idx => $fb) {
            if (isset($reviewSamples[$idx])) {
                $rev = $reviewSamples[$idx];
                Review::updateOrCreate(
                    ['idbooking' => $fb->id],
                    [
                        'idtenant'     => $tenant->id,
                        'idbooking'    => $fb->id,
                        'rating'       => $rev['rating'],
                        'komentar'     => $rev['komentar'],
                        'balasan'      => $rev['balasan'],
                        'dibalas_pada' => $rev['dibalas_pada'],
                        'is_hidden'    => $rev['is_hidden'],
                    ]
                );
            }
        }

        // Clear TenantContext after finishing tenant seed
        app(\App\Support\TenantContext::class)->clear();
    }
}
