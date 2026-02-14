<?php

// Script para probar el procesamiento de transfer_info

$jsonData = '{
  "customer_name": "Johann Sebastian Ruiz Quiroz",
  "customer_email": "johann_ruiz14@hotmail.com",
  "customer_phone": "+50497525987",
  "customer_rtn": "",
  "items": [
    {
      "product_id": 1788,
      "quantity": 1,
      "price": 21.74,
      "discount": 0
    }
  ],
  "delivery_type": "domicilio",
  "delivery_address": "El centro de AMDC",
  "payment_method": "Transferencia Bancaria",
  "notes": "",
  "subtotal": 21.74,
  "discount": 0,
  "shipping_cost": 60,
  "tax": 3.26,
  "total": 25,
  "transfer_info": {
    "account_bank": "BAC",
    "account_type": "ahorro",
    "account_number": "7777725",
    "account_holder": "Paperland",
    "transfer_date": "2026-01-01"
  }
}';

$data = json_decode($jsonData, true);

echo "============================================\n";
echo "SIMULACIÓN DE PROCESAMIENTO\n";
echo "============================================\n\n";

// Simular lo que hace OrderService
$metadata = [
    'api_client' => 'Test Client',
    'delivery_type' => $data['delivery_type'] ?? null,
    'delivery_address' => $data['delivery_address'] ?? null,
    'shipping_cost' => $data['shipping_cost'] ?? 0,
];

echo "Metadata ANTES de agregar transfer_info:\n";
print_r($metadata);

// Agregar transfer_info si existe
if (isset($data['transfer_info']) && is_array($data['transfer_info'])) {
    echo "\n✓ transfer_info existe y es un array\n";
    $metadata['transfer_info'] = $data['transfer_info'];
} else {
    echo "\n✗ transfer_info NO existe o no es un array\n";
    echo "isset: " . (isset($data['transfer_info']) ? 'YES' : 'NO') . "\n";
    echo "is_array: " . (is_array($data['transfer_info'] ?? null) ? 'YES' : 'NO') . "\n";
}

echo "\nMetadata DESPUÉS de agregar transfer_info:\n";
print_r($metadata);

echo "\n============================================\n";
echo "VERIFICACIÓN FINAL\n";
echo "============================================\n";
echo "¿Existe metadata['transfer_info']? " . (isset($metadata['transfer_info']) ? 'SI' : 'NO') . "\n";
echo "Contenido de transfer_info:\n";
if (isset($metadata['transfer_info'])) {
    print_r($metadata['transfer_info']);
}

echo "\nJSON resultante:\n";
echo json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
