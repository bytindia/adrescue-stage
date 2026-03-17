<?php
/**
 * Campaign configuration for email-to-ad automation.
 * Edit ad account, page, territories, targeting, and budget.
 */

return [
    /* Filter: only process emails from these addresses */
    'from_email' => 'prabhu@bytindia.com',

    /* Meta Ad Account ID (without act_ prefix) - 5656356187816184*/ 
    'ad_account_id' => '494189059371075', 

    /* Facebook Page ID for the ads */
    'page_id' => '197785600066235',

    /* Daily budget per ad set (in currency units, e.g. 100 = ₹100 or $100) */
    'daily_budget' => 100,

    /* Objective: OUTCOME_AWARENESS (brand awareness) or OUTCOME_TRAFFIC (link clicks).
       OUTCOME_AWARENESS: optimizes for REACH or AD_RECALL_LIFT, billing=IMPRESSIONS.
       OUTCOME_TRAFFIC: optimizes for LINK_CLICKS. */
    'objective' => 'OUTCOME_AWARENESS',

    /* Optimization goal (used with OUTCOME_AWARENESS): REACH or AD_RECALL_LIFT. REACH = max unique people; AD_RECALL_LIFT = people likely to remember ad. */
    'optimization_goal' => 'REACH',

    /* Advantage+ audience: 0 = opt out (strict targeting), 1 = opt in (Meta may expand audience). Required since API v23.0. */
    'advantage_audience' => 0,

    /* Initial status: PAUSED = create but don't run; ACTIVE = launch immediately */
    'ad_status' => 'PAUSED',

    /* Age targeting - same for all territories */
    'age_min' => 25,
    'age_max' => 55,

    /* Territories - each gets its own campaign */
    'territories' => ['Chennai', 'Tamil Nadu', 'Karnataka', 'Andhra Pradesh', 'Bengaluru'],

    /* Geo targeting per territory. Use Meta location IDs.
       IMPORTANT: Do NOT use both countries and cities/regions - Meta rejects "locations overlap".
       Use either countries only OR cities/regions only. */
    'territory_targeting' => [
        'Chennai' => [
            'geo_locations' => [
                'cities' => [['key' => '1021534', 'radius' => 50, 'distance_unit' => 'kilometer']],
            ],
        ],
        'Tamil Nadu' => [
            'geo_locations' => [
                'regions' => [['key' => '1744']],
            ],
        ],
        'Karnataka' => [
            'geo_locations' => [
                'regions' => [['key' => '1756']],
            ],
        ],
        'Andhra Pradesh' => [
            'geo_locations' => [
                'regions' => [['key' => '1734']],
            ],
        ],
        'Bengaluru' => [
            'geo_locations' => [
                'cities' => [['key' => '1021218', 'radius' => 50, 'distance_unit' => 'kilometer']],
            ],
        ],
    ],
];
