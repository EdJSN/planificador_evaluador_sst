{{-- Encabezado --}}
<h1 class="mb-4 text-center">Ajustes</h1>

<div class="card">
    <div class="card-header text-center Azlo-light">
        <h5 class="mb-0 text-white" id="formTitle">Crear nuevo usuario</h5>
    </div>
    <div class="card-body">
        <form id="createUserForm" method="POST" action="{{ route('settings.users.store') }}">
            @csrf

            <div class="row">
                <x-forms.input label="Nombre*" id="name" name="name" col="col-md-6" autofocus />
                <x-forms.input type="email" label="Email*" id="email" name="email" col="col-md-6" />
            </div>

            <div class="row">
                <x-forms.input type="password" label="Contraseña*" id="password" name="password" col="col-md-6" />
                <x-forms.input type="password" label="Confirmar contraseña*" id="password_confirmation"
                    name="password_confirmation" col="col-md-6" />
            </div>

            @if (isset($roles) && $roles->count())
                <div class="row">
                    <x-forms.select label="Rol" id="role" name="role" col="col-md-6" :options="$roles"
                        class="form-select" data-placeholder="Selecciona un rol" />
                </div>
            @endif
        </form>
    </div>
    <div class="card-footer text-center">
        <div>
            <x-buttons.button type="submit" id="createUserBtn" icon="fa fa-save" text="Guardar" form="createUserForm" />
        </div>
    </div>
</div>
