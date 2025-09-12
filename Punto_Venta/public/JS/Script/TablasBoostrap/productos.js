 var productosZenvyTableObserver = null;
 var productosValenciaTableObserver = null;

        function initproductosZenvyTable() {
            setTimeout(function() {
                var $table = $('#productosZenvyTable');
                if ($table.length) {
                    $table.css('border', ''); // Quita el borde de depuración
                    // Verificar si la tabla tiene datos reales (no solo el mensaje de "no hay datos")
                    var $dataRows = $table.find('tbody tr').not(':contains("No hay productos disponibles")');
                    var hasData = $dataRows.length > 0;

                    if (hasData && !$.fn.DataTable.isDataTable($table)) {
                        if (productosZenvyTableObserver) productosZenvyTableObserver.disconnect();
                        $table.DataTable({
                            responsive: true,
                            language: {
                                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                            }
                        });
                        if (productosZenvyTableObserver) productosZenvyTableObserver.observe(document.querySelector('main'), { childList: true, subtree: true });
                    }
                }
            }, 300);
        }
        window.livewire && window.livewire.hook('message.processed', () => {
            initproductosZenvyTable();
        });

        // Detecta cambios en el contenido principal y reinicializa la tabla
        document.addEventListener('DOMContentLoaded', function() {
            var main = document.querySelector('main');
            if (main) {
                productosZenvyTableObserver = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList') {
                            initproductosZenvyTable();
                        }
                    });
                });
                productosZenvyTableObserver.observe(main, { childList: true, subtree: true });
            }
        });

         function initproductosValenciaTable() {
            setTimeout(function() {
                var $table = $('#productosValenciaTable');
                if ($table.length) {
                    $table.css('border', ''); // Quita el borde de depuración
                    // Verificar si la tabla tiene datos reales (no solo el mensaje de "no hay datos")
                    var $dataRows = $table.find('tbody tr').not(':contains("No hay productos disponibles")');
                    var hasData = $dataRows.length > 0;

                    if (hasData && !$.fn.DataTable.isDataTable($table)) {
                        if (productosValenciaTableObserver) productosValenciaTableObserver.disconnect();
                        $table.DataTable({
                            responsive: true,
                            language: {
                                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                            }
                        });
                        if (productosValenciaTableObserver) productosValenciaTableObserver.observe(document.querySelector('main'), { childList: true, subtree: true });
                    }
                }
            }, 300);
        }
        window.livewire && window.livewire.hook('message.processed', () => {
            initproductosValenciaTable();
        });

        // Detecta cambios en el contenido principal y reinicializa la tabla
        document.addEventListener('DOMContentLoaded', function() {
            var main = document.querySelector('main');
            if (main) {
                productosValenciaTableObserver = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList') {
                            initproductosValenciaTable();
                        }
                    });
                });
                productosValenciaTableObserver.observe(main, { childList: true, subtree: true });
            }
        });
