@props([
    'modalId' => 'editUserModal',
    'user',
    'roles' => [],
])

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header Azlo-light">
                <h5 class="modal-title text-white d-block mx-auto">Editar usuario</h5>
            </div>

            <div class="modal-body">
                <form method="POST" action="{{ route('settings.users.update', $user) }}" id="{{ $modalId }}Form">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <x-forms.input label="Nombre*" name="name" col="col-md-12" :value="$user->name" />
                    </div>
                    <div class="row">
                        <x-forms.input type="email" label="Email*" name="email" col="col-md-12" :value="$user->email" />
                    </div>
                    <div class="row">
                        <x-forms.input type="password" label="Nueva contraseña (opcional)" name="password"
                            col="col-md-12" />
                    </div>
                    <div class="row">
                        <x-forms.input type="password" label="Confirmar contraseña" name="password_confirmation"
                            col="col-md-12" />
                    </div>
                    <div class="row">
                        <x-forms.select label="Rol" name="role" col="col-md-12" :options="$roles"
                            class="form-select" :value="$user->roles->pluck('name')->first()" />
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-center">
                <div class="row">
                    <div class="col-md-6">
                        <x-buttons.button type="submit" id="saveEmployeeBtn" icon="fa fa-floppy-o" text="Guardar"
                            form="{{ $modalId }}Form" />
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
