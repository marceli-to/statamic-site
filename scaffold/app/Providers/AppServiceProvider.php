<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Statamic\Statamic;

class AppServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		//
	}

	public function boot(): void
	{
		// Outside production, funnel ALL outgoing mail to MAIL_TO so real
		// recipient addresses are never contacted from dev/staging.
		if (!$this->app->isProduction() && ($catchAll = config('site.mail_to'))) {
			Mail::alwaysTo($catchAll);
		}

		// Custom Control Panel assets — built by `npm run cp:build`.
		Statamic::vite('app', [
			'input' => [
				'resources/js/cp.js',
				'resources/css/cp.css',
			],
			'hotFile' => public_path('cp-hot'),
			'buildDirectory' => 'vendor/app',
		]);
	}
}
