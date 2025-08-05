 var productosSeccionTableObserver = null;

function initProductosSeccionTable() {
    setTimeout(function() {
        var $table = $('#productosSeccionTable');
        if ($table.length && $table.find('tbody tr').length > 0) {
            $table.css('border', ''); // Quita el borde de depuración
            
            // Destruir tabla existente si existe
            if ($.fn.DataTable.isDataTable($table)) {
                $table.DataTable().destroy();
            }
            
            if (productosSeccionTableObserver) {
                productosSeccionTableObserver.disconnect();
            }
            
            // Configuración completa de DataTables
            $table.DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                order: [[7, 'desc']], // Ordenar por fecha de recibido descendente
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                },
                columnDefs: [
                    { targets: [0], width: '5%', className: 'text-center' }, // ID
                    { targets: [1], width: '20%' }, // Producto
                    { targets: [2], width: '10%', className: 'text-center' }, // Código
                    { targets: [3], width: '10%' }, // Marca
                    { targets: [4], width: '12%' }, // Categoría
                    { targets: [5], width: '8%', className: 'text-center' }, // U. Medida
                    { targets: [6], width: '8%', className: 'text-center', orderable: true, type: 'num' }, // Stock
                    { targets: [7], width: '10%', className: 'text-center', orderable: true, type: 'date' }, // F. Recibido
                    { targets: [8], width: '10%', className: 'text-center', orderable: true, type: 'date' }, // F. Expiración
                    { targets: [9], width: '10%', className: 'text-end', orderable: true, type: 'num' }, // Precio
                    { targets: [10], width: '7%', className: 'text-center', orderable: false } // Estado
                ],
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="fas fa-file-excel"></i> Excel',
                        className: 'btn btn-success btn-sm me-1',
                        title: 'Productos por Sección',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                            format: {
                                body: function(data, row, column, node) {
                                    // Limpiar HTML de los datos para exportación
                                    return $('<div>').html(data).text();
                                }
                            }
                        },
                        customize: function(xlsx) {
                            var sheet = xlsx.xl.worksheets['sheet1.xml'];
                            // Agregar estilos personalizados si es necesario
                        }
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fas fa-file-pdf"></i> PDF',
                        className: 'btn btn-danger btn-sm me-1',
                        title: 'Productos por Sección',
                        orientation: 'landscape',
                        pageSize: 'A4',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                            format: {
                                body: function(data, row, column, node) {
                                    return $('<div>').html(data).text();
                                }
                            }
                        },
                        customize: function(doc) {
                            // Personalizar el PDF
                            doc.content[1].table.widths = ['5%', '20%', '10%', '10%', '12%', '8%', '8%', '10%', '10%', '10%', '7%'];
                            doc.styles.tableHeader.fontSize = 8;
                            doc.defaultStyle.fontSize = 7;
                            doc.content[0].text = 'Reporte de Productos por Sección';
                            doc.content[0].style = 'header';
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="fas fa-print"></i> Imprimir',
                        className: 'btn btn-info btn-sm me-1',
                        title: 'Productos por Sección',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
                        }
                    },
                    {
                        text: '<i class="fas fa-sync-alt"></i> Actualizar',
                        className: 'btn btn-secondary btn-sm',
                        action: function(e, dt, node, config) {
                            // Recargar la tabla
                            if (window.Livewire) {
                                window.Livewire.emit('$refresh');
                            } else {
                                dt.ajax.reload();
                            }
                        }
                    }
                ],
                search: {
                    smart: true,
                    caseInsensitive: true
                },
                searchBuilder: {
                    columns: [1, 2, 3, 4, 6, 7, 8, 9] // Permitir búsqueda avanzada en estas columnas
                },
                initComplete: function(settings, json) {
                    console.log('Tabla de productos por sección inicializada correctamente');
                    
                    // Agregar búsqueda individual por columna
                    this.api().columns([1, 2, 3, 4]).every(function() {
                        var column = this;
                        var columnIndex = column.index();
                        var columnName = column.header().textContent.trim();
                        
                        if (columnIndex <= 4) { // Solo para las primeras columnas
                            var select = $('<select class="form-select form-select-sm"><option value="">' + columnName + '</option></select>')
                                .appendTo($(column.footer()).empty())
                                .on('change', function() {
                                    var val = $.fn.dataTable.util.escapeRegex($(this).val());
                                    column.search(val ? '^' + val + '$' : '', true, false).draw();
                                });
                            
                            column.data().unique().sort().each(function(d, j) {
                                var cleanData = $('<div>').html(d).text().trim();
                                if (cleanData && cleanData !== 'N/A') {
                                    select.append('<option value="' + cleanData + '">' + cleanData + '</option>');
                                }
                            });
                        }
                    });
                },
                drawCallback: function(settings) {
                    // Re-inicializar tooltips después de cada redraw
                    $('[data-bs-toggle="tooltip"]').tooltip();
                },
                footerCallback: function(row, data, start, end, display) {
                    var api = this.api();
                    
                    // Calcular totales
                    var totalStock = api.column(6, { page: 'current' }).data()
                        .reduce(function(a, b) {
                            var val = parseInt($(b).text()) || 0;
                            return a + val;
                        }, 0);
                    
                    // Mostrar totales en el footer
                    $(api.column(6).footer()).html(
                        '<strong>Total: ' + totalStock.toLocaleString() + '</strong>'
                    );
                }
            });
            
            // Observer para detectar cambios
            if (productosSeccionTableObserver) {
                productosSeccionTableObserver.observe(document.querySelector('main'), { 
                    childList: true, 
                    subtree: true 
                });
            }
        }
    }, 300);
}

// Hook para Livewire
if (window.livewire) {
    window.livewire.hook('message.processed', () => {
        initProductosSeccionTable();
    });
}

// Detecta cambios en el contenido principal y reinicializa la tabla
document.addEventListener('DOMContentLoaded', function() {
    var main = document.querySelector('main');
    if (main) {
        productosSeccionTableObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    // Verificar si la tabla de productos por sección está presente
                    if (document.getElementById('productosSeccionTable')) {
                        initProductosSeccionTable();
                    }
                }
            });
        });
        productosSeccionTableObserver.observe(main, { 
            childList: true, 
            subtree: true 
        });
    }
    
    // Inicializar inmediatamente si la tabla ya existe
    initProductosSeccionTable();
});

// Función para limpiar cuando se cambie de vista
window.addEventListener('beforeunload', function() {
    if (productosSeccionTableObserver) {
        productosSeccionTableObserver.disconnect();
    }
});

// Exponer función globalmente para uso manual si es necesario
window.initProductosSeccionTable = initProductosSeccionTable;
