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


        //doctor
        'doctor-logout',
        'get-doctor-summary',
        'update-availability',
        'view-doctor-schedules',
        'update-doctor-schedules',
        'view-doctor-reviews',
        'submit-doctor-review',
        //patient
        'user-logout',
        'start-diagnose',
        'search-symptom',
        'view-symptom-questions',
        'continue-diagnose',
        'view-medical-history',
        'generate-report',
        'download-report',
        'preview-report',
        'create-intent',
        'status-payment-intent',
        'mark-as-unread-notifications',
        'destroy-all-notifications',
        'destroy-notification', 
        

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
            'view-tracking-data',
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

            'view-tracking-data',

        ],

        'doctor' => [
            'doctor-logout',
            'view-profile',
            'edit-profile',
            'get-doctor-summary',
            'update-availability',
            'view-tracking-data',
            'view-doctor-schedules',
            'update-doctor-schedules',
            'view-doctor-reviews',
            'submit-doctor-review',

        ],
    ],
];
