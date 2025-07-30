
        var marcasTableObserver = null;
        function initMarcasTable() {
            setTimeout(function() {
                var $table = $('#marcasTable');
                if ($table.length) {
                    $table.css('border', ''); // Quita el borde de depuración
                    if (!$.fn.DataTable.isDataTable($table)) {
                        if (marcasTableObserver) marcasTableObserver.disconnect();
                        $table.DataTable({
                            responsive: true,
                            language: {
                                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                            }
                        });
                        if (marcasTableObserver) marcasTableObserver.observe(document.querySelector('main'), { childList: true, subtree: true });
                    }
                }
            }, 300);
        }
        window.livewire && window.livewire.hook('message.processed', () => {
            initMarcasTable();
        });

        // Detecta cambios en el contenido principal y reinicializa la tabla
        document.addEventListener('DOMContentLoaded', function() {
            var main = document.querySelector('main');
            if (main) {
                marcasTableObserver = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList') {
                            initMarcasTable();
                        }
                    });
                });
                marcasTableObserver.observe(main, { childList: true, subtree: true });
            }
        });
