# RF-06 — SUBSCRIPTION & ENTITLEMENT REFACTOR

> **Work Order:** RF-06
> **Phase:** Subscription Architecture
> **Baseline Branch:** `Refactor`
> **Baseline Commit:** `ccbcef00ad8726c1cef4ee56e6a2345c5941fbf8`
> **Status:** Planned
> **Depends On:** RF-00 Foundation, RF-02 Payment, RF-04 Application Layer
> **Related Phase:** RF-07 Cleanup & Alignment
> **Primary Areas:** Subscription, Plan, entitlement, usage limits, feature gating, subscription checkout
> **Primary Goal:** Memusatkan subscription state, entitlement, plan capability, usage limit, dan subscription payment tanpa mengubah intended product behavior

---

# 1. Purpose

RF-06 bertujuan merapikan seluruh mekanisme subscription BookQu menjadi satu boundary yang konsisten.

Subscription bukan sekadar halaman pricing.

Subscription menentukan:

```text
Plan
   ↓
Subscription Status
   ↓
Entitlement
   ↓
Feature Access
   ↓
Usage Limits
```

Saat ini sebagian logika tersebut tersebar di beberapa lokasi.

Contoh current implementation:

```text
CheckSubscription middleware
OwnerSubscriptionController
OwnerCheckoutController
registration flow
individual owner controllers
tests
```

Akibatnya, aturan subscription sulit ditemukan dan berpotensi berkembang menjadi beberapa definisi yang berbeda.

RF-06 harus menghasilkan struktur:

```text
Subscription
      ↓
Plan
      ↓
Entitlement
      ↓
Feature Gate / Usage Policy
      ↓
HTTP + Application
```

dengan satu sumber aturan subscription.

---

# 2. Source of Truth

RF-06 mengikuti hierarchy:

```text
PRODUCT.md
    ↓
REQUIREMENTS.md
    ↓
ARCHITECTURE.md
    ↓
RF-06
    ↓
Implementation
    ↓
Tests
```

Dokumen requirements subscription tetap menjadi authority untuk perilaku produk.

Code existing adalah implementation evidence, bukan otomatis definisi requirement.

Jika ditemukan perbedaan:

```text
Requirement
    ≠
Current Implementation
```

jangan langsung mengubah code berdasarkan asumsi.

Identifikasi dahulu:

```text
expected behavior
current behavior
test evidence
business impact
```

---

# 3. Scope

RF-06 mencakup:

```text
Plan
Subscription
Trial
Subscription Status
Feature Entitlement
Feature Gating
Usage Limits
Usage Calculation
Staff Limits
Service Limits
Booking Limits
Analytics Entitlement
Landing Page Entitlement
Subscription Checkout
Subscription Payment State
Subscription Payment Callback
Subscription Invoice
Subscription History
Subscription Access Middleware
```

RF-06 juga mencakup boundary antara subscription dan:

```text
Payment
Owner Portal
Service
Staff
Booking
Analytics
Landing Page
```

---

# 4. Current Subscription Architecture

Current implementation memiliki beberapa komponen utama:

```text
app/Models/Plan.php
app/Models/Subscription.php
app/Http/Middleware/CheckSubscription.php
app/Http/Controllers/Owner/OwnerSubscriptionController.php
app/Http/Controllers/Owner/OwnerCheckoutController.php
```

Subscription payment menggunakan:

```text
Payment
+
Midtrans
```

dengan:

```text
Payment.tipe = subscription
```

Current subscription checkout juga masih langsung berhubungan dengan Midtrans SDK dari `OwnerCheckoutController`.

---

# 5. Current Plan Data

Current `Plan` model memiliki:

```text
namapaket
hargabulanan
maxlayanan
maxbooking
isunlimited
```

Current tests juga menunjukkan adanya staff limitation berdasarkan plan.

Evidence yang tersedia pada baseline:

```text
Small
- maxlayanan = 5
- maxbooking = 300
- staff limit = 2

Medium
- service limit = effectively unlimited
- maxbooking = 500
- staff limit = 15
- Analytics accessible
- Analytics export accessible

Pro
- unlimited
- no booking limit
- no service limit
- no staff limit
```

Nilai aktual harus tetap mengikuti requirement dan seeded/test configuration yang menjadi baseline.

Jangan memindahkan angka tersebut ke banyak tempat baru.

---

# 6. Current Trial Behavior

Registration flow saat ini membuat subscription trial:

```text
status = trial
trial_berakhir = now() + 7 days
plan = Pro
```

Dengan demikian, current onboarding concept adalah:

```text
New Owner
   ↓
Tenant
   ↓
7-day Trial
   ↓
Pro-level capability
```

`CheckSubscription` saat ini memperlakukan status `trial` sebagai special case dan mengizinkan feature access.

RF-06 wajib memastikan definisi trial menjadi eksplisit.

---

# 7. Current Feature Gating

`CheckSubscription` saat ini menerima feature parameter, misalnya:

```text
subscription:medium
subscription:pro
```

Current implementation membandingkan:

```text
small = 1
medium = 2
pro = 3
```

dan kemudian menentukan apakah current plan memenuhi required level.

Contoh current routes:

```text
GET /owner/analytics
    middleware subscription:medium

GET /owner/analytics/export
    middleware subscription:medium

GET /owner/landing-page
    middleware subscription:pro

POST /owner/landing-page
    middleware subscription:pro
```

