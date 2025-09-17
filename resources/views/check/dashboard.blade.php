<x-app-layout>

    {{-- Incluir control de asistencia --}}
    @include('check.create')

    {{-- Incluir control de asistencia --}}
    @include('check.index')

    <script>
        window.activeActivities = @json($selected->values()->all());
        window.bulkUpdateUrl = "{{ route('check.attendance.bulkUpdate') }}";
    </script>

</x-app-layout>


