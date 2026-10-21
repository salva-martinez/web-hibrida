<?php

/*
|--------------------------------------------------------------------------
| Mensajes de validación en español
|--------------------------------------------------------------------------
|
| Sin este fichero, Laravel devuelve la clave de la regla (por ejemplo
| "validation.required") en lugar de un mensaje legible, porque el framework
| solo trae las traducciones en inglés.
|
| Los nombres de los campos (":attribute") se personalizan abajo para que los
| mensajes se lean en español y no con el nombre de la columna.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Mensajes de validación
    |--------------------------------------------------------------------------
    */

    'accepted' => 'El campo :attribute debe ser aceptado.',
    'accepted_if' => 'El campo :attribute debe ser aceptado cuando :other es :value.',
    'active_url' => 'El campo :attribute no es una URL válida.',
    'after' => 'El campo :attribute debe ser una fecha posterior a :date.',
    'after_or_equal' => 'El campo :attribute debe ser una fecha posterior o igual a :date.',
    'alpha' => 'El campo :attribute solo puede contener letras.',
    'alpha_dash' => 'El campo :attribute solo puede contener letras, números, guiones y guiones bajos.',
    'alpha_num' => 'El campo :attribute solo puede contener letras y números.',
    'array' => 'El campo :attribute debe ser un array.',
    'ascii' => 'El campo :attribute solo puede contener caracteres y símbolos de un byte.',
    'before' => 'El campo :attribute debe ser una fecha anterior a :date.',
    'before_or_equal' => 'El campo :attribute debe ser una fecha anterior o igual a :date.',
    'between' => [
        'array' => 'El campo :attribute debe tener entre :min y :max elementos.',
        'file' => 'El campo :attribute debe pesar entre :min y :max kilobytes.',
        'numeric' => 'El campo :attribute debe estar entre :min y :max.',
        'string' => 'El campo :attribute debe tener entre :min y :max caracteres.',
    ],
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'can' => 'El campo :attribute contiene un valor no autorizado.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'contains' => 'Al campo :attribute le falta un valor obligatorio.',
    'current_password' => 'La contraseña es incorrecta.',
    'date' => 'El campo :attribute no es una fecha válida.',
    'date_equals' => 'El campo :attribute debe ser una fecha igual a :date.',
    'date_format' => 'El campo :attribute no coincide con el formato :format.',
    'decimal' => 'El campo :attribute debe tener :decimal decimales.',
    'declined' => 'El campo :attribute debe ser rechazado.',
    'declined_if' => 'El campo :attribute debe ser rechazado cuando :other es :value.',
    'different' => 'Los campos :attribute y :other deben ser diferentes.',
    'digits' => 'El campo :attribute debe tener :digits dígitos.',
    'digits_between' => 'El campo :attribute debe tener entre :min y :max dígitos.',
    'dimensions' => 'El campo :attribute tiene dimensiones de imagen no válidas.',
    'distinct' => 'El campo :attribute tiene un valor repetido.',
    'doesnt_end_with' => 'El campo :attribute no debe terminar con: :values.',
    'doesnt_start_with' => 'El campo :attribute no debe empezar con: :values.',
    'email' => 'El campo :attribute debe ser una dirección de correo válida.',
    'ends_with' => 'El campo :attribute debe terminar con: :values.',
    'enum' => 'El valor seleccionado de :attribute no es válido.',
    'exists' => 'El campo :attribute seleccionado no es válido.',
    'extensions' => 'El campo :attribute debe tener una de las extensiones: :values.',
    'file' => 'El campo :attribute debe ser un archivo.',
    'filled' => 'El campo :attribute no puede estar vacío.',
    'gt' => [
        'array' => 'El campo :attribute debe tener más de :value elementos.',
        'file' => 'El campo :attribute debe pesar más de :value kilobytes.',
        'numeric' => 'El campo :attribute debe ser mayor que :value.',
        'string' => 'El campo :attribute debe tener más de :value caracteres.',
    ],
    'gte' => [
        'array' => 'El campo :attribute debe tener :value elementos o más.',
        'file' => 'El campo :attribute debe pesar :value kilobytes o más.',
        'numeric' => 'El campo :attribute debe ser mayor o igual que :value.',
        'string' => 'El campo :attribute debe tener :value caracteres o más.',
    ],
    'image' => 'El campo :attribute debe ser una imagen.',
    'in' => 'El valor seleccionado de :attribute no es válido.',
    'in_array' => 'El campo :attribute no existe en :other.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'ip' => 'El campo :attribute debe ser una dirección IP válida.',
    'ipv4' => 'El campo :attribute debe ser una dirección IPv4 válida.',
    'ipv6' => 'El campo :attribute debe ser una dirección IPv6 válida.',
    'json' => 'El campo :attribute debe ser una cadena JSON válida.',
    'lt' => [
        'array' => 'El campo :attribute debe tener menos de :value elementos.',
        'file' => 'El campo :attribute debe pesar menos de :value kilobytes.',
        'numeric' => 'El campo :attribute debe ser menor que :value.',
        'string' => 'El campo :attribute debe tener menos de :value caracteres.',
    ],
    'lte' => [
        'array' => 'El campo :attribute no puede tener más de :value elementos.',
        'file' => 'El campo :attribute debe pesar :value kilobytes o menos.',
        'numeric' => 'El campo :attribute debe ser menor o igual que :value.',
        'string' => 'El campo :attribute debe tener :value caracteres o menos.',
    ],
    'mac_address' => 'El campo :attribute debe ser una dirección MAC válida.',
    'max' => [
        'array' => 'El campo :attribute no puede tener más de :max elementos.',
        'file' => 'El campo :attribute no puede pesar más de :max kilobytes.',
        'numeric' => 'El campo :attribute no puede ser mayor que :max.',
        'string' => 'El campo :attribute no puede superar los :max caracteres.',
    ],
    'mimes' => 'El campo :attribute debe ser un archivo de tipo: :values.',
    'mimetypes' => 'El campo :attribute debe ser un archivo de tipo: :values.',
    'min' => [
        'array' => 'El campo :attribute debe tener al menos :min elementos.',
        'file' => 'El campo :attribute debe pesar al menos :min kilobytes.',
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'missing' => 'El campo :attribute no debe estar presente.',
    'multiple_of' => 'El campo :attribute debe ser múltiplo de :value.',
    'not_in' => 'El valor seleccionado de :attribute no es válido.',
    'not_regex' => 'El formato del campo :attribute no es válido.',
    'numeric' => 'El campo :attribute debe ser un número.',
    'present' => 'El campo :attribute debe estar presente.',
    'present_if' => 'El campo :attribute debe estar presente cuando :other es :value.',
    'prohibited' => 'El campo :attribute está prohibido.',
    'prohibited_if' => 'El campo :attribute está prohibido cuando :other es :value.',
    'prohibits' => 'El campo :attribute prohíbe la presencia de :other.',
    'regex' => 'El formato del campo :attribute no es válido.',
    'required' => 'El campo :attribute es obligatorio.',
    'required_array_keys' => 'Al campo :attribute le faltan las claves: :values.',
    'required_if' => 'El campo :attribute es obligatorio cuando :other es :value.',
    'required_if_accepted' => 'El campo :attribute es obligatorio cuando :other está marcado.',
    'required_if_declined' => 'El campo :attribute es obligatorio cuando :other está desmarcado.',
    'required_unless' => 'El campo :attribute es obligatorio salvo que :other sea :values.',
    'required_with' => 'El campo :attribute es obligatorio cuando hay :values.',
    'required_with_all' => 'El campo :attribute es obligatorio cuando hay :values.',
    'required_without' => 'El campo :attribute es obligatorio cuando no hay :values.',
    'required_without_all' => 'El campo :attribute es obligatorio cuando no hay :values.',
    'same' => 'Los campos :attribute y :other deben coincidir.',
    'size' => [
        'array' => 'El campo :attribute debe tener :size elementos.',
        'file' => 'El campo :attribute debe pesar :size kilobytes.',
        'numeric' => 'El campo :attribute debe ser :size.',
        'string' => 'El campo :attribute debe tener :size caracteres.',
    ],
    'starts_with' => 'El campo :attribute debe empezar con: :values.',
    'string' => 'El campo :attribute debe ser una cadena de texto.',
    'timezone' => 'El campo :attribute debe ser una zona horaria válida.',
    'unique' => 'El valor del campo :attribute ya está en uso.',
    'uploaded' => 'No se ha podido subir el campo :attribute.',
    'url' => 'El campo :attribute debe ser una URL válida.',
    'ulid' => 'El campo :attribute debe ser un ULID válido.',
    'uuid' => 'El campo :attribute debe ser un UUID válido.',

    /*
    |--------------------------------------------------------------------------
    | Mensajes personalizados
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'password' => [
            'min' => 'La contraseña debe tener al menos :min caracteres.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Nombres de los atributos
    |--------------------------------------------------------------------------
    |
    | Traducen el nombre técnico del campo a algo legible, para que los
    | mensajes se lean en español y no con el nombre de la columna.
    |
    */

    'attributes' => [
        'nombre' => 'nombre',
        'apellido1' => 'primer apellido',
        'apellido2' => 'segundo apellido',
        'fecha_nacimiento' => 'fecha de nacimiento',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'password_confirmation' => 'confirmación de la contraseña',
        'paciente_id' => 'paciente',
        'titulo' => 'título',
        'fecha' => 'fecha',
        'activo' => 'plan activo',
        'estimulo_id' => 'estímulo',
        'video_url' => 'URL del vídeo',
        'descripcion' => 'descripción',
        'orden' => 'orden',
        'notas_fisio' => 'notas para el fisioterapeuta',
        'dias' => 'días de entrenamiento',
        'dias.*.ejercicios' => 'ejercicios del día',
        'dias.*.ejercicios.*.ejercicio_id' => 'ejercicio',
        'dias.*.ejercicios.*.series' => 'series',
        'dias.*.ejercicios.*.repeticiones' => 'repeticiones',
        'dias.*.ejercicios.*.intensidad' => 'intensidad',
        'dias.*.ejercicios.*.kg' => 'peso',
        'dias.*.ejercicios.*.descanso' => 'descanso',
        'login_type' => 'tipo de acceso',
        'message' => 'mensaje',
    ],

];
