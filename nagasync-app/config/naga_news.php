<?php

// News shown on the Guide page (News tab) and the Dashboard "Latest news" panel.
// Images live in public/images/news/. `focus` is a CSS object-position (top, center, bottom)
// that decides which part of a graphic the cropped thumbnail shows.
// Newest first: the first item becomes the featured story.
return [
    [
        'tag' => 'City news',
        'title' => 'Naga City is declared the 34th Highly Urbanized City',
        'body' => 'After being named the Most Competitive Component City for four straight years as an Independent Component City, Naga has officially joined the Philippines’ top-tier urban centers. The proclamation was signed by President Ferdinand Marcos Jr. and announced by Mayor Leni Robredo on May 27, 2026, during the commemoration of the 68th birth anniversary of the late Jesse Robredo.',
        'date' => 'May 27, 2026',
        'image' => '/images/news/huc-declaration.webp',
        'focus' => 'top',
        'source' => 'Green and Gold Publication',
    ],
    [
        'tag' => 'Technology',
        'title' => 'Naga City set to launch the Philippines’ first AI-powered City Planner',
        'body' => 'The city is preparing to launch what is reported to be the country’s first AI-powered City Planner.',
        'date' => null, // TODO: set the publish date
        'image' => '/images/news/ai-city-planner.webp',
        'focus' => 'top',
        'source' => 'The Compass',
    ],
    [
        'tag' => 'Economy',
        'title' => 'Naga City eyes a bigger role in Bicol’s growing IT-BPM sector',
        'body' => 'PEZA and Inspiro are exploring expansion as Naga looks to take a larger part in the region’s growing IT-BPM industry.',
        'date' => null, // TODO: set the publish date
        'image' => '/images/news/it-bpm-expansion.webp',
        'focus' => 'top',
        'source' => 'LGU Naga City',
    ],
    [
        'tag' => 'Explainer',
        'title' => 'So what if Naga City is now a highly urbanized city?',
        'body' => 'A quick explainer on what the change in city status could mean for Naga and its residents.',
        'date' => null, // TODO: set the publish date
        'image' => '/images/news/huc-explainer.webp',
        'focus' => 'bottom', // the headline sits at the top of this graphic, the building at the bottom
        'source' => null,
    ],
    [
        'tag' => 'NagaSync',
        'title' => 'Welcome to NagaSync',
        'body' => 'Request barangay and City Hall documents online, track them, and claim them once approved.',
        'date' => 'Nov 7, 2026',
    ],
    [
        'tag' => 'NagaSync',
        'title' => 'Verification opens document requests',
        'body' => 'Barangay staff confirm that you live in the barangay you registered with. Once verified, you can request documents, pay fees and vote in polls.',
        'date' => 'Nov 7, 2026',
    ],
];