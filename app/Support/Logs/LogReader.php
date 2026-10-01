<?php

namespace App\Support\Logs;

use Illuminate\Support\Collection;

/**
 * Lee y parsea los archivos de log de Laravel en storage/logs.
 */
class LogReader
{
    /** Máximo que se lee de cada archivo (los últimos N bytes). */
    public const MAX_BYTES = 10 * 1024 * 1024;

    public const NIVELES = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

    private const INICIO_ENTRADA = '/^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[+-]\d{2}:?\d{2})?)\] (\w+)\.(\w+): (.*)$/';

    public function __construct(private ?string $directorio = null)
    {
        $this->directorio ??= storage_path('logs');
    }

    /**
     * Archivos disponibles, el más reciente primero.
     *
     * @return Collection<int, array{nombre: string, bytes: int, modificado: int}>
     */
    public function archivos(): Collection
    {
        return collect(glob($this->directorio.DIRECTORY_SEPARATOR.'*.log') ?: [])
            ->map(fn (string $ruta) => [
                'nombre' => basename($ruta),
                'bytes' => filesize($ruta),
                'modificado' => filemtime($ruta),
            ])
            ->sortByDesc('modificado')
            ->values();
    }

    public function existe(string $nombre): bool
    {
        return $this->archivos()->contains('nombre', $nombre);
    }

    /** Ruta absoluta de un archivo listado. Nunca acepta rutas arbitrarias. */
    public function ruta(string $nombre): string
    {
        abort_unless($this->existe($nombre), 404);

        return $this->directorio.DIRECTORY_SEPARATOR.$nombre;
    }

    /**
     * Entradas del archivo, la más reciente primero.
     *
     * @return Collection<int, LogEntry>
     */
    public function entradas(string $nombre): Collection
    {
        $entradas = [];
        $actual = null;

        foreach ($this->lineas($this->ruta($nombre)) as $linea) {
            if (preg_match(self::INICIO_ENTRADA, $linea, $m)) {
                if ($actual) {
                    $entradas[] = $actual;
                }
                $actual = new LogEntry(fecha: $m[1], entorno: $m[2], nivel: strtolower($m[3]), mensaje: $m[4]);
            } elseif ($actual) {
                $actual->detalle .= $linea."\n";
            }
        }

        if ($actual) {
            $entradas[] = $actual;
        }

        return collect(array_reverse($entradas));
    }

    public function vaciar(string $nombre): void
    {
        file_put_contents($this->ruta($nombre), '');
    }

    /** @return array<int, string> */
    private function lineas(string $ruta): array
    {
        $tamanio = filesize($ruta);
        $handle = fopen($ruta, 'r');

        if ($tamanio > self::MAX_BYTES) {
            fseek($handle, -self::MAX_BYTES, SEEK_END);
            fgets($handle); // descarta la línea cortada
        }

        $contenido = stream_get_contents($handle);
        fclose($handle);

        return preg_split('/\r?\n/', rtrim($contenido));
    }
}
