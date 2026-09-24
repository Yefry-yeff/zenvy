
        var marcasZenvyTableObserver = null;
        var marcasValenciaTableObserver = null;
        function initmarcasZenvyTable() {
            setTimeout(function() {
                var $table = $('#marcasZenvyTable');
                if ($table.length) {
                    $table.css('border', ''); // Quita el borde de depuración
                    // Verificar si la tabla tiene datos reales (no solo el mensaje de "no hay datos")
                    var $dataRows = $table.find('tbody tr').not(':contains("No hay marcas disponibles")');
                    var hasData = $dataRows.length > 0;

                    if (hasData && !$.fn.DataTable.isDataTable($table)) {
                        if (marcasZenvyTableObserver) marcasZenvyTableObserver.disconnect();
                        $table.DataTable({
                            responsive: true,
                            language: {
                                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                            }
                        });
                        if (marcasZenvyTableObserver) marcasZenvyTableObserver.observe(document.querySelector('main'), { childList: true, subtree: true });
                    }
                }
            }, 300);
        }
        window.livewire && window.livewire.hook('message.processed', () => {
            initmarcasZenvyTable();
        });

        // Detecta cambios en el contenido principal y reinicializa la tabla
        document.addEventListener('DOMContentLoaded', function() {
            var main = document.querySelector('main');
            if (main) {
                marcasZenvyTableObserver = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList') {
                            initmarcasZenvyTable();
                        }
                    });
                });
                marcasZenvyTableObserver.observe(main, { childList: true, subtree: true });
            }
        });

        function initmarcasValenciaTable() {
            setTimeout(function() {
                var $table = $('#marcasValenciaTable');
                if ($table.length) {
                    $table.css('border', ''); // Quita el borde de depuración
                    // Verificar si la tabla tiene datos reales (no solo el mensaje de "no hay datos")
                    var $dataRows = $table.find('tbody tr').not(':contains("No hay marcas disponibles")');
                    var hasData = $dataRows.length > 0;

                    if (hasData && !$.fn.DataTable.isDataTable($table)) {
                        if (marcasValenciaTableObserver) marcasValenciaTableObserver.disconnect();
                        $table.DataTable({
                            responsive: true,
                            language: {
                                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                            }
                        });
                        if (marcasValenciaTableObserver) marcasValenciaTableObserver.observe(document.querySelector('main'), { childList: true, subtree: true });
                    }
                }
            }, 300);
        }
        window.livewire && window.livewire.hook('message.processed', () => {
            initmarcasValenciaTable();
        });

        // Detecta cambios en el contenido principal y reinicializa la tabla
        document.addEventListener('DOMContentLoaded', function() {
            var main = document.querySelector('main');
            if (main) {
                marcasValenciaTableObserver = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList') {
                            initmarcasValenciaTable();
                        }
                    });
                });
                marcasValenciaTableObserver.observe(main, { childList: true, subtree: true });
            }
        });
