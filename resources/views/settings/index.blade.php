<h1 class="my-4 text-center">Gestión de usuarios</h1>

<div class="card mt-4">
    <div class="card-header text-center Azlo-light">
        <h5 class="mb-0 text-white">Usuarios del sistema</h5>
    </div>

    <div class="card-body p-0">
        <div class="card-body body-table table-responsive-fixed-header py-0">
            <table class="table table-border table-hover table-interactive">
                <thead>
                    <tr class="text-center">
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Fecha de creación</th>
                        <th>Opciones</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($users as $u)
                        <tr>
                            <td>{{ $u->name }}</td>
                            <td>{{ $u->email }}</td>
                            <td>{{ $u->roles->pluck('name')->join(', ') ?: '—' }}</td>
                            <td class="text-center">{{ optional($u->created_at)->format('Y-m-d') }}</td>
                            <td class="text-center">
                                @can('edit_user')
                                    <x-buttons.small-button class="editUserBtn" icon="fa fa-pencil" data-bs-toggle="modal"
                                        data-bs-target="#editUserModal{{ $u->id }}" />
                                @endcan

                                @can('delete_user')
                                    <x-buttons.small-button class="deleteUserBtn" variant="outline-danger"
                                        icon="fa fa-trash" data-bs-toggle="modal"
                                        data-bs-target="#deleteUserModal{{ $u->id }}" />
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center p-4">No hay usuarios registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer py-4"></div>
</div>

{{-- Modales de actualización y eliminación --}}
@if($users->count())
    @foreach ($users as $u)
        @can('edit_user')
            <x-modals.edit-user-modal
                :user="$u"
                :roles="$roles"
                :modalId="'editUserModal'.$u->id"
            />
        @endcan

        @can('delete_user')
            <x-modals.delete-user-modal
                :modalId="'deleteUserModal'.$u->id"
                :formId="'deleteUserForm'.$u->id"
                :inputId="'deleteUserId'.$u->id"
                :route="route('settings.users.destroy', $u)"
                :message="'¿Seguro que deseas eliminar a «'.$u->name.'»? Esta acción no se puede deshacer.'"
            />
        @endcan
    @endforeach
@endif