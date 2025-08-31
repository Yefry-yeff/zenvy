/**
 * Escáner de Códigos de Barras con Cámara - Versión Alpine.js
 * Sistema de escaneo de códigos de barras usando la cámara del dispositivo
 */

// Clase para manejar el escáner de códigos de barras
class BarcodeScanner {
    constructor() {
        this.codeReader = null;
        this.videoElement = null;
        this.isScanning = false;
        this.currentStream = null;
        this.devices = [];
    }

    async init() {
        console.log('BarcodeScanner: Inicializando escáner de códigos de barras...');
        
        // Verificar soporte de ZXing
        if (typeof ZXing === 'undefined') {
            console.error('BarcodeScanner: ZXing no está disponible');
            alert('Error: Librería ZXing no está cargada. Verifica que la librería esté incluida en la página.');
            return false;
        }

        try {
            // Inicializar el lector de códigos
            this.codeReader = new ZXing.BrowserMultiFormatReader();
            
            // Detectar métodos disponibles para debugging
            console.log('BarcodeScanner: Métodos disponibles en codeReader:', Object.getOwnPropertyNames(this.codeReader));
            console.log('BarcodeScanner: Prototype methods:', Object.getOwnPropertyNames(Object.getPrototypeOf(this.codeReader)));
            
            // No buscar el elemento de video aquí, se buscará cuando se necesite
            console.log('BarcodeScanner: Lector de códigos inicializado');

            // Obtener dispositivos de cámara
            await this.getVideoDevices();
            console.log('BarcodeScanner: Escáner inicializado correctamente');
            return true;
        } catch (error) {
            console.error('BarcodeScanner: Error inicializando escáner:', error);
            alert('Error inicializando escáner: ' + error.message);
            return false;
        }
    }

    async getVideoDevices() {
        try {
            this.devices = await this.codeReader.getVideoInputDevices();
            console.log('Dispositivos de video encontrados:', this.devices.length);
        } catch (error) {
            console.error('Error obteniendo dispositivos:', error);
            this.devices = [];
        }
    }

