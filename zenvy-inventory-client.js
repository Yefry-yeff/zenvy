/**
 * Zenvy Inventory API - JavaScript Client
 * 
 * Ejemplos de cómo consumir los endpoints de inventario desde el frontend
 * para sincronización en tiempo real
 */

class ZenvyInventoryClient {
  constructor(baseUrl = 'http://localhost:8000', token = '') {
    this.baseUrl = baseUrl;
    this.token = token;
    this.apiPrefix = '/api/v1/inventory';
    this.syncIntervals = {};
  }

  /**
   * Configurar token de autenticación
   */
  setToken(token) {
    this.token = token;
  }

  /**
   * Headers comunes para todas las peticiones
   */
  getHeaders() {
    return {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${this.token}`
    };
  }

  /**
   * Manejo centralizado de errores
   */
  handleError(error, context = '') {
    console.error(`[Inventory] Error en ${context}:`, error);
    throw error;
  }

  // ================================================
  // MÉTODO 1: Obtener productos por categoría
  // ================================================
  
  /**
   * Obtiene todos los productos con stock, agrupados por categoría
   * 
   * @returns {Promise} { categories, meta }
   */
  async getProductsByCategory() {
    try {
      const response = await fetch(
        `${this.baseUrl}${this.apiPrefix}/by-category`,
        {
          method: 'GET',
          headers: this.getHeaders()
        }
      );
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      const data = await response.json();
      return data;
    } catch (error) {
      this.handleError(error, 'getProductsByCategory');
    }
  }

  /**
   * Iniciar sincronización automática de productos por categoría
   * 
   * @param {Function} callback - Función a ejecutar cuando se actualicen
   * @param {Number} intervalMs - Milisegundos entre actualizaciones (default: 30000 = 30s)
   * @returns {String} ID del intervalo para detenerlo después
   */
  startProductsCategorySync(callback, intervalMs = 30000) {
    const syncId = 'products_category_sync';
    
    // Ejecutar inmediatamente
    this.getProductsByCategory()
      .then(data => callback(null, data))
      .catch(err => callback(err, null));
    
    // Sincronizar cada X segundos
    this.syncIntervals[syncId] = setInterval(() => {
      this.getProductsByCategory()
        .then(data => callback(null, data))
        .catch(err => callback(err, null));
    }, intervalMs);
    
    console.log(`[Inventory] Sincronización de productos iniciada (cada ${intervalMs}ms)`);
    return syncId;
  }

  /**
   * Detener sincronización de productos
   */
  stopProductsCategorySync() {
    const syncId = 'products_category_sync';
    if (this.syncIntervals[syncId]) {
      clearInterval(this.syncIntervals[syncId]);
      delete this.syncIntervals[syncId];
      console.log('[Inventory] Sincronización de productos detenida');
    }
  }

  // ================================================
  // MÉTODO 2: Obtener solo categorías
  // ================================================
  
  /**
   * Obtiene solo las categorías con stock disponible
   * Más ligero para actualizar selectores
   * 
   * @returns {Promise} { categories, meta }
   */
  async getCategories() {
    try {
      const response = await fetch(
        `${this.baseUrl}${this.apiPrefix}/categories`,
        {
          method: 'GET',
          headers: this.getHeaders()
        }
      );
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      return await response.json();
    } catch (error) {
      this.handleError(error, 'getCategories');
    }
  }

  /**
   * Iniciar sincronización automática de categorías
   * 
   * @param {Function} callback
   * @param {Number} intervalMs - Default: 300000 (5 minutos)
   */
  startCategoriesSync(callback, intervalMs = 300000) {
    const syncId = 'categories_sync';
    
    this.getCategories()
      .then(data => callback(null, data))
      .catch(err => callback(err, null));
    
    this.syncIntervals[syncId] = setInterval(() => {
      this.getCategories()
        .then(data => callback(null, data))
        .catch(err => callback(err, null));
    }, intervalMs);
    
    console.log(`[Inventory] Sincronización de categorías iniciada (cada ${intervalMs}ms)`);
    return syncId;
  }

  stopCategoriesSync() {
    const syncId = 'categories_sync';
    if (this.syncIntervals[syncId]) {
      clearInterval(this.syncIntervals[syncId]);
      delete this.syncIntervals[syncId];
      console.log('[Inventory] Sincronización de categorías detenida');
    }
  }

  // ================================================
  // MÉTODO 3: Inventario paginado
  // ================================================
  
  /**
   * Obtiene inventario completo con filtros
   * 
   * @param {Object} params - { per_page, page, category_id, search, etc }
   */
  async getInventory(params = {}) {
    try {
      const queryString = new URLSearchParams(params).toString();
      const url = `${this.baseUrl}${this.apiPrefix}?${queryString}`;
      
      const response = await fetch(url, {
        method: 'GET',
        headers: this.getHeaders()
      });
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      return await response.json();
    } catch (error) {
      this.handleError(error, 'getInventory');
    }
  }

  // ================================================
  // MÉTODO 4: Productos con stock bajo
  // ================================================
  
  /**
   * Obtiene productos con stock bajo
   * 
   * @param {Number} threshold - Cantidad mínima de stock
   */
  async getLowStockProducts(threshold = 10) {
    try {
      const response = await fetch(
        `${this.baseUrl}${this.apiPrefix}/low-stock?threshold=${threshold}`,
        {
          method: 'GET',
          headers: this.getHeaders()
        }
      );
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      return await response.json();
    } catch (error) {
      this.handleError(error, 'getLowStockProducts');
    }
  }

  // ================================================
  // MÉTODO 5: Validar stock
  // ================================================
  
  /**
   * Valida disponibilidad de stock para múltiples productos
   * 
   * @param {Array} items - [ { sku, quantity }, ... ]
   */
  async validateStock(items) {
    try {
      const response = await fetch(
        `${this.baseUrl}${this.apiPrefix}/validate-stock`,
        {
          method: 'POST',
          headers: this.getHeaders(),
          body: JSON.stringify({ items })
        }
      );
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      return await response.json();
    } catch (error) {
      this.handleError(error, 'validateStock');
    }
  }

  // ================================================
  // MÉTODO 6: Producto por SKU
  // ================================================
  
  /**
   * Obtiene un producto específico por SKU
   * 
   * @param {String} sku
   */
  async getProductBySku(sku) {
    try {
      const response = await fetch(
        `${this.baseUrl}${this.apiPrefix}/${sku}`,
        {
          method: 'GET',
          headers: this.getHeaders()
        }
      );
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      return await response.json();
    } catch (error) {
      this.handleError(error, 'getProductBySku');
    }
  }

  // ================================================
  // MÉTODO 7: Producto por código de barras
  // ================================================
  
  /**
   * Obtiene un producto por código de barras
   * 
   * @param {String} barcode
   */
  async getProductByBarcode(barcode) {
    try {
      const response = await fetch(
        `${this.baseUrl}${this.apiPrefix}/barcode/${barcode}`,
        {
          method: 'GET',
          headers: this.getHeaders()
        }
      );
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      return await response.json();
    } catch (error) {
      this.handleError(error, 'getProductByBarcode');
    }
  }

  /**
   * Detener todas las sincronizaciones
   */
  stopAllSync() {
    Object.values(this.syncIntervals).forEach(interval => clearInterval(interval));
    this.syncIntervals = {};
    console.log('[Inventory] Todas las sincronizaciones detenidas');
  }
}

// ================================================
// EJEMPLOS DE USO
// ================================================

/*
// 1. Inicializar cliente
const inventory = new ZenvyInventoryClient('http://localhost:8000', 'tu_token_jwt');

// 2. Sincronizar productos cada 30 segundos
inventory.startProductsCategorySync((error, data) => {
  if (error) {
    console.error('Error sincronizando:', error);
    return;
  }
  
  console.log('Productos actualizados:', data.data);
  console.log('Meta:', data.meta);
  
  // Actualizar UI aquí
  renderProductsByCategory(data.data);
}, 30000);

// 3. Sincronizar categorías cada 5 minutos
inventory.startCategoriesSync((error, data) => {
  if (error) {
    console.error('Error sincronizando categorías:', error);
    return;
  }
  
  console.log('Categorías actualizadas:', data.data);
  
  // Actualizar selector
  updateCategoryDropdown(data.data);
}, 5 * 60 * 1000);

// 4. Validar stock antes de comprar
const cartItems = [
  { sku: 'AGD-001', quantity: 5 },
  { sku: 'CUA-001', quantity: 2 }
];

inventory.validateStock(cartItems).then(result => {
  if (result.data.available) {
    console.log('Stock disponible, proceder con compra');
  } else {
    console.log('Stock insuficiente para algunos productos:', result.data.items);
  }
});

// 5. Detener sincronización cuando sea necesario
inventory.stopProductsCategorySync();
inventory.stopCategoriesSync();
// O detener todo:
inventory.stopAllSync();
*/

// Exportar para uso modular
if (typeof module !== 'undefined' && module.exports) {
  module.exports = ZenvyInventoryClient;
}