Current design bekerja, tetapi feature entitlement masih terlalu bergantung pada nama package dan integer level hardcoded.

---

# 8. Current Usage Logic

`OwnerSubscriptionController` saat ini menghitung:

```text
jumlah layanan
jumlah booking bulan ini
jumlah staff
maxlayanan
maxbooking
maxstaff
persenlayanan
persenbooking
persenstaff
```

Staff limit saat ini ditentukan melalui:

```php
match ($planName) {
    'small'  => 2,
    'medium' => 15,
    'pro'    => 0,
}
```

Ini menunjukkan bahwa subscription capability belum sepenuhnya berasal dari satu model/policy/configuration.

RF-06 harus mengurangi duplication ini.

---

# 9. Main Architectural Problems

## 9.1 Subscription Rules Scattered

Subscription knowledge saat ini tersebar:

```text
Middleware
Controller
Registration
Checkout
Tests
```

Developer harus mencari ke banyak file untuk memahami satu feature.

---

## 9.2 Plan Name Used as Business Logic

Current logic bergantung pada string:

```text
small
medium
pro
```

dalam beberapa lokasi.

Contoh:

```php
$levels = [
    'small' => 1,
    'medium' => 2,
    'pro' => 3,
];
```

dan:

```php
$maxstaff = match ($planName) {
    'small' => 2,
    'medium' => 15,
    'pro' => 0,
};
```

Jika logic ini bertambah di banyak file, perubahan plan akan menjadi berisiko.

---

## 9.3 Entitlement Tidak Eksplisit

Saat ini subscription dapat menjawab:

```text
plan apa?
status apa?
```

tetapi belum mempunyai boundary eksplisit untuk:

```text
boleh memakai feature X?
berapa max service?
berapa max booking?
berapa max staff?
apakah unlimited?
```

---

## 9.4 Usage Policy Bercampur dengan Presentation

`OwnerSubscriptionController` saat ini sekaligus:

```text
fetch subscription
fetch plans
calculate usage
calculate limits
calculate percentages
prepare page data
```

Perhitungan usage dan entitlement sebaiknya tidak menjadi concern utama controller.

---

## 9.5 Subscription Payment Terlalu Dekat dengan Controller

`OwnerCheckoutController` saat ini melakukan:

```text
validation
tenant resolution
plan lookup
payment creation
order ID generation
Midtrans parameter construction
Snap token generation
payment update
payment status verification
invoice resolution
```

RF-02 menangani general payment boundary.

RF-06 harus memastikan subscription payment menggunakan boundary tersebut, bukan membangun payment architecture kedua.

---

# 10. Target Subscription Architecture

Target:

```text
                         ┌───────────────────┐
                         │      Plan         │
                         └────────┬──────────┘
                                  │
                                  ▼
                         ┌───────────────────┐
                         │   Subscription    │
                         │ state + period    │
                         └────────┬──────────┘
                                  │
                                  ▼
                         ┌───────────────────┐
                         │   Entitlement     │
                         │ policy / access   │
                         └────────┬──────────┘
                                  │
                 ┌────────────────┼────────────────┐
                 ▼                ▼                ▼
          Feature Access     Usage Limit      Trial Status
                 │                │                │
                 ▼                ▼                ▼
             Middleware       Actions         UI/Portal
```

Application layer:

```text
HTTP
  ↓
Subscription Action / Policy
  ↓
Subscription Domain
  ↓
Payment Boundary
```

---

# 11. Target Folder Structure

Target direction:

```text
app/
├── Actions/
│   └── Subscription/
│       ├── CreateTrialSubscription.php
│       ├── CreateSubscriptionCheckout.php
│       ├── ActivateSubscription.php
│       ├── ChangeSubscription.php
│       └── ...
│
├── Domain/
│   └── Subscription/
│       ├── SubscriptionStatus.php
│       ├── SubscriptionEntitlement.php
│       ├── SubscriptionUsage.php
│       ├── SubscriptionPolicy.php
│       └── ...
│
├── Http/
│   ├── Controllers/
│   │   └── Owner/
│   │       └── OwnerSubscriptionController.php
│   │
│   ├── Requests/
│   │   └── Subscription/
│   │
│   └── Middleware/
│       └── CheckSubscription.php
│
├── Services/
│   └── Payment/
│
└── Infrastructure/
    └── Payments/
```

Tidak semua class harus dibuat.

Class hanya dibuat jika memang dibutuhkan oleh responsibility yang sedang dipindahkan.

---

# 12. Subscription Entity Responsibility

`Subscription` harus menjadi representasi subscription state.

Minimal concern:

```text
tenant
plan
status
trial period
subscription period
```

Subscription model tidak boleh menjadi tempat seluruh feature policy.

Hindari menjadikan model sebagai:

```text
feature manager
usage dashboard
payment processor
notification service
```

---

# 13. Plan Responsibility

`Plan` merepresentasikan package/capability configuration.

Plan bertanggung jawab terhadap data seperti:

```text
name
price
service limit
booking limit
unlimited capability
```

Plan tidak seharusnya mengurus:

```text
payment gateway
tenant authentication
HTTP response
redirect
Blade
```

---

# 14. Subscription Status Model

Status subscription harus mempunyai semantic yang jelas.

Current statuses yang terlihat pada implementation:

```text
trial
active
expired
cancelled
```

Payment status:

```text
pending
sukses
gagal
```

harus tidak dicampur dengan subscription status.

