@props([
    'title',
    'description',
    'image' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="theme-color" content="#fbf8f2" />

        <title>{{ $title }}</title>
        <meta name="description" content="{{ $description }}" />

        <meta property="og:type" content="website" />
        <meta property="og:title" content="{{ $title }}" />
        <meta property="og:description" content="{{ $description }}" />
        <meta property="og:url" content="{{ url()->current() }}" />
        @if ($image)
            <meta property="og:image" content="{{ $image }}" />
            <meta name="twitter:image" content="{{ $image }}" />
        @endif
        <meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}" />

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="paper-texture min-h-screen font-serif [--font-sans:var(--font-label)] text-ink antialiased selection:bg-blush/50">
        {{ $slot }}
    </body>
</html>
