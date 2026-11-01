<ul {{ $attributes->class(['space-y-2.5 text-sm']) }} data-plan-features="{{ $key }}">
    @foreach ($features as $feature)
        <li class="flex items-start gap-2.5">
            <svg class="h-4 w-4 shrink-0 text-emerald-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            <span data-feature-label>{{ $feature }}</span>
        </li>
    @endforeach
</ul>