Contoh:

```text
Payment status:
pending

Subscription status:
trial
```

merupakan dua konsep berbeda.

---

# 15. Subscription vs Payment

Hubungan:

```text
Subscription
    =
akses berlangganan

Payment
    =
transaksi pembayaran
```

Payment success dapat menyebabkan perubahan subscription state.

Tetapi payment bukan subscription.

Contoh:

```text
Payment sukses
    ↓
Activate / Extend Subscription
```

bukan:

```text
Payment.status === subscription.status
```

---

# 16. Subscription Lifecycle

Target lifecycle harus eksplisit.

Contoh conceptual lifecycle:

```text
New Owner
    ↓
Trial
    ↓
Active
    ↓
Expired

Active
    ↓
Cancelled
```

Transisi aktual harus mengikuti business requirements.

Jangan membuat state transition baru yang belum dibutuhkan.

Yang harus jelas:

```text
trial → active
trial → expired
active → expired
active → cancelled
```

dan behavior masing-masing.

---

# 17. Trial Rules

Trial harus mempunyai satu definisi yang jelas mengenai:

```text
start
end
duration
status
entitlement
expiration behavior
```

Saat ini trial dibuat selama 7 hari dengan capability Pro.

RF-06 harus memusatkan logic ini sehingga:

```text
registration
middleware
subscription page
feature policy
```

tidak masing-masing mendefinisikan trial secara berbeda.

---

# 18. Trial Expiration Verification

Current middleware memiliki special-case:

```text
if subscription.status === 'trial'
    allow access
```

sementara subscription page juga membaca:

```text
trial_berakhir
```

Hal ini harus diverifikasi dalam RF-06.

Pertanyaan yang harus dijawab oleh implementation berdasarkan requirement:

```text
Apa yang terjadi setelah trial_berakhir terlewati tetapi status masih trial?
```

Expected behavior harus berasal dari requirement baseline.

Jangan hanya memperbaiki code berdasarkan dugaan.

Jika requirement menetapkan trial harus berhenti setelah tanggal berakhir, maka entitlement check harus menggunakan waktu trial sebagai authority dan status stale tidak boleh memberikan unlimited access.

Jika behavior current ternyata berbeda dengan requirement, perubahan tersebut harus dicatat sebagai behavior correction, bukan disamarkan sebagai refactor.

---

# 19. Entitlement Concept

Entitlement adalah jawaban atas:

> Apa yang boleh dilakukan tenant saat ini?

Contoh:

```text
canUseAnalytics()
canExportAnalytics()
canUseLandingPage()
canManageStaff()
canCreateService()
canCreateBooking()
```

Namun jangan membuat puluhan method tanpa kebutuhan.

Entitlement harus mengikuti feature yang benar-benar digunakan sistem.

---

# 20. Feature Gate Design

Target:

```text
Feature
     ↓
Entitlement Check
     ↓
Current Subscription
     ↓
Current Plan
```

bukan:

```text
Feature
     ↓
if ($plan === 'pro')
```

yang tersebar di banyak controller.

Contoh conceptual API:

```php
$subscription->entitledTo('analytics');
```

atau:

```php
$entitlement->allows('analytics');
```

atau policy/service equivalent.

Nama dan implementation final dapat dipilih saat coding berdasarkan architecture convention.

Yang penting adalah satu boundary.

---

# 21. Plan Level vs Feature Entitlement

RF-06 harus membedakan dua konsep:

```text
Plan hierarchy
```

dan:

```text
Feature entitlement
```

Hierarchy:

```text
small < medium < pro
```

berguna jika memang package memang bertingkat.

Tetapi feature access jangan selamanya bergantung pada angka:

```text
1 < 2 < 3
```

Karena sebuah plan dapat memiliki capability khusus tanpa mengikuti hierarchy sederhana.

Target jangka panjang:

```text
Plan
  ↓
Capabilities
```

bukan hanya:

```text
Plan
  ↓
Numeric Level
```

Tidak perlu langsung membangun permission matrix besar apabila product belum membutuhkannya.

---

# 22. Usage Limit Architecture

Usage limit berbeda dengan feature entitlement.

Contoh:

```text
Feature:
Analytics allowed?

Usage:
How many services can exist?

Feature:
Staff management allowed?

Usage:
How many staff can exist?
```

Target conceptual API:

```text
Subscription
   ↓
Usage Policy
   ├── service limit
   ├── booking limit
   └── staff limit
```

---

# 23. Service Limit

Current plan memiliki:

```text
maxlayanan
```

Usage dihitung dari Service tenant.

Rule harus dipusatkan:

```text
current service usage
        <
plan service limit
```

kecuali plan unlimited.

Validation harus dilakukan server-side sebelum creation.

UI hanya menampilkan informasi.

---

# 24. Booking Limit

Current tests menunjukkan Small mempunyai monthly booking quota.

Current behavior diuji dengan:

```text
300 bookings
+
new booking attempt
=
blocked
```

Pro diuji sebagai unlimited.

RF-06 harus mempertahankan semantic:

```text
booking quota is a subscription rule
```

Tetapi detail booking creation tetap berada di Booking domain/application layer.

Artinya:

```text
Booking Action
    ↓
Subscription usage policy
    ↓
Allowed / rejected
```

bukan:

```text
CheckSubscription middleware
    ↓
entire booking quota logic
```

---

# 25. Booking Quota Period

Current implementation menghitung:

