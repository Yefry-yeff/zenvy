<?php

namespace App\Livewire\Gestion;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\TipoDocumentoFiscal;
use App\Models\Tiendas;
use App\Models\Cai as caiModel;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use App\Models\GestionCai;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class Cai extends Component
{

    use WithPagination; // Usa el trait

     public $form = [
        'id' => null,
        'cai' => '',
        'fecha_limite_emision' => '',
        'fecha_solicitud' => '',
        'punto_emision' => '',
        'tipo_documento_fiscal_id' => '',
        'cantidad_solicitada' => '',
        'cantidad_otorgada' => '',
        'rango_inicio' => '',
        'rango_final' => '',
        'tienda_id' => '',
        'users_registro_id' => '',
        'estado_id' => '',
        'created_at' => '',
        'updated_at' => '',
    ];
    public $modalAbierto = false;

    public $modalCrearAbierto = false;
    //variables a recibir por el front
    public $nuevoCai = '';
    public $nuevoFechaLimite = '';
    public $nuevoFechaSolicitud = '';
    public $nuevoPuntoEmision = '';
    public $tipoDocumentoSeleccionado = '';
    public $tiendaSeleccionado = '';
    public $nuevoCantidadOtorgada = '';
    public $nuevoCantidadSolicitada = '';
    public $nuevoRangoFinal = '';
    public $nuevoRangoInicial = '';
    public $modalEliminarAbierto = false;
    public $marcaAEliminar = null;

    public $tiposDocumento;
    public $tiendas;

    protected $paginationTheme = 'bootstrap';

    public function render()
    {
            $cai = DB::table('cai as A')
                ->join('tipo_documento_fiscal as B', 'A.tipo_documento_fiscal_id', '=', 'B.id')
                ->join('users as C', 'C.id', '=', 'A.users_registro_id')
                ->join('tienda as D', 'D.id', '=', 'A.tienda_id')
                ->select(
                    'A.id',
                    'A.cai',
                    'A.fecha_limite_emision',
                    'A.fecha_solicitud',
                    'A.punto_emision',
                    'B.nombre as tipo_documento_fiscal',
                    'A.cantidad_solicitada',
                    'A.cantidad_otorgada',
                    'A.rango_inicio',
                    'A.rango_final',
                    'D.denominacion_social',
                    'D.numero_sucursal',
                    'C.name as users_registro',
                    'A.estado_id',
                    'A.created_at',
                    'A.updated_at'
                )
                ->orderBy('A.created_at', 'desc')
                ->paginate(10);

            return view('livewire.gestion.cai', compact('cai'));
    }

    public function mount()
    {
        $this->tiposDocumento = TipoDocumentoFiscal::all();
        $this->tiendas =  Tiendas::select('id', 'denominacion_social', 'numero_sucursal','identificador_legal')->get();
    }

    public function crearCai()
    {
        try {
            $this->validate([
                'nuevoCai'             => 'required|string',
                'nuevoFechaLimite'     => 'required|date',
                'nuevoFechaSolicitud'  => 'required|date',
                'nuevoPuntoEmision'    => 'required|string|max:255',
                'tipoDocumentoSeleccionado' => 'required|exists:tipo_documento_fiscal,id',
                'tiendaSeleccionado'   => 'required|exists:tienda,id',
                'nuevoCantidadOtorgada' => 'required|integer|min:1',
                'nuevoCantidadSolicitada' => 'required|integer|min:1',
                'nuevoRangoInicial'  => 'required|string',
                'nuevoRangoFinal'  => 'required|string',
            ]);

            // Verificar si existe un CAI activo para esta tienda
            $caiAnterior = caiModel::where('tienda_id', $this->tiendaSeleccionado)
                        ->where('estado_id', 1)
                        ->first();

            if ($caiAnterior) {
                // Inactivar el CAI anterior
                caiModel::where('id', $caiAnterior->id)
                    ->update(['estado_id' => 2]);

                // Inactivar la gestión CAI anterior
                GestionCai::where('cai_id', $caiAnterior->id)
                    ->where('estado_id', 1)
                    ->update(['estado_id' => 2]);
            }

            // Crear el nuevo CAI
            $nuevoCai = new caiModel();
            $nuevoCai->cai = $this->nuevoCai;
            $nuevoCai->fecha_limite_emision = $this->nuevoFechaLimite;
            $nuevoCai->fecha_solicitud = $this->nuevoFechaSolicitud;
            $nuevoCai->punto_emision = $this->nuevoPuntoEmision;
            $nuevoCai->tipo_documento_fiscal_id = $this->tipoDocumentoSeleccionado;
            $nuevoCai->cantidad_solicitada = $this->nuevoCantidadSolicitada;
            $nuevoCai->cantidad_otorgada = $this->nuevoCantidadOtorgada;
            $nuevoCai->rango_inicio = $this->nuevoRangoInicial;
            $nuevoCai->rango_final = $this->nuevoRangoFinal;
            $nuevoCai->tienda_id = $this->tiendaSeleccionado;
            $nuevoCai->users_registro_id = Auth::user()->id;
            $nuevoCai->estado_id = 1;
            $nuevoCai->save();

            // Crear el registro en gestion_cai con la lógica especificada
            $this->crearGestionCai($nuevoCai);

            // Limpiar campos
            $this->limpiarCampos();
            
            $this->modalCrearAbierto = false;
            session()->flash('mensaje', 'CAI registrado exitosamente.');

        } catch (ValidationException $e) {
            session()->flash('error', 'Error de validación: ' . $e->getMessage());
        } catch (QueryException $e) {
            session()->flash('error', 'Error en la base de datos: ' . $e->getMessage());
        } catch (\Throwable $e) {
            session()->flash('error', 'Error inesperado: ' . $e->getMessage());
        }
    }

    private function crearGestionCai($cai)
    {
        // Extraer número actual del rango_inicio
        // Formato esperado: XXX-XXX-XX-00000001
        $rangoInicioParts = explode('-', $cai->rango_inicio);
        $numeroInicialStr = end($rangoInicioParts); // Obtener la última parte
        $numeroActual = (int) ltrim($numeroInicialStr, '0'); // Quitar ceros a la izquierda y convertir a entero

        // Extraer cantidad no utilizada del rango_final
        $rangoFinalParts = explode('-', $cai->rango_final);
        $numeroFinalStr = end($rangoFinalParts); // Obtener la última parte
        $cantidadNoUtilizada = (int) ltrim($numeroFinalStr, '0'); // Quitar ceros a la izquierda y convertir a entero

        // Crear número base (todo antes del último guion, incluyendo el guion)
        $numeroBase = substr($cai->rango_inicio, 0, strrpos($cai->rango_inicio, '-') + 1);

        // Crear registro en gestion_cai
        $gestionCai = new GestionCai();
        $gestionCai->numero_actual = $numeroActual;
        $gestionCai->numero_base = $numeroBase;
        $gestionCai->serie = null; // Puedes ajustar esto según tus necesidades
        $gestionCai->cantidad_no_utilizada = $cantidadNoUtilizada;
        $gestionCai->cai_id = $cai->id;
        $gestionCai->estado_id = 1; // Activo
        $gestionCai->save();
    }

    private function limpiarCampos()
    {
        $this->nuevoCai = '';
        $this->nuevoFechaLimite = '';
        $this->nuevoFechaSolicitud = '';
        $this->nuevoPuntoEmision = '';
        $this->tipoDocumentoSeleccionado = '';
        $this->tiendaSeleccionado = '';
        $this->nuevoCantidadSolicitada = '';
        $this->nuevoCantidadOtorgada = '';
        $this->nuevoRangoInicial = '';
        $this->nuevoRangoFinal = '';
    }


    public function abrirModalCrear()
    {
        $this->modalCrearAbierto = true;
        $this->limpiarCampos();
    }

    public function cerrarModalCrear()
    {
        $this->modalCrearAbierto = false;
    }

    public function cerrarModal()
    {
        $this->modalAbierto = false;
    }



}
