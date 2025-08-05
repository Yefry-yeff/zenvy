 var sucursalesTableObserver = null;
        function initsucursalesTable() {
            setTimeout(function() {
                var $table = $('#sucursalesTable');
                if ($table.length) {
                    $table.css('border', ''); // Quita el borde de depuración
                    if (!$.fn.DataTable.isDataTable($table)) {
                        if (sucursalesTableObserver) sucursalesTableObserver.disconnect();
                        $table.DataTable({
                            responsive: true,
                            language: {
                                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                            }
                        });
                        if (sucursalesTableObserver) sucursalesTableObserver.observe(document.querySelector('main'), { childList: true, subtree: true });
                    }
                }
            }, 300);
        }
        window.livewire && window.livewire.hook('message.processed', () => {
            initsucursalesTable();
        });

        // Detecta cambios en el contenido principal y reinicializa la tabla
        document.addEventListener('DOMContentLoaded', function() {
            var main = document.querySelector('main');
            if (main) {
                sucursalesTableObserver = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList') {
                            initsucursalesTable();
                        }
                    });
                });
                sucursalesTableObserver.observe(main, { childList: true, subtree: true });
            }
        });
