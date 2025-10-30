{{-- Modal para confirmar eliminación de usuarios --}}

@props([
    'modalId' => 'deleteUserModal',
    'title' => 'Confirmar eliminación',
    'message' => 'Estás a punto de eliminar este usuario. Esta acción es irreversible.',
    'formId' => 'deleteUserForm',
    'route' => '',
    'inputId' => 'deleteUserId',
])

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" data-bs-backdrop="static"
    aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white d-block mx-auto" id="{{ $modalId }}Label">{{ $title }}</h5>
            </div>
            <div class="modal-body">
                <p class="mb-2">{{ $message }}</p>
                <p class="mb-3">Para confirmar, por favor ingresa tu contraseña:</p>
                <form id="{{ $formId }}" method="POST" action="{{ $route }}">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="id" id="{{ $inputId }}">
                    <div class="mb-3">
                        <label for="passwordConfirmation-{{ $modalId }}" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="passwordConfirmation-{{ $modalId }}"
                            name="password" required>
                        <div class="invalid-feedback">Por favor, introduce tu contraseña.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-center p-2">
                <div class="row">
                    <div class="col-md-6 text-center">
                        <x-buttons.button type="submit" variant="danger" icon="fa fa-trash" text="Eliminar" :form="$formId" />
                    </div>
                    <div class="col-md-6 text-center">
                        <x-buttons.button type="button" variant="secondary" icon="fa fa-times" text="Cancelar" data-bs-dismiss="modal" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
