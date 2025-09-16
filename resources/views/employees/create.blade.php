{{-- Modal nuevo usuario --}}
<div class="modal fade" id="createEmployeeModal" data-bs-backdrop="static" data-bs-keyboard="true" tabindex="-1"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header Azlo-light">
                <h5 class="modal-title text-white d-block mx-auto">Registrar nuevo empleado
                </h5>
            </div>
            <div class="modal-body text-start">
                <form id="createEmployeeForm" method="POST" action="{{ route('employees.store') }}">
                    @csrf

                    <input type="hidden" id="editEmployeeId" name="id">
                    <input type="hidden" id="editFormMethod" name="_method" value="POST">

                    <div class="mb-3">
                        <x-forms.input label="Nombres" id="names" name="names" col='col-md-12' autofocus />
                    </div>
                    <div class="mb-3">
                        <x-forms.input label="Apellido" id="lastname1" name="lastname1" col='col-md-12' />
                    </div>
                    <div class="mb-3">
                        <x-forms.input label="Apellido (opcional)" id="lastname2" name="lastname2" col='col-md-12' />
                    </div>
                    <div class="mb-3">
                        <x-forms.input label="Documento" id="document" name="document" col='col-md-12' />
                    </div>
                    {{-- Traer array de cargos al select --}}
                    @php
                        $positionOptions =
                            ['' => 'Seleccione un cargo'] + $positions->pluck('position', 'id')->toArray();
                    @endphp
                    <div class="mb-3">
                        <x-forms.select label="Cargo" id="position_id" name="position_id" col="col-md-12"
                            :options="$positionOptions" />
                    </div>
                    {{-- Traer array de audiencias al select --}}
                    @php
                        $audienceOptions = \App\Models\Audience::pluck('name', 'id')->toArray();
                    @endphp
                    <div class="mb-3">
                        <x-forms.select label="Área/Rol" id="audiences" name="audiences[]" col='col-md-12'
                            class="js-tomselect form-select" data-placeholder="Seleccione las áreas" :options="$audienceOptions" multiple />
                    </div>
                    {{-- Canva para firma digital --}}
                    <div class="mb-3 mx-3">
                        <label for="signatureInput" class="form-label">Firma</label>
                        <div class="signature-wrapper">
                            <div class="signature-canvas-container">
                                <canvas id="signatureCanvas"></canvas>
                            </div>
                            <input type="hidden" id="signatureInput" name="signature">
                        </div>
                    </div>
                    <div class="text-center">
                        <x-buttons.small-button id="clearSignatureBtn" variant="outline-danger" icon="fa fa-eraser" />
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-center">
                <div class="row">
                    <div class="col-md-6">
                        <x-buttons.button type="submit" id="saveEmployeeBtn" icon="fa fa-floppy-o" text="Guardar"
                            form="createEmployeeForm" />
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
