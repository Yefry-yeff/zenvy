// Event listeners para Dashboard dinámico
// Este archivo maneja los eventos de Livewire para actualizar los gráficos

document.addEventListener('livewire:init', () => {
    console.log('Dashboard events inicializados con Livewire');
    
    // Escuchar eventos de Livewire
    Livewire.on('dashboardRenderizado', () => {
        console.log('Evento dashboardRenderizado recibido');
        if (typeof window.chartsInitialized !== 'undefined' && window.chartsInitialized) {
            if (typeof destroyCharts === 'function') {
                destroyCharts();
            }
        }
        setTimeout(() => {
            if (typeof initCharts === 'function') {
                initCharts();
            }
        }, 150);
    });
    
    Livewire.on('datosActualizados', () => {
        console.log('Evento datosActualizados recibido');
        if (typeof window.chartsInitialized !== 'undefined' && window.chartsInitialized) {
            if (typeof destroyCharts === 'function') {
                destroyCharts();
            }
        }
        setTimeout(() => {
            if (typeof initCharts === 'function') {
                initCharts();
            }
        }, 150);
    });
});
