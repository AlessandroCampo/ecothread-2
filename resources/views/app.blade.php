<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
         <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="viewport" content="width=device-width, initial-scale=1">
      @routes


        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <title inertia>Ecothread</title>

     <link rel="icon" type="image/png" href="/logo-mobile.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/logo-mobile.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/logo-mobile.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/logo-mobile.png">
        <link rel="shortcut icon" href="/logo-mobile.png">

        <!-- Per Phantom e social preview -->
        <meta property="og:image" content="{{ url('/logo-mobile.png') }}">
        <meta name="msapplication-TileImage" content="/logo-mobile.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
