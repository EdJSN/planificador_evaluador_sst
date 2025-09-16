<x-app-layout>

    {{-- Incluir control de asistencia --}}
    @include('check.create')

    {{-- Incluir control de asistencia --}}
    @include('check.index')

    <script>
        window.activeActivities = @json($selected->values()->all());
    </script>

</x-app-layout>
