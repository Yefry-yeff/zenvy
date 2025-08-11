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

class Cai extends Component
{

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
                    'C.name as users_registro',
                    'A.estado_id',
                    'A.created_at',
                    'A.updated_at'
                )
                ->orderBy('A.estado_id', 'ASC') // Activos (1) primero, luego inactivos (2)
                ->orderBy('A.created_at', 'DESC') // Luego por fecha de creación más reciente
                ->get();

            return view('livewire.gestion.cai', compact('cai'));
    }

    public function mount()
    {
        $this->tiposDocumento = TipoDocumentoFiscal::all();
        $this->tiendas =  Tiendas::select('id', 'denominacion_social', 'numero_sucursal','identificador_legal')->get();
    }

    public function crearCai()
    {
        //dd("entra");
        try {
            // Validar datos del formulario
            $this->validate([
                'nuevoCai'             => 'required|string|max:60',
                'nuevoFechaLimite'     => 'required|date|after:today',
                'nuevoFechaSolicitud'  => 'required|date',
                'nuevoPuntoEmision'    => 'required|string|max:100',
                'tipoDocumentoSeleccionado' => 'required|exists:tipo_documento_fiscal,id',
                'tiendaSeleccionado'   => 'required|exists:tienda,id',
                'nuevoCantidadSolicitada' => 'required|integer|min:1',
                'nuevoCantidadOtorgada' => 'required|integer|min:1',
                'nuevoRangoInicial'  => 'required|string|max:45',
                'nuevoRangoFinal'  => 'required|string|max:45',
            ]);

            // Iniciar transacción para mantener integridad de datos
            DB::beginTransaction();

            $docFiscal = caiModel::where('tipo_documento_fiscal_id', $this->tipoDocumentoSeleccionado)
                        ->where('tienda_id',$this->tiendaSeleccionado)
                        ->where('estado_id', 1)
                        ->exists();

            if ($docFiscal) {
                //Se recupera el cai para darle en la nuca
                $cai = caiModel::where('tipo_documento_fiscal_id', $this->tipoDocumentoSeleccionado)
                        ->where('tienda_id',$this->tiendaSeleccionado)
                        ->where('estado_id', 1)
                        ->first();

                //Inactivando el cai actual
                caiModel::where('id', $cai->id)
                ->update(['estado_id' => 2]);

                //Inactivando la secuencia Actuál activa para ese cai dekeu

                GestionCai::where('cai_id', $cai->id)
                ->update(['estado_id' => 2]);

                /* Creación de registros en ambas tablas, cai como principal y gestion_cai
                como la tabla donde se registrara y actualizará cada vez que se facture
                o se mueva el correlativo */
                //dd("llega");
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

                // Crear automáticamente el registro en gestion_cai
                $this->crearGestionCai($nuevoCai->id, $this->nuevoRangoInicial, $this->nuevoRangoFinal);

            }else{
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
                
                // Crear automáticamente el registro en gestion_cai
                $this->crearGestionCai($nuevoCai->id, $this->nuevoRangoInicial, $this->nuevoRangoFinal);
            }

            // Confirmar transacción
            DB::commit();

            $this->modalCrearAbierto = false;
            session()->flash('mensaje', 'CAI y gestión de CAI registrados exitosamente.');
            
            // Limpiar el formulario
            $this->limpiarFormulario();

        } catch (ValidationException $e) {
            DB::rollBack();
            session()->flash('error', 'Error de validación: ' . implode(', ', $e->validator->errors()->all()));
        } catch (QueryException $e) {
            DB::rollBack();
            session()->flash('error', 'Error de base de datos: ' . $e->getMessage());
        } catch (\Throwable $e) {
            DB::rollBack();
            session()->flash('error', 'Error inesperado: ' . $e->getMessage());
        }
    }

    /**
     * Extrae el número actual del rango inicial
     * Toma el rango_inicio después del último guión y quita los ceros a la izquierda
     */
    private function extraerNumeroActual($rangoInicial)
    {
        $partes = explode('-', $rangoInicial);
        $ultimaParte = end($partes);
        return (int) $ultimaParte; // Convertir a entero elimina los ceros a la izquierda
    }

    /**
     * Extrae la cantidad no utilizada del rango final
     * Toma el rango_final después del último guión
     */
    private function extraerCantidadNoUtilizada($rangoFinal)
    {
        $partes = explode('-', $rangoFinal);
        $ultimaParte = end($partes);
        return (int) $ultimaParte;
    }

    /**
     * Extrae el número base del rango inicial
     * Toma todo hasta el último guión incluyendo el guión
     */
    private function extraerNumeroBase($rangoInicial)
    {
        $ultimoGuion = strrpos($rangoInicial, '-');
        if ($ultimoGuion !== false) {
            return substr($rangoInicial, 0, $ultimoGuion + 1); // Incluye el guión
        }
        return $rangoInicial . '-'; // Si no hay guión, agrega uno
    }

    /**
     * Crea automáticamente el registro en gestion_cai
     */
    private function crearGestionCai($caiId, $rangoInicial, $rangoFinal)
    {
        $numeroActual = $this->extraerNumeroActual($rangoInicial);
        $cantidadNoUtilizada = $this->extraerCantidadNoUtilizada($rangoFinal);
        $numeroBase = $this->extraerNumeroBase($rangoInicial);

        $gestionCai = new GestionCai();
        $gestionCai->cai_id = $caiId;
        $gestionCai->numero_actual = $numeroActual;
        $gestionCai->cantidad_no_utilizada = $cantidadNoUtilizada;
        $gestionCai->numero_base = $numeroBase;
        $gestionCai->estado_id = 1;
        $gestionCai->save();
    }

    /**
     * Limpia el formulario después de crear un CAI
     */
    private function limpiarFormulario()
    {
        $this->nuevoCai = '';
        $this->nuevoFechaLimite = '';
        $this->nuevoFechaSolicitud = '';
        $this->nuevoPuntoEmision = '';
        $this->tipoDocumentoSeleccionado = '';
        $this->nuevoCantidadSolicitada = '';
        $this->nuevoCantidadOtorgada = '';
        $this->nuevoRangoInicial = '';
        $this->nuevoRangoFinal = '';
        $this->tiendaSeleccionado = '';
    }


    public function abrirModalCrear()
    {
        $this->modalCrearAbierto = true;
        $this->nuevoCai = '';
        $this->nuevoFechaLimite = '';
        $this->nuevoFechaSolicitud = '';
        $this->nuevoPuntoEmision = '';
        $this->tipoDocumentoSeleccionado = '';
        $this->nuevoCantidadSolicitada = '';
        $this->nuevoCantidadOtorgada = '';
        $this->nuevoRangoInicial = '';
        $this->nuevoRangoFinal = '';
        $this->tiendaSeleccionado = '';
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
