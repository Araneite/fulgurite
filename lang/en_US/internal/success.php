<?php
return [
    "login"=> [
        "message"=> "Login successful.",
        "detail"=> "You're logged in as :user."
    ],
    "logout"=> [
        "message"=> "Logout successful.",
        "detail"=> "The account `:user` was successfully logged out."
    ],
    "index"=> [
        "message"=> "The list was successfully loaded.",
        "detail"=> "The list `:model` was successfully loaded."
    ] ,
    "show"=> [
        "message"=> "The resource was successfully displayed.",
        "detail"=> "The resource `:model` for `:requested` was successfully displayed."
    ],
    "store"=> [
        "message"=> "New entry was successfully stored.",
        "details"=> "The new resource `:model` was successfully created."
    ],
    "update"=> [
        "message"=> "Update successful.",
        "detail"=> "The resource `:model` for `:requested` was successfully updated."
    ],
    "change"=> [
        "message"=> "Update successful.",
        "detail"=> "The field(s) :fields was successfully update in `:model` for `:requested`."
    ],
    "destroy"=> [
        "message"=> "Entry move to trash successful.",
        "detail"=> "The resource `:model` for `:requested` was successfully moved to trash."
    ], 
    "restore"=> [
        "message"=> "Entry restore successful.",
        "detail"=> "The resource `:model` for `:requested` was successfully restore from trash."
    ],
    "force_delete"=> [
        "message"=> "Entry permanently delete successful.",
        "detail"=> "The resource `:model` for `:requested` was permanently delete. All data associated was removed."
    ],
    "reset_password"=> [
        "message"=> "Reset password successful.",
        "detail"=> "The user password for :user was successfully reset."
    ]
];