```text
current month
+
current year
```

RF-06 harus mempertahankan definisi period tersebut jika requirement menganggap quota monthly.

Centralize period calculation supaya tidak ada:

```text
controller A → current month
controller B → last 30 days
controller C → billing cycle
```

tanpa keputusan eksplisit.

---

# 26. Staff Limit

Current implementation memiliki staff cap:

```text
small = 2
medium = 15
pro = unlimited
```

Logic ini saat ini berada di subscription page/controller dan enforcement terjadi di staff-related implementation/tests.

RF-06 harus memastikan:

```text
staff creation
    ↓
subscription usage policy
    ↓
limit check
```

dan tidak bergantung pada angka hardcoded yang tersebar.

---

# 27. Feature Entitlement Examples

Current verified feature gating includes:

```text
Analytics
    → Medium+

Analytics Export
    → Medium+

Landing Page
    → Pro
```

Current tests juga menunjukkan:

```text
Small
    → Analytics blocked

Medium
    → Analytics allowed
    → Analytics export allowed
    → Landing Page blocked

Pro
    → Pro-level capabilities
```

These are implementation-backed entitlement examples.

The final capability matrix must follow `REQUIREMENTS.md`.

---

# 28. Subscription Dashboard Data

Subscription page saat ini menampilkan:

```text
current subscription
available plans
service usage
booking usage
staff usage
limits
usage percentages
trial status
remaining trial days
subscription payment history
```

RF-06 harus memisahkan:

```text
business calculation
```

dari:

```text
presentation preparation
```

Ideal:

```text
Subscription Application Service / Action
        ↓
Subscription View Model / DTO
        ↓
Blade
```

Controller tidak perlu menghitung semua percentage sendiri.

---

# 29. Usage Percentage

Usage percentage adalah presentation-derived data.

Contoh:

```text
services used = 4
service limit = 5
percentage = 80%
```

Source of truth:

```text
usage
+
limit
```

bukan percentage itu sendiri.

Percentage boleh dihitung di application/read layer.

Jangan menyimpan percentage ke database hanya untuk dashboard.

---

# 30. Unlimited Behavior

Current implementation menggunakan:

```text
max = 0
```

sebagai salah satu representation of unlimited.

`isunlimited` juga tersedia.

Ini harus dinormalisasi.

Jangan membuat code seperti:

```text
0 means unlimited
```

tersebar di banyak file.

Target:

```text
SubscriptionUsage::isUnlimited()
```

atau equivalent policy abstraction.

Code pemanggil sebaiknya tidak perlu menebak semantic angka `0`.

---

# 31. Subscription Middleware

`CheckSubscription` tetap dapat menjadi HTTP-level feature gate.

Tanggung jawab ideal:

```text
Resolve tenant
      ↓
Load current subscription
      ↓
Ask entitlement policy
      ↓
Allow / redirect
```

Middleware tidak seharusnya mengetahui detail:

```text
plan level numbers
staff limits
booking counting
service count
payment processing
```

---

# 32. Middleware Failure Behavior

Middleware harus menangani kondisi:

```text
No subscription
Expired
Cancelled
Insufficient entitlement
```

dengan behavior yang konsisten.

Namun error message dan redirect tidak boleh menjadi satu-satunya representation of policy.

Policy harus sudah menentukan:

```text
allowed
blocked
reason
```

middleware kemudian menerjemahkan hasil tersebut menjadi HTTP behavior.

---

# 33. Subscription Checkout

Subscription checkout saat ini melalui:

```text
OwnerSubscription
      ↓
OwnerCheckoutController
      ↓
Payment record
      ↓
Midtrans
      ↓
Payment status
      ↓
Subscription activation
```

Target:

```text
Owner HTTP
   ↓
CreateSubscriptionCheckout
   ↓
Payment Application Boundary
   ↓
Midtrans Adapter
```

Subscription checkout harus menggunakan payment architecture RF-02.

Jangan membuat architecture payment kedua khusus subscription.

---

# 34. Subscription Order ID

Current `OwnerCheckoutController` memiliki logic:

```text
BQ-YYYYMMDD-XXXX
```

untuk order ID.

RF-06 harus memutuskan, bersama boundary RF-02, apakah order ID generation merupakan:

```text
payment infrastructure concern
```

atau:

```text
application order identity concern
```

Jangan membiarkan generator tersebar di booking payment dan subscription payment.

---

# 35. Subscription Payment Type

Current implementation menggunakan:

```text
Payment.tipe = subscription
```

sedangkan booking payment mempunyai tipe berbeda.

Payment type harus tetap eksplisit.

RF-06 tidak boleh menggabungkan:

```text
booking payment
```

dan:

```text
subscription payment
```

menjadi satu business object tanpa distinction.

---

# 36. Payment → Subscription Activation

Saat subscription payment sukses, lifecycle target adalah:

```text
Payment verified
       ↓
Subscription application action
       ↓
Subscription activated/extended
```

Payment verification:

```text
Midtrans responsibility
```

Subscription activation:

```text
Subscription responsibility
```

Kedua operation boleh berada dalam satu application use case, tetapi boundary-nya harus jelas.

---

# 37. Idempotency

Subscription payment callback harus idempotent.

Contoh:

```text
Webhook
+
client callback
+
manual status check
```

dapat mencapai system untuk payment yang sama.

Hanya boleh ada satu effective subscription activation.

Do not:

