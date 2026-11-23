<?php

namespace App\Support;

/**
 * Single source for the announcements shown on the dashboard's "Latest news" panel
 * and on the News page. Placeholder content: move this to a table when admins need to post news,
 * then only this class changes.
 *
 * Item shape: title, body, date?, tag?, image?, focus?, source?
 */
class NewsFeed
{
    public static function all(): array
    {
        $today = now()->toFormattedDateString();

        return [
            ['title' => 'Welcome to NagaSync', 'date' => $today,
             'body' => 'Request barangay and City Hall documents online, track them, and claim them once approved.'],
            ['title' => 'Verification opens document requests', 'date' => $today,
             'body' => 'Barangay staff confirm that you live in the barangay you registered with. Once verified, you can request documents, pay fees and vote in polls.'],
        ];
    }
}