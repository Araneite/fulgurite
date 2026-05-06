<?php

return [
    "unauthenticated"=> [
        "message"=> "You're not authenticated.",
        "detail"=> "You need to be logged in to access this resource."
    ],
    "unauthorized"=> [
        "message"=> "You don't have permission to access this resource.",
        "detail"=> "You don't have permission to perform :end_sentence."
    ],
    "login"=> [
        "message"=> "Login or password incorrect.",
        "detail"=> "Check your login and password and try again."
    ],
    "validation"=> [
        "message"=> "Data validation failed.",
        "detail"=> "An error occurred on data validation, see more details on errors."
    ],
    "404"=> [
        "message"=> "The resource was not found.",
        "detail"=> "The resource `:model` was not found for `:requested`."
    ],
    "not_trashed"=> [
        "message"=> "The resource is not trashed.",
        "detail"=> "The resource `:model` with `:requested` is not trashed, restore unavailable."
    ],
    "confirmation"=> [
        "message"=> "The request was not confirmed.",
        "detail"=> "This action is sensitive, and need to be confirmed. Confirm your action before continue."
    ],
    "page_not_found"=> [
        "message"=> "Page not found.",
        "detail"=> "The page `:page` doesn't exist. The last available page is `:last_page`."
    ],
    "invalid_date"=> [
        "message"=> "Invalid date.",
        "detail"=> "The date `:date` is not valid."
    ],
    "disabled"=> [
        "message"=> "Resource blocked.",
        "detail"=> "The resource `:model` for `:requested` is currently blocked. Impossible to used until unblock. If you think this is a mistake, please contact the administrator."
    ],
    "reset_password"=> [
        "message"=> "Reset password failed.",
        "detail"=> "The reset password failed. Please try again or contact an administrator."
    ],
    "reused_password"=> [
        "message"=> "Same password.",
        "detail"=> "The new password can't be the same than old password."
    ]
];
