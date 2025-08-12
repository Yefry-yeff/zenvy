 var clientesTableObserver = null;
        function initClientesTable() {
            setTimeout(function() {
                var $table = $('#clientesTable');
                if ($table.length) {
                    $table.css('border', ''); // Quita el borde de depuración
                    // Verificar si la tabla tiene datos reales (no solo el mensaje de "no hay datos")
                    var $dataRows = $table.find('tbody tr').not(':contains("No hay clientes disponibles")').not(':contains("No hay")').not('.text-muted');
                    var hasData = $dataRows.length > 0 && $dataRows.find('td').length >= 3;

                    if (hasData && !$.fn.DataTable.isDataTable($table)) {
                        if (clientesTableObserver) clientesTableObserver.disconnect();
                        $table.DataTable({
                            responsive: true,
                            language: {
                                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                            }
                        });
                        if (clientesTableObserver) clientesTableObserver.observe(document.querySelector('main'), { childList: true, subtree: true });
                    }
                }
            }, 300);
        }
        window.livewire && window.livewire.hook('message.processed', () => {
            initClientesTable();
        });

        // Detecta cambios en el contenido principal y reinicializa la tabla
        document.addEventListener('DOMContentLoaded', function() {
            var main = document.querySelector('main');
            if (main) {
                clientesTableObserver = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList') {
                            initClientesTable();
                        }
                    });
                });
                clientesTableObserver.observe(main, { childList: true, subtree: true });
            }
        });
