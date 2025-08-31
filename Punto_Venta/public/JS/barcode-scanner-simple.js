/**
 * Escáner de Códigos de Barras Simplificado - Versión de Respaldo
 * Sistema básico que funciona sin ZXing como fallback
 */

console.log('BarcodeScanner: Iniciando versión simplificada...');

// Crear un escáner básico que siempre funcione
/**
 * Sistema de escaneo de códigos de barras optimizado
 * Versión: 3.0 - Mejorado y optimizado
 * Requiere: ZXing library
 */

// Verificar disponibilidad de ZXing al cargar
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 Verificando disponibilidad de ZXing...');
    
    // Función para verificar ZXing cada segundo hasta encontrarlo
    let checkCount = 0;
    const checkZXing = setInterval(() => {
        checkCount++;
        
        if (typeof ZXing !== 'undefined' && ZXing.BrowserMultiFormatReader) {
            console.log('✅ ZXing cargado correctamente después de', checkCount, 'intentos');
            clearInterval(checkZXing);
            return;
        }
        
        if (checkCount > 10) { // Máximo 10 segundos
            console.error('❌ ZXing no se cargó en 10 segundos. Verificar conexión a internet.');
            clearInterval(checkZXing);
        } else {
            console.log(`⏳ Esperando ZXing... (${checkCount}/10)`);
        }
    }, 1000);
});

