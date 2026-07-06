<?php

/*
|--------------------------------------------------------------------------
| Customer Price List — Industry Presets
|--------------------------------------------------------------------------
| Preconfigured industry/use-case selections for the price list generator.
| Presets only pre-check category checkboxes — staff can always add or
| remove categories afterward, and the generated document stays purely
| category-based.
|
| Matching is slug-based (stable), never display-name-based:
|   - 'slugs'         exact product-category slugs
|   - 'slug_keywords' substring matches against category slugs, so
|                     'excavator' also catches 'mini-excavators' and
|                     'attachment' catches every attachments category.
| Slugs that do not exist in this environment are silently ignored — keep
| this list aligned with the live category slugs when categories change.
*/

return [

    'dirt_work' => [
        'label'         => 'Dirt Work / Grading',
        'description'   => 'Skid steers, excavators, dozers, rollers & compaction, attachments',
        'slugs'         => ['skid-steers', 'excavators', 'dozers', 'rollers', 'compaction'],
        'slug_keywords' => ['skid-steer', 'excavator', 'dozer', 'roller', 'compact', 'attachment'],
    ],

    'tree_work' => [
        'label'         => 'Tree Work / Arborist',
        'description'   => 'Chippers, stump grinders, mini skids, brush cutters, trailers, attachments',
        'slugs'         => ['wood-chippers', 'stump-grinders', 'mini-skids', 'brush-cutters', 'trailers'],
        'slug_keywords' => ['chipper', 'stump', 'mini-skid', 'brush', 'trailer', 'attachment'],
    ],

    'plumbing_utility' => [
        'label'         => 'Plumbing / Utility',
        'description'   => 'Trenchers, mini excavators, compaction, concrete saws, pumps, attachments',
        'slugs'         => ['trenchers', 'mini-excavators', 'compaction', 'concrete-saws', 'pumps'],
        'slug_keywords' => ['trencher', 'mini-excavator', 'compact', 'concrete-saw', 'saw', 'pump', 'attachment'],
    ],

    'home_building' => [
        'label'         => 'Home Building / Construction',
        'description'   => 'Skid steers, excavators, telehandlers, lifts, compaction, attachments',
        'slugs'         => ['skid-steers', 'excavators', 'telehandlers', 'lifts', 'compaction'],
        'slug_keywords' => ['skid-steer', 'excavator', 'telehandler', 'lift', 'compact', 'attachment'],
    ],

];