```text
callback #1 → activate
callback #2 → activate again
callback #3 → activate again
```

without checking current state.

Payment architecture RF-02 menjadi reference utama untuk mekanisme idempotency.

---

# 38. Subscription Extension

Jika product mendukung renewal/extension:

```text
Existing active subscription
      ↓
New successful subscription payment
      ↓
new subscription period
```

Aturan period harus eksplisit.

Jangan membuat:

```text
start = now()
end = now() + 30 days
```

tanpa mempertimbangkan current active period apabila requirement menghendaki extension.

RF-06 harus mengikuti behavior yang sudah didefinisikan requirement.

---

# 39. Multiple Subscription Records

Current code menggunakan:

```text
latest subscription
```

sebagai active subscription lookup.

RF-06 harus menetapkan semantic:

```text
Which subscription is the current authoritative subscription?
```

Idealnya, application code tidak boleh sekadar:

```php
->latest()->first()
```

dan menganggap itu selalu active.

Jika multiple historical subscription rows memang allowed, policy harus menentukan:

```text
current
historical
expired
cancelled
```

dengan jelas.

---

# 40. Subscription History

Payment history adalah:

```text
Payment
where tipe = subscription
```

Ini berbeda dengan subscription history.

Jika system membutuhkan history subscription:

```text
Subscription records
```

harus tetap dapat dibedakan dari:

```text
Payment records
```

Jangan menjadikan payment table sebagai substitute untuk subscription lifecycle history.

---

# 41. Registration Flow

Current registration flow membuat:

```text
User
Tenant
Pro Plan
Trial Subscription
```

secara langsung di route closure.

Ini bukan ideal.

RF-06 harus memindahkan workflow tersebut ke application layer.

Target:

```text
Registration
    ↓
Owner/Tenant creation
    ↓
CreateTrialSubscription
```

Registration route/controller tidak boleh mengetahui detail:

```text
7 days
Pro plan
subscription row creation
```

secara langsung.

---

# 42. Subscription Checkout Controller

`OwnerCheckoutController` saat ini terlalu besar untuk hanya menjadi HTTP coordinator.

RF-06 harus memanfaatkan RF-02 dan RF-04.

Target responsibility:

```text
show checkout page
submit checkout request
show payment page
receive callback request
return invoice
```

Business operation:

```text
CreateSubscriptionCheckout
VerifySubscriptionPayment
ActivateSubscription
```

dipindahkan ke Application boundary.

---

# 43. Subscription Page Controller

`OwnerSubscriptionController` sebaiknya menjadi read orchestration.

Controller cukup:

```text
resolve tenant
load subscription overview
return view
```

Perhitungan:

```text
limits
usage
entitlements
trial
```

dipindahkan ke subscription read/application boundary.

---

# 44. Suggested Subscription Application Services

Possible structures:

```text
app/Actions/Subscription/
├── CreateTrialSubscription.php
├── CreateSubscriptionCheckout.php
├── ActivateSubscription.php
├── ChangePlan.php
└── RenewSubscription.php
```

dan:

```text
app/Domain/Subscription/
├── SubscriptionEntitlement.php
├── SubscriptionUsage.php
├── SubscriptionPolicy.php
└── SubscriptionState.php
```

Tidak semua harus dibuat.

Prioritaskan kebutuhan nyata.

---

# 45. Entitlement API

Recommended conceptual contract:

```php
$subscription->can('analytics');
$subscription->can('analytics.export');
$subscription->can('landing-page');
```

atau equivalent centralized policy.

Untuk limits:

```php
$subscription->limit('services');
$subscription->limit('bookings');
$subscription->limit('staff');
```

Untuk usage:

```php
$subscription->usage('services');
$subscription->usage('bookings');
$subscription->usage('staff');
```

Nama final dapat mengikuti coding convention yang dipilih.

Tujuannya adalah menghindari business rules tersebar.

---

# 46. Avoid Giant Subscription Service

Jangan membuat satu:

```text
SubscriptionService
```

yang menangani:

```text
plan
trial
usage
feature gate
checkout
payment
invoice
notification
```

semuanya sekaligus.

Pisahkan berdasarkan use case/responsibility.

---

# 47. Authorization vs Entitlement

Bedakan:

```text
Authorization
```

dengan:

```text
Subscription Entitlement
```

Authorization menjawab:

> Apakah actor ini berhak melakukan operation?

Entitlement menjawab:

> Apakah tenant subscription mengizinkan feature ini?

Contoh:

```text
Owner A
   authorized = true
   entitlement = false
```

hasilnya tetap:

```text
blocked
```

Kedua boundary harus tetap diperiksa.

---

# 48. Subscription and Tenant Isolation

Subscription adalah tenant-owned data.

Every subscription query harus:

```text
tenant-scoped
```

dan tidak boleh memungkinkan:

```text
Tenant A
→ melihat subscription Tenant B
```

Exception seperti:

```text
Subscription::withoutGlobalScopes()
```

hanya boleh digunakan untuk system-level operation dengan authorization yang jelas.

---

# 49. Subscription Data Access

Preferred:

```text
current tenant context
    ↓
subscription relation/query
```

Avoid arbitrary:

```php
Subscription::find($id)
```

untuk owner request tanpa ownership verification.

Subscription ID dari request adalah untrusted input.

---

# 50. Blade Rules

Subscription Blade harus hanya menampilkan:

```text
plan name
price
usage
limit
status
trial information
available actions
payment history
```

