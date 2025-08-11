var CaiTableObserver = null;
function initCaiTable() {
    setTimeout(function() {
        try {
            var $table = $('#tbl_cai');
            if ($table.length) {
                $table.css('border', '');
                if (!$.fn.DataTable.isDataTable($table)) {
                    if (CaiTableObserver) CaiTableObserver.disconnect();
                    $table.DataTable({
                        responsive: true,
                        language: {
                            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                        },
                        order: [[12, 'asc'], [13, 'desc']], // Ordenar por Estado (columna 12) ASC, luego por fecha creación DESC
                        columnDefs: [
                            {
                                targets: 12, // Columna de Estado
                                type: 'html', // Permite ordenar por contenido HTML
                                render: function(data, type, row) {
                                    if (type === 'sort' || type === 'type') {
                                        // Para ordenamiento, retorna 1 para Activo, 2 para Inactivo
                                        return data.includes('Activo') ? 1 : 2;
                                    }
                                    return data; // Para display, retorna el HTML original
                                }
                            }
                        ],
                        initComplete: function() {
                            // Agregar filtro por estado
                            var column = this.api().column(12);
                            var select = $('<select class="form-select form-select-sm ms-2"><option value="">Todos los estados</option></select>')
                                .appendTo($('#tbl_cai_wrapper .dataTables_filter label'))
                                .on('change', function() {
                                    var val = $.fn.dataTable.util.escapeRegex($(this).val());
                                    column.search(val ? '^' + val + '$' : '', true, false).draw();
                                });
                            
                            // Agregar opciones al select
                            select.append('<option value="Activo">Solo Activos</option>');
                            select.append('<option value="Inactivo">Solo Inactivos</option>');
                        }
                    });
                    // Fixed: Set childList to true for proper MutationObserver options
                    if (CaiTableObserver && document.querySelector('main')) {
                        CaiTableObserver.observe(document.querySelector('main'), { childList: true, subtree: true });
                    }
                }
            }
        } catch (error) {
            console.warn('Error initializing CAI table:', error);
        }
    }, 300);
}
window.livewire && window.livewire.hook('message.processed', () => {
    initCaiTable();
});

// Detecta cambios en el contenido principal y reinicializa la tabla
document.addEventListener('DOMContentLoaded', function() {
    try {
        var main = document.querySelector('main');
        if (main) {
            CaiTableObserver = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        initCaiTable(); // Reinicializa tabla cuando hay cambios
                    }
                });
            });
            // Configuración corregida: childList debe ser true
            CaiTableObserver.observe(main, { childList: true, subtree: true });
        }
        
        // Inicializar tabla al cargar la página
        initCaiTable();
    } catch (error) {
        console.warn('Error setting up CAI MutationObserver:', error);
        // Fallback: solo inicializar la tabla
        initCaiTable();
    }
});
