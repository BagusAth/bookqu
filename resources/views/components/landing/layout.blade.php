@props([
    'title' => 'BookQu - Kelola Booking Bisnis Anda',
    // Optional SEO metadata; pages that pass nothing render exactly as before.
    'description' => null,
    'canonical' => null,
    'ogImage' => null,
])
<!DOCTYPE html>
<html lang="id" class="scroll-smooth scroll-pt-20">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#4F46E5">
    <title>{{ $title }}</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif
    @if ($canonical)
        <link rel="canonical" href="{{ $canonical }}">
        <meta name="robots" content="index, follow">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="BookQu">
        <meta property="og:locale" content="id_ID">
        <meta property="og:url" content="{{ $canonical }}">
        <meta property="og:title" content="{{ $title }}">
        @if ($description)
            <meta property="og:description" content="{{ $description }}">
        @endif
        <meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
        <meta name="twitter:title" content="{{ $title }}">
        @if ($description)
            <meta name="twitter:description" content="{{ $description }}">
        @endif
        @if ($ogImage)
            <meta property="og:image" content="{{ $ogImage }}">
            <meta name="twitter:image" content="{{ $ogImage }}">
        @endif
    @endif

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('logo_icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{ $head ?? '' }}

    <style>
        @keyframes float-slow {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        @keyframes fade-up {
            0% { opacity: 0; transform: translateY(16px); }
            100% { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased overflow-x-clip" style="font-family: 'Plus Jakarta Sans', sans-serif;">
    <div class="relative">
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -top-24 right-0 h-80 w-80 rounded-full bg-indigo-200/40 blur-3xl"></div>
            <div class="absolute top-64 -left-16 h-72 w-72 rounded-full bg-indigo-100/40 blur-3xl"></div>
        </div>

        {{ $slot }}
    </div>
</body>
</html>
