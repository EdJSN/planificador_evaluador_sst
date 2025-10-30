{{-- Encabezado --}}
<h2 class="my-5 text-center">Listado de personal</h2>

{{-- Tabla con listado de empleados --}}
<div class="card">
    <div class="card-header text-end Azlo-light">
        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            @can('create_employee')
                <x-buttons.button id="createEmployeeBtn" variant="secondary-light" icon="fa fa-plus-circle" text="Nuevo" />
            @endcan
            <x-buttons.button id="btn-double-employees" variant="secondary-light" icon="fa fa-search" text="Ver más" />
        </div>
    </div>
    <div id="card-body-table-employees" class="card-body body-table table-responsive-fixed-header py-0">
        <table id="employeesTable" class="table table-border table-hover table-interactive">
            <thead class="text-center">
                <tr>
                    <th scope="col">Nombres</th>
                    <th scope="col">Documento</th>
                    <th scope="col">Cargo</th>
                    <th scope="col">Opciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employees as $employee)
                    <tr data-id="{{ $employee->id }}" data-names="{{ $employee->names ?? '' }}"
                        data-lastname1="{{ $employee->lastname1 ?? '' }}"
                        data-lastname2="{{ $employee->lastname2 ?? '' }}"
                        data-document="{{ $employee->document ?? '' }}"
                        data-position_id="{{ $employee->position_id ?? '' }}"
                        data-audiences='@json($employee->audiences->pluck('id'))'>
                        <td>{{ $employee->full_name }}</td>
                        <td>{{ $employee->document }}</td>
                        <td class="text-center">{{ $employee->position->position }}</td>
                        <td class="text-center">
                            @can('edit_employee')
                                <x-buttons.small-button class="editEmployeeBtn" icon="fa fa-pencil" />
                            @endcan
                            @can('delete_employee')
                                <x-buttons.small-button class="deleteEmployeeBtn" variant="outline-danger"
                                    icon="fa fa-trash" />
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">No hay empleados registrados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if ($employees->count())
            <div class="pagination-wrapper m-2">
                <div>
                    {{ $employees->onEachSide(1)->links() }}
                </div>
            </div>
        @endif
    </div>
    <div class="card-footer text-center">
        <div class="row">
            <div>
                <x-buttons.button id="printEmployeesBtn" icon="fa fa-print" text="Imprimir" />
            </div>
        </div>
    </div>
</div>
