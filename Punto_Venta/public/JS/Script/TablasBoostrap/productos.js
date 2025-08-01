document.addEventListener("DOMContentLoaded", function() {
    function initializeProductosTable() {
        if ($.fn.DataTable.isDataTable('#productosTable')) {
            $('#productosTable').DataTable().destroy();
        }

        $('#productosTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
            },
            "pageLength": 25,
            "dom": 'frtip',
            "autoWidth": false,
            "order": [[6, "desc"]],  // Ordenar por fecha de creación
            "columnDefs": [
                { "width": "auto", "targets": 0 },   // Nombre
                { "width": "auto", "targets": 1 },   // Descripción
                { "width": "120px", "targets": 2 },  // Categoría
                { "width": "120px", "targets": 3 },  // Subcategoría
                { "width": "100px", "targets": 4 },  // Marca
                { "width": "100px", "targets": 5 },  // Precio Base
                { "width": "150px", "targets": 6 },  // Fecha
                { "width": "60px", "targets": 7, "orderable": false }  // Acciones
            ]
        });
    }

    // Inicializar la tabla cuando se carga la página
    initializeProductosTable();

    // Reinicializar después de actualizaciones de Livewire
    Livewire.hook('morph.updated', () => {
        setTimeout(() => {
            initializeProductosTable();
        }, 100);
    });

    // También escuchar eventos de Livewire específicos
    window.livewire.on('refreshTable', () => {
        setTimeout(() => {
            initializeProductosTable();
        }, 100);
    });
});
