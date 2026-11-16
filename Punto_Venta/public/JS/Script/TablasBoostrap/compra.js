 /*var comprasTableObserver = null;
        function initComprasTable() {
            setTimeout(function() {
                var $table = $('#comprasTabla');
                if ($table.length) {
                    $table.css('border', ''); // Quita el borde de depuración
                    // Verificar si la tabla tiene datos reales (no solo el mensaje de "no hay datos")
                    var $dataRows = $table.find('tbody tr').not(':contains("No hay compras disponibles")').not(':contains("No hay")').not('.text-muted');
                    var hasData = $dataRows.length > 0 && $dataRows.find('td').length >= 3;

                    if (hasData && !$.fn.DataTable.isDataTable($table)) {
                        if (comprasTableObserver) comprasTableObserver.disconnect();
                        $table.DataTable({
                            responsive: true,
                            language: {
                                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                            }
                        });
                        if (comprasTableObserver) comprasTableObserver.observe(document.querySelector('main'), { childList: true, subtree: true });
                    }
                }
            }, 300);
        }
        window.livewire && window.livewire.hook('message.processed', () => {
            initComprasTable();
        });

        // Detecta cambios en el contenido principal y reinicializa la tabla
        document.addEventListener('DOMContentLoaded', function() {
            var main = document.querySelector('main');
            if (main) {
                comprasTableObserver = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList') {
                            initComprasTable();
                        }
                    });
                });
                comprasTableObserver.observe(main, { childList: true, subtree: true });
            }
        });*/
