/**
 * Escáner de Códigos de Barras con Cámara
 * Sistema de escaneo de códigos de barras usando la cámara del dispositivo
 */

// Función para inicializar el escáner
function initializeBarcodeScanner() {
    let codeReader = null;
    let videoElement = document.getElementById('barcode-video');
    let toggleButton = document.getElementById('toggle-camera-btn');
    let toggleButtonHeader = document.getElementById('toggle-camera-btn-header');
    let closeButton = document.getElementById('close-camera-btn');
    let cameraContainer = document.getElementById('camera-container');
    let cameraStatus = document.getElementById('camera-status');
    let cameraTitle = document.getElementById('camera-title');
    let cameraPlaceholder = document.getElementById('camera-placeholder');
    let scannerElements = document.getElementById('scanner-elements');
    let codigoBarrasInput = document.getElementById('codigo_barras');
    let isScanning = false;

    // Verificar que los elementos esenciales existen
    const elements = {
        videoElement: videoElement,
        toggleButton: toggleButton,
        toggleButtonHeader: toggleButtonHeader,
        closeButton: closeButton,
        cameraContainer: cameraContainer,
        codigoBarrasInput: codigoBarrasInput,
        cameraPlaceholder: cameraPlaceholder
    };

    // Debug: mostrar qué elementos están presentes/ausentes
    const missingElements = [];
    const presentElements = [];
    
    Object.keys(elements).forEach(key => {
        if (elements[key]) {
            presentElements.push(key);
        } else {
            missingElements.push(key);
        }
    });
    
    console.log('Elementos presentes:', presentElements);
    if (missingElements.length > 0) {
        console.log('Elementos faltantes:', missingElements);
    }

    // El contenedor de la cámara ahora siempre debe estar visible
    if (!videoElement || !cameraContainer || !codigoBarrasInput || !cameraPlaceholder) {
        console.log('Elementos esenciales del escáner no encontrados - reintentando...');
        return false;
    }

    console.log('✅ Todos los elementos del escáner encontrados, inicializando...');

    // Función para inicializar el escáner
    function initializeScanner() {
        if (!window.ZXing) {
            console.error('ZXing library no está cargada');
            if (cameraTitle) cameraTitle.textContent = 'Error: Librería ZXing no disponible';
            return;
        }

        if (!codeReader) {
            codeReader = new ZXing.BrowserMultiFormatReader();
            // Optimizar configuraciones para mejor rendimiento
            codeReader.timeBetweenDecodingAttempts = 300; // 300ms entre intentos
        }
        
        if (cameraTitle) cameraTitle.textContent = 'Iniciando cámara...';
        
        // Obtener dispositivos de cámara
        codeReader.listVideoInputDevices()
            .then(videoInputDevices => {
                if (videoInputDevices.length === 0) {
                    if (cameraTitle) cameraTitle.textContent = 'No se encontraron cámaras disponibles';
                    return;
                }
                
                // Preferir cámara trasera si está disponible
                let selectedDevice = videoInputDevices[0];
                for (let device of videoInputDevices) {
                    if (device.label.toLowerCase().includes('back') || 
                        device.label.toLowerCase().includes('rear') ||
                        device.label.toLowerCase().includes('trasera')) {
                        selectedDevice = device;
                        break;
                    }
                }
                
                if (cameraTitle) cameraTitle.textContent = 'Enfoque el código de barras';
                
                // Configurar constraints para mejor performance
                const constraints = {
                    video: {
                        deviceId: selectedDevice.deviceId,
                        width: { ideal: 640 },
                        height: { ideal: 480 },
                        facingMode: 'environment'
                    }
                };
                
                // Iniciar escaneo con configuraciones optimizadas
                codeReader.decodeFromVideoDevice(selectedDevice.deviceId, videoElement, (result, err) => {
                    if (result) {
                        // Código detectado
                        console.log('✅ Código detectado:', result.text);
                        
                        if (cameraTitle) {
                            cameraTitle.textContent = `¡Código detectado! ${result.text}`;
                            cameraTitle.className = 'text-xs font-semibold text-green-600 flex items-center';
                        }
                        
                        // Poner el código en el input
                        if (codigoBarrasInput) {
                            codigoBarrasInput.value = result.text;
                            
                            // Disparar evento de Livewire para actualizar el modelo
                            codigoBarrasInput.dispatchEvent(new Event('input', { bubbles: true }));
                            codigoBarrasInput.dispatchEvent(new Event('change', { bubbles: true }));
                            
                            // Simular Enter para procesar el producto (sin setTimeout excesivo)
                            const enterEvent = new KeyboardEvent('keydown', {
                                key: 'Enter',
                                keyCode: 13,
                                which: 13,
                                bubbles: true
                            });
                            codigoBarrasInput.dispatchEvent(enterEvent);
                        }
                        
                        // Cerrar cámara después de escanear (reducir timeout)
                        setTimeout(stopCamera, 1500);
                    }
                    
                    // Solo mostrar errores significativos, no NotFoundException
                    if (err && !(err instanceof ZXing.NotFoundException)) {
                        console.warn('Error de escaneo:', err);
                    }
                });
                
                isScanning = true;
                
            })
            .catch(err => {
                console.error('Error al acceder a las cámaras:', err);
                if (cameraTitle) cameraTitle.textContent = 'Error al acceder a la cámara';
            });
    }

    // Función para detener la cámara
    function stopCamera() {
        if (codeReader && isScanning) {
            codeReader.reset();
            isScanning = false;
        }
        
        // Ocultar video y mostrar placeholder
        if (videoElement) {
            videoElement.classList.add('hidden');
        }
        if (cameraPlaceholder) {
            cameraPlaceholder.classList.remove('hidden');
        }
        if (scannerElements) {
            scannerElements.classList.add('hidden');
        }
        
        // Actualizar UI
        if (cameraTitle) {
            cameraTitle.textContent = 'Vista previa cámara';
        }
        if (closeButton) {
            closeButton.classList.add('hidden');
        }
        if (toggleButtonHeader && toggleButtonHeader.querySelector('svg')) {
            toggleButtonHeader.querySelector('svg').style.color = '';
        }
        if (codigoBarrasInput) {
            codigoBarrasInput.classList.remove('camera-active');
        }
        
        // Actualizar estado si existe
        if (cameraStatus) {
            cameraStatus.textContent = 'Cámara detenida';
            cameraStatus.className = 'mt-2 text-xs text-gray-500 text-center';
        }
        
        console.log('🔴 Cámara detenida');
    }

    // Función para iniciar la cámara
    function startCamera() {
        // Mostrar video y ocultar placeholder
        if (cameraPlaceholder) {
            cameraPlaceholder.classList.add('hidden');
        }
        if (videoElement) {
            videoElement.classList.remove('hidden');
        }
        if (scannerElements) {
            scannerElements.classList.remove('hidden');
        }
        
        // Actualizar UI
        if (cameraTitle) {
            cameraTitle.textContent = 'Escáner Activo';
        }
        if (closeButton) {
            closeButton.classList.remove('hidden');
        }
        if (toggleButtonHeader && toggleButtonHeader.querySelector('svg')) {
            toggleButtonHeader.querySelector('svg').style.color = '#10b981';
        }
        if (codigoBarrasInput) {
            codigoBarrasInput.classList.add('camera-active');
        }
        
        console.log('🟢 Iniciando cámara...');
        initializeScanner();
    }

    // Event listeners para los botones
    if (toggleButton) {
        toggleButton.addEventListener('click', function() {
            if (isScanning) {
                stopCamera();
            } else {
                startCamera();
            }
        });
    }

    if (toggleButtonHeader) {
        toggleButtonHeader.addEventListener('click', function() {
            if (isScanning) {
                stopCamera();
            } else {
                startCamera();
            }
        });
    }

    if (closeButton) {
        closeButton.addEventListener('click', function() {
            stopCamera();
        });
    }

    // Auto-activar cámara cuando el input recibe foco (solo si el usuario lo desea)
    if (codigoBarrasInput) {
        codigoBarrasInput.addEventListener('focus', function() {
            // Ya no activamos automáticamente, la cámara siempre está visible
            // El usuario puede usar el botón para activarla cuando desee
            console.log('Input enfocado, cámara lista para activar manualmente');
        });

        // Ya no necesitamos manejar blur para detener la cámara automáticamente
        // porque ahora el contenedor siempre está visible
    }

    // Limpiar al cerrar/refrescar la página
    window.addEventListener('beforeunload', function() {
        if (codeReader) {
            codeReader.reset();
        }
    });

    // Detectar cuando Livewire limpia el input y volver a enfocar
    document.addEventListener('livewire:updated', function() {
        // Si el input está vacío después de una actualización de Livewire, enfocarlo
        if (codigoBarrasInput && codigoBarrasInput.value === '') {
            setTimeout(() => {
                codigoBarrasInput.focus();
            }, 100);
        }
    });

    // Exponer funciones globalmente para uso externo si es necesario
    window.BarcodeScanner = {
        start: startCamera,
        stop: stopCamera,
        isScanning: function() { return isScanning; }
    };
    
    return true;
}

