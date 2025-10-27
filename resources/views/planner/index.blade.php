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
                    <th scope="col">Inicio</th>
                    <th scope="col">Fin</th>
                    <th scope="col">Dirigido</th>
                    <th scope="col">Facilitador</th>
                    <th scope="col">Duración</th>
                    <th scope="col">N°</th>
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

{{-- Información/datos generales --}}
<h2 class="my-5 pt-4 text-center">Datos generales</h2>

{{-- Card contenedora (mismo estilo que la “madre”) --}}
<div class="card mt-3">
    <div class="card-header text-center Azlo-light">
        <h5 class="mb-0 text-white">Datos generales</h5>
    </div>

    {{-- Cuerpo: dos cards internas lado a lado, equidistantes --}}
    <div class="card-body">
        <div class="row justify-content-center g-4">

            {{-- Card: Cobertura --}}
            <div class="col-sm-10 col-md-6 col-xl-5">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body text-center">
                        {{-- SELECTOR DE AÑO --}}
                        <form method="GET" action="{{ route('planner.dashboard') }}" class="mb-2">
                            <div class="d-flex justify-content-center align-items-center gap-2">
                                <label for="year" class="mb-0">Año:</label>
                                <select id="year" name="year" class="form-select form-select-sm w-auto"
                                    onchange="this.form.submit()">
                                    @foreach ($years as $yr)
                                        <option value="{{ $yr }}"
                                            {{ (int) $yearToShow === (int) $yr ? 'selected' : '' }}>
                                            {{ $yr }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                        <div class="text-muted mb-2">
                            Cobertura total {{ $yearToShow ?? date('Y') }}
                        </div>
                        <div class="d-flex justify-content-center gap-4">
                            <div>
                                <small class="text-muted d-block">Requerido</small>
                                <div class="fs-5">{{ (int) ($summaryTotals['required'] ?? 0) }}</div>
                            </div>
                            <div>
                                <small class="text-muted d-block">Ejecutado</small>
                                <div class="fs-5">{{ (int) ($summaryTotals['executed'] ?? 0) }}</div>
                            </div>
                            <div>
                                <small class="text-muted d-block">% Total</small>
                                <div class="fs-5">
                                    {{ is_null($summaryTotals['pct'] ?? null) ? '—' : ((int) $summaryTotals['pct']) . '%' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card: Leyenda --}}
            <div class="col-sm-10 col-md-6 col-xl-5">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="mb-2 text-center">Leyenda</div>
                        <div class="text-muted text-center d-flex flex-column gap-2 ps-1">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-secondary me-2">&nbsp;&nbsp;</span>
                                <span>P = Planificado</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-warning me-2">&nbsp;&nbsp;</span>
                                <span>A = Aplazado</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-danger me-2">&nbsp;&nbsp;</span>
                                <span>R = Reprogramado</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-success me-2">&nbsp;&nbsp;</span>
                                <span>E = Ejecutado</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <div class="card-footer text-center"></div>
</div>
