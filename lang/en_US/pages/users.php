<?php

return [
    'drawer'=> [
        'title'=> 'User details',
        'description'=> 'Main user information',
        'open_full_page'=> 'Open full user page',
        'loading'=> 'Loading user…',
        'unknown_status'=> 'Unknown status',
        'not_provided'=> 'Not provided',
        'sections'=> [
            'contact'=> 'Contact details',
            'information'=> 'Information',
            'administration'=> 'Administration',
        ],
        'fields'=> [
            'id'=> 'Identifier',
            'created_at'=> 'Created at',
            'updated_at'=> 'Updated at',
            'roles'=> 'Roles',
            'last_login'=> 'Last login',
            'expire_at'=> 'Expiration',
            'suspended_until'=> 'Suspension',
            'suspension_reason'=> 'Suspension reason',
            'admin_notes'=> 'Administration notes',
        ],
        'errors'=> [
            'title'=> 'Unable to load user',
            'unknown'=> 'An unexpected error occurred.',
            'load'=> 'Unable to load user (:status).',
        ],
        'actions'=> [
            'view_details'=> 'View details',
            'retry'=> 'Retry',
            'close'=> 'Close',
        ],
    ],

    'table'=> [
        'name'=> 'Users list',
        'columns'=> 'Columns',
        'header'=> [
            'id'=> 'ID',
            'username'=> 'Username',
            'email'=> 'Email',
            'contact'=> 'Contact',
            'active'=> 'Status',
            'admin_notes'=> 'Notes',
            'updated_at'=> 'Updated at',
        ],
        'cell'=> [
            'username'=> [
                'badge'=> 'You',
                'job'=> 'Job',
                'name'=> 'Name',
                'last_name'=> 'Last name',
                'first_name'=> 'First name',
            ],
            'active'=> [
                'true'=> 'Enabled',
                'false'=> 'Disabled',
            ],
            'email'=> [
                'label'=> 'Email',
            ],
            'phone'=> [
                'label'=> 'Phone',
            ],
            'admin_notes'=> [
                'label'=> 'Notes',
                'expand'=> 'View the full note',
                'collapse'=> 'Collapse the note',
            ],
        ],
        'actions'=> [
            'select'=> [
                'connecting_word'=> 'of',
                'selected'=> 'selected item(s)',
                'unselect'=> 'Unselect',
            ],
            'sections'=> [
                'actions'=> 'Actions',
            ],
            'edit'=> 'Edit',
            'copy_email'=> 'Copy email address',
            'delete'=> 'Delete',
            'create'=> 'Add a new user',
        ],
        'toasts'=> [
            'copy_email'=> [
                'title'=> 'Email address copied to clipboard!',
            ],
            'delete'=> [
                'title'=> 'User deleted',
            ],
        ],
    ],

    'actions'=> [
        'create'=> [
            'modal'=> [
                'title'=> 'Add user',
                'buttons'=> [
                    'save'=> 'Create',
                    'cancel'=> 'Cancel',
                ],
            ],
        ],
        'edit'=> [
            'modal'=> [
                'title'=> 'Edit user',
                'buttons'=> [
                    'save'=> 'Update',
                    'cancel'=> 'Cancel',
                ],
            ],
        ],
    ],

    'fields'=> [
        'username'=> [
            'label'=> 'Username',
            'help'=> 'Username that can be used to sign in.',
        ],
        'email'=> [
            'label'=> 'Email',
        ],
        'first_name'=> [
            'label'=> 'First name',
        ],
        'last_name'=> [
            'label'=> 'Last name',
        ],
        'job_title'=> [
            'label'=> 'Job title',
        ],
        'active'=> [
            'label'=> 'Status',
        ],
        'admin_notes'=> [
            'label'=> 'Notes',
        ],
        'phone'=> [
            'label'=> 'Phone number',
        ],
        'password'=> [
            'label'=> 'Password',
        ],
        'password_confirmation'=> [
            'label'=> 'Confirm password',
        ],
        'expire_at'=> [
            'label'=> 'Account expiration date',
            'help'=> 'The account will not be deleted, but sign-in will become impossible after this date.',
        ],
        'required'=> 'Fields marked with "*" are required.',
    ],

    'validations'=> [
        'password'=> [
            'length'=> 'Minimum 8 characters',
            'number'=> 'Minimum 1 number',
            'lowercase'=> 'Minimum 1 lowercase letter',
            'uppercase'=> 'Minimum 1 uppercase letter',
            'symbol'=> 'Minimum 1 symbol',
        ],
    ],

    'tabs'=> [
        'actions'=> [
            'create'=> [
                'verb'=> 'Create',
            ],
            'edit'=> [
                'verb'=> 'Edit',
            ],
        ],
        'data'=> [
            'title'=> 'Information',
            'description'=> ' user information.',
        ],
        'security'=> [
            'title'=> 'Security',
            'description'=> ' user security settings.',
        ],
        'admin'=> [
            'title'=> 'Administration',
            'description'=> 'Perform administration actions for the user.',
        ],
    ],
];
