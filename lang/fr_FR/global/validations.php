<?php

return [
    'username'=> [
        'required'=> "Le nom d'utilisateur est obligatoire.",
        'unique'=> "Ce nom d'utilisateur est déjà utilisé sur un autre compte.",
    ],
    'email'=> [
        'required'=> "L'adresse email est obligatoire.",
        'email'=> "L'adresse email n'est pas valide.",
        'unique' => "Cette adresse email est déjà utilisé sur un autre compte.",
    ]
];
