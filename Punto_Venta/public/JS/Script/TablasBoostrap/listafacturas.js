
        var listafacturasTableObserver = null;
        function initlistafacturasTable() {
            setTimeout(function() {
                var $table = $('#listafacturasTable');
                if ($table.length) {
                    $table.css('border', ''); // Quita el borde de depuración
                    
                    // Validar estructura de la tabla antes de inicializar DataTables
                    var theadCols = $table.find('thead tr th').length;
                    var tbodyFirstRowCols = $table.find('tbody tr:first td').length;
                    
                    console.log('Thead columns:', theadCols);
                    console.log('Tbody first row columns:', tbodyFirstRowCols);
                    
                    // Solo inicializar si la estructura es válida o si tbody está vacío
                    if (theadCols > 0 && (tbodyFirstRowCols === 0 || theadCols === tbodyFirstRowCols)) {
                        if (!$.fn.DataTable.isDataTable($table)) {
                            if (listafacturasTableObserver) listafacturasTableObserver.disconnect();
                            try {
                                $table.DataTable({
                                    responsive: true,
                                    language: {
                                        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                                    }
                                });
                                console.log('DataTable inicializada correctamente');
                            } catch (error) {
                                console.error('Error al inicializar DataTable:', error);
                            }
                            if (listafacturasTableObserver) listafacturasTableObserver.observe(document.querySelector('main'), { childList: true, subtree: true });
                        }
                    } else {
                        console.warn('Estructura de tabla inválida. Thead:', theadCols, 'cols, Tbody first row:', tbodyFirstRowCols, 'cols');
                    }
                }
            }, 500); // Aumentamos el delay para dar más tiempo a Livewire
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
