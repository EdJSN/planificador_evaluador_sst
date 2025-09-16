<!-- Modal para exportar lista de empleados a controles de asistencia digital -->
<div class="modal fade" id="exportListModal" tabindex="-1" aria-labelledby="exportListModalLabel" aria-hidden="true"
    data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header Azlo-light">
                <h5 class="modal-title text-white d-block mx-auto" id="exportListModalLabel">Correlacionar actividades</h5>
            </div>
            <div class="modal-body text-start">
                <form id="exportListForm" action="{{ route('planner.export') }}" method="POST">
                    @csrf
                    <!-- Selección del tipo -->
                    <div class="mb-3">
                        <label class="form-label">Tipo de exportación</label>
                        <div class="row">
                            <div class="col-md-6">
                                <input type="radio" id="tipo_individual" name="tipo" value="individual" checked>
                                <label for="tipo_individual">Individual</label>
                            </div>
                            <div class="col-md-6">
                                <input type="radio" id="tipo_grupo" name="tipo" value="grupo" class="ms-3">
                                <label for="tipo_grupo">Grupal</label>
                            </div>
                        </div>
                        <!-- Selección de actividades -->
                        @php
                            $activityOptions =
                                $activities->whereIn('states', ['P','A','R'])->pluck('topic', 'id')->toArray();
                        @endphp
                        <div>
                            <div id="actividad_individual" class="mb-3">
                                <x-forms.select label="Actividad" id="activity_id_individual" name="activity_id"
                                    col="col-md-12" class="js-tomselect form-select" :options="$activityOptions" required />
                            </div>

                            <div id="actividad_grupo" class="mb-3 d-none">
                                <x-forms.select label="Actividades del grupo" id="activity_ids_group"
                                    name="activity_ids[]" col="col-md-12" :options="$activityOptions" multiple />
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer justify-content-center">
                <div class="row">
                    <div class="col-md-6">
                        <x-buttons.button type="submit" id="" icon="fa fa-upload" text="Exportar" form="exportListForm" />
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
