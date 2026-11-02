<?php

return [

    // Registrierung: true = jeder kann ein Konto anlegen. false = nur das erste Konto.
    'open_registration' => (bool) env('FRISTWATCHER_OPEN_REGISTRATION', false),

    // Stunde (Ortszeit), zu der Erinnerungen versendet werden.
    'reminder_hour' => (int) env('FRISTWATCHER_REMINDER_HOUR', 7),

    // Standard-Vorlaufzeiten in Tagen vor dem empfohlenen Versandtag.
    'default_offsets' => [30, 7, 0],

    // Arbeitstage zwischen Versand und letztem Eingangstag (Einschreiben).
    'default_send_buffer_days' => 5,

    // Tage nach Versand, bis an die Kündigungsbestätigung erinnert wird.
    'confirmation_after_days' => 14,

    // Ab wann ein Abo als "ungenutzt" gilt.
    'unused_after_days' => 60,

    // Vorlaufzeiten für Ablaufdaten (Tage vor Ablauf).
    'expiry_offsets' => [
        'passport' => [180, 60],
        'id_card' => [180, 60],
        'driving_license' => [90, 30],
        'warranty' => [30, 7],
        'vignette' => [30, 7],
        'other' => [90, 30],
    ],

    'cantons' => [
        'AG', 'AI', 'AR', 'BE', 'BL', 'BS', 'FR', 'GE', 'GL', 'GR', 'JU', 'LU', 'NE',
        'NW', 'OW', 'SG', 'SH', 'SO', 'SZ', 'TG', 'TI', 'UR', 'VD', 'VS', 'ZG', 'ZH',
    ],

    /*
     * Feiertage. Bundesweit nur der 1. August; die übrigen hier gelten in fast allen
     * Kantonen und werden für die Fristberechnung vorsichtig überall angewendet.
     * Ein Feiertag zu viel macht eine Frist nur einen Tag früher, nie zu spät.
     * Fixe Daten als "MM-DD", bewegliche als Offset zu Ostersonntag.
     */
    'holidays' => [
        'fixed' => ['01-01', '08-01', '12-25', '12-26'],
        'easter_offsets' => [-2, 1, 39, 50], // Karfreitag, Ostermontag, Auffahrt, Pfingstmontag
        'cantonal' => [
            'SG' => ['11-01'],
            'AI' => ['11-01', '12-08'],
            'AR' => [],
            'TG' => ['01-02', '05-01'],
            'ZH' => ['01-02', '05-01'],
            'BE' => ['01-02'],
            'LU' => ['01-02', '11-01', '12-08'],
            'AG' => ['01-02'],
        ],
    ],

];
