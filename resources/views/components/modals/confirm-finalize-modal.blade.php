{{-- Modal para confirmar finalización --}}
<div class="modal fade" id="confirm-finalize-modal" tabindex="-1" data-bs-backdrop="static"
    aria-labelledby="confirmFinalizeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-white d-block mx-auto" id="confirmFinalizeModalLabel">
                    Confirmar finalización
                </h5>
            </div>
            <div class="modal-body">
                @php
                    $activityNames = $activeActivities->join(' • ');
                @endphp
                <p>Estás a punto de finalizar el control de asistencia para las siguientes actividades:</p>
                <p class="fw-bold text-center text-primary">{{ $activityNames }}</p>
                <p>Si estás seguro, introduce tu contraseña de usuario para confirmar.</p>

                <form id="finalizeForm" method="POST" action="{{ route('attendance.finalize') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="passwordConfirmation" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="passwordConfirmation"
                               name="password" required>
                        <div class="invalid-feedback">Por favor, introduce tu contraseña.</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 text-center">
                            <x-buttons.button type="submit" icon="fa fa-check" text="Finalizar"/>
                        </div>
                        <div class="col-md-6 text-center">
                            <x-buttons.button variant="secondary" icon="fa fa-times" text="Cancelar" data-bs-dismiss="modal"/>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
