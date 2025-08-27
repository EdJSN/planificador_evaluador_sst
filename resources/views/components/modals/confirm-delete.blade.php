{{-- Modal para confirmar eliminación --}}

@props([
    'modalId' => 'confirmDeleteModal',
    'title' => 'Confirmar eliminación',
    'message' => 'Estás a punto de eliminar este elemento. Esta acción es irreversible.',
    'formId' => 'deleteForm',
    'route' => '',
    'inputId' => 'deleteId',
])

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" data-bs-backdrop="static"
    aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white d-block mx-auto" id="{{ $modalId }} Label">{{ $title }}</h5>
            </div>
            <div class="modal-body">
                <p>{{ $message }}</p>
                <p>Para confirmar, por favor ingresa tu contraseña:</p>
                <form id="{{ $formId }}" method="POST" action="{{ $route }}">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="id" id="{{ $inputId }}">
                    <div class="mb-3">
                        <label for="passwordConfirmation" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="passwordConfirmation" name="password" required>
                        <div class="invalid-feedback">Por favor, introduce tu contraseña.</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 text-center">
                            <x-buttons.button type="submit" variant="danger" icon="fa fa-trash" text="Eliminar"/>
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
