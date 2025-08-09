
        var listafacturasTableObserver = null;
        function initlistafacturasTable() {
            setTimeout(function() {
                var $table = $('#listafacturasTable');
                if ($table.length) {
                    $table.css('border', ''); // Quita el borde de depuración
                    if (!$.fn.DataTable.isDataTable($table)) {
                        if (listafacturasTableObserver) listafacturasTableObserver.disconnect();
                        $table.DataTable({
                            responsive: true,
                            language: {
                                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                            }
                        });
                        if (listafacturasTableObserver) listafacturasTableObserver.observe(document.querySelector('main'), { childList: true, subtree: true });
                    }
                }
            }, 300);
        }
        window.livewire && window.livewire.hook('message.processed', () => {
            initlistafacturasTable();
        });

        // Detecta cambios en el contenido principal y reinicializa la tabla
        document.addEventListener('DOMContentLoaded', function() {
            var main = document.querySelector('main');
            if (main) {
                listafacturasTableObserver = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList') {
                            initlistafacturasTable();
                        }
                    });
                });
                listafacturasTableObserver.observe(main, { childList: true, subtree: true });
            }
        });
