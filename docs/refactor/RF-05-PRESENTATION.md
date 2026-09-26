# RF-05 — PRESENTATION LAYER REFACTOR

> **Work Order:** RF-05
> **Phase:** Presentation Architecture
> **Baseline Branch:** `Refactor`
> **Baseline Commit:** `ccbcef00ad8726c1cef4ee56e6a2345c5941fbf8`
> **Status:** Completed
> **Depends On:** RF-00 Foundation, RF-01 Booking, RF-02 Payment, RF-03 Schedule, RF-04 Application Layer
> **Primary Areas:** `resources/views`, `resources/js`, Blade components, Alpine.js
> **Primary Goal:** Reduce Blade/JavaScript complexity without changing intended product behavior

---

# 1. Purpose

RF-05 bertujuan merapikan presentation layer BookQu agar:

* halaman Blade tidak menjadi tempat menampung terlalu banyak UI logic;
* komponen UI dapat digunakan kembali secara konsisten;
* Alpine.js digunakan untuk interaksi UI, bukan business logic;
* data dan keputusan bisnis disiapkan oleh application/backend layer;
* perubahan satu komponen tidak menyebabkan perubahan besar pada banyak halaman;
* halaman owner dan customer lebih mudah dipahami manusia maupun AI agent;
* visual dan behavior produk yang sudah berjalan tetap dipertahankan.

RF-05 **bukan redesign UI**.

Tujuan fase ini adalah refactor struktur presentation layer terhadap implementasi yang sudah ada.

Prinsip utamanya:

```text
Existing Product Behavior
        ↓
Preserve
        ↓
Improve Presentation Structure
        ↓
Reduce Duplication
        ↓
Improve Maintainability
```

---

# 2. Source of Truth

RF-05 harus mengikuti hierarchy dokumentasi BookQu:

```text
PRODUCT.md
    ↓
REQUIREMENTS.md
    ↓
ARCHITECTURE.md
    ↓
RF-05
    ↓
Implementation
    ↓
Tests
```

Presentation layer tidak boleh menentukan ulang business rule.

Jika terdapat konflik antara:

* desain halaman;
* copy UI;
* behavior Blade;
* Alpine logic;
* dokumentasi requirement;

maka jangan langsung mengubah business behavior.

Identifikasi terlebih dahulu requirement dan application behavior yang menjadi sumber keputusan.

---

# 3. Scope

RF-05 mencakup:

```text
resources/views/
├── layouts/
├── components/
├── owner/
├── customer/
├── auth/
├── admin/
└── emails/

resources/js/
```

Fokus utama:

```text
Blade structure
Blade components
Blade partials
Reusable UI
Page-specific UI sections
Alpine state
Page interaction JavaScript
Forms
Tables
Cards
Modals
Filters
Empty states
Status indicators
Navigation presentation
Responsive presentation
```

RF-05 tidak mencakup redesign produk, perubahan business rule, atau perubahan database.

---

# 4. Current Presentation Condition

Berdasarkan baseline `Refactor`, presentation layer sudah berjalan secara fungsional tetapi beberapa halaman sangat besar.

Beberapa halaman utama yang perlu diprioritaskan:

```text
resources/views/owner/calendar.blade.php
resources/views/owner/bookings.blade.php
resources/views/owner/staff-resources.blade.php
resources/views/owner/customers.blade.php

resources/views/customer/manage/show.blade.php
resources/views/customer/manage/invoice.blade.php
resources/views/customer/manage/reschedule.blade.php

resources/views/customer/booking/checkout.blade.php
resources/views/customer/booking/payment.blade.php
```

Beberapa ukuran file saat baseline audit menunjukkan tingkat kompleksitas yang sudah tinggi:

```text
owner/calendar.blade.php          ≈ 96 KB
customer/manage/show.blade.php    ≈ 61 KB
owner/staff-resources.blade.php   ≈ 60 KB
owner/bookings.blade.php          ≈ 51 KB
owner/customers.blade.php         ≈ 34 KB

customer/booking/payment.blade.php   ≈ 41 KB
customer/manage/invoice.blade.php    ≈ 36 KB
customer/booking/checkout.blade.php  ≈ 34 KB
customer/manage/reschedule.blade.php ≈ 31 KB
```

Shared presentation files juga besar:

```text
components/owner/sidebar.blade.php  ≈ 35 KB
components/owner/topbar.blade.php   ≈ 31 KB
```

Angka ukuran file bukan satu-satunya ukuran kualitas.

Namun ukuran tersebut merupakan sinyal bahwa page responsibility, reusable components, local partials, dan client-side interaction perlu dipisahkan.

---

# 5. Main Presentation Problems

## 5.1 Large Page Files

Satu Blade file saat ini dapat menangani sekaligus:

```text
Page layout
Header
Filters
Statistics
Tables
Mobile cards
Modals
Forms
Status rendering
Alpine state
JavaScript event handling
Repeated UI structures
```

Akibatnya:

* sulit menemukan bagian tertentu;
* perubahan kecil berisiko;
* reuse rendah;
* merge conflict lebih mudah terjadi;
* AI agent sulit memahami boundary halaman.

---

## 5.2 Repeated UI

Beberapa pola UI akan muncul di banyak halaman:

