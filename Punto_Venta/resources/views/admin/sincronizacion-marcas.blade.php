@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">🔄 Panel de Sincronización de Marcas</h4>
                    <p class="card-subtitle text-muted">
                        Gestión y monitoreo de la sincronización con el sistema externo profac_app
                    </p>
                </div>
                
                <div class="card-body">
                    <!-- Estado de Conectividad -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-primary">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-network-wired"></i> Estado de Conectividad
                                    </h5>
                                    @if($conectividad['status'])
                                        <div class="alert alert-success">
                                            <i class="fas fa-check-circle"></i> 
                                            Conexión establecida con profac_app
                                        </div>
                                    @else
                                        <div class="alert alert-danger">
                                            <i class="fas fa-exclamation-triangle"></i> 
                                            {{ $conectividad['message'] }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-info">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-chart-bar"></i> Estadísticas
                                    </h5>
                                    <table class="table table-sm">
                                        <tr>
                                            <td>Marcas Locales:</td>
                                            <td><strong>{{ $estadisticas['marcas_locales'] ?? 'N/A' }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td>Marcas Externas:</td>
                                            <td><strong>{{ $estadisticas['marcas_externas'] ?? 'N/A' }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td>Cache:</td>
                                            <td>
                                                <span class="badge {{ $estadisticas['ultimo_cache'] === 'Activo' ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ $estadisticas['ultimo_cache'] ?? 'Inactivo' }}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones de Sincronización -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-warning">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-tools"></i> Acciones de Sincronización
                                    </h5>
                                    
                                    <div class="btn-group" role="group">
                                        <button type="button" 
                                                class="btn btn-primary"
                                                onclick="sincronizarMarcas(false)">
                                            <i class="fas fa-sync"></i> Sincronización Normal
                                        </button>
                                        
                                        <button type="button" 
                                                class="btn btn-warning"
                                                onclick="sincronizarMarcas(true)">
                                            <i class="fas fa-sync-alt"></i> Forzar Sincronización
                                        </button>
                                        
                                        <button type="button" 
                                                class="btn btn-info"
                                                onclick="limpiarCache()">
                                            <i class="fas fa-trash"></i> Limpiar Cache
                                        </button>
                                        
                                        <button type="button" 
                                                class="btn btn-secondary"
                                                onclick="window.location.reload()">
                                            <i class="fas fa-redo"></i> Refrescar Estado
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Marcas Recientes -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-list"></i> Marcas Sincronizadas Recientemente
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @if($marcasRecientes && $marcasRecientes->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Nombre</th>
                                                        <th>Origen</th>
                                                        <th>Última Actualización</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($marcasRecientes as $marca)
                                                        <tr>
                                                            <td>{{ $marca->id }}</td>
                                                            <td>{{ $marca->nombre }}</td>
                                                            <td>
                                                                <span class="badge bg-primary">
                                                                    Sistema Externo
                                                                </span>
                                                            </td>
                                                            <td>{{ $marca->updated_at ?? 'N/A' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-center text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <p>No hay marcas sincronizadas recientemente</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2 mb-0">Sincronizando marcas...</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function sincronizarMarcas(forzar = false) {
    // Mostrar modal de carga
    const modal = new bootstrap.Modal(document.getElementById('loadingModal'));
    modal.show();
    
    const url = forzar ? '{{ route("sincronizacion.forzar") }}' : '{{ route("sincronizacion.normal") }}';
    
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        modal.hide();
        
        if (data.success) {
            toastr.success(data.message);
            setTimeout(() => window.location.reload(), 1500);
        } else {
            toastr.error(data.message);
        }
    })
    .catch(error => {
        modal.hide();
        toastr.error('Error en la sincronización: ' + error.message);
    });
}

function limpiarCache() {
    fetch('{{ route("sincronizacion.limpiar-cache") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toastr.success('Cache limpiado exitosamente');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            toastr.error('Error al limpiar cache');
        }
    })
    .catch(error => {
        toastr.error('Error: ' + error.message);
    });
}
</script>
@endpush
@endsection
