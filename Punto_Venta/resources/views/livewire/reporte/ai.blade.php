<div class="container-fluid p-4">
    <style>
        .markdown-content {
            font-size: 15px;
            line-height: 1.6;
        }

        .markdown-content h1,
        .markdown-content h2,
        .markdown-content h3 {
            margin-top: 1rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .markdown-content table {
            width: 100%;
            margin: 1rem 0;
            border-collapse: collapse;
        }

        .markdown-content table th,
        .markdown-content table td {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
        }

        .markdown-content table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .markdown-content pre {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            overflow-x: auto;
        }

        .markdown-content code {
            background-color: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }

        .markdown-content ul,
        .markdown-content ol {
            padding-left: 2rem;
        }

        /* Animación de carga personalizada */
        .loading-dots {
            display: inline-flex;
            gap: 4px;
        }

        .loading-dots span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #0d6efd;
            animation: bounce 1.4s infinite ease-in-out both;
        }

        .loading-dots span:nth-child(1) {
            animation-delay: -0.32s;
        }

        .loading-dots span:nth-child(2) {
            animation-delay: -0.16s;
        }

        @keyframes bounce {
            0%, 80%, 100% {
                transform: scale(0);
            }
            40% {
                transform: scale(1);
            }
        }

        .pulse-animation {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: .5;
            }
        }
    </style>

    <div class="row">
        <!-- Panel Principal -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-robot me-2"></i>
                        Reportes con Inteligencia Artificial
                    </h4>
                    <small>Genera reportes personalizados usando lenguaje natural</small>
                </div>
                <div class="card-body">
                    <!-- Área de Consulta -->
                    <div class="mb-4">
                        <label for="prompt" class="form-label fw-bold">¿Qué deseas saber?</label>
                        <textarea
                            wire:model="prompt"
                            id="prompt"
                            class="form-control @error('prompt') is-invalid @enderror"
                            rows="4"
                            placeholder="Ejemplo: Dame un reporte de las ventas del último mes agrupadas por día"
                            @if($cargando) disabled @endif
                        ></textarea>
                        @error('prompt')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Botones de Acción -->
                    <div class="d-flex gap-2 mb-4">
                        <button
                            wire:click="generarReporte"
                            class="btn btn-primary"
                            @if($cargando) disabled @endif
                        >
                            @if($cargando)
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Generando reporte...
                            @else
                                <i class="fas fa-magic me-2"></i>
                                Generar Reporte
                            @endif
                        </button>
                        <button
                            wire:click="limpiar"
                            class="btn btn-outline-secondary"
                            @if($cargando) disabled @endif
                        >
                            <i class="fas fa-eraser me-2"></i>
                            Limpiar
                        </button>
                    </div>

                    <!-- Indicador de Carga -->
                    @if($cargando)
                        <div class="card border-primary shadow-sm mb-4">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="spinner-border text-primary me-3" role="status" style="width: 3rem; height: 3rem;">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="mb-2">
                                            <strong>Generando tu reporte</strong>
                                            <span class="loading-dots ms-2">
                                                <span></span>
                                                <span></span>
                                                <span></span>
                                            </span>
                                        </h5>
                                        <p class="mb-1 text-muted">
                                            <i class="fas fa-brain me-2"></i>
                                            La IA está analizando tu consulta
                                        </p>
                                        <p class="mb-0 text-muted">
                                            <i class="fas fa-database me-2"></i>
                                            Obteniendo datos de la base de datos
                                        </p>
                                    </div>
                                </div>
                                <div class="progress mt-3" style="height: 4px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                         role="progressbar" 
                                         style="width: 100%"
                                         aria-valuenow="100" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Ejemplos de Consultas -->
                    <div class="alert alert-info">
                        <strong><i class="fas fa-lightbulb me-2"></i>Ejemplos de consultas (con tablas descargables):</strong>
                        <ul class="mb-0 mt-2">
                            <li>Dame los productos más vendidos del último mes con sus cantidades</li>
                            <li>Muestra las ventas del día de hoy con detalle</li>
                            <li>Lista los clientes con más compras este año</li>
                            <li>¿Qué productos tienen stock bajo? Muestra una tabla</li>
                            <li>Reporte de ventas por método de pago de esta semana</li>
                            <li>Top 20 productos por ingresos generados</li>
                        </ul>
                        <small class="text-muted d-block mt-2">
                            💡 <strong>Tip:</strong> Los reportes se generan automáticamente como tablas y puedes descargarlos en Excel
                        </small>
                    </div>

                    <!-- Mensaje de Error -->
                    @if($error)
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            {{ $error }}
                            <button type="button" class="btn-close" wire:click="$set('error', '')"></button>
                        </div>
                    @endif

                    <!-- Respuesta de la AI -->
                    @if($respuesta && !$cargando)
                        <div class="card border-success mt-4" id="resultado-reporte">
                            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Resultado
                                </h5>
                                @if($datosTabla)
                                    <button wire:click="descargarExcel" class="btn btn-light btn-sm">
                                        <i class="fas fa-file-excel me-1"></i>
                                        Descargar Excel
                                    </button>
                                @endif
                            </div>
                            <div class="card-body">
                                <!-- Solo mostrar respuesta si hay texto además del SQL -->
                                @if(trim(preg_replace('/```sql.*?```/s', '', $respuesta)))
                                    <div class="markdown-content mb-3">
                                        {!! \Illuminate\Support\Str::markdown(preg_replace('/```sql.*?```/s', '', $respuesta)) !!}
                                    </div>
                                @endif

                                <!-- Tabla de Datos si existe -->
                                @if($datosTabla && count($datosTabla) > 0)
                                    <div>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="text-primary mb-0">
                                                <i class="fas fa-table me-2"></i>
                                                {{ count($datosTabla) }} registro{{ count($datosTabla) != 1 ? 's' : '' }} encontrado{{ count($datosTabla) != 1 ? 's' : '' }}
                                            </h6>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover table-bordered table-sm">
                                                <thead class="table-dark">
                                                    <tr>
                                                        @foreach(array_keys($datosTabla[0]) as $columna)
                                                            <th class="text-nowrap">{{ ucfirst(str_replace('_', ' ', $columna)) }}</th>
                                                        @endforeach
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($datosTabla as $fila)
                                                        <tr>
                                                            @foreach($fila as $valor)
                                                                <td>
                                                                    @if(is_numeric($valor) && !is_string($valor))
                                                                        {{ number_format($valor, 2) }}
                                                                    @else
                                                                        {{ $valor }}
                                                                    @endif
                                                                </td>
                                                            @endforeach
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @elseif(!$datosTabla)
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        {!! \Illuminate\Support\Str::markdown($respuesta) !!}
                                    </div>
                                @else
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        No se encontraron datos para esta consulta.
                                    </div>
                                @endif
                            </div>
                        </div>

                        <script>
                            // Auto-scroll hacia la tabla cuando se genere
                            document.addEventListener('DOMContentLoaded', function() {
                                const resultadoElemento = document.getElementById('resultado-reporte');
                                if (resultadoElemento) {
                                    setTimeout(() => {
                                        resultadoElemento.scrollIntoView({ 
                                            behavior: 'smooth', 
                                            block: 'start'
                                        });
                                    }, 100);
                                }
                            });

                            // También aplicar scroll cuando Livewire actualice
                            Livewire.hook('message.processed', (message, component) => {
                                const resultadoElemento = document.getElementById('resultado-reporte');
                                if (resultadoElemento) {
                                    setTimeout(() => {
                                        resultadoElemento.scrollIntoView({ 
                                            behavior: 'smooth', 
                                            block: 'start'
                                        });
                                    }, 100);
                                }
                            });
                        </script>
                    @endif
                </div>
            </div>
        </div>

        <!-- Panel de Historial -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        Historial de Consultas
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if(count($historial) > 0)
                        <div class="list-group list-group-flush">
                            @foreach($historial as $consulta)
                                <a
                                    href="#"
                                    wire:click.prevent="usarHistorial({{ $consulta->id }})"
                                    class="list-group-item list-group-item-action"
                                >
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1 text-truncate" style="max-width: 250px;">
                                            {{ \Illuminate\Support\Str::limit($consulta->pregunta, 50) }}
                                        </h6>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($consulta->created_at)->diffForHumans() }}
                                        </small>
                                    </div>
                                    @if($consulta->script ?? null)
                                        <small class="text-success">
                                            <i class="fas fa-code me-1"></i>
                                            Con consulta SQL
                                        </small>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No hay consultas previas</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Tarjeta de Configuración -->
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-cog me-2"></i>
                        Configuración
                    </h6>
                </div>
                <div class="card-body">
                    <p class="small mb-2">
                        <strong>API:</strong> Groq (Llama 3.3 70B)
                    </p>
                    <p class="small mb-2">
                        <strong>Estado:</strong>
                        @if(env('GROQ_API_KEY'))
                            <span class="badge bg-success">Configurado</span>
                        @else
                            <span class="badge bg-danger">No configurado</span>
                        @endif
                    </p>
                    @if(!env('GROQ_API_KEY'))
                        <hr>
                        <div class="alert alert-warning small mb-0">
                            <strong>Configuración requerida:</strong>
                            <ol class="mb-0 ps-3">
                                <li>Visita <a href="https://console.groq.com" target="_blank">console.groq.com</a></li>
                                <li>Crea una cuenta gratuita</li>
                                <li>Genera una API Key</li>
                                <li>Agrega en .env:<br>
                                    <code>GROQ_API_KEY=tu_key_aqui</code>
                                </li>
                            </ol>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
