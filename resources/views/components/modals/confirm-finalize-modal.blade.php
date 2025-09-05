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
                    <p>Completa los datos del cierre y tu contraseña para confirmar:</p>
                    <div class=" row">
                        <x-forms.input label="Lugar" id="place" name="place" col='col-md-12' required />
                    </div>
                    <div class="row">
                        <x-forms.input type="time" label="Hora inicio" id="start_time" name="start_time"
                            col='col-md-6' required />
                        <x-forms.input type="time" label="Hora fin" id="end_time" name="end_time" col='col-md-6' required/>
                    </div>
                    <div class=" row">
                        <x-forms.input label="Facilitador" id="facilitator_name" name="facilitator_name" col='col-md-6'
                            required />
                        <x-forms.input label="Documento" id="facilitator_document" name="facilitator_document"
                            col='col-md-6' required />
                    </div>
                    {{-- Canvas de firma --}}
                    <div class="mb-3">
                        <label for="facilitatorSignatureInput" class="form-label">Firma</label>
                        <div class="signature-wrapper">
                            <div class="signature-canvas-container">
                                <canvas id="facilitatorSignatureCanvas"></canvas>
                            </div>
                            <input type="hidden" id="facilitatorSignatureInput" name="facilitator_signature">
                        </div>
                    </div>
                    <div class="text-center mb-3">
                        <x-buttons.small-button id="clearFacilitatorSignatureBtn" variant="outline-danger" icon="fa fa-eraser" />
                    </div>
                    @php $activityNames = $activeActivities->join(' • '); @endphp
                    <p>Estás a punto de finalizar el control de asistencia para las siguientes actividades:</p>
                    <p class="fw-bold text-center">{{ $activityNames }}</p>
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
                        <x-buttons.button type="submit" icon="fa fa-check" text="Finalizar" form="finalizeForm"/>
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
