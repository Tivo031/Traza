<?php
return [
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',
    'email' => 'Escribe un correo electrónico válido.',
    'unique' => 'El valor de :attribute ya está registrado.',
    'same' => 'La confirmación debe coincidir con la contraseña.',
    'prohibited' => 'No está permitido enviar el campo :attribute.',
    'max' => ['string' => 'El campo :attribute no debe superar :max caracteres.'],
    'min' => ['string' => 'El campo :attribute debe tener al menos :min caracteres.'],
    'password' => [
        'letters' => 'La contraseña debe contener al menos una letra.',
        'numbers' => 'La contraseña debe contener al menos un número.',
    ],
    'attributes' => [
        'nombre' => 'nombre', 'correo' => 'correo', 'contrasena' => 'contraseña',
        'confirmacion_contrasena' => 'confirmación de contraseña', 'token' => 'enlace',
    ],
];
