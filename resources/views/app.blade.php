<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- Primary Meta Tags -->
    <title inertia>Aninka Fashion — Daster Chibi Rayon Twill & One Set Monica</title>
    <meta name="title" content="Aninka Fashion — Daster Chibi Rayon Twill & One Set Monica" />
    <meta name="description" content="Temukan koleksi Daster Chibi Jumbo, One Set Twill Monica, dan fashion wanita trendy hanya di Aninka Fashion. Bahan nyaman, cocok untuk harian." />

    <!-- SEO Keywords -->
    <meta name="keywords" content="Aninka Fashion, daster chibi, rayon twill, daster jumbo, twill monica, baju tidur wanita, fashion muslimah, daster adem" />
    <meta name="author" content="Aninka Fashion" />
    <meta name="robots" content="index, follow" />

    <!-- Theme & App Settings -->
    <meta name="theme-color" content="#4CAF50" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    <meta name="mobile-web-app-capable" content="yes" />

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json?v=2" />
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png" />
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192x192.png" />
    <link rel="icon" type="image/svg+xml" href="/icons/icon-192x192.svg" />

    <!-- Font & Preconnect -->
    <link rel="preconnect" href="https://fonts.bunny.net" />
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Theme Handling Script -->
    <script>
      (function () {
        const appearance = '{{ $appearance ?? "system" }}';
        if (appearance === 'system') {
          const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
          if (prefersDark) {
            document.documentElement.classList.add('dark');
          }
        }
      })();
    </script>

    <!-- Quick Background Fallback -->
    <style>
      html {
        background-color: oklch(1 0 0);
      }
      html.dark {
        background-color: oklch(0.145 0 0);
      }
    </style>

    @routes
    @vite(['resources/js/app.ts'])
    @inertiaHead
  </head>

  <body class="font-sans antialiased">
    @inertia
  </body>
</html>
