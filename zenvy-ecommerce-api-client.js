/**
 * Cliente JavaScript para la API de Ventas E-commerce de Zenvy
 * 
 * Este módulo proporciona funciones para interactuar con la API de ventas
 * desde una aplicación web frontend (React, Vue, Angular, etc.)
 */

class ZenvyEcommerceAPI {
    /**
     * Constructor
     * @param {string} baseUrl - URL base de la API (ej: https://api.mitienda.com)
     * @param {string} apiKey - API Key para autenticación
     */
    constructor(baseUrl, apiKey) {
        this.baseUrl = baseUrl.replace(/\/$/, ''); // Remover slash final
        this.apiKey = apiKey;
    }

    /**
     * Realizar petición HTTP
     * @private
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        
        const config = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-API-Key': this.apiKey,
                ...options.headers
            },
            ...options
        };

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!response.ok) {
                throw new APIError(data, response.status);
            }

            return data;
        } catch (error) {
            if (error instanceof APIError) {
                throw error;
            }
            throw new APIError({
                success: false,
                error: {
                    code: 'NETWORK_ERROR',
                    message: error.message
                }
            }, 0);
        }
    }

    /**
     * Crear una nueva venta
     * @param {Object} saleData - Datos de la venta
     * @returns {Promise<Object>} Respuesta de la API
     */
    async createSale(saleData) {
        return await this.request('/api/v1/sales', {
            method: 'POST',
            body: JSON.stringify(saleData)
        });
    }

    /**
     * Obtener información de una venta
     * @param {number} saleId - ID de la factura
     * @returns {Promise<Object>} Información de la venta
     */
    async getSale(saleId) {
        return await this.request(`/api/v1/sales/${saleId}`, {
            method: 'GET'
        });
    }

    /**
     * Anular una venta
     * @param {number} saleId - ID de la factura
     * @param {string} reason - Motivo de anulación
     * @returns {Promise<Object>} Respuesta de la API
     */
    async cancelSale(saleId, reason) {
        return await this.request(`/api/v1/sales/${saleId}/cancel`, {
            method: 'PUT',
            body: JSON.stringify({ motivo: reason })
        });
    }

    /**
     * Validar datos de cliente antes de enviar
     * @param {Object} customer - Datos del cliente
     * @returns {Object} Errores de validación (vacío si todo está bien)
     */
    validateCustomer(customer) {
        const errors = {};

        if (!customer.name || customer.name.trim().length === 0) {
            errors.name = 'El nombre es requerido';
        }

        if (!customer.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(customer.email)) {
            errors.email = 'Email inválido';
        }

        if (!customer.phone || !/^\+504\d{8}$/.test(customer.phone)) {
            errors.phone = 'Teléfono debe estar en formato +504XXXXXXXX';
        }

        return errors;
    }

    /**
     * Validar datos de entrega
     * @param {Object} delivery - Datos de entrega
     * @returns {Object} Errores de validación
     */
    validateDelivery(delivery) {
        const errors = {};

        if (!delivery.type || !['retiro_tienda', 'domicilio'].includes(delivery.type)) {
            errors.type = 'Tipo de entrega debe ser "retiro_tienda" o "domicilio"';
        }

        if (delivery.type === 'domicilio' && (!delivery.address || delivery.address.trim().length === 0)) {
            errors.address = 'La dirección es requerida para entregas a domicilio';
        }

        return errors;
    }

    /**
     * Calcular totales de la venta
     * @param {Array} items - Items de la venta
     * @returns {Object} Totales calculados
     */
    calculateTotals(items) {
        let subtotal = 0;
        let totalDiscount = 0;

        items.forEach(item => {
            const itemSubtotal = item.quantity * item.price;
            const itemDiscount = (itemSubtotal * (item.discount || 0)) / 100;
            
            subtotal += itemSubtotal - itemDiscount;
            totalDiscount += itemDiscount;
        });

        const tax = subtotal * 0.15; // ISV 15%
        const total = subtotal + tax;

        return {
            subtotal: parseFloat(subtotal.toFixed(2)),
            discount: parseFloat(totalDiscount.toFixed(2)),
            tax: parseFloat(tax.toFixed(2)),
            total: parseFloat(total.toFixed(2))
        };
    }
}

/**
 * Clase de error personalizada para la API
 */
class APIError extends Error {
    constructor(data, statusCode) {
        super(data.error?.message || 'Error desconocido');
        this.name = 'APIError';
        this.code = data.error?.code || 'UNKNOWN_ERROR';
        this.details = data.error?.details || {};
        this.statusCode = statusCode;
    }

    /**
     * Verificar si es un error de validación
     */
    isValidationError() {
        return this.code === 'VALIDATION_ERROR';
    }

    /**
     * Verificar si es un error de stock
     */
    isStockError() {
        return this.code === 'INSUFFICIENT_STOCK';
    }

    /**
     * Obtener mensajes de error legibles
     */
    getUserFriendlyMessage() {
        switch (this.code) {
            case 'INSUFFICIENT_STOCK':
                return 'No hay stock suficiente para completar tu pedido';
            case 'VALIDATION_ERROR':
                return 'Por favor verifica que todos los datos estén correctos';
            case 'PRODUCT_NOT_FOUND':
                return 'Uno o más productos no están disponibles';
            case 'NETWORK_ERROR':
                return 'Error de conexión. Por favor intenta nuevamente';
            default:
                return this.message;
        }
    }
}

// ============================================================================
// EJEMPLO DE USO CON REACT
// ============================================================================

/**
 * Ejemplo de componente React para checkout
 */