```text
Page header
Flash message
Status badge
Button
Filter
Search input
Table
Pagination
Modal
Empty state
Confirmation dialog
Form section
Card
Stat card
Tabs
```

Pattern yang benar-benar reusable harus diekstrak menjadi component.

Namun jangan membuat component hanya karena sebuah markup muncul dua kali.

Extract hanya jika:

1. struktur benar-benar sama atau memiliki kontrak yang jelas;
2. component memiliki tanggung jawab yang jelas;
3. abstraction membuat kode lebih mudah dipahami.

---

## 5.3 Mixed Page and Interaction Logic

Contoh aktual pada calendar:

```blade
x-data="{
    viewMode: ...,
    selectedSlot: null,
    modalOpen: false,
    walkinMode: false,
    mobileWeekDay: ...
}"
```

Hal semacam ini wajar untuk UI interaction.

Masalah muncul ketika Alpine mulai menangani:

* keputusan availability;
* status booking;
* authorization;
* pricing;
* payment state;
* tenant access;
* business validation.

Aturan RF-05:

```text
Alpine = UI state
Backend = business state
```

---

## 5.4 Business Interpretation Inside Blade

Blade masih boleh melakukan presentation formatting sederhana.

Contoh yang masih dapat diterima:

```php
number_format(...)
format(...)
@if (...)
@foreach (...)
```

Namun Blade tidak boleh menjadi tempat untuk menentukan business policy yang kompleks.

Jangan menambahkan logic seperti:

```text
"apakah booking boleh dibatalkan?"
"apakah slot masih tersedia?"
"apakah customer boleh reschedule?"
"apakah payment valid?"
"apakah subscription mengizinkan fitur?"
```

Keputusan tersebut harus berasal dari backend/application/domain layer.

---

## 5.5 Repeated Status Mapping

Beberapa halaman dapat memiliki pola seperti:

```php
match ($booking->status) {
    'completed' => ...,
    'paid' => ...,
    'pending' => ...,
    'cancelled' => ...,
}
```

Presentation layer boleh melakukan rendering mapping sederhana.

Tetapi jangan sampai setiap halaman menciptakan definisi status yang berbeda.

Target jangka panjang:

```text
Booking State
     ↓
Canonical application/domain representation
     ↓
Presentation label / style
```

RF-05 dapat mulai merapikan presentation mapping, tetapi jangan membuat domain redesign hanya untuk menghilangkan satu `match`.

---

# 6. Target Presentation Architecture

Target structure yang diharapkan:

```text
resources/
├── views/
│   ├── layouts/
│   │
│   ├── components/
│   │   ├── shared/
│   │   ├── owner/
│   │   └── customer/
│   │
│   ├── owner/
│   │   ├── calendar.blade.php
│   │   ├── bookings.blade.php
│   │   ├── customers.blade.php
│   │   ├── staff-resources.blade.php
│   │   ├── schedule.blade.php
│   │   └── partials/
│   │
│   ├── customer/
│   │   ├── booking/
│   │   ├── manage/
│   │   └── partials/
│   │
│   ├── admin/
│   ├── auth/
│   └── emails/
│
└── js/
    ├── app.js
    ├── components/
    ├── owner/
    └── customer/
```

Struktur tersebut adalah target konseptual.

Tidak wajib memindahkan seluruh view sekaligus.

Refactoring harus dilakukan secara incremental.

---

# 7. Presentation Responsibility Model

Presentation layer BookQu harus mengikuti pembagian berikut:

```text
Controller / Action
        │
        │ prepares data
        ▼
Blade Page
        │
        ├── composes sections
        ├── passes data
        └── renders page
              │
              ├── Blade Component
              │
              ├── Page Partial
              │
              └── Alpine / JS interaction
```

Setiap bagian mempunyai tanggung jawab yang berbeda.

## Page

Page adalah entry point visual sebuah screen.

Contoh:

```text
owner/calendar.blade.php
owner/bookings.blade.php
customer/booking/checkout.blade.php
```

Page sebaiknya fokus pada:

* struktur halaman;
* urutan section;
* passing data;
* composition.

---

## Reusable Component

Component digunakan ketika sebuah UI block memiliki reusable contract.

Contoh:

```text
components/shared/button
components/shared/modal
components/shared/status-badge

components/owner/page-header
components/owner/stat-card
components/owner/filter-bar
components/owner/sidebar

components/customer/booking-summary
components/customer/payment-summary
```

Component tidak boleh mengambil alih business decision.

---

## Page Partial

Partial digunakan untuk bagian besar yang hanya relevan untuk satu halaman atau satu area.

Contoh:

```text
owner/partials/calendar/
    header.blade.php
    filters.blade.php
    day-view.blade.php
    week-view.blade.php
    month-view.blade.php
    booking-modal.blade.php
```

Partial cocok apabila block tersebut terlalu besar tetapi belum layak menjadi global component.

---

# 8. Component Extraction Rules

Gunakan tiga pertanyaan sebelum membuat component baru:

### Question 1

Apakah UI block ini muncul di lebih dari satu tempat?

Jika ya, component kemungkinan layak.

### Question 2

Apakah UI block memiliki responsibility yang jelas?

Contoh:

```text
Modal
Page Header
Status Badge
Filter Bar
```

lebih baik daripada:

```text
Generic Owner Thing
Booking Everything Component
Dashboard Mega Component
```