Blade tidak boleh menghitung:

```text
whether user can access
whether quota exceeded
whether subscription expired
whether payment is successful
```

Backend harus mengirim state/decision yang dibutuhkan.

---

# 51. Subscription UI as Consumer

Conceptual flow:

```text
Subscription Read Model
        ↓
owner/subscription.blade.php
```

UI dapat menggunakan:

```text
canAnalytics
canLandingPage
serviceUsage
serviceLimit
servicePercentage
```

daripada melakukan:

```text
if plan == 'medium'
```

sendiri.

---

# 52. Navigation Entitlement

Sidebar/menu may hide unavailable feature.

Namun:

```text
hidden menu != security
```

Route middleware/policy tetap melakukan enforcement.

RF-06 hanya memastikan navigation representation konsisten.

UI-specific adjustments can later be covered by RF-05.

---

# 53. Testing Strategy

Subscription harus memiliki coverage terhadap:

```text
Plan
Trial
Active subscription
Expired subscription
Cancelled subscription
Entitlement
Service quota
Booking quota
Staff quota
Analytics access
Landing page access
Subscription payment
Payment callback
Idempotency
Tenant isolation
```

---

# 54. Characterization Tests

Sebelum refactor, gunakan existing tests sebagai behavior baseline.

Current tests yang sangat relevan:

```text
tests/Feature/SubscriptionRulesAndMechanismsTest.php
tests/Feature/Owner/AnalyticsAndSubscriptionTest.php
```

Test tersebut sudah mencakup evidence untuk:

```text
Pro unlimited booking
Small 300 booking quota
Small 2 staff limit
Medium 15 staff limit
Medium analytics access
Medium analytics export access
Medium landing page blocked
Small analytics blocked
Trial Pro access
```

Jangan menghapus test lama hanya karena implementation dipindahkan.

Test harus dipertahankan atau diperbaiki untuk mencerminkan boundary baru.

---

# 55. Additional Tests to Add

Setelah architecture refactor, tambahkan tests untuk:

```text
trial expiration
entitlement policy directly
usage calculation
unlimited semantics
tenant isolation
subscription payment activation
duplicate payment callback
expired payment
subscription checkout authorization
```

Testing target bukan hanya HTTP route.

Business rule harus dapat diuji lebih dekat ke application/domain layer.

---

# 56. Critical Trial Test

Tambahkan explicit scenario:

```text
trial_berakhir < now()
status = trial
```

dan verifikasi behavior berdasarkan requirement.

Test ini penting karena current `CheckSubscription` memiliki special-case trial yang berpotensi melewati expiration check.

Jangan menentukan expected result berdasarkan current code.

Gunakan requirement as authority.

---

# 57. Critical Tenant Test

Scenario:

```text
Tenant A active subscription
Tenant B active subscription
Owner A requests Subscription B
```

Expected:

```text
B must never be exposed to A
```

---

# 58. Critical Entitlement Test

Scenario:

```text
Small
→ analytics
```

Expected:

```text
blocked
```

Scenario:

```text
Medium
→ analytics
```

Expected:

```text
allowed
```

Scenario:

```text
Medium
→ landing page
```

Expected:

```text
blocked
```

Scenario:

```text
Pro
→ pro feature
```

Expected:

```text
allowed
```

These expectations must remain traceable to requirement IDs.

---

# 59. Critical Usage Tests

### Service

```text
limit reached
→ creation rejected
```

### Booking

```text
monthly quota reached
→ booking creation/selection rejected
```

### Staff

```text
limit reached
→ staff creation rejected
```

Unlimited plan:

```text
limit reached
→ no quota rejection
```

---

# 60. Subscription Payment Tests

Verify:

```text
checkout creation
payment record
payment type = subscription
amount
plan association
tenant association
payment status
callback
verification
activation
invoice
```

Verify failure paths:

```text
provider failure
expired payment
duplicate callback
already-successful payment
unauthorized tenant
```

---

# 61. Refactor Sequence

## RF-06.1 — Subscription Baseline

Capture:

```text
plan values
subscription statuses
trial behavior
entitlement behavior
usage behavior
payment lifecycle
```

Run relevant tests.

---

## RF-06.2 — Subscription Responsibility Map

Map:

```text
Plan
Subscription
Trial
Entitlement
Usage
Middleware
Checkout
Payment
```

to current files.

Output should make it obvious where every rule currently lives.

---

## RF-06.3 — Introduce Subscription Domain Boundary

Create only the minimum required domain objects.

Possible first step:

```text
SubscriptionEntitlement
SubscriptionUsage
SubscriptionPolicy
```

Do not build a framework.

---

## RF-06.4 — Centralize Plan Capability

Move:

```text
plan hierarchy
service limit
booking limit
staff limit
unlimited behavior
```

into one consistent policy boundary.

Remove duplicated hardcoded plan logic gradually.

---

## RF-06.5 — Centralize Entitlement

Replace scattered:

```text
if plan === ...
```

and hardcoded level comparisons with the centralized entitlement policy.

---

## RF-06.6 — Centralize Usage

Move:

```text
service count
booking count
staff count
quota period
limit handling
```

into a subscription usage boundary.

---

## RF-06.7 — Refactor CheckSubscription

Make middleware:

```text
tenant resolution
↓
subscription lookup
↓
entitlement decision
↓
HTTP result
```

No plan-level business logic inside middleware.

---