window.BarcodeScanner = {
    isScanning: false,
    currentStream: null,
    videoElement: null,

    async start() {
        console.log('BarcodeScanner: 🎥 Iniciando cámara (modo básico)...');
        
        try {
            // Verificar soporte del navegador
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error('Navegador no soporta acceso a cámara');
            }

            // Buscar elemento de video
            this.videoElement = document.getElementById('barcode-video');
            if (!this.videoElement) {
                throw new Error('Elemento de video no encontrado');
            }

            // Solicitar acceso a la cámara - MEJOR RESOLUCIÓN
            this.currentStream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 1280, min: 640 },
                    height: { ideal: 720, min: 480 },
                    frameRate: { ideal: 30, min: 15 },
                    facingMode: { ideal: 'environment' }, // Preferir cámara trasera
                    focusMode: { ideal: 'continuous' } // Auto-enfoque continuo
                }
            });

            // Asignar stream al video
            this.videoElement.srcObject = this.currentStream;
            this.isScanning = true;

            // EVENTOS IMPORTANTES PARA VALIDAR CARGA DEL VIDEO
            this.videoElement.addEventListener('loadedmetadata', () => {
                console.log('BarcodeScanner: 📹 Video metadata cargada -', 
                    this.videoElement.videoWidth + 'x' + this.videoElement.videoHeight);
            });

            this.videoElement.addEventListener('loadeddata', () => {
                console.log('BarcodeScanner: 🎬 Video data cargada - listo para procesar');
            });

            this.videoElement.addEventListener('canplay', () => {
                console.log('BarcodeScanner: ▶️ Video puede empezar a reproducirse');
            });

            // Esperar a que el video esté completamente listo
            await new Promise((resolve) => {
                if (this.videoElement.readyState >= 3) {
                    resolve();
                } else {
                    this.videoElement.addEventListener('canplay', resolve, { once: true });
                }
            });

            console.log('BarcodeScanner: ✅ Cámara iniciada correctamente (modo básico)');
            console.log('BarcodeScanner: ⚠️ Detección automática no disponible - use input manual');
            
            // Actualizar estado
            this.updateScanStatus('Cámara iniciada - Preparando...', 'processing');

            // Intentar inicializar ZXing si está disponible
            this.tryInitializeZXing();

        } catch (error) {
            console.error('BarcodeScanner: ❌ Error iniciando cámara:', error);
            
            if (error.name === 'NotAllowedError') {
                alert('Permisos de cámara denegados. Por favor, permite el acceso y recarga la página.');
            } else if (error.name === 'NotFoundError') {
                alert('No se encontró cámara disponible en este dispositivo.');
            } else {
                alert('Error accediendo a la cámara: ' + error.message);
            }
        }
    },

    async tryInitializeZXing() {
        console.log('BarcodeScanner: Intentando inicializar ZXing...');
        this.updateScanStatus('Cargando detector...', 'processing');
        
        // Esperar a que ZXing esté disponible
        for (let i = 0; i < 50; i++) { // Esperar hasta 5 segundos
            if (typeof ZXing !== 'undefined' && ZXing.BrowserMultiFormatReader) {
                console.log('BarcodeScanner: ✅ ZXing disponible, activando detección automática');
                this.updateScanStatus('Listo - Apunte al código', 'success');
                this.startZXingScanning();
                return;
            }
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        
        console.log('BarcodeScanner: ⚠️ ZXing no disponible, solo modo manual');
        this.updateScanStatus('Error: ZXing no cargado', 'error');
    },

    startZXingScanning() {
        try {
            // CREAR Y ASIGNAR READER CORRECTAMENTE
            this.codeReader = new ZXing.BrowserMultiFormatReader();
            console.log('BarcodeScanner: 🔍 ZXing reader inicializado');
            console.log('BarcodeScanner: ✅ CodeReader asignado a this.codeReader');
            
            // Verificar métodos disponibles  
            const methods = Object.getOwnPropertyNames(Object.getPrototypeOf(this.codeReader));
            const decodeMethods = methods.filter(m => m.includes('decode'));
            console.log('BarcodeScanner: Métodos de decodificación disponibles:', decodeMethods);
            console.log('BarcodeScanner: Todos los métodos:', methods.slice(0, 10)); // Solo los primeros 10
            
            // Verificar específicamente los métodos que necesitamos
            console.log('BarcodeScanner: Verificación directa:');
            console.log('  - decode:', typeof this.codeReader.decode);  
            console.log('  - decodeOnce:', typeof this.codeReader.decodeOnce);
            console.log('  - decodeBitmap:', typeof this.codeReader.decodeBitmap);

            let scanCount = 0;
            
            // Escaneo continuo - OPTIMIZADO a 300ms para asegurar video cargado
            const scanInterval = setInterval(() => {
                scanCount++;
                
                if (!this.isScanning || !this.videoElement) {
                    clearInterval(scanInterval);
                    return;
                }

                // VALIDACIÓN COMPLETA DEL VIDEO ANTES DE PROCESAR
                if (this.videoElement.readyState === 4 && 
                    this.videoElement.videoWidth > 0 && 
                    this.videoElement.videoHeight > 0 &&
                    !this.videoElement.paused &&
                    !this.videoElement.ended) {
                    
                    // Log cada 100 intentos para evitar spam
                    if (scanCount === 1) {
                        console.log(`BarcodeScanner: 🎯 Iniciando escaneo automático - Video: ${this.videoElement.videoWidth}x${this.videoElement.videoHeight}`);
                        this.updateScanStatus('Buscando códigos...', 'scanning');
                    } else if (scanCount % 150 === 0) {
                        console.log(`BarcodeScanner: 📊 Escaneos: ${scanCount} - Sistema activo`);
                        // Actualizar UI ocasionalmente
                        this.updateScanStatus('Escaneando...', 'scanning');
                    }
                    
                    // Usar dimensiones reales del video
                    const width = this.videoElement.videoWidth;
                    const height = this.videoElement.videoHeight;
                    
                    // SEGUNDA VALIDACIÓN: Verificar que las dimensiones sean válidas
                    if (width <= 0 || height <= 0) {
                        console.warn('BarcodeScanner: Dimensiones de video inválidas:', width, 'x', height);
                        return;
                    }
                    
                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d', { 
                        willReadFrequently: true // Optimización para getImageData frecuente
                    });
                    
                    if (!context) {
                        console.error('BarcodeScanner: No se pudo obtener contexto del canvas');
                        return;
                    }
                    
                    canvas.width = width;
                    canvas.height = height;
                    
                    try {
                        // VALIDACIÓN PREVIA: Asegurarse que el drawImage fue exitoso
                        context.drawImage(this.videoElement, 0, 0, width, height);
                        
                        // VERIFICAR QUE EL CANVAS TIENE CONTENIDO VÁLIDO
                        if (canvas.width <= 0 || canvas.height <= 0) {
                            console.warn('BarcodeScanner: Canvas con dimensiones inválidas:', canvas.width, 'x', canvas.height);
                            return;
                        }
                        
                        // PROCESAMIENTO DE IMAGEN SIMPLIFICADO - Solo si es necesario
                        
                        // Solo procesar imagen cada 5to frame para mejor performance
                        if (scanCount % 5 === 0) {
                            try {
                                const imageData = context.getImageData(0, 0, width, height);
                                const data = imageData.data;
                                
                                // Contraste muy rápido - procesar aún menos pixels
                                for (let i = 0; i < data.length; i += 60) { // Menos procesamiento
                                    const brightness = (data[i] + data[i + 1] + data[i + 2]) / 3;
                                    const contrast = brightness > 120 ? 255 : 0;
                                    data[i] = data[i + 1] = data[i + 2] = contrast;
                                }
                                context.putImageData(imageData, 0, 0);
                            } catch (imageError) {
                                console.warn('BarcodeScanner: Error procesando imagen:', imageError.message);
                                // Continuar sin procesamiento de imagen
                            }
                        }
                        
                        // ESTRATEGIA SEGURA: Solo usar decodeBitmap con ImageData
                        // Esto evita que ZXing acceda directamente al canvas
                        
                        try {
                            // VALIDACIÓN FINAL antes de getImageData para ZXing
                            if (width > 0 && height > 0 && context) {
                                const imageDataForDecoding = context.getImageData(0, 0, width, height);
                                
                                // Verificar que ImageData es válido
                                if (imageDataForDecoding && imageDataForDecoding.data && imageDataForDecoding.data.length > 0) {
                                    // Solo usar decodeBitmap que es más seguro
                                    if (this.codeReader.decodeBitmap && typeof this.codeReader.decodeBitmap === 'function') {
                                        this.codeReader.decodeBitmap(imageDataForDecoding)
                                            .then(result => {
                                                if (result && result.text) {
                                                    console.log('BarcodeScanner: 🎉 CÓDIGO DETECTADO (decodeBitmap):', result.text);
                                                    this.onBarcodeDetected(result.text);
                                                }
                                            })
                                            .catch(() => {
                                                // Silencioso - no hay código en este frame
                                            });
                                    }
                                } else {
                                    console.warn('BarcodeScanner: ImageData inválido:', imageDataForDecoding);
                                }
                            } else {
                                console.warn('BarcodeScanner: Dimensiones o contexto inválido para ZXing:', width, 'x', height);
                            }
                        // Error handling sin spam de logs
                        } catch (zxingError) {
                            // Solo mostrar errores importantes, no "No code detected" que es normal
                            if (scanCount % 500 === 0 && !zxingError.message.includes('No MultiFormat Readers')) { 
                                console.warn('BarcodeScanner: Error en ZXing processing:', zxingError.message);
                            }
                        }
                    } catch (error) {
                        if (scanCount % 200 === 0) { // Log cada 200 errores para evitar spam
                            console.log('BarcodeScanner: Frame processing info:', error.message);
                            
                            // Debug del codeReader si hay problemas
                            if (!this.codeReader) {
                                console.error('BarcodeScanner: ❌ codeReader es null/undefined');
                            } else {
                                console.log('BarcodeScanner: ✅ codeReader está disponible');
                            }
                        }
                    }
                }
            }, 400); // AUMENTADO: 400ms para reducir violations y mejorar performance

        } catch (error) {
            console.error('BarcodeScanner: Error inicializando ZXing:', error);
        }
    },

    // Métodos alternativos de decodificación para mayor compatibilidad
    tryAlternativeDecoding(canvas, imageData, codeReader) {
        // Método 2: decodeOnceFromCanvas
        if (typeof codeReader.decodeOnceFromCanvas === 'function') {
            codeReader.decodeOnceFromCanvas(canvas)
                .then(result => {
                    if (result && result.text) {
                        console.log('BarcodeScanner: ✅ Código detectado (decodeOnceFromCanvas):', result.text);
                        this.onBarcodeDetected(result.text);
                        return;
                    }
                    // Si falla, intentar siguiente método
                    this.tryImageDataDecoding(imageData, codeReader);
                })
                .catch(() => {
                    this.tryImageDataDecoding(imageData, codeReader);
                });
        } else {
            this.tryImageDataDecoding(imageData, codeReader);
        }
    },

    tryImageDataDecoding(imageData, codeReader) {
        // Método 3: decodeFromImageData
        if (typeof codeReader.decodeFromImageData === 'function') {
            codeReader.decodeFromImageData(imageData)
                .then(result => {
                    if (result && result.text) {
                        console.log('BarcodeScanner: ✅ Código detectado (decodeFromImageData):', result.text);
                        this.onBarcodeDetected(result.text);
                        return;
                    }
                    // Si falla, intentar método directo
                    this.tryDirectDecoding(imageData, codeReader);
                })
                .catch(() => {
                    this.tryDirectDecoding(imageData, codeReader);
                });
        } else {
            this.tryDirectDecoding(imageData, codeReader);
        }
    },

    tryDirectDecoding(imageData, codeReader) {
        // Método 4: decode directo
        try {
            if (typeof codeReader.decode === 'function') {
                const result = codeReader.decode(imageData);
                if (result && result.text) {
                    console.log('BarcodeScanner: ✅ Código detectado (decode directo):', result.text);
                    this.onBarcodeDetected(result.text);
                }
            }
        } catch (error) {
            // Silencioso - normal cuando no hay código
        }
    },

    // Función para actualizar el estado visual del escaneo
    updateScanStatus(message, type = 'waiting') {
        const statusElement = document.querySelector('.scan-status-text');
        const indicatorElement = document.querySelector('#scan-status span');
        
        if (statusElement) {
            statusElement.textContent = message;
        }
        
        if (indicatorElement) {
            // Cambiar el color del indicador según el tipo
            indicatorElement.className = {
                'waiting': 'text-green-400',
                'scanning': 'text-yellow-400',
                'success': 'text-green-500',
                'error': 'text-red-400',
                'processing': 'text-blue-400'
            }[type] || 'text-green-400';
            
            // Cambiar el símbolo del indicador
            indicatorElement.textContent = {
                'waiting': '●',
                'scanning': '◐',
                'success': '✓',
                'error': '✗',
                'processing': '◉'
            }[type] || '●';
        }
        
        // Auto-limpiar después de 2 segundos si es éxito o error
        if (type === 'success' || type === 'error') {
            setTimeout(() => {
                this.updateScanStatus('Listo para escanear', 'waiting');
            }, 2000);
        }
    },

    onBarcodeDetected(code) {
        console.log('BarcodeScanner: 🎯 ¡CÓDIGO DETECTADO!', code);
        
        // Actualizar estado visual
        this.updateScanStatus(`✅ ${code}`, 'success');
        
        // Pausar escaneo para evitar duplicados
        const wasScanning = this.isScanning;
        this.isScanning = false;
        setTimeout(() => {
            this.isScanning = wasScanning;
        }, 1000);

        // ESTRATEGIA 1: Emitir evento Livewire (preferida)
        try {
            console.log('BarcodeScanner: 🎯 Intentando Livewire emit...');
            if (window.Livewire && window.Livewire.emit) {
                window.Livewire.emit('codigoBarrasDetectado', code);
                console.log('BarcodeScanner: ✅ Evento Livewire emitido');
                this.playSuccessSound();
                this.showSuccessFeedback();
                return;
            }
        } catch (error) {
            console.error('BarcodeScanner: ❌ Error Livewire:', error);
        }

        // INSERCIÓN AUTOMÁTICA EN INPUT - SIN NECESIDAD DE ENFOQUE
        const input = document.getElementById('codigo_barras');
        if (input) {
            console.log('BarcodeScanner: 📝 Insertando código en input...');
            
            // Limpiar input primero
            input.value = '';
            
            // FORZAR ENFOQUE Y VALOR
            input.focus();
            input.value = code;
            
            // DISPARAR TODOS LOS EVENTOS NECESARIOS
            console.log('BarcodeScanner: 🔄 Disparando eventos...');
            
            // 1. Evento input para Livewire
            input.dispatchEvent(new Event('input', { 
                bubbles: true, 
                cancelable: true,
                composed: true 
            }));
            
            // 2. Evento change
            input.dispatchEvent(new Event('change', { 
                bubbles: true, 
                cancelable: true,
                composed: true 
            }));
            
            // 3. MÚLTIPLES ESTRATEGIAS PARA ACTIVAR LIVEWIRE
            
            // Estrategia A: Evento Livewire específico
            setTimeout(() => {
                console.log('BarcodeScanner: 🚀 Estrategia A - Livewire emit...');
                try {
                    if (window.Livewire) {
                        // Método 1: emit al componente
                        window.Livewire.emit('codigoBarrasDetectado', code);
                        console.log('BarcodeScanner: ✅ Livewire emit enviado');
                        
                        // Método 2: dispatchTo específico si conocemos el componente
                        window.Livewire.dispatchTo('sala-de-ventas.ventas', 'codigoBarrasDetectado', code);
                        console.log('BarcodeScanner: ✅ Livewire dispatchTo enviado');
                    }
                } catch (e) {
                    console.log('BarcodeScanner: Info - Livewire emit falló:', e.message);
                }
            }, 100);
            
            // Estrategia B: Simular entrada manual completa
            setTimeout(() => {
                console.log('BarcodeScanner: 🚀 Estrategia B - Simulación manual...');
                
                // Forzar enfoque nuevamente
                input.focus();
                input.select(); // Seleccionar todo el texto
                
                // Simular tecleo del código
                for (let i = 0; i < code.length; i++) {
                    const char = code[i];
                    input.dispatchEvent(new KeyboardEvent('keydown', {
                        key: char,
                        code: 'Digit' + char,
                        bubbles: true,
                        cancelable: true
                    }));
                    input.dispatchEvent(new KeyboardEvent('keypress', {
                        key: char,
                        code: 'Digit' + char,
                        bubbles: true,
                        cancelable: true
                    }));
                    input.dispatchEvent(new KeyboardEvent('keyup', {
                        key: char,
                        code: 'Digit' + char,
                        bubbles: true,
                        cancelable: true
                    }));
                }
                
                console.log('BarcodeScanner: ✅ Simulación de tecleo completada');
            }, 200);
            
            // Estrategia C: PRESIONAR ENTER AUTOMÁTICAMENTE - SIN NECESIDAD DE ENFOQUE
            setTimeout(() => {
                console.log('BarcodeScanner: 🚀 Estrategia C - Enter automático...');
                
                // Asegurar que el valor está establecido
                input.value = code;
                
                // Simular Enter con todas las variantes posibles
                const enterEvents = [
                    new KeyboardEvent('keydown', { key: 'Enter', keyCode: 13, which: 13, bubbles: true, cancelable: true }),
                    new KeyboardEvent('keypress', { key: 'Enter', keyCode: 13, which: 13, bubbles: true, cancelable: true }),
                    new KeyboardEvent('keyup', { key: 'Enter', keyCode: 13, which: 13, bubbles: true, cancelable: true })
                ];
                
                enterEvents.forEach(event => input.dispatchEvent(event));
                
                // También submit directo del formulario
                const form = input.closest('form');
                if (form) {
                    console.log('BarcodeScanner: 📤 Submit automático del formulario...');
                    
                    // Crear evento submit
                    const submitEvent = new Event('submit', {
                        bubbles: true,
                        cancelable: true
                    });
                    
                    // Prevenir envío real y usar wire:submit
                    submitEvent.preventDefault = () => {};
                    form.dispatchEvent(submitEvent);
                }
                
                console.log('BarcodeScanner: ✅ Enter automático completado');
                
            }, 500);
            
        } else {
            console.error('BarcodeScanner: ❌ Input codigo_barras no encontrado');
        }

        // MOSTRAR FEEDBACK VISUAL PROMINENTE
        this.showFeedback('🎯 CÓDIGO: ' + code);
        
        // EVENTO PERSONALIZADO PARA OTROS LISTENERS
        window.dispatchEvent(new CustomEvent('barcode-detected', { 
            detail: { 
                code: code,
                timestamp: new Date().toISOString()
            }
        }));
    },

    showFeedback(message) {
        console.log('BarcodeScanner: 🔔', message);
        
        // Mostrar en indicador visual si existe
        const indicator = document.getElementById('barcode-detected-indicator');
        if (indicator) {
            indicator.textContent = message;
            indicator.style.opacity = '1';
            indicator.style.transform = 'translate(-50%, -50%) translateY(60px) scale(1.1)';
            indicator.style.backgroundColor = '#10b981'; // Verde más brillante
            indicator.style.boxShadow = '0 4px 20px rgba(16, 185, 129, 0.5)';
            
            setTimeout(() => {
                indicator.style.opacity = '0';
                indicator.style.transform = 'translate(-50%, -50%) translateY(60px) scale(1)';
                indicator.style.boxShadow = 'none';
            }, 3000); // Mostrar por 3 segundos
        }
        
        // También mostrar notificación del navegador si es posible
        if ('Notification' in window) {
            if (Notification.permission === 'granted') {
                new Notification('Código de Barras Detectado', {
                    body: message,
                    icon: '/favicon.ico',
                    tag: 'barcode-scan',
                    requireInteraction: false
                });
            } else if (Notification.permission !== 'denied') {
                // Pedir permiso para notificaciones
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        new Notification('Código de Barras Detectado', {
                            body: message,
                            icon: '/favicon.ico',
                            tag: 'barcode-scan'
                        });
                    }
                });
            }
        }

        // Feedback audio (opcional)
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
            
            oscillator.start();
            oscillator.stop(audioContext.currentTime + 0.5);
        } catch (error) {
            // Audio no disponible - no problem
            console.log('BarcodeScanner: Audio feedback no disponible');
        }
    },

    stop() {
        console.log('BarcodeScanner: ⏹️ Deteniendo cámara...');
        
        this.isScanning = false;
        
        if (this.currentStream) {
            this.currentStream.getTracks().forEach(track => track.stop());
            this.currentStream = null;
        }
        
        if (this.videoElement) {
            this.videoElement.srcObject = null;
        }
        
        console.log('BarcodeScanner: ✅ Cámara detenida');
    }
};

console.log('BarcodeScanner: ✅ Sistema básico listo');

// Auto-inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    console.log('BarcodeScanner: DOM listo - sistema disponible para usar');
});
