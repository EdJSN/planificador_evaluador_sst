{{-- Encabezado --}}
<h2 class="my-5 pt-4 text-center">Listado de actividades</h2>

{{-- Tabla con listado de actividades --}}
<div class="card">
    <div class="card-header text-center Azlo-light">
        <h5 class="mb-0 text-white">Actividades planificadas (orden descendente)</h5>
    </div>
    <div id="card-body-table" class="card-body body-table table-responsive-fixed-header p-1">
        <table id="activitiesTable" class="table-bordered table-hover table-interactive text-center">
            <thead>
                <tr>
                    <th scope="col">Eje Temático</th>
                    <th scope="col">Tema</th>
                    <th scope="col">Objetivo</th>
                    <th scope="col">Lugar</th>
                    <th scope="col">Hora inicio</th>
                    <th scope="col">Hora fin</th>
                    <th scope="col">Dirigido a</th>
                    <th scope="col">Facilitador</th>
                    <th scope="col">Duración</th>
                    <th scope="col">N° de participantes</th>
                    <th scope="col">Fecha estimada de ejecución</th>
                    <th scope="col">Métodos de evaluación</th>
                    <th scope="col">Recursos</th>
                    <th scope="col">Presupuesto</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Evaluación de la eficacia</th>
                    <th scope="col">Fecha de evaluación eficacia</th>
                    <th scope="col">Responsable de evaluar</th>
                    <th scope="col">Cobertura</th>
                    <th scope="col">Observaciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activities as $activity)
                    <tr data-id="{{ $activity->id }}" data-thematic_axis="{{ $activity->thematic_axis ?? '' }}"
                        data-topic="{{ $activity->topic ?? '' }}" data-objective="{{ $activity->objective ?? '' }}"
                        data-place="{{ $activity->place ?? '' }}"
                        data-start_time="{{ $activity->start_time ? \Carbon\Carbon::parse($activity->start_time)->format('H:i') : '' }}"
                        data-end_time="{{ $activity->end_time ? \Carbon\Carbon::parse($activity->end_time)->format('H:i') : '' }}"
                        data-audiences="{{ $activity->audiences->pluck('id')->implode(',') }}"
                        data-facilitator="{{ $activity->facilitator ?? '' }}"
                        data-facilitator_document="{{ $activity->facilitator_document ?? '' }}"
                        data-duration="{{ $activity->duration ?? '' }}"
                        data-number_participants="{{ $activity->number_participants ?? '' }}"
                        data-estimated_date="{{ $activity->estimated_date ? \Carbon\Carbon::parse($activity->estimated_date)->format('Y-m-d') : '' }}"
                        data-evaluation_methods="{{ $activity->evaluation_methods ?? '' }}"
                        data-resources="{{ $activity->resources ?? '' }}" data-budget="{{ $activity->budget ?? '' }}"
                        data-states="{{ $activity->states ?? '' }}"
                        data-efficacy_evaluation="{{ $activity->efficacy_evaluation ?? '' }}"
                        data-efficacy_evaluation_date="{{ $activity->efficacy_evaluation_date ? \Carbon\Carbon::parse($activity->efficacy_evaluation_date)->format('Y-m-d') : '' }}"
                        data-responsible="{{ $activity->responsible ?? '' }}"
                        data-coverage="{{ $activity->coverage ?? '' }}"
                        data-observations="{{ $activity->observations ?? '' }}">
                        <td>{{ $activity->thematic_axis }}</td>
                        <td>{{ $activity->topic }}</td>
                        <td>{{ $activity->objective }}</td>
                        <td>{{ $activity->place }}</td>
                        <td>{{ $activity->start_time ? \Carbon\Carbon::parse($activity->start_time)->format('h:i A') : '' }}
                        </td>
                        <td>{{ $activity->end_time ? \Carbon\Carbon::parse($activity->end_time)->format('h:i A') : '' }}
                        </td>
                        <td>{{ $activity->audiences->pluck('name')->join(', ') }}</td>
                        <td>{{ $activity->facilitator }}</td>
                        <td>{{ $activity->duration !== null ? number_format($activity->duration, 2) : '' }}</td>
                        <td>{{ $activity->number_participants }}</td>
                        <td>
                            {{ $activity->estimated_date ? \Carbon\Carbon::parse($activity->estimated_date)->format('d/m/Y') : '' }}
                        </td>
                        <td>{{ $activity->evaluation_methods }}</td>
                        <td>{{ $activity->resources }}</td>
                        <td>{{ $activity->budget }}</td>
                        <td
                            class="text-center {{ $activity->states == 'E'
                                ? 'status-e'
                                : ($activity->states == 'A'
                                    ? 'status-a'
                                    : ($activity->states == 'R'
                                        ? 'status-r'
                                        : '')) }}">
                            {{ $activity->states }}
                        </td>
                        <td>{{ $activity->efficacy_evaluation }}</td>
                        <td>
                            {{ $activity->efficacy_evaluation_date ? \Carbon\Carbon::parse($activity->efficacy_evaluation_date)->format('d/m/Y') : '' }}
                        </td>
                        <td>{{ $activity->responsible }}</td>
                        <td>{{ $activity->coverage_label }}</td>
                        <td>{{ $activity->observations }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="21" class="text-center">No hay actividades registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer text-center">
        <div class="row">
            <div class="col-md-3">
                <x-buttons.button id="selectBtn" icon="fa fa-mouse-pointer" text="Editar" />
            </div>
            <div class="col-md-3">
                <x-buttons.button id="openExportModalBtn" icon="fa fa-upload" text="Exportar" data-bs-toggle="modal"
                    data-bs-target="#exportListModal" />
            </div>
            <div class="col-md-3">
                <x-buttons.button id="btn-double" icon="fa fa-search" text="Ver mas" />
            </div>
            <div class="col-md-3">
                <x-buttons.button id="deleteActivityButton" variant="danger" icon="fa fa-trash" text="Eliminar" />
            </div>
        </div>
    </div>
</div>