## RF-06.8 — Refactor Subscription Page

Make `OwnerSubscriptionController` a thin read boundary.

Extract:

```text
subscription overview
usage
entitlement
trial
payment history
```

into appropriate application/read structures.

---

## RF-06.9 — Refactor Trial Creation

Move current registration-time trial creation into:

```text
CreateTrialSubscription
```

or equivalent application action.

---

## RF-06.10 — Integrate Subscription with Payment Boundary

Coordinate with RF-02.

Refactor:

```text
OwnerCheckoutController
```

so it no longer owns provider-specific payment implementation.

---

## RF-06.11 — Verify Subscription Lifecycle

Verify:

```text
trial
active
expired
cancelled
renewal/change
payment success
payment failure
```

---

## RF-06.12 — Final Cleanup

Search for remaining subscription logic:

```text
plan ===
namapaket
small
medium
pro
maxlayanan
maxbooking
isunlimited
maxstaff
subscription:
Subscription::
```

Classify every result:

```text
domain rule
application use case
presentation
configuration
test
legacy
```

Then remove only redundant logic.

---

# 62. Files Likely to Change

Primary files:

```text
app/Http/Middleware/CheckSubscription.php

app/Http/Controllers/Owner/OwnerSubscriptionController.php
app/Http/Controllers/Owner/OwnerCheckoutController.php

app/Models/Plan.php
app/Models/Subscription.php

registration flow
routes/web.php
```

Potential new:

```text
app/Actions/Subscription/*
app/Domain/Subscription/*
app/Http/Requests/Subscription/*
```

Potential RF-02 coordination:

```text
app/Services/Payment/*
app/Infrastructure/Payments/*
```

Tests:

```text
tests/Feature/SubscriptionRulesAndMechanismsTest.php
tests/Feature/Owner/AnalyticsAndSubscriptionTest.php
tests/Unit/Subscription/*
tests/Feature/Owner/Subscription/*
```

---

# 63. Files That Should Not Become Subscription Containers

Do not move subscription logic into:

```text
BookingController
OwnerDashboardController
OwnerPortalController
OwnerScheduleController
Blade files
routes/web.php
```

These files may consume subscription decisions.

They must not become the source of subscription rules.

---

# 64. Dashboard Boundary

`OwnerDashboardController` currently loads Subscription directly.

Dashboard may display:

```text
trial status
remaining days
plan information
```

but should not calculate subscription state independently.

Preferred:

```text
Subscription Overview
       ↓
Dashboard presentation
```

One subscription definition should feed multiple screens.

---

# 65. Service Management Boundary

Service creation can consume:

```text
canCreateService()
```

and:

```text
serviceLimit()
```

but actual Service CRUD remains in Service domain/application.

Avoid:

```text
SubscriptionService::createService()
```

because that mixes subscription and service ownership.

---

# 66. Staff Management Boundary

Staff controller/action should call subscription policy for quota enforcement.

Example:

```text
Create Staff
    ↓
Authorize owner
    ↓
Check Staff Entitlement / Limit
    ↓
Create Staff
```

Subscription determines whether operation is permitted.

Staff application logic remains Staff logic.

---

# 67. Booking Boundary

Booking flow may consult subscription quota.

However subscription should not become the owner of:

```text
schedule availability
double booking
booking status
payment state
```

Correct relationship:

```text
Booking Application
        ↓
Subscription Usage Policy
        ↓
quota decision

Booking Domain
        ↓
availability / state / concurrency
```

---

# 68. Subscription and Caching

Subscription data may be cached later if profiling proves useful.

However:

```text
cache != source of truth
```

Do not introduce subscription caching during initial RF-06 extraction unless needed to preserve existing behavior.

The DB remains authoritative.

---

# 69. Database Changes

RF-06 should avoid schema changes by default.

Do not create migrations merely to make architecture look cleaner.

Potential future schema changes require separate justification if they affect:

```text
subscription lifecycle
plan capabilities
usage tracking
billing period
entitlement persistence
```

A migration should not be introduced simply to support a preferred class structure.

---

# 70. Configuration vs Database

Determine explicitly which subscription data is:

```text
database configuration
```

and which is:

```text
business code
```

Examples:

```text
Plan price
Plan limits
Plan availability
```

are naturally configuration/data.

Whereas:

```text
how quota is measured
what "active" means
what expiration means
```

are business rules.

Do not hardcode database configuration into PHP when it can legitimately remain plan data.

---

# 71. Avoid Hardcoded Plan Names

After RF-06, search for:

```text
'small'
'medium'
'pro'
```

outside:

```text
seeders
test fixtures
plan initialization
explicit compatibility mapping
```

Every other occurrence must be reviewed.

The goal is not zero occurrences.

The goal is zero unexplained business logic tied directly to strings.

---

# 72. Avoid Numeric Plan Levels as Global Convention

The current:

```text
small = 1
medium = 2
pro = 3
```

model may remain temporarily.

But the final architecture should not require every developer to know:

```text
3 means Pro
```

Capability checks should use semantic names.

Example:

```text
requires Analytics
requires Landing Page
```

rather than:

```text
requires level >= 3
```

---

# 73. Error Handling

Subscription policy should return predictable outcomes.

Conceptually:

```text
Allowed
Blocked
Reason
```

Reasons may include:

```text
no subscription
expired subscription
cancelled subscription
feature unavailable
quota exceeded
trial expired
```

