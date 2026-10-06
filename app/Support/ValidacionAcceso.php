<?php

namespace App\Support;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class ValidacionAcceso
{
    public static function normalizarCorreo(Request $solicitud): void
    {
        if (is_string($solicitud->input('correo'))) {
            $solicitud->merge(['correo' => mb_strtolower(trim($solicitud->input('correo')))]);
        }
    }

    public static function reglasContrasena(): array
    {
        return [
            'bail', 'required', 'string', 'max:72',
            Password::min(12)->letters()->numbers(),
            function (string $atributo, mixed $valor, Closure $fallar): void {
                // Bcrypt trabaja con un maximo de 72 bytes; no truncar silenciosamente.
                if (is_string($valor) && strlen($valor) > 72) {
                    $fallar('La contraseña supera 72 bytes. Usa menos caracteres para evitar truncamientos.');
                }
            },
        ];
    }
}