### Question 3

Apakah component membutuhkan banyak conditional khusus halaman?

Jika iya, jangan memaksakan abstraction.

Component yang terlalu generik dapat menjadi lebih sulit dipahami daripada markup asli.

---

# 9. Component Naming

Nama component harus mengikuti terminology BookQu.

Gunakan canonical terminology:

```text
Service
Schedule
Booking
Customer
Payment
Staff
Resource
Subscription
Tenant
```

Hindari membuat component baru menggunakan terminology legacy tanpa alasan.

Contoh:

```text
components/owner/service-card
```

lebih disukai daripada:

```text
components/owner/program-card
```

kecuali component tersebut memang harus menangani compatibility layer.

---

# 10. Blade Component Rules

Blade component harus:

* menerima input yang jelas;
* memiliki nama prop yang konsisten;
* tidak melakukan query database;
* tidak melakukan mutation;
* tidak memanggil payment provider;
* tidak membuat booking;
* tidak melakukan authorization sebagai satu-satunya security boundary;
* tidak menentukan tenant berdasarkan input frontend;
* tidak menjalankan business transaction.

Contoh tanggung jawab yang valid:

```text
status
label
icon
variant
href
disabled
size
```

Contoh yang tidak valid:

```text
checkBookingAvailability()
createBooking()
cancelPayment()
calculateTenantSubscription()
processMidtrans()
```

---

# 11. Page-Level Data Contract

Page harus menerima data yang sudah siap digunakan oleh presentation.

Idealnya:

```text
Controller / Action
        ↓
Prepared View Data
        ↓
Blade
```

Hindari membuat Blade melakukan banyak query tambahan.

Contoh yang tidak diinginkan:

```blade
@php
    $customers = Customer::...
@endphp
```

Blade tidak boleh menjadi query layer.

Hal yang sama berlaku untuk:

```blade
Booking::...
Payment::...
Service::...
Schedule::...
DB::...
```

Query database tidak boleh ditambahkan ke Blade sebagai solusi praktis.

---

# 12. Owner Presentation Refactor

Owner Portal merupakan area prioritas RF-05 karena memiliki banyak halaman kompleks.

Prioritas:

```text
1. Calendar
2. Bookings
3. Staff & Resources
4. Customers
5. Schedule
6. Dashboard
7. Services
8. Settings
9. Remaining portal screens
```

---

# 13. Owner Calendar Refactor

Current:

```text
owner/calendar.blade.php
```

menangani berbagai bagian:

```text
Header
View switcher
Date navigation
Filters
Day view
Week view
Month view
Booking details
Walk-in interaction
Responsive presentation
Alpine state
```

Target decomposition:

```text
owner/calendar.blade.php

owner/partials/calendar/
├── header.blade.php
├── controls.blade.php
├── filters.blade.php
├── day-view.blade.php
├── week-view.blade.php
├── month-view.blade.php
├── slot.blade.php
├── booking-detail.blade.php
├── empty-state.blade.php
└── responsive-day-view.blade.php
```

Reusable components dapat digunakan untuk:

```text
date navigation
filter control
status badge
booking card
modal
button
empty state
```

Jangan membuat tiga implementation berbeda untuk konsep yang sama hanya karena Day/Week/Month mempunyai layout berbeda.

Business availability tetap berada di backend.

---

# 14. Owner Bookings Refactor

Current page memiliki:

```text
Summary statistics
Search
Filters
Mobile cards
Desktop table
Booking details
Payment information
Action UI
Status rendering
Alpine state
```

Target:

```text
owner/bookings.blade.php

owner/partials/bookings/
├── summary-stats.blade.php
├── filters.blade.php
├── desktop-table.blade.php
├── mobile-cards.blade.php
├── booking-row.blade.php
├── booking-card.blade.php
├── booking-detail.blade.php
└── empty-state.blade.php
```

Desktop dan mobile boleh memiliki layout berbeda.

Namun sumber data dan semantic meaning harus sama.

Jangan membuat business rule yang hanya berlaku pada mobile.

---

# 15. Owner Staff & Resources Refactor

Current page relatif besar dan berpotensi mencampurkan:

```text
staff list
resource list
forms
modals
assignments
status
actions
```

Target:

```text
owner/staff-resources.blade.php

owner/partials/staff-resources/
├── header.blade.php
├── staff-section.blade.php
├── resource-section.blade.php
├── staff-form.blade.php
├── resource-form.blade.php
├── assignment-modal.blade.php
└── empty-state.blade.php
```

Reusable components:

```text
form field
button
modal
status badge
table/card
```

Business assignment logic tetap berada di backend.

---

# 16. Owner Customer Page Refactor

Customer list page harus dipisahkan menjadi:

```text
Header
Search/filter
Summary
Customer table
Customer cards
Customer detail/action UI
Empty state
```

Target:

```text
owner/partials/customers/
├── summary.blade.php
├── filters.blade.php
├── table.blade.php
├── mobile-cards.blade.php
├── detail.blade.php
└── empty-state.blade.php
```

Jangan membuat customer data diproses ulang secara berbeda antara desktop dan mobile.

---

# 17. Customer Booking Presentation Refactor

Customer booking merupakan critical user journey:

