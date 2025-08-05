
        var municipiosTableObserver = null;
        function initMunicipiosTable() {
            setTimeout(function() {
                var $table = $('#municipiosTable');
                if ($table.length) {
                    $table.css('border', ''); // Quita el borde de depuración
                    if (!$.fn.DataTable.isDataTable($table)) {
                        if (municipiosTableObserver) municipiosTableObserver.disconnect();
                        $table.DataTable({
                            responsive: true,
                            language: {
                                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                            }
                        });
                        if (municipiosTableObserver) municipiosTableObserver.observe(document.querySelector('main'), { childList: true, subtree: true });
                    }
                }
            }, 300);
        }
        window.livewire && window.livewire.hook('message.processed', () => {
            initMunicipiosTable();
        });

        // Detecta cambios en el contenido principal y reinicializa la tabla
        document.addEventListener('DOMContentLoaded', function() {
            var main = document.querySelector('main');
            if (main) {
                municipiosTableObserver = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList') {
                            initMunicipiosTable();
                        }
                    });
                });
                municipiosTableObserver.observe(main, { childList: true, subtree: true });
            }
        });
