<!-- Modal para exportar lista de empleados a controles de asistencia digital -->
<div class="modal fade" id="exportListModal" tabindex="-1" aria-labelledby="exportListModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="exportListForm" action="{{ route('check.prepare') }}" method="POST">
                @csrf
                <div class="modal-header Azlo-light">
                    <h5 class="modal-title text-white d-block mx-auto" id="exportListModalLabel">Exportar lista de empleados</h5>
                </div>
                <div class="modal-body text-start">
                    <div class="mb-3">
                        @php
                            $activityOptions = ['' => 'Seleccione una actividad'] + $activities->pluck('topic', 'id')->toArray();
                        @endphp
                        <div class="mb-3">
                            <x-forms.select label="Actividad" id="activity_id" name="activity_id[]" col="col-md-12" :options="$activityOptions" multiple required/>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <div class="row">
                        <div class="col-md-6">
                            <x-buttons.button type="submit" id="" icon="fa fa-upload" text="Exportar" />
                        </div>
                        <div class="col-md-6">
                            <x-buttons.button variant="secondary" icon="fa fa-times" text="Cancelar" data-bs-dismiss="modal" />
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

