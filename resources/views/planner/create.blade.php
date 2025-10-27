{{-- Encabezado --}}
<h1 class="mb-4 text-center">Planificador de actividades</h1>

{{-- Formulario de actividades --}}
<div class="card">
    <div class="card-header text-center Azlo-light">
        <h5 class="mb-0 text-white" id="formTitle">
            @isset($is_edit_mode)
                Editar actividad
            @else
                Planificar nueva actividad
            @endisset
        </h5>
    </div>
    <div class="card-body">
        <form id="{{ isset($is_edit_mode) ? 'editActivityForm' : 'createActivityForm' }}" method="POST"
            action="{{ route('planner.store') }}">
            @csrf

            {{-- Especificar tipo de formulario (crear/editar) y id --}}
            <input type="hidden" name="_method" id="{{ isset($is_edit_mode) ? 'editFormMethod' : 'createFormMethod' }}"
                value="POST">
            <input type="hidden" name="id"
                id="{{ isset($is_edit_mode) ? 'editActivityId' : 'createActivityId' }}">
            <div class="row">
                <x-forms.input label="Eje temático*" id="thematic_axis" name="thematic_axis" />
                <x-forms.input label="Tema*" id="topic" name="topic" />
            </div>
            <div class="row">
                <x-forms.input label="Objetivo*" id="objective" name="objective" />
                <x-forms.input label="Lugar" id="place" name="place" col="col-md-2" />
                <x-forms.input type="time" label="Hora inicio" id="start_time" name="start_time" col='col-md-2' />
                <x-forms.input type="time" label="Hora fin" id="end_time" name="end_time" col='col-md-2' />
            </div>
            <div class="row">
                <x-forms.select label="Dirigido a*" id="audiences" name="audiences[]" col="col-md-3" :options="$audienceOptions"
                    class="js-tomselect form-select" data-placeholder="Seleccione las áreas" multiple />
                <x-forms.input label="Facilitador" id="facilitator" name="facilitator" col="col-md-3" />
                <x-forms.input label="Documento (Facilitador)" id="facilitator_document" name="facilitator_document"
                    col='col-md-2' />
                <x-forms.input label="Duración" type="number" id="duration" name="duration" col="col-md-2"
                    step="0.01" />
                <x-forms.input label="N° de participantes" type="number" id="number_participants"
                    name="number_participants" col="col-md-2" />
            </div>
            <div class="row">
                <x-forms.input label="Fecha estimada de ejecución*" type="date" id="estimated_date"
                    name="estimated_date" col="col-md-2" />
                <x-forms.input label="Métodos de evaluación" id="evaluation_methods" name="evaluation_methods"
                    col="col-md-3" />
                <x-forms.input label="Recursos" id="resources" name="resources" col="col-md-3" />
                <x-forms.input label="Presupuesto" id="budget" name="budget" col="col-md-3" />
                <x-forms.select label="Estado*" id="states" name="states" col="col-md-1" :options="['P' => 'P', 'A' => 'A', 'R' => 'R', 'E' => 'E']" />
            </div>
            <div class="row">
                <x-forms.input label="Evaluación de la eficacia" id="efficacy_evaluation" name="efficacy_evaluation"
                    col="col-md-4" />
                <x-forms.input label="Fecha de evaluación de la eficacia" type="date" id="efficacy_evaluation_date"
                    name="efficacy_evaluation_date" col="col-md-4" />
                <x-forms.input label="Responsable de evaluar" id="responsible" name="responsible" col="col-md-4" />
            </div>
            <div class="row">
                <x-forms.textarea label="Observaciones" id="observations" name="observations" col="col-md-12" />
            </div>
        </form>
    </div>
    <div class="card-footer text-center">
        <div>
            {{-- Botones condicionales según el modo (creación o edición) --}}
            @isset($is_edit_mode)
                {{-- Si estamos en modo edición (dentro del modal) --}}
                <div class="row">
                    <div class="col-md-6">
                        <x-buttons.button type="submit" id="updateActivityBtn" icon="fa fa-pencil" text="Actualizar"
                            form="editActivityForm" />
                    </div>
                    <div class="col-md-6">
                        <x-buttons.button variant="secondary" icon="fa fa-times" text="Cancelar"
                            data-bs-dismiss="modal" />
                    </div>
                </div>
            @else
                {{-- Si estamos en modo creación (el formulario original) --}}
                @can('create_activity')
                <x-buttons.button type="submit" id="createPlannerBtn" icon="fa fa-floppy-o" text="Guardar" form="createActivityForm" />
                @endcan
            @endisset
        </div>
    </div>
</div>
