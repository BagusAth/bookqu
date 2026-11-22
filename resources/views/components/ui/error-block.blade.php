@props(['title', 'description' => null])

<x-ui.banner :title="$title" :description="$description" tone="danger" {{ $attributes }}>
    {{ $slot }}
    @isset($action)
        <div class="mt-2 flex flex-wrap gap-2">{{ $action }}</div>
    @endisset
</x-ui.banner>
