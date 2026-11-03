<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Akses reservasi BookQu</title>
</head>
<body>
    <p id="message">Gunakan tautan akses dari konfirmasi reservasi Anda.</p>
    <script>
        (() => {
            let credential = window.location.hash.slice(1);
            window.history.replaceState(null, '', window.location.pathname);
            if (!credential) return;
            fetch(@js(route('customer.access.store', [], false)), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token())},
                body: JSON.stringify({credential})
            }).then(response => {
                credential = '';
                if (!response.ok) throw new Error('unavailable');
                return response.json();
            }).then(data => window.location.replace(data.redirect)).catch(() => {
                credential = '';
                document.getElementById('message').textContent = 'Tautan tidak tersedia atau telah kedaluwarsa. Hubungi pengelola reservasi.';
            });
        })();
    </script>
</body>
</html>
