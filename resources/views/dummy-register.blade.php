<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dummy Registration - BookQu</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f6f7fb;
            color: #1f2937;
            margin: 0;
            padding: 40px 16px;
        }
        .card {
            max-width: 560px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 10px;
            padding: 24px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }
        h1 {
            margin: 0 0 8px;
            font-size: 22px;
        }
        p {
            margin: 0 0 16px;
            color: #4b5563;
        }
        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 6px;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
        }
        input[type="text"]:focus {
            border-color: #111827;
        }
        .preview {
            margin-top: 10px;
            padding: 10px 12px;
            background: #f9fafb;
            border: 1px dashed #d1d5db;
            border-radius: 8px;
            font-size: 14px;
        }
        .error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        button {
            margin-top: 16px;
            width: 100%;
            padding: 12px;
            background: #111827;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        .backlink {
            display: inline-block;
            margin-top: 16px;
            font-size: 14px;
            color: #111827;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Dummy Registration</h1>
        <p>Modul ini hanya untuk mengetes generate URL dari nama bisnis.</p>

        @if ($errors->any())
            <div class="error">
                {{ $errors->first('namabisnis') }}
            </div>
        @endif

        <form method="POST" action="{{ route('dummy-register.process') }}">
            @csrf
            <label for="namabisnis">Nama Bisnis</label>
            <input
                type="text"
                id="namabisnis"
                name="namabisnis"
                value="{{ old('namabisnis') }}"
                placeholder="Contoh: Badminton Jaya"
                required
            >
            <div class="preview">
                Preview URL: <strong>www.bookqu.com/<span id="preview-slug">-</span></strong>
            </div>
            <button type="submit">Generate URL</button>
        </form>

        <a class="backlink" href="/">Kembali ke Home</a>
    </div>

    <script>
        (function () {
            var input = document.getElementById('namabisnis');
            var preview = document.getElementById('preview-slug');

            function toSlug(value) {
                return value
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }

            function updatePreview() {
                var slug = toSlug(input.value);
                preview.textContent = slug || '-';
            }

            input.addEventListener('input', updatePreview);
            updatePreview();
        })();
    </script>
</body>
</html>