    async start() {
        console.log('BarcodeScanner: ===== INICIANDO ESCÁNER =====');
        console.log('BarcodeScanner: isScanning actual:', this.isScanning);
        
        if (this.isScanning) {
            console.log('BarcodeScanner: El escáner ya está activo, saliendo...');
            return;
        }

        console.log('BarcodeScanner: Iniciando proceso de escáner...');

        // Buscar el elemento de video dinámicamente - Con fallback
        this.videoElement = document.getElementById('barcode-video');
        let videoElementEmergency = document.getElementById('barcode-video-emergency');
        
        console.log('BarcodeScanner: Buscando elemento barcode-video...');
        console.log('BarcodeScanner: Elemento principal encontrado:', this.videoElement);
        console.log('BarcodeScanner: Elemento de emergencia encontrado:', videoElementEmergency);
        
        // Si el elemento principal no tiene dimensiones, usar el de emergencia
        if (this.videoElement) {
            const rect = this.videoElement.getBoundingClientRect();
            console.log('BarcodeScanner: Dimensiones del video principal:', rect.width + 'x' + rect.height);
            
            if (rect.width === 0 && rect.height === 0 && videoElementEmergency) {
                console.log('BarcodeScanner: 🔄 Video principal sin dimensiones, cambiando al de emergencia');
                this.videoElement = videoElementEmergency;
            }
        }
        
        if (!this.videoElement) {
            console.error('BarcodeScanner: Elemento de video no encontrado al iniciar');
            
            // Debugging adicional - buscar todos los videos
            const allVideos = document.querySelectorAll('video');
            console.log('BarcodeScanner: Videos encontrados en página:', allVideos.length);
            allVideos.forEach((video, index) => {
                console.log(`BarcodeScanner: Video ${index}:`, {
                    id: video.id,
                    class: video.className,
                    visible: video.offsetWidth > 0 && video.offsetHeight > 0,
                    display: window.getComputedStyle(video).display
                });
            });
            
            alert('Error: No se encuentra el elemento de video de la cámara. Asegúrate de estar en la página correcta.');
            return;
        }

        console.log('BarcodeScanner: Elemento de video final seleccionado:', this.videoElement.id);
        console.log('BarcodeScanner: Propiedades del video:', {
            width: this.videoElement.offsetWidth,
            height: this.videoElement.offsetHeight,
            display: window.getComputedStyle(this.videoElement).display,
            visibility: window.getComputedStyle(this.videoElement).visibility,
            zIndex: window.getComputedStyle(this.videoElement).zIndex
        });
        console.log('BarcodeScanner: Dispositivos disponibles:', this.devices.length);

        // Verificar permisos de cámara primero
        try {
            console.log('BarcodeScanner: Verificando permisos de cámara...');
            const permissions = await navigator.permissions.query({ name: 'camera' });
            console.log('BarcodeScanner: Permisos de cámara:', permissions.state);
        } catch (error) {
            console.log('BarcodeScanner: No se pudieron verificar permisos:', error);
        }

        try {
            let selectedDeviceId = undefined;
            
            // Preferir cámara trasera si está disponible
            if (this.devices.length > 0) {
                console.log('BarcodeScanner: Dispositivos disponibles:', this.devices.map(d => ({id: d.deviceId, label: d.label})));
                
                const backCamera = this.devices.find(device => 
                    device.label.toLowerCase().includes('back') || 
                    device.label.toLowerCase().includes('rear')
                );
                
                if (backCamera) {
                    selectedDeviceId = backCamera.deviceId;
                    console.log('BarcodeScanner: Usando cámara trasera:', backCamera.label);
                } else {
                    selectedDeviceId = this.devices[0].deviceId;
                    console.log('BarcodeScanner: Usando primera cámara disponible:', this.devices[0].label);
                }
            } else {
                console.log('BarcodeScanner: No se especificará deviceId, usando cámara por defecto');
            }

            // Configurar constraints de video optimizadas
            const constraints = {
                video: {
                    deviceId: selectedDeviceId ? { exact: selectedDeviceId } : undefined,
                    width: { ideal: 640 },
                    height: { ideal: 480 },
                    frameRate: { ideal: 15, max: 30 }
                }
            };

            console.log('BarcodeScanner: Constraints finales:', JSON.stringify(constraints, null, 2));
            console.log('BarcodeScanner: Solicitando acceso a cámara...');

            // Obtener stream de video
            this.currentStream = await navigator.mediaDevices.getUserMedia(constraints);
            console.log('BarcodeScanner: ✅ Stream de video obtenido correctamente');
            console.log('BarcodeScanner: Stream:', this.currentStream);
            console.log('BarcodeScanner: Tracks:', this.currentStream.getTracks().map(t => ({kind: t.kind, label: t.label, enabled: t.enabled})));
            
            console.log('BarcodeScanner: Asignando stream al elemento de video...');
            this.videoElement.srcObject = this.currentStream;
            console.log('BarcodeScanner: Stream asignado, srcObject:', this.videoElement.srcObject);
            console.log('BarcodeScanner: Video autoplay:', this.videoElement.autoplay);
            console.log('BarcodeScanner: Video muted:', this.videoElement.muted);
            console.log('BarcodeScanner: Video playsinline:', this.videoElement.playsInline);
            
            // Configurar eventos del video
            this.videoElement.onloadedmetadata = () => {
                console.log('BarcodeScanner: ✅ Metadata del video cargada');
                console.log('BarcodeScanner: Video dimensiones:', this.videoElement.videoWidth + 'x' + this.videoElement.videoHeight);
                console.log('BarcodeScanner: Video readyState:', this.videoElement.readyState);
                console.log('BarcodeScanner: Iniciando reproducción...');
                
                this.videoElement.play().then(() => {
                    console.log('BarcodeScanner: ✅ Video reproduciendo correctamente');
                    console.log('BarcodeScanner: Video currentTime:', this.videoElement.currentTime);
                    console.log('BarcodeScanner: Video paused:', this.videoElement.paused);
                    this.isScanning = true;
                    this.startDecoding();
                }).catch(error => {
                    console.error('BarcodeScanner: ❌ Error reproduciendo video:', error);
                    alert('Error reproduciendo video: ' + error.message);
                });
            };

            // Manejar errores de video
            this.videoElement.onerror = (error) => {
                console.error('BarcodeScanner: ❌ Error en elemento de video:', error);
                this.showError('Error reproduciendo video de cámara');
            };

            console.log('BarcodeScanner: Configuración completada, esperando metadata...');

        } catch (error) {
            console.error('BarcodeScanner: ❌ Error iniciando cámara:', error);
            console.error('BarcodeScanner: Error name:', error.name);
            console.error('BarcodeScanner: Error message:', error.message);
            
            if (error.name === 'NotAllowedError') {
                this.showError('Permisos de cámara denegados. Por favor, permite el acceso a la cámara y recarga la página.');
            } else if (error.name === 'NotFoundError') {
                this.showError('No se encontró cámara disponible en este dispositivo.');
            } else if (error.name === 'NotReadableError') {
                this.showError('La cámara está siendo usada por otra aplicación.');
            } else {
                this.showError('Error accediendo a la cámara: ' + error.message);
            }
        }
    }

