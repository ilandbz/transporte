<?php

namespace App\Jobs;

use App\Models\CpeError;
use App\Models\Ticket;
use App\Services\SunatGreenterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(private Ticket $ticket) {}

    public function handle(SunatGreenterService $greenter): void
    {
        try {
            $resultado = $greenter->emitirBoleta($this->ticket);

            if ($resultado['status']) {
                $this->ticket->update([
                    'tipo_documento'  => 'BOLETA',
                    'serie_cpe'       => $resultado['serie'],
                    'correlativo_cpe' => $resultado['correlativo'],
                    'cdr_status'      => $resultado['cdr'],
                    'sincronizado'    => true,
                    'sincronizado_en' => now(),
                ]);
            } else {
                $this->registrarError('SUNAT retornó status false', $resultado);
            }
        } catch (\Exception $e) {
            $this->registrarError($e->getMessage(), []);
            throw $e;
        }
    }

    private function registrarError(string $mensaje, array $payload): void
    {
        CpeError::create([
            'documento_type'  => Ticket::class,
            'documento_id'    => $this->ticket->id,
            'error_mensaje'   => $mensaje,
            'payload_enviado' => $payload,
            'intentos'        => $this->attempts(),
            'ultimo_intento'  => now(),
        ]);
    }
}
