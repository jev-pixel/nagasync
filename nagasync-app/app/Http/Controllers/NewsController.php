<?php

namespace App\Http\Controllers;

use App\Support\NewsFeed;
use Inertia\Inertia;

/**
 * City news and announcements. Open to every signed-in account, including
 * citizens who are not verified yet (same as the Guide).
 */
class NewsController extends Controller
{
    public function __invoke()
    {
        return Inertia::render('News/Index', [
            'news' => NewsFeed::all(),
        ]);
    }
}