HTTP layer translates these into:

```text
redirect
validation error
403
response
```

Do not embed HTTP redirect logic inside domain objects.

---

# 74. Definition of Done

RF-06 is complete when:

### Subscription Architecture

* [ ] Plan responsibility is clear.
* [ ] Subscription responsibility is clear.
* [ ] Trial lifecycle is explicit.
* [ ] Feature entitlement has one clear boundary.
* [ ] Usage limits have one clear boundary.
* [ ] Unlimited semantics are explicit.
* [ ] Subscription status is separate from payment status.

### Feature Gating

* [ ] Middleware no longer contains complex plan logic.
* [ ] Feature access uses centralized entitlement logic.
* [ ] Analytics gating preserved.
* [ ] Landing page gating preserved.
* [ ] Other gated features remain correct.

### Usage

* [ ] Service limit preserved.
* [ ] Booking quota preserved.
* [ ] Staff limit preserved.
* [ ] Unlimited plan behavior preserved.
* [ ] Usage period semantics preserved.

### Payment

* [ ] Subscription checkout uses payment boundary.
* [ ] Subscription payment remains distinct from booking payment.
* [ ] Callback is idempotent.
* [ ] Successful payment activates correct subscription.
* [ ] Unauthorized tenant access is rejected.

### Registration

* [ ] Trial creation moved out of route-level business logic.
* [ ] Trial behavior remains unchanged unless explicitly corrected from requirement evidence.

### Quality

* [ ] Existing subscription tests pass.
* [ ] New entitlement tests pass.
* [ ] Trial expiry behavior is explicitly verified.
* [ ] Tenant isolation is verified.
* [ ] No unrelated booking/payment/schedule behavior regresses.

---

# 75. Acceptance Criteria

## AC-RF06-001 — Single Subscription Policy

There is one authoritative application/domain path for determining subscription entitlement.

---

## AC-RF06-002 — Single Usage Policy

Service, booking, and staff quota semantics are not independently reimplemented in multiple controllers.

---

## AC-RF06-003 — Feature Gate Independence

Feature access is not determined solely through scattered:

```text
if planName == ...
```

conditions.

---

## AC-RF06-004 — Trial Semantics

Trial state and trial expiration are explicitly represented and tested.

---

## AC-RF06-005 — Payment Separation

Subscription payment remains a payment concern while subscription activation remains a subscription concern.

---

## AC-RF06-006 — Idempotent Activation

Repeating the same successful payment callback cannot produce duplicate effective subscription activation.

---

## AC-RF06-007 — Tenant Isolation

Owner A cannot use subscription endpoints to inspect or operate on Owner B's subscription.

---

## AC-RF06-008 — Usage Enforcement

Plan limits continue to be enforced server-side.

---

## AC-RF06-009 — Presentation Independence

Subscription Blade files do not determine subscription entitlement independently.

---

## AC-RF06-010 — Controller Thinness

`OwnerSubscriptionController` and subscription-related checkout endpoints primarily orchestrate requests and return responses.

---

# 76. Stop Conditions

Agent must stop and report if it discovers:

```text
subscription requirement conflict
unclear trial expiration policy
multiple competing current subscriptions
unknown billing period rules
renewal behavior not documented
plan feature matrix contradicting tests
payment activation behavior contradicting requirements
subscription data accessible across tenants
```

Do not resolve these by inventing behavior.

---

# 77. AI Agent Instructions

Before modifying subscription code:

```text
Read:
AGENTS.md
PRODUCT.md
REQUIREMENTS.md
ARCHITECTURE.md
DEVELOPMENT.md
TRACKER.md
RF-02-PAYMENT.md
RF-04-APPLICATION-LAYER.md
```

Then inspect:

```text
Subscription
Plan
CheckSubscription
OwnerSubscriptionController
OwnerCheckoutController
payment implementation
registration flow
relevant tests
routes
```

Search the entire repository for:

```text
Subscription::
Subscription
Plan::
namapaket
small
medium
pro
maxlayanan
maxbooking
isunlimited
trial_berakhir
subscription:
```

before creating new abstractions.

---

# 78. Refactor Safety Rules

Do not:

```text
rewrite all subscription code at once
replace Midtrans implementation
change plan pricing
change quota numbers
change feature availability
change trial duration
change booking rules
change payment semantics
change database schema unnecessarily
```

unless the requirement baseline explicitly requires it.

RF-06 adalah architecture consolidation, bukan product redesign.

---

# 79. Final Principle

Subscription harus menjadi satu konsep yang dapat dipahami dari satu tempat.

Target akhir:

```text
Plan
   ↓
Subscription
   ↓
Entitlement
   ↓
Usage
   ↓
Feature / Quota Decision
```

dengan consumer:

```text
Owner UI
Booking
Service
Staff
Analytics
Landing Page
Checkout
Dashboard
Middleware
```

semuanya membaca keputusan subscription yang sama.

Tidak boleh terjadi kondisi di mana:

```text
Dashboard mengatakan allowed
Middleware mengatakan blocked
Controller mengatakan unlimited
Blade mengatakan quota penuh
```

untuk subscription yang sama.

RF-06 selesai ketika subscription bukan lagi kumpulan conditional `small/medium/pro` yang tersebar di controller dan middleware, tetapi menjadi satu architectural boundary yang dapat diuji, digunakan ulang, dan dipahami oleh developer maupun AI agent tanpa harus membaca seluruh repository.
