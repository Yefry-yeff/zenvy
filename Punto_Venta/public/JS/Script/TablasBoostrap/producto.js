document.addEventListener('livewire:load', function () {
    function initProductoTable() {
        setTimeout(function() {
            if ($('#productoTable').length) {
                if ($.fn.DataTable.isDataTable('#productoTable')) {
                    $('#productoTable').DataTable().destroy();
                }
                $('#productoTable').DataTable({
                    responsive: true,
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                    }
                });
            }
        }, 100);
    }

    Livewire.hook('message.processed', () => {
        initProductoTable();
    });

    document.addEventListener('DOMContentLoaded', initProductoTable);
});