// Inicializar cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, intentando inicializar escáner...');
    
    // Función para verificar elementos y reintentar (optimizado)
    function attemptInitialization(attempt = 1, maxAttempts = 8) {
        console.log(`Intento ${attempt}/${maxAttempts} de inicialización...`);
        
        if (initializeBarcodeScanner()) {
            console.log('✅ Escáner inicializado correctamente!');
            return;
        }
        
        if (attempt < maxAttempts) {
            // Usar requestAnimationFrame para mejor performance
            requestAnimationFrame(() => {
                setTimeout(() => {
                    attemptInitialization(attempt + 1, maxAttempts);
                }, 800); // Reducido de 1000ms a 800ms
            });
        } else {
            console.log('❌ No se pudo inicializar el escáner después de todos los intentos');
        }
    }
    
    // Intentar inicialización inmediata
    attemptInitialization();
});

// Eventos de Livewire - múltiples puntos de entrada
document.addEventListener('livewire:load', function() {
    console.log('Livewire:load - reintentando inicialización...');
    setTimeout(() => {
        initializeBarcodeScanner();
    }, 500);
});

document.addEventListener('livewire:update', function() {
    console.log('Livewire:update - verificando escáner...');
    setTimeout(() => {
        if (!window.BarcodeScanner || !window.BarcodeScanner.isScanning()) {
            initializeBarcodeScanner();
        }
    }, 300);
});

