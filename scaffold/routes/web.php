<?php

use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// Pages are served by Statamic's `pages` collection.

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
