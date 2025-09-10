 var categoriasTableObserver = null;
        function initCategoriasTable() {
            setTimeout(function() {
                var $table = $('#categoriaTable');
                if ($table.length) {
                    $table.css('border', ''); // Quita el borde de depuración
// Verificar si la tabla tiene datos reales (no solo el mensaje de "no hay datos")
                    var $dataRows = $table.find('tbody tr').not(':contains("No hay categorías disponibles")');
                    var hasData = $dataRows.length > 0;

                    if (hasData && !$.fn.DataTable.isDataTable($table)) {
                        if (categoriasTableObserver) categoriasTableObserver.disconnect();
                        $table.DataTable({
                            responsive: true,
                            language: {
                                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                            }
                        });
                        if (categoriasTableObserver) categoriasTableObserver.observe(document.querySelector('main'), { childList: true, subtree: true });
                    }
                }
            }, 300);
        }
        window.livewire && window.livewire.hook('message.processed', () => {
            initCategoriasTable();
        });

        // Detecta cambios en el contenido principal y reinicializa la tabla
        document.addEventListener('DOMContentLoaded', function() {
            var main = document.querySelector('main');
            if (main) {
                categoriasTableObserver = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList') {
                            initCategoriasTable();
                        }
                    });
                });
                categoriasTableObserver.observe(main, { childList: true, subtree: true });
            }
        });