const CheckoutExample = () => {
    const [loading, setLoading] = React.useState(false);
    const [error, setError] = React.useState(null);

    // Inicializar API
    const api = new ZenvyEcommerceAPI(
        'https://api.mitienda.com',
        'tu-api-key-aqui'
    );

    const handleCheckout = async (cartItems, customer, delivery, paymentMethod) => {
        setLoading(true);
        setError(null);

        try {
            // 1. Validar datos del cliente
            const customerErrors = api.validateCustomer(customer);
            if (Object.keys(customerErrors).length > 0) {
                setError(customerErrors);
                setLoading(false);
                return;
            }

            // 2. Validar datos de entrega
            const deliveryErrors = api.validateDelivery(delivery);
            if (Object.keys(deliveryErrors).length > 0) {
                setError(deliveryErrors);
                setLoading(false);
                return;
            }

            // 3. Preparar items
            const items = cartItems.map(item => ({
                product_id: item.id,
                quantity: item.quantity,
                price: item.price,
                discount: item.discount || 0
            }));

            // 4. Calcular totales
            const totals = api.calculateTotals(items);

            // 5. Preparar datos de la venta
            const saleData = {
                customer_name: customer.name,
                customer_email: customer.email,
                customer_phone: customer.phone,
                customer_rtn: customer.rtn || null,
                
                items: items,
                
                delivery_type: delivery.type,
                delivery_address: delivery.type === 'domicilio' ? delivery.address : null,
                
                payment_method: paymentMethod,
                notes: customer.notes || null,
                
                ...totals
            };

            // 6. Crear la venta
            const response = await api.createSale(saleData);

            // 7. Éxito - Redirigir o mostrar confirmación
            console.log('Venta creada:', response.data);
            alert(`¡Pedido confirmado! Factura #${response.data.factura_id}`);
            
            // Aquí podrías redirigir a una página de confirmación
            // window.location.href = `/confirmacion/${response.data.factura_id}`;

        } catch (err) {
            if (err instanceof APIError) {
                console.error('Error de API:', err);
                setError(err.getUserFriendlyMessage());
                
                // Manejar errores específicos
                if (err.isStockError()) {
                    // Mostrar productos sin stock
                    console.log('Productos sin stock:', err.details);
                }
                
                if (err.isValidationError()) {
                    // Mostrar errores de validación
                    console.log('Errores de validación:', err.details);
                }
            } else {
                console.error('Error inesperado:', err);
                setError('Ocurrió un error inesperado. Por favor intenta nuevamente.');
            }
        } finally {
            setLoading(false);
        }
    };

    return (
        <div>
            {/* Tu formulario de checkout aquí */}
            {error && <div className="error">{error}</div>}
            <button onClick={handleCheckout} disabled={loading}>
                {loading ? 'Procesando...' : 'Confirmar Pedido'}
            </button>
        </div>
    );
};

// ============================================================================
// EJEMPLO DE USO CON VANILLA JAVASCRIPT
// ============================================================================

async function ejemploVanillaJS() {
    // Inicializar cliente API
    const api = new ZenvyEcommerceAPI(
        'https://api.mitienda.com',
        'tu-api-key-aqui'
    );

    // Datos del carrito
    const cartItems = [
        { id: 25, quantity: 2, price: 150.00, discount: 10 },
        { id: 42, quantity: 1, price: 300.00, discount: 0 }
    ];

    // Datos del cliente
    const customer = {
        name: 'María González',
        email: 'maria@example.com',
        phone: '+50498765432',
        rtn: '08011985654321'
    };

    // Datos de entrega
    const delivery = {
        type: 'domicilio',
        address: 'Colonia Trejo, Casa 25, Tegucigalpa'
    };

    // Preparar items para la API
    const items = cartItems.map(item => ({
        product_id: item.id,
        quantity: item.quantity,
        price: item.price,
        discount: item.discount || 0
    }));

    // Calcular totales
    const totals = api.calculateTotals(items);

    // Crear venta
    try {
        const response = await api.createSale({
            customer_name: customer.name,
            customer_email: customer.email,
            customer_phone: customer.phone,
            customer_rtn: customer.rtn,
            
            items: items,
            
            delivery_type: delivery.type,
            delivery_address: delivery.address,
            
            payment_method: 'Tarjeta de Crédito',
            notes: 'Entregar en horario de tarde',
            
            ...totals
        });

        console.log('✅ Venta creada exitosamente:', response);
        console.log('Factura ID:', response.data.factura_id);
        
    } catch (error) {
        if (error instanceof APIError) {
            console.error('❌ Error:', error.getUserFriendlyMessage());
            console.error('Detalles:', error.details);
        } else {
            console.error('❌ Error inesperado:', error);
        }
    }
}

// ============================================================================
// UTILIDADES ADICIONALES
// ============================================================================

/**
 * Formateador de teléfono hondureño
 */
function formatHondurasPhone(phone) {
    // Remover todo excepto números
    const cleaned = phone.replace(/\D/g, '');
    
    // Si empieza con 504, agregar +
    if (cleaned.startsWith('504') && cleaned.length === 11) {
        return '+' + cleaned;
    }
    
    // Si tiene 8 dígitos, agregar +504
    if (cleaned.length === 8) {
        return '+504' + cleaned;
    }
    
    return phone;
}

/**
 * Validador de email
 */
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

/**
 * Formateador de moneda hondureña
 */
function formatLempiras(amount) {
    return new Intl.NumberFormat('es-HN', {
        style: 'currency',
        currency: 'HNL'
    }).format(amount);
}

// Exportar para uso en módulos ES6
export { ZenvyEcommerceAPI, APIError, formatHondurasPhone, isValidEmail, formatLempiras };

// Para uso en navegador sin módulos
if (typeof window !== 'undefined') {
    window.ZenvyEcommerceAPI = ZenvyEcommerceAPI;
    window.APIError = APIError;
}
