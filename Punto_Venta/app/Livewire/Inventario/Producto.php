<?php

namespace App\Livewire\Inventario;

use Livewire\Component;

class Producto extends Component
{
    public $productos = [];
    public $nuevoNombreProducto = '';
    public $nuevaCategoria = '';
    public $nuevoPrecio = '';
    public $selectedProducto = [
        'id' => null,
        'nombre' => '',
        'categoria' => '',
        'precio' => ''
    ];

    public function mount()
    {
        $this->cargarProductos();
    }

    public function cargarProductos()
    {
        $this->productos = \App\Models\Producto::all(['id', 'nombre', 'categoria', 'precio']);
    }

    public function crearProducto()
    {
        \App\Models\Producto::create([
            'nombre' => $this->nuevoNombreProducto,
            'categoria' => $this->nuevaCategoria,
            'precio' => $this->nuevoPrecio
        ]);
        $this->nuevoNombreProducto = '';
        $this->nuevaCategoria = '';
        $this->nuevoPrecio = '';
        $this->cargarProductos();
        session()->flash('success', 'Producto agregado correctamente.');
    }

    public function selectProducto($id)
    {
        $producto = \App\Models\Producto::find($id);
        if ($producto) {
            $this->selectedProducto = [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'categoria' => $producto->categoria,
                'precio' => $producto->precio
            ];
        }
    }

    public function updateProducto()
    {
        if ($this->selectedProducto['id']) {
            $producto = \App\Models\Producto::find($this->selectedProducto['id']);
            if ($producto) {
                $producto->nombre = $this->selectedProducto['nombre'];
                $producto->categoria = $this->selectedProducto['categoria'];
                $producto->precio = $this->selectedProducto['precio'];
                $producto->save();
                $this->cargarProductos();
                session()->flash('success', 'Producto actualizado correctamente.');
            }
        }
    }

    public function render()
    {
        return view('livewire.inventario.producto', [
            'productos' => $this->productos
        ]);
    }
}