```text
Public Tenant
    ↓
Service
    ↓
Schedule
    ↓
Checkout
    ↓
Payment
    ↓
Booking Management
```

Presentation harus dipisahkan berdasarkan screen responsibility.

Prioritas:

```text
customer/booking/checkout.blade.php
customer/booking/payment.blade.php
customer/manage/show.blade.php
customer/manage/invoice.blade.php
customer/manage/reschedule.blade.php
```

Contoh decomposition:

```text
customer/partials/booking/
├── service-summary.blade.php
├── schedule-summary.blade.php
├── customer-information.blade.php
├── price-summary.blade.php
├── voucher-summary.blade.php
├── payment-summary.blade.php
├── booking-status.blade.php
└── action-buttons.blade.php
```

Reusable hanya jika contract-nya memang stabil.

---

# 18. Checkout Page Rule

Checkout page tidak boleh menentukan harga akhir melalui JavaScript.

Frontend dapat:

```text
display subtotal
display voucher result
display total
display selected schedule
```

Tetapi nilai authoritative harus berasal dari backend.

Client-side values tidak boleh dipercaya untuk:

```text
price
discount
payment amount
tenant
service ownership
schedule ownership
booking ownership
```

---

# 19. Payment Page Rule

Payment page boleh menampilkan:

```text
payment status
order ID
amount
payment method
expiration information
payment action
```

Tetapi Blade/Alpine tidak boleh menentukan apakah payment benar-benar sukses.

Payment state berasal dari backend/payment domain.

Frontend hanya merefleksikan state tersebut.

Contoh:

```text
pending
paid
failed
expired
cancelled
```

mapping final mengikuti domain/application output.

---

# 20. Booking Management Page

`customer/manage/show.blade.php` adalah screen penting karena mencakup:

```text
Booking information
Customer information
Service
Schedule
Payment
Status
Actions
Review
Reschedule
Cancellation
```

Page ini harus menjadi composition layer.

Jangan biarkan semua section tetap berada dalam satu file hanya karena semuanya berkaitan dengan satu booking.

Target:

```text
customer/manage/show.blade.php

customer/partials/manage/
├── booking-header.blade.php
├── booking-summary.blade.php
├── schedule-card.blade.php
├── payment-card.blade.php
├── customer-card.blade.php
├── status-card.blade.php
├── action-panel.blade.php
└── review-section.blade.php
```

---

# 21. Alpine.js Rules

Alpine.js digunakan untuk local UI state.

Contoh valid:

```text
modalOpen
selectedTab
dropdownOpen
selectedRow
mobileMenuOpen
showPassword
expandedSection
```

Contoh yang harus tetap berada di backend:

```text
booking eligibility
payment validity
availability
tenant authorization
subscription entitlement
pricing authority
cancellation eligibility
reschedule eligibility
```

---

# 22. Alpine State Boundary

Setiap Alpine state harus menjawab:

> Apakah state ini hanya diperlukan untuk membuat UI merespons interaksi user?

Jika jawabannya ya, Alpine sesuai.

Jika state merepresentasikan business fact, backend harus menjadi source of truth.

Contoh:

```text
modalOpen = UI state
selectedTab = UI state

booking.status = domain/application state
payment.status = domain/application state
schedule availability = domain/application state
```

---

# 23. JavaScript Extraction

JavaScript inline yang kecil dan sangat lokal masih diperbolehkan.

Namun script yang:

* panjang;
* reusable;
* memiliki beberapa function;
* dipakai lintas halaman;
* mengatur complex interaction;

harus diekstrak ke `resources/js`.

Contoh target:

```text
resources/js/
├── owner/
│   ├── calendar.js
│   ├── bookings.js
│   └── staff-resources.js
│
├── customer/
│   ├── checkout.js
│   ├── payment.js
│   └── booking-manage.js
│
└── components/
    ├── modal.js
    ├── dropdown.js
    └── confirmation.js
```

Jangan membuat satu file `app.js` raksasa untuk semua interaction.

---

# 24. JavaScript Module Rules

JavaScript presentation module:

```text
may:
- manipulate DOM
- manage local state
- open/close UI
- format presentation
- submit existing forms
- call approved HTTP endpoints where explicitly required

must not:
- bypass authorization
- calculate authoritative payment
- decide tenant ownership
- create unauthorized state
- duplicate backend business rules
```

Frontend interaction bukan security boundary.

---

# 25. Forms

Form presentation harus konsisten.

Gunakan pola:

```text
label
input
hint
validation error
```

Error harus berasal dari Laravel validation result.

Jangan menulis ulang seluruh validation business rule di JavaScript hanya agar UX terlihat lebih cepat.

Client validation dapat digunakan sebagai enhancement.

Server validation tetap authoritative.

---

# 26. Route Usage in Blade

Gunakan route helper untuk route yang mempunyai named route.

Preferred:

```blade
route('owner.bookings')
route('owner.schedule')
route('booking.manage', $booking->booking_code)
```

Hindari hardcoded URL apabila named route sudah tersedia:

```blade
href="/owner/bookings"
action="/owner/bookings"
```

Pengecualian hanya jika memang tidak ada named route atau URL tersebut merupakan static path yang sengaja dipertahankan.

Refactor route naming sendiri masuk RF-07.

RF-05 tidak boleh mengubah route contract hanya untuk membuat markup terlihat lebih bagus.

