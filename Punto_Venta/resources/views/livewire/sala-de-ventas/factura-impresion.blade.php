<div class="min-vh-100 bg-light">
    <div class="container-fluid">
        <!-- Barra de herramientas superior -->
        <div class="py-3 mb-3 bg-white row border-bottom">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-file-invoice"></i>
                        Factura {{ $factura->numero_factura }}
                    </h4>
                    <div class="btn-group">
                        <button onclick="window.print()" class="btn btn-primary btn-sm">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                        <a href="{{ route('factura.pdf', $factura->id) }}" class="btn btn-danger btn-sm">
                            <i class="fas fa-download"></i> Descargar PDF
                        </a>
                        @if($factura->factura_imagen)
                            <a href="{{ route('factura.imagen', $factura->id) }}" target="_blank" class="btn btn-info btn-sm">
                                <i class="fas fa-image"></i> Ver Imagen
                            </a>
                        @endif
                        <button onclick="history.back()" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Volver
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenido principal - Solo visor PDF -->
        <div class="row">
            <div class="col-12">
                <div class="card h-100">
                    <div class="text-white card-header bg-danger">
                        <h5 class="mb-0"><i class="fas fa-file-pdf"></i> Visor PDF</h5>
                    </div>
                    <div class="p-0 card-body">
                        <iframe
                            src="{{ route('factura.pdf.preview', $factura->id) }}"
                            width="100%"
                            height="700"
                            style="border: none; min-height: 85vh;"
                            id="pdfViewer">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estilos -->
<style>
    /* Loader para el iframe */
    #pdfViewer {
        background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="20" fill="none" stroke="%23007bff" stroke-width="4" stroke-dasharray="31.416" stroke-dashoffset="31.416"><animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/><animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/></circle></svg>') center center no-repeat;
        background-size: 50px 50px;
    }

    /* Altura mínima para el iframe */
    #pdfViewer {
        min-height: 85vh;
    }
</style>
