/**
 * Escáner de Códigos de Barras Simplificado - Versión de Respaldo
 * Sistema básico que funciona sin ZXing como fallback
 */

console.log('BarcodeScanner: Iniciando versión simplificada...');

// Crear un escáner básico que siempre funcione
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
                this.updateScanStatus('Detector listo - Escaneando...', 'scanning');
                this.startZXingScanning();
                return;
            }
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        
        console.log('BarcodeScanner: ⚠️ ZXing no disponible, solo modo manual');
        this.updateScanStatus('Modo manual - ZXing no disponible', 'error');
    },

    startZXingScanning() {
        try {
            const codeReader = new ZXing.BrowserMultiFormatReader();
            console.log('BarcodeScanner: 🔍 ZXing reader inicializado');
            console.log('BarcodeScanner: Métodos disponibles:', Object.getOwnPropertyNames(codeReader));

            let scanCount = 0;
            
            // Escaneo continuo - MÁS RÁPIDO
            const scanInterval = setInterval(() => {
                scanCount++;
                
                if (!this.isScanning || !this.videoElement) {
                    clearInterval(scanInterval);
                    return;
                }

                if (this.videoElement.readyState === 4 && this.videoElement.videoWidth > 0) {
                    
                    // Log cada 50 intentos para monitoring + status update
                    if (scanCount % 50 === 0) {
                        console.log(`BarcodeScanner: 📊 Intento #${scanCount} - Video: ${this.videoElement.videoWidth}x${this.videoElement.videoHeight}`);
                        this.updateScanStatus(`Escaneando... (${scanCount} intentos)`, 'scanning');
                    }
                    
                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');
                    
                    // Usar dimensiones reales del video
                    const width = this.videoElement.videoWidth;
                    const height = this.videoElement.videoHeight;
                    
                    canvas.width = width;
                    canvas.height = height;
                    
                    try {
                        context.drawImage(this.videoElement, 0, 0, width, height);
                        
                        // Mejorar contraste para mejor lectura
                        const imageData = context.getImageData(0, 0, width, height);
                        const data = imageData.data;
                        
                        // Aumentar contraste
                        for (let i = 0; i < data.length; i += 4) {
                            const brightness = (data[i] + data[i + 1] + data[i + 2]) / 3;
                            const contrast = brightness > 120 ? 255 : 0;
                            data[i] = data[i + 1] = data[i + 2] = contrast;
                        }
                        context.putImageData(imageData, 0, 0);
                        
                        // MÚLTIPLES MÉTODOS DE DETECCIÓN PARA MEJOR COMPATIBILIDAD
                        
                        // Método 1: decodeFromCanvas (más común)
                        if (typeof codeReader.decodeFromCanvas === 'function') {
                            codeReader.decodeFromCanvas(canvas)
                                .then(result => {
                                    if (result && result.text) {
                                        console.log('BarcodeScanner: ✅ Código detectado (decodeFromCanvas):', result.text);
                                        this.onBarcodeDetected(result.text);
                                    }
                                })
                                .catch(() => {
                                    // Intentar método 2 si falla el primero
                                    this.tryAlternativeDecoding(canvas, imageData, codeReader);
                                });
                        } else {
                            // Si no existe decodeFromCanvas, usar alternativas directamente
                            this.tryAlternativeDecoding(canvas, imageData, codeReader);
                        }
                    } catch (error) {
                        if (scanCount % 100 === 0) { // Log cada 100 errores
                            console.log('BarcodeScanner: Info frame error:', error.message);
                        }
                    }
                }
            }, 200); // Más rápido - cada 200ms

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
        
        // Auto-limpiar después de 3 segundos si es éxito o error
        if (type === 'success' || type === 'error') {
            setTimeout(() => {
                this.updateScanStatus('Esperando código...', 'waiting');
            }, 3000);
        }
    },

    onBarcodeDetected(code) {
        console.log('BarcodeScanner: 🎯 ¡CÓDIGO DETECTADO!', code);
        
        // Actualizar estado visual
        this.updateScanStatus(`Procesando: ${code}`, 'processing');
        
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
                this.updateScanStatus(`✅ Código enviado: ${code}`, 'success');
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
