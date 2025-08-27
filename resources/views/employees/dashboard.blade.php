<x-app-layout>

    {{-- Modal para confirmar eliminación de empleados --}}
    <x-modals.confirm-delete
    modalId="confirmDeleteEmployeeModal"
    title="Eliminar empleado"
    message="Esta acción eliminará al empleado de forma lógica (no se borra definitivamente)."
    formId="deleteEmployeeForm"
    route=""
    inputId="deleteEmployeeId"
    />

    {{-- Incluir el modal de creación de empleado --}}
    @include('employees.create')

    {{-- Incluir listado de personal --}}
    @include('employees.index')

</x-app-layout>
