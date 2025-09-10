{{-- Encabezado --}}
<h2 class="my-5 pt-4 text-center">Listado de actividades</h2>

{{-- Tabla con listado de actividades --}}
<div style="max-width: 82vw;" class="mx-auto">
    <div class="card">
        <div class="card-header text-center Azlo-light">
            <h5 class="mb-0 text-white">Actividades planificadas (orden descendente)</h5>
        </div>
        <div id="card-body-table" class="card-body body-table table-responsive-fixed-header py-0">
            <table id="activitiesTable" class="table table-border table-hover table-interactive personal-container">
                <thead class="text-center">
                    <tr>
                        <th scope="col">Eje Temático</th>
                        <th scope="col">Tema</th>
                        <th scope="col">Objetivo</th>
                        <th scope="col">Lugar y Hora</th>
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
                            data-place_time="{{ $activity->place_time ?? '' }}"
                            data-group_types="{{ $activity->group_types ?? '' }}"
                            data-facilitators="{{ $activity->facilitators ?? '' }}"
                            data-duration="{{ $activity->duration ?? '' }}"
                            data-number_participants="{{ $activity->number_participants ?? '' }}"
                            data-estimated_date="{{ $activity->estimated_date ? \Carbon\Carbon::parse($activity->estimated_date)->format('Y-m-d') : '' }}"
                            data-evaluation_methods="{{ $activity->evaluation_methods ?? '' }}"
                            data-resources="{{ $activity->resources ?? '' }}"
                            data-budget="{{ $activity->budget ?? '' }}" data-states="{{ $activity->states ?? '' }}"
                            data-efficacy_evaluation="{{ $activity->efficacy_evaluation ?? '' }}"
                            data-efficacy_evaluation_date="{{ $activity->efficacy_evaluation_date ? \Carbon\Carbon::parse($activity->efficacy_evaluation_date)->format('Y-m-d') : '' }}"
                            data-responsible="{{ $activity->responsible ?? '' }}"
                            data-coverage="{{ $activity->coverage ?? '' }}"
                            data-observations="{{ $activity->observations ?? '' }}">
                            <td>{{ $activity->thematic_axis }}</td>
                            <td>{{ $activity->topic }}</td>
                            <td>{{ $activity->objective }}</td>
                            <td>{{ $activity->place_time }}</td>
                            <td>{{ $activity->group_types }}</td>
                            <td>{{ $activity->facilitators }}</td>
                            <td class="text-center">{{ number_format($activity->duration, 1) }}</td>
                            <td class="text-center">{{ $activity->number_participants }}</td>
                            <td class="text-center">
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
                            <td class="text-center">
                                {{ $activity->efficacy_evaluation_date ? \Carbon\Carbon::parse($activity->efficacy_evaluation_date)->format('d/m/Y') : '' }}
                            </td>
                            <td>{{ $activity->responsible }}</td>
                            <td class="text-center">{{ $activity->coverage }}</td>
                            <td>{{ $activity->observations }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="18" class="text-start">No hay actividades registradas.</td>
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
</div>
