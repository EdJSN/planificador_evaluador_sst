{{-- Card control de asistencia --}}
<h1 class="mb-4 text-center">Control de asistencia digital</h1>

<div class="card" id="activeControlCard">
    <div class="card-header text-center Azlo-light">
        @php
            $activeActivities = isset($activities) ? $activities->pluck('topic')->filter()->values() : collect();
        @endphp
        <h5 id="activeActivitiesTitle" class="mb-0 text-white">
            Control de asistencia activo
            @if ($activeActivities->count())
                : {{ $activeActivities->join(' • ') }}
            @endif
        </h5>
        {{-- Mini listado de actividades con botón "Desvincular" --}}
        @if (isset($activities) && $activities->count())
            <div class="text-dark p- mt-1">
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    @foreach ($activities as $activity)
                        <div class="d-inline-flex align-items-center border rounded-pill px-3 py-1 gap-2 bg-light">
                            <span >{{ $activity->topic ?? 'Actividad #' . $activity->id }} </span>
                            @if ($activity->states !== 'E')
                            @can('unlink_activity_from_control')
                                <x-buttons.small-button class="unlink-activity" variant="outline-danger"
                                icon="fa fa-ban" data-url="{{ route('check.activities.unlink', $activity) }}"/>
                            @endcan
                            @else
                                <span class="ms-2 text-muted small">—</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
    <div class="card-body body-table table-responsive-fixed-header py-0 standard-height">
        <table id="attendanceTable" class="table table-border table-hover table-interactive">
            <thead class="text-center">
                <tr>
                    <th scope="col">Nombres</th>
                    <th scope="col">Documento</th>
                    <th scope="col">Cargo</th>
                    <th scope="col">Asiste</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($attendances->unique('employee_id') as $attendance)
                    <tr data-attendance-id="{{ $attendance->id }}">
                        <td>{{ $attendance->employee->full_name }}</td>
                        <td>{{ $attendance->employee->document }}</td>
                        <td>{{ $attendance->employee->position->position }}</td>
                        <td>
                            <div class="text-center">
                                {{-- Opción SÍ --}}
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input attend-radio" type="radio"
                                        name="attend[{{ $attendance->id }}]" id="attend_yes_{{ $attendance->id }}"
                                        value="1" data-attendance-id="{{ $attendance->id }}"
                                        {{ $attendance->attend ? 'checked' : '' }}>
                                    <label class="form-check-label" for="attend_yes_{{ $attendance->id }}">SÍ</label>
                                </div>
                                {{-- Opción NO --}}
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input attend-radio" type="radio"
                                        name="attend[{{ $attendance->id }}]" id="attend_no_{{ $attendance->id }}"
                                        value="0" data-attendance-id="{{ $attendance->id }}"
                                        {{ !$attendance->attend ? 'checked' : '' }}>
                                    <label class="form-check-label" for="attend_no_{{ $attendance->id }}">NO</label>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">No hay actividades relacionadas en el momento.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer" id="activeControlActions">
        <div class="row text-center">
            <div class="col-md-6">
                <x-buttons.button id="saveSignatureBtn" icon="fa fa-floppy-o" text="Guardar" />
            </div>
            <div class="col-md-6">
                <x-buttons.button type="submit" id="finalizeControlBtn" icon="fa fa-list-alt" text="Finalizar"
                    data-bs-toggle="modal" data-bs-target="#confirm-finalize-modal" />
            </div>
            <!-- Modal para finalizar -->
            <x-modals.confirm-finalize-modal :activeActivities="$activeActivities" />
        </div>
    </div>
</div>
