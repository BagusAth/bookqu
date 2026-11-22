@props(['id', 'title', 'description' => null, 'reopenFor' => null])

<x-owner.dialog :id="$id" :title="$title" :description="$description" :reopen-for="$reopenFor" sheet {{ $attributes }}>
    {{ $slot }}
    @isset($footer)
        <x-slot:footer>{{ $footer }}</x-slot:footer>
    @endisset
</x-owner.dialog>