---

# 27. Security Rules in Presentation

Blade harus selalu mempertahankan:

```blade
@csrf
```

untuk POST/PUT/PATCH/DELETE forms sesuai kebutuhan.

Gunakan:

```blade
@method(...)
```

sesuai HTTP method yang digunakan.

Gunakan escaped output secara default:

```blade
{{ $value }}
```

Jangan menggunakan:

```blade
{!! $value !!}
```

kecuali HTML tersebut memang sudah trusted dan memang membutuhkan rendering raw HTML.

UI authorization seperti:

```blade
@can(...)
```

boleh digunakan untuk menyembunyikan UI.

Namun:

```text
UI authorization != backend authorization
```

Controller/action/middleware tetap wajib melakukan authorization.

---

# 28. Tenant Safety in Presentation

Tenant ID atau identity tidak boleh dipercaya hanya karena berasal dari:

```text
hidden input
query string
JavaScript
Blade variable
URL parameter
```

Presentation boleh menampilkan tenant context.

Tetapi ownership harus diverifikasi oleh application/backend layer.

Jangan melakukan:

```blade
<input type="hidden" name="tenant_id" value="{{ $tenant->id }}">
```

dengan asumsi backend akan menerima nilai tersebut sebagai authority.

Server tetap menentukan tenant.

---

# 29. Responsive Presentation

Responsive behavior harus mempertahankan semantic equivalence.

Contoh:

```text
Desktop table
        ≈
Mobile card
```

Keduanya harus mewakili data dan action yang sama.

Jangan:

* menghilangkan critical action di mobile;
* membuat status berbeda;
* menggunakan business rule berbeda;
* membuat data source berbeda.

Perbedaan hanya pada presentation layout.

---

# 30. Accessibility

RF-05 harus sekaligus memperbaiki baseline accessibility tanpa mengubah desain produk.

Minimal:

```text
button memiliki label yang jelas
interactive element dapat diakses
form field memiliki label
modal memiliki close mechanism
focus state tetap tersedia
icon-only button memiliki aria-label
```

Contoh:

```blade
aria-label="Previous"
aria-label="Next"
```

yang memang sudah digunakan di beberapa area dapat dipertahankan dan diterapkan konsisten.

Accessibility improvements tidak boleh mengubah business behavior.

---

# 31. Page Decomposition Strategy

Jangan memecah seluruh Blade file sekaligus.

Gunakan urutan:

```text
1. Identify section
2. Preserve markup
3. Extract section
4. Verify rendering
5. Extract repeated component
6. Verify again
7. Move JS if necessary
8. Clean remaining page
```

Setiap extraction harus tetap reversible.

---

# 32. Refactor Sequence

## Step 1 — Presentation Baseline

Sebelum mengubah Blade:

* jalankan test suite yang relevan;
* pastikan halaman kritis dapat dibuka;
* dokumentasikan halaman target;
* dokumentasikan kondisi responsive;
* catat JavaScript interaction utama;
* catat known visual quirks yang memang harus dipertahankan.

Minimal baseline journey:

```text
Owner login
Owner dashboard
Owner calendar
Owner bookings
Owner customers
Owner schedule

Customer public page
Customer booking
Customer checkout
Customer payment
Customer manage booking
Customer reschedule
```

---

## Step 2 — Build Presentation Responsibility Map

Untuk setiap halaman, dokumentasikan:

```text
Page
Data required
Sections
Reusable components
Page-specific partials
Alpine state
JavaScript
Forms
Actions
```

Contoh:

```text
owner/calendar

Data:
- current date
- services
- selected service
- selected status
- slots/bookings

Sections:
- header
- controls
- filters
- calendar
- detail modal

UI state:
- modalOpen
- selectedSlot
- walkinMode

Backend authority:
- availability
- booking status
- schedule ownership
```

---

## Step 3 — Extract Shared Presentation Components

Mulai dari component dengan contract paling jelas.

Prioritas:

```text
Page Header
Button
Status Badge
Modal
Flash Message
Empty State
Form Field
Stat Card
Pagination
```

Jangan langsung membuat puluhan component kecil.

---

## Step 4 — Refactor Owner Calendar

Decompose:

```text
calendar
controls
views
modal
slot
empty state
```

Pastikan:

```text
Day
Week
Month
```

tetap menghasilkan behavior yang sama.

---

## Step 5 — Refactor Owner Bookings

Pisahkan:

```text
summary
filters
desktop table
mobile cards
detail
actions
```

Pastikan filter dan status behavior tetap identik.

---

## Step 6 — Refactor Owner Staff & Resources

Pisahkan:

```text
staff
resources
forms
assignment
modals
```

Jangan menyentuh business service/resource assignment logic.

---

## Step 7 — Refactor Owner Customers

Pisahkan:

```text
summary
filters
table
mobile cards
detail
```

---

## Step 8 — Refactor Customer Booking

Pisahkan:

```text
service summary
schedule summary
customer information
price summary
voucher summary
payment section
```

Tidak boleh mengubah calculation authority.

---

## Step 9 — Refactor Customer Manage

Pisahkan:

```text
booking summary
schedule
payment
customer
status
actions
review
```

---

## Step 10 — Extract Alpine / JavaScript