// También escuchar cuando Alpine.js esté listo (usado por Livewire)
document.addEventListener('alpine:init', function() {
    console.log('Alpine:init - reintentando escáner...');
    setTimeout(() => {
        initializeBarcodeScanner();
    }, 500);
});

// Escuchar cambios en el DOM que podrían indicar que Livewire terminó de renderizar
if (typeof MutationObserver !== 'undefined') {
    const observer = new MutationObserver(function(mutations) {
        let shouldRetry = false;
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                // Verificar si se agregaron elementos relevantes
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Es un elemento
                        if (node.id === 'camera-container' || 
                            node.id === 'codigo_barras' || 
                            node.querySelector && (
                                node.querySelector('#camera-container') ||
                                node.querySelector('#codigo_barras')
                            )) {
                            shouldRetry = true;
                        }
                    }
                });
            }
        });
        
        if (shouldRetry) {
            console.log('DOM cambió - elementos del escáner detectados, reintentando...');
            setTimeout(() => {
                initializeBarcodeScanner();
            }, 200);
        }
    });
    
    // Observar cambios en el body
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}

/**
 * Funciones de utilidad para manejo de códigos de barras
 */
window.BarcodeUtils = {
    // Función para formatear códigos de barras según estándares
    formatBarcode: function(code) {
        if (!code) return '';
        
        // Remover espacios y caracteres especiales
        code = code.trim().replace(/[^\d\w]/g, '');
        
        return code;
    },
    
    // Validar formato de código de barras
    validateBarcode: function(code) {
        if (!code || typeof code !== 'string') return false;
        
        // Verificar longitud mínima
        if (code.length < 4) return false;
        
        // Verificar que contenga solo números y letras
        return /^[a-zA-Z0-9]+$/.test(code);
    },
    
    // Función de debug para forzar inicialización
    forceInitialize: function() {
        console.log('🔧 Forzando inicialización del escáner...');
        return initializeBarcodeScanner();
    },
    
    // Función para verificar elementos DOM
    checkElements: function() {
        const elements = [
            'barcode-video',
            'toggle-camera-btn', 
            'close-camera-btn',
            'camera-container',
            'camera-status',
            'codigo_barras'
        ];
        
        console.log('🔍 Verificando elementos DOM:');
        elements.forEach(id => {
            const element = document.getElementById(id);
            console.log(`  ${id}: ${element ? '✅ Presente' : '❌ Faltante'}`);
        });
        
        return elements.every(id => document.getElementById(id));
    }
};
