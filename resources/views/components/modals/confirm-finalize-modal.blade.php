{{-- Modal para confirmar finalización --}}
<div class="modal fade" id="confirm-finalize-modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header Azlo-light">
                <h5 class="modal-title text-white d-block mx-auto" id="confirmFinalizeModalLabel">Confirmar finalización
                </h5>
            </div>
            <div class="modal-body text-start mx-3">
                <form id="finalizeForm" method="POST" action="{{ route('attendance.finalize') }}">
                    @csrf
                    @php $activityNames = $activeActivities->join(' • '); @endphp
                    <p>Estás a punto de finalizar el control de asistencia para las siguientes actividades:</p>
                    <p class="fw-bold text-center">{{ $activityNames }}</p>
                    <p>Completa los datos del cierre y tu contraseña para confirmar:</p>
                    {{-- Canvas de firma --}}
                    <div class="mb-3">
                        <label for="facilitatorSignatureInput" class="form-label">Firma (Facilitador)</label>
                        <div class="signature-wrapper">
                            <div class="signature-canvas-container">
                                <canvas id="facilitatorSignatureCanvas"></canvas>
                            </div>
                            <input type="hidden" id="finalizeActivityIds" name="activity_ids">
                            <input type="hidden" id="facilitatorSignatureInput" name="facilitator_signature">
                        </div>
                    </div>
                    <div class="text-center mb-3">
                        <x-buttons.small-button id="clearFacilitatorSignatureBtn" variant="outline-danger"
                            icon="fa fa-eraser" />
                    </div>
                    <div>
                        <x-forms.input type="password" label="Contraseña" id="passwordConfirmation" name="password"
                            col='col-md-' required />
                        <div class="invalid-feedback">Por favor, introduce tu contraseña.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-center">
                <div class="row">
                    <div class="col-md-6">
                        <x-buttons.button type="submit" icon="fa fa-check" text="Finalizar" form="finalizeForm" />
                    </div>
                    <div class="col-md-6">
                        <x-buttons.button variant="secondary" icon="fa fa-times" text="Cancelar"
                            data-bs-dismiss="modal" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