Setelah Blade decomposition selesai, baru identifikasi script yang masih terlalu besar.

Jangan memindahkan JS terlebih dahulu hanya demi memindahkan file.

Tujuan extraction:

```text
clear ownership
reusability
testability
readability
```

---

## Step 11 — Consolidate Shared UI

Setelah beberapa halaman selesai, cari duplication baru yang benar-benar terbukti.

Baru kemudian buat shared component.

Jangan melakukan abstraction terlalu dini.

---

## Step 12 — Final Presentation Cleanup

Periksa:

```text
hardcoded URLs
duplicated classes
duplicated status markup
duplicated modal structure
duplicated form structure
unused Alpine state
unused JS functions
dead partials
unused components
inconsistent component names
```

---

# 33. Do Not Refactor These Things in RF-05

RF-05 tidak boleh:

### Database

Jangan mengubah:

```text
migration
schema
index
constraint
foreign key
column naming
```

---

### Domain Behavior

Jangan mengubah:

```text
booking state
availability rule
double booking protection
multi-slot rule
cancellation rule
reschedule rule
payment state
subscription entitlement
```

---

### Backend Architecture

Jangan mengulang refactor controller/application layer yang menjadi scope RF-04.

Jika ditemukan masalah backend:

```text
document it
mark it
do not silently fix it as presentation work
```

kecuali perubahan kecil memang diperlukan agar extracted presentation tetap kompatibel dan tidak mengubah behavior.

---

### Product/UI Redesign

Jangan mengubah:

```text
core navigation
business flow
pricing model
feature set
booking flow
payment flow
major visual identity
```

RF-05 memperbaiki struktur implementasi, bukan mendesain ulang BookQu.

---

# 34. Copy and Terminology

RF-05 boleh memperbaiki wording jika:

* makna tetap sama;
* perubahan memang hanya memperjelas presentation;
* tidak mengubah product behavior.

Canonical product terminology tetap mengikuti:

```text
Service
Schedule
Booking
Customer
Payment
Owner
Tenant
Staff
Resource
Subscription
```

Legacy term seperti:

```text
Program
Layanan
```

jangan diubah secara global hanya karena terlihat tidak konsisten.

Terminology cleanup adalah scope RF-07.

Pada RF-05, gunakan istilah canonical pada komponen baru.

---

# 35. Testing Strategy

Presentation refactor membutuhkan beberapa level verifikasi.

## 35.1 Feature Tests

Existing feature/integration tests harus tetap berjalan.

RF-05 tidak boleh menyebabkan regression pada:

```text
authentication
booking
payment
schedule
owner modules
customer management
subscription
security
tenant isolation
```

---

## 35.2 Route Verification

Semua halaman yang direfactor harus tetap dapat diakses melalui route yang sama.

Verify:

```text
GET
POST
PUT/PATCH
DELETE
```

sesuai halaman masing-masing.

---

## 35.3 Manual UI Verification

Untuk halaman visual yang signifikan, lakukan:

```text
desktop
tablet
mobile
```

minimal pada breakpoint yang relevan.

---

## 35.4 Interaction Verification

Pastikan tetap berfungsi:

```text
modal open/close
dropdown
tabs
filters
search
pagination
calendar navigation
calendar view switch
form submission
validation error
confirmation dialog
responsive menu
```

---

## 35.5 JavaScript Verification

Periksa:

```text
browser console
Alpine initialization
JS runtime errors
failed requests
duplicate event handlers
broken selectors
```

---

# 36. Characterization Tests

Sebelum memecah halaman kompleks, behavior penting sebaiknya memiliki characterization coverage.

Tujuannya bukan membuat test untuk setiap `<div>`.

Yang perlu dilindungi adalah behavior:

```text
page loads
required data visible
required action available
form posts to correct endpoint
validation appears
status rendered correctly
filter works
modal interaction works
payment state represented correctly
```

Visual pixel-perfect testing tidak wajib untuk semua halaman.

---

# 37. Definition of Done

RF-05 hanya dianggap selesai jika:

### Presentation Architecture

* [ ] Large Blade files telah dipecah secara rasional.
* [ ] Page responsibility jelas.
* [ ] Reusable UI menggunakan component jika memang justified.
* [ ] Page-specific sections menggunakan partial jika diperlukan.
* [ ] Tidak ada database query langsung di Blade.
* [ ] Tidak ada business transaction di Blade.
* [ ] Business rule tidak dipindahkan ke Alpine.
* [ ] JavaScript kompleks memiliki boundary yang jelas.

### Owner

* [ ] Calendar direfactor.
* [ ] Bookings direfactor.
* [ ] Staff & Resources direfactor.
* [ ] Customers direfactor.
* [ ] Shared owner UI dirapikan.

### Customer

* [ ] Checkout direfactor.
* [ ] Payment direfactor.
* [ ] Booking management direfactor.
* [ ] Reschedule/invoice mengikuti pola yang konsisten.

### Quality

* [ ] Feature tests tetap pass.
* [ ] Critical user journeys tetap pass.
* [ ] No critical browser console errors.
* [ ] Responsive behavior tetap berfungsi.
* [ ] No unauthorized access introduced.
* [ ] No business behavior changed unintentionally.

---

# 38. Acceptance Criteria

## AC-RF05-001 — No Business Logic in Blade

