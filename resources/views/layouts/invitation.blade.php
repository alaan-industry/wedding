@props([
    'title',
    'description',
    'image' => null,
    'imageAlt' => null,
    'siteName' => null,
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
        @if ($siteName)
            <meta property="og:site_name" content="{{ $siteName }}" />
        @endif
        <meta property="og:title" content="{{ $title }}" />
        <meta property="og:description" content="{{ $description }}" />
        <meta property="og:url" content="{{ url()->current() }}" />
        @if ($image)
            <meta property="og:image" content="{{ $image['url'] }}" />
            <meta property="og:image:secure_url" content="{{ $image['url'] }}" />
            <meta property="og:image:type" content="{{ $image['type'] }}" />
            <meta property="og:image:width" content="{{ $image['width'] }}" />
            <meta property="og:image:height" content="{{ $image['height'] }}" />
            <meta name="twitter:image" content="{{ $image['url'] }}" />
            @if ($imageAlt)
                <meta property="og:image:alt" content="{{ $imageAlt }}" />
                <meta name="twitter:image:alt" content="{{ $imageAlt }}" />
            @endif
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
