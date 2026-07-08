<?php

return [
    'permissions' => [
        'view-dashboard', 'view-diseases','add-diseases', 'edit-diseases', 'delete-diseases',
        'view-symptoms', 'add-symptoms','edit-symptoms', 'delete-symptoms',
        'view-advices', 'add-advices', 'edit-advices',  'delete-advices',
        'view-doctors', 'accept-doctors', 'reject-doctors', 'edit-doctors', 'active-doctors',
        'non-active-doctors','delete-doctors','view-users','edit-users', 'ban-users','unban-users',
        'send-notifications', 'view-notifications','contact-user', 'review-report',
        'edit-profile',  'view-profile', 'search-symptom',
        'start-diagnose', 'continue-diagnose','download-report',
        'contact-doctor', 'choose-option', 'cancel-diagnose-session', 'recieve-notifications',
        'make-notification-as-read','view-medical-history', 'logout'
    ],

    'roles' => [
        'super-admin' => ['all'], 
        'admin' => [
            'view-dashboard', 'view-diseases',  'add-diseases','edit-diseases', 'delete-diseases',
            'view-symptoms', 'add-symptoms', 'edit-symptoms','delete-symptoms',  'view-advices',
            'add-advices', 'edit-advices', 'delete-advices', 'view-doctors',  'accept-doctors',
            'reject-doctors', 'edit-doctors', 'active-doctors',  'non-active-doctors',
            'delete-doctors', 'view-users', 'edit-users','ban-users', 'unban-users',
            'send-notifications', 'view-notifications',  'logout'
        ],

        'patient' => [
            'edit-profile','view-profile', 'search-symptom',
            'start-diagnose', 'continue-diagnose', 'download-report', 'contact-doctor',
            'choose-option', 'cancel-diagnose-session',
            'recieve-notifications', 'view-notifications',
            'make-notification-as-read', 'view-medical-history', 'logout'
        ],

        'doctor' => [
            'contact-user', 'review-report', 'logout'
        ],
    ],
];