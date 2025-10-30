<!-- Card para consulta de actividades -->
<h1 class="my-4 text-center">Consulta de historial</h1>

<div class="card" id="historyControlCard">
    <div class="card-header text-center Azlo-light">
        <h5 class="mb-0 text-white">Consultar historial</h5>
    </div>
    <div id="card-body-table-check" class="card-body body-table table-responsive-fixed-header py-0">
        <div class="row my-3">
            <form id="searchActivityForm" class="d-flex">
                @csrf
                <div class="col-md-6 px-0">
                    <x-forms.input id="searchInput" name="searchInput" col='col-md-12' class="px-0"
                        placeholder="Buscar por tema o fecha" />
                </div>
                <div class="col-md-6 ms-2 text-center">
                    <x-buttons.button type="submit" id="filterControlBtn" icon="fa fa-search" text="Buscar" />
                </div>
            </form>
            <div>
                <table class="table table-border table-hover table-interactive" id="activitiesResultsTable">
                    <thead class="text-center">
                        <tr>
                            <th>Tema actividad</th>
                            <th>Fecha de ejecución</th>
                            <th>Estado</th>
                            <th>Opciones</th>
                        </tr>
                    </thead>
                    <tbody id="activitiesResultsBody">
                    </tbody>
                </table>
                @if ($attendances->count())
                    <div class="pagination-wrapper m-2">
                        <div>
                            {{ $attendances->onEachSide(1)->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="card-footer text-center">
        <div class="row">
            <div>
                <x-buttons.button id="btn-double-check" icon="fa fa-search" text="Ver mas" />
            </div>
        </div>
    </div>
</div>

<script>
    window.printAttendeesUrl = "{{ route('check.print.attendees') }}";
</script>
