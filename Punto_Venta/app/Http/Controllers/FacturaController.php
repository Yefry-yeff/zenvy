<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Factura;

class FacturaController extends Controller
{
    public function detalle($id)
    {
        $factura = Factura::findOrFail($id);
        
        return view('livewire.sala-de-ventas.factura-impresion', compact('factura'));
    }
}
