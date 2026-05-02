<?php

return [
    "errors"=> [
        "unauthenticated"=> [
            "message"=> "You're not authenticated.",
            "description"=> "You need to be logged in to access this page.",
        ],
        "unauthorized"=> [
            "message"=> "You don't have necessaries permissions.",
            "description"=> "You don't have permissions to :end_sentence."
        ],
        "login"=> [
            "message"=> "Authentication failed.",
            "description"=> "Invalid email or password.",
        ],
        "validation"=> [
            "message"=> "Data validation failed.",
            "description"=> "An error occurred while validating your data. See more details in errors.",
        ],
        "users"=> [
            "404"=> [
                "message"=> "User not found.",
                "description"=> "The user `:user` not found."
            ],
            "reused_password"=> [
                "message"=> "The password with the same than the actual password.",
                "description"=> "The new password can't be the same as the last used password."
            ],
            "reset_password"=> [
                "message"=> "Password reset failed.",
                "description"=> "The reset password request has failed, try again or contact an administrator."
            ],
            "not_trashed"=> [
                "message"=> "User not trashed",
                "description"=> "The user `:user` is not trashed, Impossible to restore it."
            ],
            "confirmation"=> [
                "message"=> "Action not confirmed.",
                "description"=> "The sensitive action need to be confirmed, you need to confirm it."
            ],
            "account_disabled"=> [
                "message"=> "User blocked.",
                "description"=> "The user you try to use is blocked, you can't use it. Please contact an administrator."
            ]
        ],
        "pagination"=> [
            "404"=> [
                "message"=> "Page not found.",
                "description"=> "Page :page not found. Last available page is :last_page."
            ]
        ],
        "logs"=> [
            "404"=> [
                "message"=> "This log was not found.",
                "description"=> "The log with id `:id` not found."
            ]
        ]
    ],
    "success"=> [
        "login"=> [
            "message"=> "You have successfully logged in.",
            "description"=> "You're logged in as :user.",
        ],
        "logout"=> [
            "message"=> "You have successfully logged out, see you soon!",
            "description"=> "The account `:user` has been logged out.",
        ],
        "users"=> [
            "index"=> [
                "message"=> "The list of users to be able to view.",
            ],
            "store"=> [
                "message"=> "The user has been created.",
                "description"=> "The new user `:user` has been created.",
            ],
            "show"=> [
                "message"=> "The user has been found.",
                "description"=> "The user `:user` has successfully found."
            ],
            "update"=> [
                "message"=> "The user has been updated.",
                "descriptions"=> "The user `:user` has successfully updated."
            ],
            "change"=> [
                "status"=> [
                    "message"=> "User status updated.",
                    "description"=> "The login of `:user` has been :status."
                ]
            ],
            "destroy"=> [
                "message"=> "The user has been deleted.",
                "description"=> "The user `:user` has successfully deleted."
            ],
            "restore"=> [
                "message"=> "The user has been restored.",
                "description"=> "The user `:user` has successfully restored."
            ],
            "reset_password"=> [
                "message"=> 'The password has been reset.',
                "description"=> "The password of user `:user` has successfully reset."
            ],
            "get_profile"=> [
                "message"=> "The user profile has been found.",
                "description"=> "The user profile `:user` has been loaded."
            ],
            "update_profile"=> [
                "message"=> "The user profile has been updated.",
                "description"=> "The user profile `:user` has successfully updated.",
            ],
            "destroy_profile"=> [
                "message"=> "The user profile has been deleted.",
                "description"=> "The user `:user` has been deleted. Your account data are still retained for a period of 1 year, as specified in the privacy policy. You can object to this backup by contacting an administrator."
            ],
            "force_delete"=> [
                "message"=> "User has been definitively deleted.",
                "description"=> "The user `:user` has been definitively deleted. And all data associated with the user was deleted.",
            ]
        ],
        "logs"=> [
            "index"=> [
                "message"=> "The list of logs was successfully loaded.",
            ],
            "show"=> [
                "message"=> "The log has been found.",
                "description"=> "The log with id `:id` has been found."
            ]
        ]
    ],
    "actions"=> [
        "users"=> [
            "show"=> "view the user",
            "list"=> "view the list of users",
            "create"=> "create a user",
            "edit"=> "edit a user",
            "delete"=> "delete a user",
            "invite"=> "invite a user",
            "suspend"=> "suspend a user",
            "restore"=> "restore a user",
            "force_delete"=> "delete definitively a user",
            "status"=> [
                "active"=> "enabled",
                "inactive"=> "disabled"
            ]
        ],
        "logs"=> [
            "list"=> "list of logs",
            "show"=> "view log",
        ]
    ]
];
