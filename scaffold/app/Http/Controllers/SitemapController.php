<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Statamic\Facades\Entry;

class SitemapController extends Controller
{
	/** Collections with a `route:` — everything else is not a page. */
	private const COLLECTIONS = ['pages'];

	public function __invoke(): Response
	{
		$urls = Entry::query()
			->whereIn('collection', self::COLLECTIONS)
			->where('published', true)
			->get()
			->reject(fn ($entry) => $entry->private())
			->reject(fn ($entry) => (bool) $entry->value('noindex'))
			->filter(fn ($entry) => $entry->url())
			->map(fn ($entry) => [
				'loc' => $entry->absoluteUrl(),
				'lastmod' => optional($entry->lastModified())->toAtomString(),
			])
			->sortBy('loc')
			->values();

		return response()
			->view('sitemap', ['urls' => $urls])
			->header('Content-Type', 'application/xml');
	}
}
