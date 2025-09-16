<x-app-layout>

    {{-- Modal para Editar Actividad --}}
    <div class="modal fade" id="editActivityModal" tabindex="-1" aria-labelledby="editActivityModalLabel"
        data-bs-backdrop="static" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- Incluir formulario de creación, adaptado para edición --}}
                    @include('planner.create', ['is_edit_mode' => true]) {{-- Bandera para indicar modo edición --}}
                </div>
            </div>
        </div>
    </div>

    {{-- Modal para confirmar eliminación --}}
    <x-modals.confirm-delete modalId="confirmDeleteActivityModal" title="Eliminar actividad"
        message="Estás a punto de eliminar la actividad seleccionada. Esta acción es irreversible."
        formId="deleteActivityForm" route="" inputId="deleteActivityId" />

    {{-- Modal para exportar --}}
    <x-modals.export-list-modal :activities="$activities" />

    {{-- Incluir el formulario de creación --}}
    @include('planner.create')

    {{-- Incluir la tabla de actividades --}}
    @include('planner.index', ['activities' => $activities])

    <script>
        window.activeActivities = @json($selected ?? collect());
        window.countByAudiencesUrl = "{{ route('employees.countByAudiences') }}";
    </script>

</x-app-layout>
