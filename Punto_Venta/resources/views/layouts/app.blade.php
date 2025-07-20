<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
       <link rel="stylesheet" href="{{ asset('build/assets/app-DjvMwtXK.css') }}">
<script type="module" src="{{ asset('build/assets/app-DaBYqt0m.js') }}"></script>
  <script src="//unpkg.com/alpinejs" defer></script>S
    </head>
   <body class="font-sans antialiased bg-gray-100 flex">

    {{-- Sidebar --}}
    <x-sidebar />

    {{-- Contenido principal --}}
    <div class="flex-1 min-h-screen ml-64">
        @include('layouts.navigation')

        <!-- Encabezado de la página -->
        @isset($header)
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <!-- Contenido de la página -->
        <main class="p-4">
            {{ $slot }}
        </main>
    </div>
    
</body>

</html>
