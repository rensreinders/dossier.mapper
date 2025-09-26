<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', config('app.name'))</title>

    {{-- Optionele extra meta van child views --}}
    @yield('meta')

    {{-- Bootstrap CSS (alleen CSS, via CDN) --}}
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    />

</head>
<body class="bg-light">
{{-- Optionele globale flash messages --}}
@if (session('status'))
    <div class="container mt-3">
        <div class="alert alert-info" role="alert">
            {{ session('status') }}
        </div>
    </div>
@endif

{{-- Hoofdinhoud --}}
<main class="container-fluid">
    @yield('content')
</main>

</body>
</html>
