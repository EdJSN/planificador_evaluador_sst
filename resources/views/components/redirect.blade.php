<!doctype html>
<html lang="en">

<head>
    <title>Planificador y evaluador SST</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700,800,900" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
</head>

<body>
    {{-- Navbar --}}
    <nav class="navbar navbar-light bg-transparent navbar-custom mb-2 px-5 py-2">
        <div class="container-fluid">
                <img src="{{ asset('images/logoAzloSide.png') }}" alt="Logo" height="40">
        </div>
    </nav>

    {{-- Contenedor con imagen y filtro transparente --}}
    <div class="container-principal bg-filtro-azlo">
        <div class="bg-image">
            <div class="container-center2">
                {{-- Page content --}}
                {{ $slot }}
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>

    @vite(['resources/js/app.js'])

    @stack('scripts')

</body>

</html>
