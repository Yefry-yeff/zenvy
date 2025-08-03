var CaiTableObserver = null;
function initCaiTable() {
    setTimeout(function() {
        var $table = $('#tbl_cai'); // id corregido
        if ($table.length) {
            $table.css('border', '');
            if (!$.fn.DataTable.isDataTable($table)) {
                if (CaiTableObserver) CaiTableObserver.disconnect();
                $table.DataTable({
                    responsive: true,
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                    }
                });
                if (CaiTableObserver) CaiTableObserver.observe(document.querySelector('main'), { childList: false, subtree: true });
            }
        }
    }, 300);
}
window.livewire && window.livewire.hook('message.processed', () => {
    initCaiTable();
});

// Detecta cambios en el contenido principal y reinicializa la tabla
document.addEventListener('DOMContentLoaded', function() {
    var main = document.querySelector('main');
    if (main) {
        CaiTableObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    initCaiTable(); // también corregido
                }
            });
        });
        CaiTableObserver.observe(main, { childList: true, subtree: true });
    }
});
