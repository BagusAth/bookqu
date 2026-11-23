<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Undangan tim BookQu</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-50 p-6 text-slate-900">
<main class="mx-auto max-w-md space-y-4 rounded-xl border bg-white p-6">
    <h1 class="text-xl font-bold">Undangan tim BookQu</h1>
    @if ($invite)
        <p>Undangan untuk {{ $invite->email }}.</p>
        @foreach ($errors->all() as $error)
            <p role="alert" class="text-red-700">{{ $error }}</p>
        @endforeach
        @guest
            <form method="POST" action="{{ route('team-invitation.login') }}">
                @csrf
                <button type="submit" class="text-indigo-700 underline">Masuk dengan akun yang sudah ada</button>
            </form>
        @endguest
        <form method="POST" action="{{ route('team-invitation.accept') }}" class="space-y-3">
            @csrf
            @guest
                <h2 class="font-semibold">Buat akun dengan email undangan</h2>
                <label class="block">Nama lengkap <input name="name" autocomplete="name" value="{{ old('name') }}" class="block w-full rounded border p-2" required maxlength="100"></label>
                <label class="block">Nomor HP <input type="tel" name="nomorhp" autocomplete="tel" value="{{ old('nomorhp') }}" class="block w-full rounded border p-2" required maxlength="20"></label>
                <label class="block">Kata sandi <input type="password" name="password" autocomplete="new-password" class="block w-full rounded border p-2" required></label>
                <label class="block">Ulangi kata sandi <input type="password" name="password_confirmation" autocomplete="new-password" class="block w-full rounded border p-2" required></label>
            @endguest
            <button class="rounded bg-indigo-700 px-4 py-2 font-semibold text-white">Terima undangan</button>
        </form>
    @else
        <p>Tautan undangan tidak tersedia. Buka kembali email undangan atau hubungi Pemilik.</p>
    @endif
    <form id="invitation-exchange" method="POST" action="{{ route('team-invitation.exchange') }}" hidden>
        @csrf
        <input type="hidden" name="credential">
    </form>
    <script>
        const credential = window.location.hash.slice(1);
        if (/^[a-f0-9]{32}\.[a-f0-9]{64}$/.test(credential)) {
            history.replaceState(null, '', window.location.pathname);
            const form = document.getElementById('invitation-exchange');
            form.elements.namedItem('credential').value = credential;
            form.submit();
        }
    </script>
</main>
</body>
</html>
