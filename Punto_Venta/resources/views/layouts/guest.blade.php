<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Animate.css (si la usas para animaciones) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Vite -->
    <link rel="stylesheet" href="{{ asset('build/assets/app-CCcw_M8Q.css') }}">
<script type="module" src="{{ asset('build/assets/app-DNxiirP_.js') }}"></script>
</head>
<body class="font-sans antialiased bg-gray-100">
    {{ $slot }}
</body>
</html>
