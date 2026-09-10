<?php

return [
    'renew_expiration'=> [
        'title'=> "Renouveler la date d'expiration",
        'description'=> "Ajouter une durée supplémentaire à la date d'expiration.",
        'submit'=> 'Modifier la date d\'expiration'
    ],
    
    'fields'=> [
        'labels'=> [
            'renew_amount'=> 'Durée du renouvellement',
            'expiration_date'=> "Date d'expiration"
        ],
        'helps'=> [
            'renew_amount'=> "Ajouter une durée en jour à la date d'expiration actuelle, ou à partir d'aujourd'hui si la date est passée.",
            'expiration_date'=> "Nouvelle date d'expiration",
        ]
    ]
];