    startDecoding() {
        console.log('BarcodeScanner: ===== INICIANDO DECODIFICACIÓN =====');
        console.log('BarcodeScanner: isScanning:', this.isScanning);
        console.log('BarcodeScanner: videoElement:', this.videoElement);
        console.log('BarcodeScanner: videoWidth:', this.videoElement?.videoWidth);
        console.log('BarcodeScanner: videoHeight:', this.videoElement?.videoHeight);
        
        if (!this.isScanning || !this.videoElement) {
            console.log('BarcodeScanner: No se puede iniciar decodificación - condiciones no cumplidas');
            return;
        }

        // Esperar a que el video tenga dimensiones válidas
        const waitForVideo = () => {
            if (this.videoElement.videoWidth === 0 || this.videoElement.videoHeight === 0) {
                console.log('BarcodeScanner: Esperando dimensiones válidas del video...');
                setTimeout(waitForVideo, 100);
                return;
            }
            
            console.log('BarcodeScanner: Configurando interval de decodificación (300ms)...');
            
            // Decodificar con intervalo optimizado
            this.decodeInterval = setInterval(() => {
                if (!this.isScanning || !this.videoElement || this.videoElement.videoWidth === 0) {
                    return;
                }

                try {
                    // Verificar que el video esté reproduciendo
                    if (this.videoElement.readyState !== 4 || this.videoElement.paused) {
                        return;
                    }
                    
                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');
                    
                    // Usar las dimensiones reales del video
                    const width = this.videoElement.videoWidth;
                    const height = this.videoElement.videoHeight;
                    
                    canvas.width = width;
                    canvas.height = height;
                    
                    // Dibujar frame actual del video - CON VERIFICACIÓN
                    try {
                        context.drawImage(this.videoElement, 0, 0, width, height);
                    } catch (drawError) {
                        console.log('BarcodeScanner: Error dibujando video:', drawError.message);
                        return;
                    }
                    
                    // Obtener datos de la imagen
                    const imageData = context.getImageData(0, 0, width, height);
                    
                    // Usar ZXing correctamente - detección automática de métodos
                    try {
                        // Detectar qué método de decodificación está disponible
                        if (typeof this.codeReader.decodeFromCanvas === 'function') {
                            // Método 1: decodeFromCanvas (más común)
                            this.codeReader.decodeFromCanvas(canvas)
                                .then(result => {
                                    if (result && result.text) {
                                        console.log('BarcodeScanner: ✅ Código detectado (canvas):', result.text);
                                        this.onBarcodeDetected(result.text);
                                    }
                                })
                                .catch(error => {
                                    // Solo logear errores no comunes
                                    if (error.message && !error.message.includes('NotFoundException') && !error.message.includes('No MultiFormat Readers')) {
                                        if (Math.random() < 0.01) {
                                            console.log('BarcodeScanner: Info decode:', error.message);
                                        }
                                    }
                                });
                        } else if (typeof this.codeReader.decodeOnceFromCanvas === 'function') {
                            // Método 2: decodeOnceFromCanvas
                            this.codeReader.decodeOnceFromCanvas(canvas)
                                .then(result => {
                                    if (result && result.text) {
                                        console.log('BarcodeScanner: ✅ Código detectado (decodeOnceFromCanvas):', result.text);
                                        this.onBarcodeDetected(result.text);
                                    }
                                })
                                .catch(() => {
                                    // Error silencioso, normal cuando no hay código
                                });
                        } else if (typeof this.codeReader.decodeFromImageData === 'function') {
                            // Método 3: decodeFromImageData
                            this.codeReader.decodeFromImageData(imageData)
                                .then(result => {
                                    if (result && result.text) {
                                        console.log('BarcodeScanner: ✅ Código detectado (imageData):', result.text);
                                        this.onBarcodeDetected(result.text);
                                    }
                                })
                                .catch(() => {
                                    // Error silencioso, normal cuando no hay código
                                });
                        } else if (typeof this.codeReader.decode === 'function') {
                            // Método 4: decode básico
                            const result = this.codeReader.decode(imageData);
                            if (result && result.text) {
                                console.log('BarcodeScanner: ✅ Código detectado (decode):', result.text);
                                this.onBarcodeDetected(result.text);
                            }
                        }
                    } catch (error) {
                        // Error en el intento de decodificación - reducir logging
                        if (Math.random() < 0.001) { // Solo log muy ocasional
                            console.log('BarcodeScanner: Decode attempt:', error.message);
                        }
                    }
                } catch (error) {
                    console.error('BarcodeScanner: ❌ Error en decodificación:', error);
                }
            }, 300); // Intervalo optimizado
            
            console.log('BarcodeScanner: ✅ Decodificación iniciada correctamente');
        };
        
        waitForVideo();
    }

