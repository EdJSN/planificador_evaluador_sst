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

    <div class="wrapper d-flex align-items-stretch">

        {{-- SideBar --}}
        <nav id="sidebar" class="active Azlo-light">
            <a href="{{ route('home') }}" class="logo">
                <img src=" {{ asset('images/logoAzloSide.png') }}" class="sidebar-img">
            </a>
            <ul class="list-unstyled components mb-5 sidebar-links">
                <li class="{{ request()->routeIs('planner.*') ? 'active' : '' }}">
                    <a href="{{ route('planner.dashboard') }}"><span class="fa fa-pencil-square-o"></span>Planificar</a>
                </li>
                <li class="{{ request()->routeIs('employees.*') ? 'active' : '' }}">
                    <a href="{{ route('employees.index') }}"><span class="fa fa-users"></span>Personal</a>
                </li>
                <li class="{{ request()->routeIs('check.*') ? 'active' : '' }}">
                    <a href="{{ route('check.index') }}"><span class="fa fa-list-ul"></span>Asistencia</a>
                </li>
                <li>
                    {{--<a href="settings.html"><span class="fa fa-cogs"></span>Ajustes</a>--}}
                </li>
            </ul>
        </nav>

        <div id="content" class="p-4">

            {{-- NavBar --}}
            <nav class="navbar navbar-expand-lg navbar-light bg-light" id="main-navbar">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn Azlo-dark">
                        <i class="fa fa-bars"></i>
                        <span class="sr-only">Toggle Menu</span>
                    </button>
                    <button type="button" class="btn Azlo-light d-inline-block d-lg-none "
                        data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
                        aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <i class="fa fa-bars"></i>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ms-auto">
                            <li class="nav-item active">
                                <a class="nav-link" href="{{ route('home') }}">Inicio</a>
                            </li>
                            {{--<li class="nav-item">
                                <a class="nav-link" href="profile.html">Perfil</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="settings.html">Usuarios</a>
                            </li>--}}
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('logout') }}"
                                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    Cerrar Sesión
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            {{-- Page content --}}
            {{ $slot }}

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