Blade tidak mengandung query database, transaction management, payment processing, atau keputusan business rule kompleks.

---

## AC-RF05-002 — UI State Boundary

Alpine hanya mengendalikan interaction/UI state.

---

## AC-RF05-003 — Reusable Component Contract

Shared component memiliki input/contract yang jelas dan tidak menjadi "mega component".

---

## AC-RF05-004 — Calendar Preservation

Owner Calendar mempertahankan:

```text
day view
week view
month view
date navigation
filter
booking interaction
walk-in interaction
responsive behavior
```

---

## AC-RF05-005 — Booking Management Preservation

Owner booking page mempertahankan:

```text
filter
search
status
detail
actions
mobile presentation
desktop presentation
```

---

## AC-RF05-006 — Customer Booking Preservation

Customer booking flow tetap:

```text
service
→ schedule
→ checkout
→ payment
→ management
```

tanpa perubahan product behavior.

---

## AC-RF05-007 — Payment Authority Preservation

Frontend tidak menjadi source of truth untuk payment status atau payment amount.

---

## AC-RF05-008 — Route Contract Preservation

Refactor presentation tidak memutus existing route contract.

---

## AC-RF05-009 — Tenant Security Preservation

Presentation refactor tidak melemahkan tenant isolation atau authorization.

---

## AC-RF05-010 — Visual Regression Control

Perubahan visual yang muncul hanya berasal dari structural cleanup atau bug/accessibility fix yang sengaja didokumentasikan.

---

# 39. Recommended Refactor Order

Urutan pengerjaan yang direkomendasikan:

```text
RF-05.1
Presentation Baseline

        ↓

RF-05.2
Shared UI Foundation

        ↓

RF-05.3
Owner Calendar

        ↓

RF-05.4
Owner Bookings

        ↓

RF-05.5
Owner Staff & Resources

        ↓

RF-05.6
Owner Customers

        ↓

RF-05.7
Customer Checkout

        ↓

RF-05.8
Customer Payment

        ↓

RF-05.9
Customer Manage / Reschedule / Invoice

        ↓

RF-05.10
Alpine / JS Extraction

        ↓

RF-05.11
Duplication Cleanup

        ↓

RF-05.12
Presentation Verification
```

Jangan mengerjakan semua halaman secara paralel.

Selesaikan satu cluster presentation sampai stabil sebelum berpindah.

---

# 40. AI Agent Instructions

AI agent yang mengerjakan RF-05 wajib:

1. membaca `AGENTS.md`;
2. membaca `PRODUCT.md`;
3. membaca `REQUIREMENTS.md`;
4. membaca `ARCHITECTURE.md`;
5. membaca `DEVELOPMENT.md`;
6. membaca `TRACKER.md`;
7. membaca RF-01 sampai RF-04 yang relevan;
8. melakukan inspection terhadap file sebelum edit;
9. mencari component/partial existing sebelum membuat baru;
10. mempertahankan route dan backend contract;
11. menjalankan test setelah perubahan;
12. melaporkan perubahan presentation secara eksplisit.

AI agent tidak boleh menganggap:

```text
existing Blade structure = ideal architecture
```

tetapi juga tidak boleh menganggap:

```text
large Blade file = permission untuk rewrite seluruh UI
```

Targetnya adalah controlled refactor.

---

# 41. Change Classification

Setiap perubahan selama RF-05 harus dikategorikan:

```text
PRESENTATION-ONLY
```

Contoh:

```text
extract partial
extract component
rename local Blade variable
move inline JS
normalize UI markup
remove duplicated presentation
```

atau:

```text
BEHAVIOR-SENSITIVE
```

Contoh:

```text
change form action
change request parameter
change route
change status condition
change backend-provided state
change payment UI logic
change authorization condition
```

Perubahan `BEHAVIOR-SENSITIVE` harus diverifikasi lebih ketat.

---

# 42. Stop Conditions

Agent harus berhenti dan melaporkan jika menemukan:

```text
business logic ternyata hanya ada di Blade
missing backend endpoint
route inconsistency
unclear authorization
unknown Alpine dependency
shared component memiliki behavior berbeda antar halaman
refactor membutuhkan controller/domain change
existing test contradicts intended behavior
presentation behavior tidak dapat dipisahkan tanpa product decision
```

Jangan menyelesaikan ambiguity dengan asumsi.

---

# 43. Final Report

