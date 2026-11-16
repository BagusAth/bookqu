@if ($tracking['googleAnalytics'])
    <script async referrerpolicy="no-referrer" src="https://www.googletagmanager.com/gtag/js?id={{ $tracking['googleAnalytics'] }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { window.dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', @js($tracking['googleAnalytics']), { allow_google_signals: false });
    </script>
@endif
@if ($tracking['metaPixel'])
    <script>
        if (!window.fbq) {
            window.fbq = function () {
                if (window.fbq.callMethod) {
                    window.fbq.callMethod.apply(window.fbq, arguments);
                } else {
                    window.fbq.queue.push(arguments);
                }
            };
            window._fbq = window.fbq;
            window.fbq.push = window.fbq;
            window.fbq.loaded = true;
            window.fbq.version = '2.0';
            window.fbq.queue = [];
            const trackingScript = document.createElement('script');
            trackingScript.async = true;
            trackingScript.referrerPolicy = 'no-referrer';
            trackingScript.src = 'https://connect.facebook.net/en_US/fbevents.js';
            document.head.append(trackingScript);
        }
        window.fbq('init', @js($tracking['metaPixel']));
        window.fbq('track', 'PageView');
    </script>
@endif
