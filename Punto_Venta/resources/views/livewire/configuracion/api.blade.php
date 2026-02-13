<div class="p-6">
    {{-- Sistema de Notificaciones --}}
    <div class="fixed top-4 right-4 z-50" x-data="{ notifications: [] }" 
         @notify.window="
            let notif = { id: Date.now(), ...$event.detail };
            notifications.push(notif);
            setTimeout(() => { 
                notifications = notifications.filter(n => n.id !== notif.id);
            }, 5000);
         ">
        <template x-for="notif in notifications" :key="notif.id">
            <div x-show="true" 
                 x-transition:enter="transform ease-out duration-300 transition"
                 x-transition:enter-start="translate-y-2 opacity-0"
                 x-transition:enter-end="translate-y-0 opacity-100"
                 :class="{
                    'bg-green-100 border-green-500 text-green-700': notif.type === 'success',
                    'bg-red-100 border-red-500 text-red-700': notif.type === 'error',
                    'bg-yellow-100 border-yellow-500 text-yellow-700': notif.type === 'warning',
                    'bg-blue-100 border-blue-500 text-blue-700': notif.type === 'info'
                 }"
                 class="mb-3 p-4 rounded-lg border-l-4 shadow-lg max-w-md">
                <p x-text="notif.message" class="font-semibold"></p>
            </div>
        </template>
    </div>
    
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            Configuración de APIs y Webhooks
        </h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            Gestiona las integraciones de Zenvy con servicios externos
        </p>
    </div>

    {{-- Estado de Conexión --}}
    @if($estadoConexion)
    <div class="mb-6 p-4 rounded-lg border-l-4 
        @if($estadoConexion['estado'] === 'conectado') bg-green-50 border-green-500 dark:bg-green-900/20
        @elseif($estadoConexion['estado'] === 'error') bg-red-50 border-red-500 dark:bg-red-900/20
        @elseif($estadoConexion['estado'] === 'no_configurado') bg-gray-50 border-gray-500 dark:bg-gray-900/20
        @else bg-yellow-50 border-yellow-500 dark:bg-yellow-900/20
        @endif
    ">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-3 h-3 rounded-full animate-pulse
                    @if($estadoConexion['estado'] === 'conectado') bg-green-500
                    @elseif($estadoConexion['estado'] === 'error') bg-red-500
                    @else bg-yellow-500
                    @endif
                "></div>
                <div>
                    <p class="font-semibold text-gray-800 dark:text-white">
                        {{ $estadoConexion['mensaje'] }}
                    </p>
                    @if($ultimoEnvio && $ultimoEnvio['fecha'])
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Último envío: {{ $ultimoEnvio['fecha'] }} - {{ $ultimoEnvio['evento'] }}
                    </p>
                    @endif
                </div>
            </div>
            <button wire:click="verificarEstadoConexion" 
                    class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                🔄 Verificar
            </button>
        </div>
    </div>
    @endif

    {{-- Tabs --}}
    <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8">
            <button wire:click="cambiarTab('webhooks')" 
                    class="py-4 px-1 border-b-2 font-medium text-sm transition
                    @if($tabActivo === 'webhooks') border-blue-500 text-blue-600 dark:text-blue-400
                    @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300
                    @endif">
                📡 Webhooks
            </button>
            <button wire:click="cambiarTab('estadisticas')" 
                    class="py-4 px-1 border-b-2 font-medium text-sm transition
                    @if($tabActivo === 'estadisticas') border-blue-500 text-blue-600 dark:text-blue-400
                    @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300
                    @endif">
                📊 Estadísticas
            </button>
            <button wire:click="cambiarTab('logs')" 
                    class="py-4 px-1 border-b-2 font-medium text-sm transition
                    @if($tabActivo === 'logs') border-blue-500 text-blue-600 dark:text-blue-400
                    @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300
                    @endif">
                📋 Logs
            </button>
            <button wire:click="cambiarTab('api-keys')" 
                    class="py-4 px-1 border-b-2 font-medium text-sm transition
                    @if($tabActivo === 'api-keys') border-blue-500 text-blue-600 dark:text-blue-400
                    @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300
                    @endif">
                🔑 API Keys
            </button>
            <button wire:click="cambiarTab('documentacion')" 
                    class="py-4 px-1 border-b-2 font-medium text-sm transition
                    @if($tabActivo === 'documentacion') border-blue-500 text-blue-600 dark:text-blue-400
                    @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300
                    @endif">
                📚 Documentación
            </button>
        </nav>
    </div>

    {{-- Contenido de Tabs --}}
    <div class="space-y-6">
        
        {{-- Tab: Webhooks Configuration --}}
        @if($tabActivo === 'webhooks')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Configuración de Webhooks
            </h3>

            <div class="space-y-4">
                {{-- Webhook Enabled --}}
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Estado del Webhook
                        </label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Activar o desactivar el envío de webhooks
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="webhook_enabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                {{-- Webhook URL --}}
                <div>
                    <label for="webhook_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        URL del Webhook
                    </label>
                    <input type="url" 
                           id="webhook_url"
                           wire:model="webhook_url" 
                           placeholder="https://tu-ecommerce.com/api/webhooks/inventario"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @error('webhook_url')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Webhook Token --}}
                <div>
                    <label for="webhook_token" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Token de Autenticación
                    </label>
                    <div class="flex gap-2">
                        <input type="password" 
                               id="webhook_token"
                               wire:model="webhook_token" 
                               placeholder="Token secreto para autenticación"
                               class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <button type="button" 
                                wire:click="generarToken"
                                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition flex items-center gap-2">
                            🔑 Generar
                        </button>
                    </div>
                    @error('webhook_token')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Advanced Settings --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="webhook_timeout" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Timeout (segundos)
                        </label>
                        <input type="number" 
                               id="webhook_timeout"
                               wire:model="webhook_timeout" 
                               min="1" max="30"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="webhook_retry" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Reintentos en caso de fallo
                        </label>
                        <input type="number" 
                               id="webhook_retry"
                               wire:model="webhook_retry_attempts" 
                               min="1" max="5"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex gap-3 pt-4">
                    <button wire:click="guardarConfiguracion" 
                            class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Guardar Configuración
                    </button>
                    <button wire:click="probarWebhook" 
                            wire:loading.attr="disabled"
                            class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium flex items-center gap-2 disabled:opacity-50">
                        <span wire:loading.remove wire:target="probarWebhook">🧪</span>
                        <span wire:loading wire:target="probarWebhook">⏳</span>
                        Probar Conexión
                    </button>
                </div>

                {{-- Resultado de Prueba --}}
                @if($resultadoPrueba)
                <div class="mt-4 p-4 rounded-lg border-l-4 
                    @if($resultadoPrueba['success']) bg-green-50 border-green-500 dark:bg-green-900/20
                    @else bg-red-50 border-red-500 dark:bg-red-900/20
                    @endif">
                    <p class="font-semibold text-gray-800 dark:text-white mb-2">
                        {{ $resultadoPrueba['mensaje'] }}
                    </p>
                    @if(isset($resultadoPrueba['status']))
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Status HTTP: {{ $resultadoPrueba['status'] }}
                        </p>
                    @endif
                    @if(isset($resultadoPrueba['tiempo']))
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Tiempo de respuesta: {{ round($resultadoPrueba['tiempo'] * 1000, 2) }}ms
                        </p>
                    @endif
                    @if(isset($resultadoPrueba['error']))
                        <pre class="mt-2 p-2 bg-red-100 dark:bg-red-900/30 rounded text-xs overflow-x-auto">{{ $resultadoPrueba['error'] }}</pre>
                    @endif
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Tab: Estadísticas --}}
        @if($tabActivo === 'estadisticas')
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {{-- Total Enviados --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Enviados</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            {{ number_format($estadisticas['total_enviados'] ?? 0) }}
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Exitosos --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Exitosos</p>
                        <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">
                            {{ number_format($estadisticas['exitosos'] ?? 0) }}
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Fallidos --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Fallidos</p>
                        <p class="text-3xl font-bold text-red-600 dark:text-red-400 mt-2">
                            {{ number_format($estadisticas['fallidos'] ?? 0) }}
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Última Hora --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Última Hora</p>
                        <p class="text-3xl font-bold text-purple-600 dark:text-purple-400 mt-2">
                            {{ number_format($estadisticas['ultima_hora'] ?? 0) }}
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Tiempo Promedio --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Tiempo Promedio</p>
                        <p class="text-3xl font-bold text-orange-600 dark:text-orange-400 mt-2">
                            {{ number_format($estadisticas['promedio_tiempo'] ?? 0, 2) }}s
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Tasa de Éxito --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Tasa de Éxito</p>
                        <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400 mt-2">
                            @php
                                $total = ($estadisticas['total_enviados'] ?? 0);
                                $exitosos = ($estadisticas['exitosos'] ?? 0);
                                $tasa = $total > 0 ? ($exitosos / $total) * 100 : 0;
                            @endphp
                            {{ number_format($tasa, 1) }}%
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Tab: Logs --}}
        @if($tabActivo === 'logs')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                    📋 Registro de Actividad
                </h3>
                <div class="flex gap-2">
                    <button wire:click="cargarLogs" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        🔄 Recargar
                    </button>
                    <button wire:click="limpiarLogs" 
                            onclick="return confirm('¿Está seguro de limpiar todos los logs?')"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        🗑️ Limpiar
                    </button>
                </div>
            </div>

            @if(empty($logs))
                <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p>No hay logs disponibles</p>
                    <button wire:click="cargarLogs" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Cargar Logs
                    </button>
                </div>
            @else
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @foreach($logs as $log)
                        <div class="p-4 rounded-lg border-l-4 
                            @if($log['tipo'] === 'error') bg-red-50 border-red-500 dark:bg-red-900/20
                            @elseif($log['tipo'] === 'warning') bg-yellow-50 border-yellow-500 dark:bg-yellow-900/20
                            @elseif($log['tipo'] === 'success') bg-green-50 border-green-500 dark:bg-green-900/20
                            @else bg-blue-50 border-blue-500 dark:bg-blue-900/20
                            @endif">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                                            {{ $log['fecha'] }}
                                        </span>
                                        @if($log['tipo'] === 'success')
                                            <span class="px-2 py-0.5 text-xs rounded-full bg-green-200 text-green-800 dark:bg-green-800 dark:text-green-200">
                                                ✅ Exitoso
                                            </span>
                                        @elseif($log['tipo'] === 'error')
                                            <span class="px-2 py-0.5 text-xs rounded-full bg-red-200 text-red-800 dark:bg-red-800 dark:text-red-200">
                                                ❌ Error
                                            </span>
                                        @elseif($log['tipo'] === 'warning')
                                            <span class="px-2 py-0.5 text-xs rounded-full bg-yellow-200 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200">
                                                ⚠️ Advertencia
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 font-mono mb-2">
                                        {{ $log['mensaje'] }}
                                    </p>
                                    @if(isset($log['detalles']) && !empty($log['detalles']))
                                        <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-600">
                                            <div class="grid grid-cols-2 gap-2 text-xs">
                                                @if(isset($log['detalles']['url']))
                                                <div>
                                                    <span class="text-gray-500 dark:text-gray-400">URL:</span>
                                                    <span class="text-gray-700 dark:text-gray-300 font-mono ml-1">
                                                        {{ Str::limit($log['detalles']['url'], 50) }}
                                                    </span>
                                                </div>
                                                @endif
                                                @if(isset($log['detalles']['intentos']))
                                                <div>
                                                    <span class="text-gray-500 dark:text-gray-400">Intentos:</span>
                                                    <span class="text-gray-700 dark:text-gray-300 ml-1">
                                                        {{ $log['detalles']['intentos'] }}
                                                    </span>
                                                </div>
                                                @endif
                                                @if(isset($log['detalles']['tiempo_respuesta']))
                                                <div>
                                                    <span class="text-gray-500 dark:text-gray-400">Tiempo:</span>
                                                    <span class="text-gray-700 dark:text-gray-300 ml-1">
                                                        {{ $log['detalles']['tiempo_respuesta'] }}
                                                    </span>
                                                </div>
                                                @endif
                                            </div>
                                            @if(isset($log['detalles']['error']) && $log['detalles']['error'])
                                            <div class="mt-2">
                                                <span class="text-gray-500 dark:text-gray-400 text-xs">Error:</span>
                                                <p class="text-xs text-red-600 dark:text-red-400 font-mono mt-1 p-2 bg-red-100 dark:bg-red-900/30 rounded">
                                                    {{ $log['detalles']['error'] }}
                                                </p>
                                            </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        @endif

        {{-- Tab: API Keys --}}
        @if($tabActivo === 'api-keys')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                        🔑 API Clients
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Gestiona los clientes API para integraciones externas
                    </p>
                </div>
            </div>

            {{-- Crear Nuevo API Client --}}
            <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <h4 class="font-semibold text-gray-800 dark:text-white mb-3">➕ Crear Nuevo API Client</h4>
                <div class="flex gap-3">
                    <input type="text" 
                           wire:model="nuevo_client_nombre" 
                           placeholder="Nombre del cliente (ej: Integración Ecommerce)"
                           class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           @if($generando_client) disabled @endif>
                    <button wire:click="crearApiClient" 
                            wire:loading.attr="disabled"
                            @if($generando_client) disabled @endif
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <span wire:loading.remove wire:target="crearApiClient">🔑 Crear Client</span>
                        <span wire:loading wire:target="crearApiClient">⏳ Creando...</span>
                    </button>
                </div>
                @error('nuevo_client_nombre')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Mostrar Credenciales Generadas --}}
            @if($client_generado)
            <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                <h4 class="font-semibold text-green-800 dark:text-green-300 mb-3">✅ Credenciales Generadas para "{{ $client_generado['name'] }}"</h4>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">API Key:</label>
                        <div class="flex gap-2 mt-1">
                            <code class="flex-1 text-xs bg-white dark:bg-gray-800 border border-green-300 dark:border-green-700 px-3 py-2 rounded font-mono text-gray-900 dark:text-white">
                                {{ $client_generado['api_key'] }}
                            </code>
                            <button onclick="navigator.clipboard.writeText('{{ $client_generado['api_key'] }}')"
                                    class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-xs">
                                📋 Copiar
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">API Secret:</label>
                        <div class="flex gap-2 mt-1">
                            <code class="flex-1 text-xs bg-white dark:bg-gray-800 border border-green-300 dark:border-green-700 px-3 py-2 rounded font-mono text-gray-900 dark:text-white">
                                {{ $client_generado['api_secret'] }}
                            </code>
                            <button onclick="navigator.clipboard.writeText('{{ $client_generado['api_secret'] }}')"
                                    class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-xs">
                                📋 Copiar
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-green-700 dark:text-green-400 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        Guarda estas credenciales en un lugar seguro. No se mostrarán de nuevo.
                    </p>
                    <button wire:click="$set('client_generado', null)" 
                            class="w-full px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 text-sm">
                        Cerrar
                    </button>
                </div>
            </div>
            @endif

            {{-- Lista de API Clients --}}
            @if(empty($api_clients))
                <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                    <p class="font-semibold">No hay API Clients creados</p>
                    <p class="text-sm mt-1">Crea tu primer cliente API para comenzar</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nombre</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">API Key</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Requests</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rate Limit</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">IPs Permitidas</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($api_clients as $client)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $client['name'] }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Creado: {{ $client['created_at'] }}
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded font-mono text-gray-700 dark:text-gray-300">
                                        {{ $client['key_preview'] }}
                                    </code>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    @if($client['is_active'])
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                            ✅ Activo
                                        </span>
                                    @else
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            ⏸️ Inactivo
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    {{ number_format($client['total_requests']) }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    {{ $client['rate_limit'] }}/min
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                    {{ $client['ip_whitelist'] }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="toggleApiClient({{ $client['id'] }})" 
                                                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                title="@if($client['is_active']) Desactivar @else Activar @endif">
                                            @if($client['is_active'])
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            @endif
                                        </button>
                                        <button wire:click="eliminarApiClient({{ $client['id'] }})" 
                                                onclick="return confirm('¿Está seguro de eliminar este cliente API?')"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                title="Eliminar">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                    <div class="flex gap-2">
                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-300">⚠️ Seguridad Importante</p>
                            <p class="text-xs text-yellow-700 dark:text-yellow-400 mt-1">
                                Las API Keys y Secrets son sensibles. Guárdalas de forma segura y nunca las compartas públicamente. 
                                Las credenciales completas solo se muestran al momento de crearlas.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        @endif

        {{-- Tab: Documentación --}}
        @if($tabActivo === 'documentacion')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">📚 Documentación de Webhooks</h3>

            <div class="prose dark:prose-invert max-w-none">
                <h4 class="text-lg font-semibold text-gray-800 dark:text-white mt-4">Eventos Soportados</h4>
                <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300">
                    <li><code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">inventario.stock_actualizado</code> - Cuando cambia el stock de un producto</li>
                    <li><code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">inventario.compra_recibida</code> - Cuando se recibe una compra en bodega</li>
                    <li><code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">inventario.venta_realizada</code> - Cuando se realiza una venta</li>
                    <li><code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">inventario.factura_anulada</code> - Cuando se anula una factura</li>
                    <li><code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">inventario.sincronizacion_completa</code> - Sincronización masiva de inventario</li>
                </ul>

                <h4 class="text-lg font-semibold text-gray-800 dark:text-white mt-6">Formato de Payload</h4>
                <div class="bg-gray-100 dark:bg-gray-900 p-4 rounded-lg overflow-x-auto">
                    <pre class="text-sm"><code>{
  "evento": "inventario.stock_actualizado",
  "timestamp": "2026-02-12T10:30:00Z",
  "producto": {
    "id": 123,
    "nombre": "Producto Ejemplo",
    "stock_anterior": 50,
    "stock_actual": 45,
    "cambio": -5,
    "razon": "venta"
  },
  "detalles": {
    "factura_id": 456,
    "usuario": "Juan Pérez"
  }
}</code></pre>
                </div>

                <h4 class="text-lg font-semibold text-gray-800 dark:text-white mt-6">Autenticación</h4>
                <p class="text-gray-700 dark:text-gray-300">
                    Todos los webhooks se envían con un header de autenticación Bearer:
                </p>
                <div class="bg-gray-100 dark:bg-gray-900 p-4 rounded-lg overflow-x-auto">
                    <pre class="text-sm"><code>Authorization: Bearer {TU_TOKEN_AQUI}</code></pre>
                </div>

                <h4 class="text-lg font-semibold text-gray-800 dark:text-white mt-6">Respuestas Esperadas</h4>
                <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300">
                    <li><code class="bg-green-100 dark:bg-green-900 px-2 py-1 rounded">200 OK</code> - Webhook procesado correctamente</li>
                    <li><code class="bg-yellow-100 dark:bg-yellow-900 px-2 py-1 rounded">202 Accepted</code> - Webhook aceptado para procesar</li>
                    <li><code class="bg-red-100 dark:bg-red-900 px-2 py-1 rounded">4xx/5xx</code> - Error en el procesamiento</li>
                </ul>

                <h4 class="text-lg font-semibold text-gray-800 dark:text-white mt-6">Reintentos</h4>
                <p class="text-gray-700 dark:text-gray-300">
                    En caso de fallo, el sistema reintentará enviar el webhook según la configuración establecida.
                    Se recomienda implementar idempotencia en el receptor para evitar duplicados.
                </p>
            </div>
        </div>
        @endif

    </div>
</div>

@push('scripts')
<script>
    // Escuchar notificaciones
    Livewire.on('notify', (data) => {
        const notification = data[0];
        if (notification.type === 'success') {
            // Usar sistema de notificaciones de Livewire o alert
            let message = notification.message;
            if (notification.detail) {
                message += '\n\n' + notification.detail;
            }
            alert(message);
        } else if (notification.type === 'error') {
            alert(notification.message);
        }
    });

    // Mostrar API Key creada
    Livewire.on('show-api-key', (data) => {
        const key = data[0].key;
        const mensaje = `✅ API Key Creada Exitosamente\n\n` +
                       `⚠️ IMPORTANTE: Guarda esta key en un lugar seguro.\n` +
                       `No podrás volver a verla.\n\n` +
                       `API Key:\n${key}\n\n` +
                       `Úsala en el header de tus requests:\n` +
                       `Authorization: Bearer ${key}\n\n` +
                       `O como:\n` +
                       `X-API-Key: ${key}`;
        
        if (confirm(mensaje + '\n\n¿Has copiado la API Key?')) {
            // Intentar copiar al portapapeles
            if (navigator.clipboard) {
                navigator.clipboard.writeText(key).then(() => {
                    alert('✅ API Key copiada al portapapeles');
                }).catch(() => {
                    prompt('Copia esta API Key:', key);
                });
            } else {
                prompt('Copia esta API Key:', key);
            }
        }
    });
</script>
@endpush
