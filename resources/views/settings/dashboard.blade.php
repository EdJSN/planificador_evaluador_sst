<x-app-layout>
    {{-- Crear usuario --}}
    @include('settings.create')

    {{-- Listado de usuarios --}}
    @can('view_settings')
        @include('settings.index')
    @endcan
</x-app-layout>
