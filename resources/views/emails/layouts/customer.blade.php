<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ $businessName }}</title>
</head>
<body style="margin:0;padding:0;background:#F5F4F9;color:#1D1638;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" role="presentation" cellpadding="0" cellspacing="0" bgcolor="#F5F4F9">
    <tr><td align="center" style="padding:16px 8px;">
        <!--[if mso]><table width="600" role="presentation" cellpadding="0" cellspacing="0"><tr><td><![endif]-->
        <table width="100%" role="presentation" cellpadding="0" cellspacing="0" bgcolor="#FFFFFF"
            style="width:100%;max-width:600px;border:1px solid #E3E0EE;">
            <tr><td bgcolor="{{ $brandColor }}" style="padding:20px;color:{{ $brandText }};font-size:16px;line-height:24px;">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="" width="44" height="44" style="display:block;margin:0 0 10px;">
                @endif
                {{ $businessName }}
            </td></tr>
            <tr><td style="padding:24px 20px;">
                <h1 style="margin:0 0 12px;font-size:24px;line-height:30px;">@yield('title')</h1>
                <p style="margin:0 0 16px;font-size:15px;line-height:22px;">Halo, {{ $customerName }}.</p>
                <p style="margin:0 0 16px;font-size:15px;line-height:22px;">@yield('message')</p>

                @if ($reference)
                    <p style="margin:0 0 12px;font-size:14px;line-height:20px;">{{ $referenceLabel }} <strong>{{ $reference }}</strong></p>
                @endif
                @hasSection('previous')
                    @yield('previous')
                @endif

                <table width="100%" role="presentation" cellpadding="0" cellspacing="0" style="font-size:14px;line-height:21px;">
                    @foreach ($sessions as $session)
                        <tr><td style="padding:10px 0;border-top:1px solid #E3E0EE;">
                            <strong>{{ $session['service'] }}</strong><br>
                            {{ $session['date'] }}<br>
                            {{ $session['time'] }}
                        </td></tr>
                    @endforeach
                    <tr><td style="padding:10px 0;border-top:1px solid #E3E0EE;">
                        Total <strong>{{ $totalLabel }}</strong>
                        @if ($paymentMethod)
                            <br>Metode pembayaran {{ $paymentMethod }}
                        @endif
                    </td></tr>
                </table>

                @yield('extra')
                @if ($address !== '')
                    <p style="margin:12px 0;font-size:14px;line-height:21px;">{{ $address }}<br>
                        <a href="{{ $mapUrl }}" style="color:#382186;text-decoration:underline;">Petunjuk arah</a>
                    </p>
                @endif

                @if ($actionUrl)
                    <table role="presentation" cellpadding="0" cellspacing="0" style="margin-top:16px;">
                        <tr><td bgcolor="{{ $brandColor }}" style="padding:12px 16px;">
                            <a href="{{ $actionUrl }}" style="display:inline-block;color:{{ $brandText }};font-size:15px;line-height:22px;font-weight:bold;text-decoration:underline;">{{ $actionLabel }}</a>
                        </td></tr>
                    </table>
                @endif
            </td></tr>
            <tr><td bgcolor="#F5F4F9" style="padding:16px 20px;border-top:1px solid #E3E0EE;color:#625E75;font-size:13px;line-height:19px;">
                Dikirim atas nama {{ $businessName }} · Didukung oleh BookQu
            </td></tr>
        </table>
        <!--[if mso]></td></tr></table><![endif]-->
    </td></tr>
</table>
</body>
</html>