    stop() {
        console.log('BarcodeScanner: Deteniendo escáner...');
        
        this.isScanning = false;
        
        // Detener interval de decodificación
        if (this.decodeInterval) {
            clearInterval(this.decodeInterval);
            this.decodeInterval = null;
        }
        
        // Detener stream de video
        if (this.currentStream) {
            this.currentStream.getTracks().forEach(track => track.stop());
            this.currentStream = null;
        }
        
        // Limpiar elemento de video si existe
        if (this.videoElement) {
            this.videoElement.srcObject = null;
        } else {
            // Buscar dinámicamente si no está referenciado
            const videoElement = document.getElementById('barcode-video');
            if (videoElement) {
                videoElement.srcObject = null;
            }
        }
        
        console.log('BarcodeScanner: Escáner detenido completamente');
    }

    onBarcodeDetected(code) {
        console.log('🔍 Procesando código detectado:', code);
        
        // Detener temporalmente el escaneo para evitar múltiples lecturas
        if (this.decodeInterval) {
            clearInterval(this.decodeInterval);
            setTimeout(() => {
                if (this.isScanning) {
                    this.startDecoding();
                }
            }, 1500); // Pausa de 1.5 segundos antes de reanudar
        }
        
        // Enviar código al input con múltiples intentos
        const codigoInput = document.getElementById('codigo_barras');
        if (codigoInput) {
            console.log('📝 Enviando código al input:', code);
            
            // Limpiar input primero
            codigoInput.value = '';
            
            // Establecer nuevo valor
            setTimeout(() => {
                codigoInput.value = code;
                codigoInput.focus();
                
                // Disparar múltiples eventos para asegurar que Livewire reciba el valor
                codigoInput.dispatchEvent(new Event('input', { bubbles: true }));
                codigoInput.dispatchEvent(new Event('change', { bubbles: true }));
                
                // Disparar evento personalizado para que Alpine/Livewire procese
                window.dispatchEvent(new CustomEvent('barcode-detected', { 
                    detail: { code: code } 
                }));
                
                // Simular Enter para ejecutar la búsqueda
                setTimeout(() => {
                    codigoInput.dispatchEvent(new KeyboardEvent('keydown', { 
                        key: 'Enter', 
                        keyCode: 13, 
                        which: 13,
                        bubbles: true 
                    }));
                    
                    console.log('✅ Código procesado y enviado:', code);
                }, 100);
                
            }, 100);
        } else {
            console.error('❌ No se encontró el input codigo_barras');
        }
        
        // Mostrar feedback visual
        this.showSuccess('✅ Código escaneado: ' + code);
    }

    showSuccess(message) {
        console.log('✓', message);
        
        // Mostrar indicador visual en la cámara
        const indicator = document.getElementById('barcode-detected-indicator');
        if (indicator) {
            indicator.style.opacity = '1';
            indicator.textContent = message;
            
            // Ocultar después de 2 segundos
            setTimeout(() => {
                indicator.style.opacity = '0';
            }, 2000);
        }
        
        // También mostrar notificación del navegador si es posible
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('Código de Barras', {
                body: message,
                icon: '/favicon.ico',
                tag: 'barcode-scan'
            });
        }
    }

    showError(message) {
        console.error('BarcodeScanner: ✗', message);
        alert('Error del escáner: ' + message);
    }
}

