<?php

return [
    'permissions' => [
        //general:
        'view-profile',
        'edit-profile',
        'show-all-notifications',
        'show-count-unread-notifications',
        'mark-all-as-read-notifications',
        'mark-as-read-notifications',
        //admin
        'admin-logout',
        'user-logout',
        'view-users',
        'toggle-user',
        'show-logs',
        'count-logs',
        'change-logs',
        'show-currentDate',
        'type-of-patient-count',
        'user-active-count',
        'doctor-active-count',
        'daily-diagnoses-count',
        'new-content-items-count',
        'top-specialties-by-diagnoses',
        'diagnosis-sessions-status-count',
        'show-doctor-requests',
        'show-doctor-request-details',
        'approve-doctor-request',
        'reject-doctor-request',
        'view-tracking-data',

        // patient
        'start-diagnose', 'search-symptom', 'view-symptom-questions', 'continue-diagnose',
        'view-medical-history',
        'generate-report', 'download-report', 'preview-report',
        'create-intent', 'status-payment-intent',
        'destroy-all-notifications', 'destroy-notification', 'mark-as-unread-notifications',

        // doctor
        'doctor-logout'


    ],

    'roles' => [
        'super-admin' => ['all'],
        'admin' => [
            'admin-logout',

            'view-profile', 'edit-profile',

            'view-users', 'toggle-user',
            
            'show-logs',  'count-logs', 'change-logs',

            'show-currentDate', 'type-of-patient-count',
            'user-active-count', 'doctor-active-count',
            'daily-diagnoses-count', 'new-content-items-count',
            'top-specialties-by-diagnoses', 'diagnosis-sessions-status-count',

            'show-doctor-requests', 'show-doctor-request-details',
            'approve-doctor-request', 'reject-doctor-request',

            'show-all-notifications', 'show-count-unread-notifications',
            'mark-all-as-read-notifications', 'mark-as-read-notifications', 

        ],

        'patient' => [
            'user-logout',

            'view-profile', 'edit-profile',

            'start-diagnose', 'search-symptom',
            'view-symptom-questions', 'continue-diagnose',
            'view-medical-history',

            'generate-report', 'download-report', 'preview-report',

            'create-intent', 'status-payment-intent',

            'show-all-notifications',  'destroy-all-notifications',
            'destroy-notification', 'show-count-unread-notifications',
            'mark-as-read-notifications', 'mark-as-unread-notifications',
            'mark-all-as-read-notifications', 

        ],

        'doctor' => [
            'doctor-logout',

        ],
    ],
];
