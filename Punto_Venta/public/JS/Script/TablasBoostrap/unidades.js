document.addEventListener("DOMContentLoaded", function() {
    function initializeUnidadesTable() {
        if ($.fn.DataTable.isDataTable('#unidadesTable')) {
            $('#unidadesTable').DataTable().destroy();
        }

        $('#unidadesTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
            },
            "pageLength": 25,
            "dom": 'frtip',
            "autoWidth": false,
            "order": [[3, "desc"]],
            "columnDefs": [
                { "width": "100px", "targets": 0 },  // Unidad
                { "width": "auto", "targets": 1 },   // Nombre
                { "width": "100px", "targets": 2 },  // Símbolo
                { "width": "150px", "targets": 3 },  // Fecha
                { "width": "60px", "targets": 4, "orderable": false }  // Acciones
            ]
        });
    }

    // Inicializar la tabla cuando se carga la página
    initializeUnidadesTable();

    // Reinicializar después de actualizaciones de Livewire
    Livewire.hook('morph.updated', () => {
        setTimeout(() => {
            initializeUnidadesTable();
        }, 100);
    });

    // También escuchar eventos de Livewire específicos
    window.livewire.on('refreshTable', () => {
        setTimeout(() => {
            initializeUnidadesTable();
        }, 100);
    });
});
