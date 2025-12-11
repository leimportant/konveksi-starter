<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <title inertia>Aninka Fashion — Platform E-Commerce</title>
  <meta name="description"
    content="Temukan koleksi Daster Chibi Jumbo, One Set Twill Monica, dan fashion wanita trendy hanya di Aninka Fashion." />

  <meta name="keywords"
    content="Aninka Fashion, daster chibi, rayon twill, daster jumbo, twill monica, baju tidur wanita, fashion muslimah, daster adem" />
  <meta name="author" content="Aninka Fashion" />
  <meta name="robots" content="index, follow" />
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <meta name="theme-color" content="#4CAF50" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
  <meta name="mobile-web-app-capable" content="yes" />

  <!-- PWA -->
  <link rel="manifest" href="/manifest.json?v=2" />
  <link rel="icon" type="image/png" sizes="192x192" href="/images/icons/icon-192x192.png">
  <link rel="apple-touch-icon" href="/icons/icon-192x192.png" />

  <link rel="preconnect" href="https://fonts.bunny.net" />
  <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

  <style>
    html {
      font-size: clamp(13px, 1.2vw + 0.3rem, 15px);
      /* Minimum di layar kecil 14px — maksimum 16px di layar besar */
    }

    body {
      font-size: 0.90rem;
      /* default nyaman untuk HP */
      line-height: 1.55;
      /* biar tulisan enak dibaca */
      -webkit-font-smoothing: antialiased;
    }

    * {
      font-size: inherit;
    }

    html {
      background-color: oklch(1 0 0);
    }

    html.dark {
      background-color: oklch(0.145 0 0);
    }
  </style>

  {{-- Ziggy routes untuk helper "route()" di Vue --}}


  {{-- Load app.ts via Vite --}}
  @vite(['resources/js/app.ts'])
  @inertiaHead
</head>

<body class="font-sans antialiased">
  @inertia
  <div id="radix-vue-teleports"></div>
</body>

</html>