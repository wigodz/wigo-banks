<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

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

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: #f3f4f6;
            }

            html.dark {
                background-color: #111827;
            }
        </style>

        <link rel="icon" type="image/x-icon" href="/favicon.ico?v={{ filemtime(public_path('favicon.ico')) }}">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png?v={{ filemtime(public_path('favicon-32x32.png')) }}">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png?v={{ filemtime(public_path('favicon-16x16.png')) }}">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png?v={{ filemtime(public_path('apple-touch-icon.png')) }}">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Wigo Banks') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
