<?php

namespace App\Support\Logs;

class LogEntry
{
    public function __construct(
        public string $fecha,
        public string $entorno,
        public string $nivel,
        public string $mensaje,
        public string $detalle = '',
    ) {}

    public function coincide(string $busqueda): bool
    {
        return $busqueda === ''
            || str_contains(mb_strtolower($this->mensaje), mb_strtolower($busqueda))
            || str_contains(mb_strtolower($this->detalle), mb_strtolower($busqueda));
    }
}
