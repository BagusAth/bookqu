{{-- Trial Banner Component --}}
@props([
    'sisahari' => 0,
])

<div class="flex flex-col gap-3 rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between" id="trial-banner">
    <div class="flex items-center gap-3">
        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-500 text-white shadow-sm">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
        </div>
        <p class="text-sm font-medium text-blue-800">
            <span class="font-bold">{{ $sisahari }}-day Free Trial</span> remaining. Upgrade now to keep all premium features.
        </p>
    </div>
    <a href="#" class="inline-flex shrink-0 items-center justify-center rounded-lg bg-bq-primary px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-bq-primary/25 transition-all duration-200 hover:bg-bq-primary-hover hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0" id="btn-upgrade-plan">
        Upgrade Plan
    </a>
</div>
