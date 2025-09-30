<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', config('app.name'))</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">


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
