{{ strtoupper($level) }} — {{ $summary }}

@foreach ($details as $label => $value)
{{ $label }}: {{ $value }}
@endforeach

Detail lengkap ada di storage/logs/laravel.log di server. Pesan yang sama tidak dikirim lagi selama 15 menit.
