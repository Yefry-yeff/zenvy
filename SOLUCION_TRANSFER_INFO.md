# SOLUCIÓN: Transfer_Info no se guardaba

## Problema Identificado
Los pedidos PW-2026-0039 y PW-2026-0040 no guardaban la información de `transfer_info` porque:

1. **La página web está usando el endpoint**: `POST /api/v1/sales/preview`
2. **Este endpoint usa**: `SalesController` → `SalesService` (NO OrderService)
3. **El Request**: `CreateSaleRequest` no tenía validación para `transfer_info`
4. **Resultado**: Aunque el JSON incluía `transfer_info`, `$request->validated()` lo descartaba

## Solución Aplicada

### Archivo: `app/Http/Requests/Api/CreateSaleRequest.php`
Se agregaron las siguientes reglas de validación:

```php
'shipping_cost' => 'nullable|numeric|min:0|max:999999',
'delivery_type' => 'required|in:retiro_tienda,domicilio,recoger',
'transfer_info' => 'nullable|array',
'transfer_info.account_bank' => 'nullable|string|max:100',
'transfer_info.account_type' => 'nullable|string|max:50',
'transfer_info.account_number' => 'nullable|string|max:50',
'transfer_info.account_holder' => 'nullable|string|max:200',
'transfer_info.transfer_date' => 'nullable|date',
'external_order_id' => 'nullable|string|max:100',
```

### Archivo: `app/Services/Api/SalesService.php`
El código ya estaba correcto en las líneas 53-56:

```php
// Agregar información de transferencia bancaria si existe
if (isset($data['transfer_info'])) {
    $metadata['transfer_info'] = $data['transfer_info'];
}
```

## Prueba
Crear un nuevo pedido desde la página web. Los nuevos pedidos SÍ guardarán el `transfer_info` correctamente.

## Pedidos Anteriores
Los pedidos PW-2026-0039 y PW-2026-0040 fueron creados antes de este fix, por eso no tienen la información.

## Endpoint Correcto
- **URL**: `POST /api/v1/sales/preview`
- **Autenticación**: Bearer token (API Client)
- **Request**: CreateSaleRequest
- **Service**: SalesService
- **Método**: createPreviewOrder()
