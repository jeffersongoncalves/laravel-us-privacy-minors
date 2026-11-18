<?php

return [

    'tables' => [
        'assurances' => 'us_privacy_age_assurances',
        'guardians' => 'us_privacy_guardian_links',
        'opt_ins' => 'us_privacy_minor_opt_ins',
    ],

    /*
     * The age below which targeted advertising and sale need consent comes from each state's law
     * (laravel-us-privacy minor_age: 13 to 18; Maryland prohibits them under 18). Below this age the
     * consent must come from a parent or guardian (COPPA).
     */
    'parent_age' => 13,

    /*
     * Most laws apply when you know the age, or wilfully disregard it. Turn this on to treat users
     * with no age result as under 13 (no targeted advertising or sale without a parent's consent),
     * e.g. for a service directed to children.
     */
    'treat_unknown_as_minor' => false,

    /*
     * Methods that count as reliable age checks. Self-declaration never does.
     */
    'reliable_methods' => [
        'os_signal',
        'store_signal',
        'document',
        'facial_estimation',
        'verifiable_credential',
    ],

    /*
     * How long a result stays valid, per method (CarbonInterval string, null = forever).
     */
    'valid_for' => [
        'self_declaration' => '1 year',
        'os_signal' => '30 days',
        'store_signal' => '30 days',
        'document' => '1 year',
        'facial_estimation' => '1 year',
        'verifiable_credential' => '1 year',
        'guardian' => '1 year',
    ],

    /*
     * Where `us-privacy.adult` sends users without a reliable adult result. Null = 403.
     */
    'verify_route' => null,

];
