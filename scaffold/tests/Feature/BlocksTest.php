<?php

namespace Tests\Feature;

use Statamic\Facades\Blueprint;
use Statamic\Facades\Fieldset;
use Tests\TestCase;

class BlocksTest extends TestCase
{
	// Set handle, fieldset and partial must match, or the page throws a missing-view error.
	public function test_every_block_has_a_fieldset_and_a_partial(): void
	{
		$blueprints = Blueprint::in('collections/pages')
			->filter(fn ($blueprint) => $blueprint->hasField('blocks'));

		$this->assertNotEmpty($blueprints);

		foreach ($blueprints as $blueprint) {
			$sets = collect($blueprint->field('blocks')->config()['sets'] ?? [])
				->flatMap(fn ($group) => $group['sets'] ?? []);

			foreach ($sets as $handle => $set) {
				$this->assertContains(['import' => $handle], $set['fields'] ?? [], "Set [{$handle}] must import the fieldset [{$handle}].");
				$this->assertNotNull(Fieldset::find($handle), "Fieldset [{$handle}] is missing.");

				$partial = 'components.blocks.'.str_replace('_', '-', $handle);
				$this->assertTrue(view()->exists($partial), "Partial [{$partial}] is missing.");
			}
		}
	}
}
