<?php

return [
    'create'=> [
        'title'=> "Créer un utilisateur",
        'submit'=> "Créer",
    ],
    
    'edit'=> [
        'title'=> "Modifier un utilisateur",
        'description'=> "Modifier rapidement certaines valeur de l'utilisateur",
        'submit'=> "Enregistrer",
    ],
    
    'help'=> [
        'username'=> "Identifiant utilisé pour la connexion.",
        'email'=> "Adresse email de l'utilisateur.",
        'username_optional'=> "Si vous ne le choisissez pas, l'utilisateur pourra choisir son nom d'utilisateur.",
        'active'=> "Définit si la connexion au compte est autorisée ou non.",
        'expire_at'=> "Après cette date, la connexion sera impossible.",
        'admin_notes'=> "Notes visibles uniquement par les administrateurs.",
        'suspended_until'=> "Date jusqu'à laquelle l'utilisateur est bloqué.",
        'suspension_reason'=> "Motif visible par l'utilisateur. Si une raison est fourni, mais aucun date n'est entré la durée de suspension est illimité."
    ],
    
    'hint'=> [
        'optional'=> "Optionnel"
    ],
    
    'sections'=> [
        'access'=> 'Accès',
        'constraints'=> 'Contraintes de connexion',
        'data'=> "Informations",
        'login'=> "Authentification",
        'notes'=> 'Notes',
        'settings'=> "Paramètres",
    ]
];
