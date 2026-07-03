<?php
namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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

        return Inertia::render('Packages/Index', compact('packages','filtros','stats'));
    }
}
