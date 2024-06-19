<?php

namespace App\Services;

use App\Models\SessionToken;
use Illuminate\Support\Facades\Log;

class CustomSession
{

    // EN ESTE APARATOD MANEJO TODO LO QUE SE TRATA DEL TOKEN DE SESION CON LA CLASE
    // SessionToken QUE SE ENCUENTRA EN LA CARPETA MODELS, LO COLOQUE COMO UN SERVICIO
    // POR QUE ES UNA FUNCIONALIDAD QUE SE VA A USAR EN VARIOS LADOS Y NO QUERIA REPETIR
    // TANTO EN EL CONTROLADOR COMO EN EL SERVICIO DE EPAYCO

    public function storeToken($key, $value)
    {
        // Guardar el token en la base de datos
        SessionToken::create([
            'key' => $key,
            'value' => $value,
        ]);
    }

    public function getToken($key)
    {
        // Buscar y devolver el token de la base de datos
        $sessionToken = SessionToken::where('key', $key)->first();

        if ($sessionToken) {
            Log::info('Token encontrado en la base de datos para la clave ' . $key . ': ' . $sessionToken->value);
            return $sessionToken->value;
        } else {
            Log::info('No se encontró ningún token en la base de datos para la clave ' . $key);
            return null;
        }
    }


    public function deleteToken($key)
    {
        // Eliminar el token de la base de datos
        SessionToken::where('key', $key)->delete();
    }
}
