<!-- Card para consulta de actividades -->
<h1 class="my-4 text-center">Consulta de historial</h1>

<div class="card" id="historyControlCard">
    <div class="card-header text-center Azlo-light">
        <h5 class="mb-0 text-white">Consultar historial</h5>
    </div>
    <div class="card-body">
        <div class="card-body body-table table-responsive-fixed-header">
            <div class="row mb-3">
                <form id="searchActivityForm" class="d-flex">
                    <div class="col-md-6 px-0">
                        <x-forms.input id="searchInput" name="searchInput" col='col-md-12' class="px-0" placeholder="Buscar por tema o fecha"/>
                    </div>
                    <div class="col-md-3 ms-2">
                        <x-buttons.button type="submit" id="filterControlBtn" icon="fa fa-search" text="Buscar" />
                    </div>
                </form>
            </div>
            <table class="table table-border table-hover table-interactive" id="activitiesResultsTable">
                <thead class="text-center">
                    <tr>
                        <th>Tema actividad</th>
                        <th>Fecha de ejecución</th>
                        <th>Estado</th>
                        <th class="text-center">Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="text-center">
                            <x-buttons.small-button icon="fa fa-print" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
    </div>
</div>