// Función para esperar a que ZXing esté disponible
function waitForZXing(maxWait = 5000) {
    return new Promise((resolve, reject) => {
        let elapsed = 0;
        const checkInterval = 100;
        
        const check = () => {
            if (typeof ZXing !== 'undefined' && ZXing.BrowserMultiFormatReader) {
                console.log('BarcodeScanner: ZXing está listo');
                resolve(true);
            } else if (elapsed >= maxWait) {
                console.error('BarcodeScanner: Timeout esperando ZXing');
                reject(new Error('ZXing no se cargó en el tiempo esperado'));
            } else {
                elapsed += checkInterval;
                setTimeout(check, checkInterval);
            }
        };
        
        check();
    });
}

// Instancia global del escáner
window.BarcodeScanner = new BarcodeScanner();

// Función de inicialización que espera a ZXing y Livewire
async function initializeWhenReady() {
    try {
        console.log('BarcodeScanner: Esperando a que ZXing esté disponible...');
        await waitForZXing();
        
        // Esperar también a que Livewire esté listo
        if (typeof Livewire !== 'undefined') {
            console.log('BarcodeScanner: Livewire detectado, esperando a que esté listo...');
            Livewire.hook('component.initialized', () => {
                console.log('BarcodeScanner: Componente Livewire inicializado');
            });
        }
        
        const success = await window.BarcodeScanner.init();
        if (success) {
            console.log('BarcodeScanner: Sistema listo para usar');
            
            // Añadir evento de debugging para códigos de barras
            window.addEventListener('barcode-detected', (e) => {
                console.log('🎯 Evento barcode-detected recibido:', e.detail.code);
            });
            
        } else {
            console.error('BarcodeScanner: Error en la inicialización final');
        }
    } catch (error) {
        console.error('BarcodeScanner: Error en inicialización:', error);
        
        // Crear un escáner de respaldo más simple
        console.log('BarcodeScanner: Creando escáner de respaldo...');
        window.BarcodeScanner = {
            start: async function() {
                console.log('BarcodeScanner: Modo respaldo - iniciando...');
                try {
                    const videoElement = document.getElementById('barcode-video');
                    if (!videoElement) {
                        throw new Error('Video element no encontrado');
                    }
                    
                    const stream = await navigator.mediaDevices.getUserMedia({
                        video: { width: 640, height: 480 }
                    });
                    
                    videoElement.srcObject = stream;
                    console.log('BarcodeScanner: Modo respaldo - stream asignado');
                    
                    // Crear un escáner simple sin ZXing
                    videoElement.addEventListener('loadedmetadata', () => {
                        console.log('BarcodeScanner: Modo respaldo - video listo, pero sin detección automática');
                        console.log('BarcodeScanner: Use el input manual para introducir códigos');
                    });
                    
                } catch (error) {
                    console.error('BarcodeScanner: Error en modo respaldo:', error);
                    alert('Error: No se puede acceder a la cámara. Verifica los permisos.');
                }
            },
            stop: function() {
                console.log('BarcodeScanner: Modo respaldo - deteniendo...');
                const videoElement = document.getElementById('barcode-video');
                if (videoElement && videoElement.srcObject) {
                    videoElement.srcObject.getTracks().forEach(track => track.stop());
                    videoElement.srcObject = null;
                }
            }
        };
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    console.log('BarcodeScanner: DOM listo, iniciando proceso de inicialización...');
    initializeWhenReady();
});

// Escuchar eventos de Livewire si está disponible
document.addEventListener('livewire:load', () => {
    console.log('BarcodeScanner: Livewire cargado, re-inicializando...');
    initializeWhenReady();
});

// También inicializar si ya está listo
if (document.readyState === 'loading') {
    // Ya se registró el evento DOMContentLoaded
    console.log('BarcodeScanner: Esperando DOMContentLoaded...');
} else {
    // DOM ya está listo
    console.log('BarcodeScanner: DOM ya listo, iniciando proceso de inicialización inmediatamente...');
    initializeWhenReady();
}
