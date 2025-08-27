<x-app-layout>
    <div class="text-center">
        <h2 class="mb-4">¡Bienvenido, {{ Auth::user()->name }}!</h2>
        <img src="{{ asset('images/LogoAzloPrin.png') }}" class="imgHome img-fluid">
    </div>
</x-app-layout>