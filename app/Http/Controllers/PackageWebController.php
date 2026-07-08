<?php
namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;

class PackageWebController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Package::with(['trip.vehicle', 'registradoPor'])
            ->orderByDesc('created_at');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('fecha')) {
            $query->whereDate('created_at', $request->fecha);
        }

        $packages = $query->paginate(25)->withQueryString();
        $filtros   = $request->only(['estado','fecha']);

        $stats = [
            'en_transito' => Package::where('estado','en_transito')->count(),
            'entregados'  => Package::where('estado','entregado')->count(),
            'pendientes'  => Package::where('estado','pendiente')->count(),
        ];
        
        $trips = \App\Models\Trip::with(['route', 'vehicle'])->where('estado', 'abierto')->get();

        return Inertia::render('Packages/Index', compact('packages','filtros','stats', 'trips'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'trip_id'               => 'required|exists:trips,id',
            'remitente_nombre'      => 'required|string|max:255',
            'remitente_dni'         => 'nullable|string|max:15',
            'remitente_telefono'    => 'nullable|string|max:20',
            'destinatario_nombre'   => 'required|string|max:255',
            'destinatario_dni'      => 'nullable|string|max:15',
            'destinatario_telefono' => 'nullable|string|max:20',
            'descripcion'           => 'required|string|max:255',
            'peso_kg'               => 'nullable|numeric|min:0.1',
            'precio'                => 'required|numeric|min:0',
            'estado_pago'           => 'required|in:pagado,por_cobrar',
        ]);

        $validated['uuid_local'] = Str::uuid()->toString();
        $validated['qr_code'] = Str::random(10) . '-' . time();
        $validated['user_id'] = auth()->id();
        $validated['estado'] = 'pendiente';
        $validated['emitido_en'] = now();

        // Guardar clientes frecuentes (Remitente)
        if (!empty($validated['remitente_dni'])) {
            Client::updateOrCreate(
                ['documento' => $validated['remitente_dni']],
                [
                    'nombre' => $validated['remitente_nombre'],
                    'telefono' => $validated['remitente_telefono'] ?? null,
                ]
            );
        }

        // Guardar clientes frecuentes (Destinatario)
        if (!empty($validated['destinatario_dni'])) {
            Client::updateOrCreate(
                ['documento' => $validated['destinatario_dni']],
                [
                    'nombre' => $validated['destinatario_nombre'],
                    'telefono' => $validated['destinatario_telefono'] ?? null,
                ]
            );
        }

        $activeBranchId = ($request->user()->role === 'admin' && session('active_branch_id'))
            ? session('active_branch_id')
            : $request->user()->branch_id;
            
        $validated['branch_id'] = $activeBranchId;

        Package::create($validated);

        return back()->with('success', 'Encomienda registrada correctamente.');
    }

    public function print(Request $request, Package $package)
    {
        $package->load(['trip.vehicle', 'trip.route']);
        
        $format = $request->query('format', '80mm'); // '80mm' or 'a4'

        return view('print.package', compact('package', 'format'));
    }
}