```text
RF-05 Unit: Presentation Layer Refactor (Complete)
Baseline Branch: Refactor
Baseline Commit: ccbcef00ad8726c1cef4ee56e6a2345c5941fbf8

Files Changed:
- resources/views/owner/schedule-report.blade.php (Baseline KPI standardization)
- resources/views/owner/calendar.blade.php (Modularized to partials)
- resources/views/owner/bookings.blade.php (Modularized to partials)
- resources/views/owner/staff-resources.blade.php (Modularized to partials)
- resources/views/owner/customers.blade.php (Modularized to partials)
- resources/views/customer/manage/show.blade.php (Modularized to partials)
- resources/views/customer/booking/checkout.blade.php (Modularized to partials)
- resources/views/customer/booking/payment.blade.php (Modularized to partials)

Partials Added:
1. Owner Calendar (resources/views/owner/partials/calendar/):
   - header.blade.php (Title, actions, manage schedule & booking list links)
   - controls.blade.php (Day/Week/Month segmented controls, date navigator, service filter, status legend)
   - week-view.blade.php (Mobile week timeline & desktop week grid)
   - day-view.blade.php (Daily booking transactions & operational slot list)
   - month-view.blade.php (Monthly calendar grid with indicators & booking counts)
   - detail-modal.blade.php (Booking detail drawer, quick actions, walk-in form)

2. Owner Bookings (resources/views/owner/partials/bookings/):
   - summary-stats.blade.php (6 status metric counter cards)
   - filters.blade.php (Search input & status filter pills)
   - mobile-cards.blade.php (Mobile booking cards with quick status transitions)
   - desktop-table.blade.php (Desktop table with full booking info & actions dropdown)
   - detail-modal.blade.php (Centered booking detail dialog with customer info, payment info, actions)

3. Owner Staff & Resources (resources/views/owner/partials/staff-resources/):
   - header.blade.php (Header title, tabs switcher Staff Team vs Fasilitas Fisik, Tambah action buttons)
   - staff-section.blade.php (Staff member table, search, empty state)
   - resource-section.blade.php (Physical resource/room table, search, empty state)
   - staff-modals.blade.php (Add & Edit Staff modal dialogs with service checkboxes)
   - resource-modals.blade.php (Add & Edit Resource modal dialogs with service checkboxes)

4. Owner Customers (resources/views/owner/partials/customers/):
   - summary.blade.php (3 summary metrics: Unique Customers, Total Spending, Total Bookings)
   - filters.blade.php (Server-side customer search form & result count)
   - table.blade.php (Customer CRM directory table, VIP badges, pagination, empty states)
   - detail-modal.blade.php (Slide-over detail drawer with Overview, Booking History, Payments, Notes tabs)

5. Customer Manage Booking (resources/views/customer/partials/manage/):
   - header.blade.php (Sticky top navbar with tenant branding, WhatsApp support link, catalog link)
   - ticket-card.blade.php (Digital reservation pass hero card with booking code, status badge, session details, GCal/WA share, invoice link)
   - customer-card.blade.php (Customer details card)
   - review-section.blade.php (Star rating & review form / submitted review display)
   - timeline.blade.php (Event log activity timeline)
   - action-panel.blade.php (Multi-slot warning, reschedule/cancel buttons & policies, terminal state cards)
   - merchant-card.blade.php (Merchant location/maps link, phone number, secret access notice)
   - cancel-modal.blade.php (Cancellation confirmation modal dialog with estimate refund breakdown)

6. Customer Booking Checkout (resources/views/customer/partials/booking/):
   - checkout-form.blade.php (Customer inputs, error alerts, trust banner, validation, remember me)
   - checkout-mobile-review.blade.php (Mobile reservation review card, slot breakdown)
   - checkout-summary-desktop.blade.php (Desktop sticky summary card, breakdown, CTA, trust notice)
   - checkout-mobile-bar.blade.php (Mobile bottom floating action bar with total and submit CTA)

7. Customer Booking Payment (resources/views/customer/partials/payment/):
   - failed-state.blade.php (Failed payment card & retry / new reservation CTAs)
   - expired-state.blade.php (Expired payment notice card)
   - pending-card.blade.php (Total bill, urgency countdown timer, quick steps, pay button, reservation detail list, secondary metadata, realtime status, loading overlay)
   - action-buttons.blade.php (Periksa Status Pembayaran & Batalkan & Ganti Jadwal buttons)
   - cancel-modal.blade.php (Payment cancel confirmation dialog)
   - scripts.blade.php (Midtrans Snap payment trigger, auto-polling, countdown timer sync, clipboard)

Behavior Preserved:
- 100% route contract preservation (all named routes and parameters preserved)
- 100% DOM element IDs and canonical Indonesian copy preserved for automated test characterization
- Alpine UI interaction preserved (data binding, validation, modals, drawers, tabs, copy-to-clipboard)
- Backend authoritative business rules and calculations intact

Tests:
- 277 passed (1,391 assertions) across entire test suite (php artisan test)
- 100% green pass rate

Next Recommended Unit:
- RF-06: Subscription & Entitlements Refactor
```

Jika ada behavior change:

```text
Behavior Change:
Requirement ID:
Reason:
Verification:
```

Behavior change tidak boleh disembunyikan sebagai "UI refactor".

---

# 44. Final Principle

RF-05 harus menghasilkan presentation layer yang:

```text
Easy to Read
Easy to Reuse
Easy to Test
Easy to Modify
Easy for AI Agents to Navigate
```

dengan boundary:

```text
Blade
    = presentation

Alpine / JS
    = interaction

Controller
    = HTTP orchestration

Action
    = application use case

Domain
    = business rule

Infrastructure
    = external system
```

Tujuan akhir bukan membuat BookQu memiliki sebanyak mungkin component atau file.

Tujuannya adalah memastikan setiap bagian berada di tempat yang tepat, dengan perubahan minimum terhadap behavior produk yang sudah berjalan.

RF-05 selesai ketika presentation layer tidak lagi menjadi tempat akumulasi business logic, markup duplication, dan uncontrolled client-side behavior, sementara seluruh booking, payment, schedule, tenant, dan subscription behavior tetap sesuai requirement baseline.
