<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="BookQu Owner Auth - Login dan Registrasi">

    <title>@yield('title', 'Owner Access') — BookQu</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-bq-background font-sans text-bq-text antialiased">
    <div class="relative min-h-screen overflow-hidden">
        <div class="pointer-events-none absolute -left-32 top-0 h-64 w-64 rounded-full bg-bq-primary/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -right-40 bottom-0 h-72 w-72 rounded-full bg-violet-400/10 blur-3xl"></div>

        <div class="mx-auto flex min-h-screen w-full max-w-6xl items-center justify-center px-4 py-10">
            @yield('content')
        </div>
    </div>
</body>
</html>
