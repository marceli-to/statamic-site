<?php

// Applies the house settings to a fresh Statamic install. Edits files in place
// and is safe to run twice. Usage: php patch.php <project-dir> <domain>

[$self, $dir, $domain] = $argv + [null, null, null];

if (!$dir || !$domain || !is_file("$dir/please")) {
	fwrite(STDERR, "Usage: php patch.php <project-dir> <domain>\n");
	exit(1);
}

function edit(string $path, callable $fn): void
{
	$before = file_get_contents($path);
	$after = $fn($before);

	if ($after === null) {
		fwrite(STDERR, "Could not patch $path\n");
		exit(1);
	}

	file_put_contents($path, $after);
}

function editJson(string $path, callable $fn): void
{
	$data = json_decode(file_get_contents($path), true);
	$data = $fn($data);
	$indent = str_ends_with($path, 'package.json') ? 2 : 4;
	$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	// json_encode always indents with 4 spaces.
	if ($indent === 2) {
		$json = preg_replace_callback('/^( +)/m', fn ($m) => str_repeat(' ', strlen($m[1]) / 2), $json);
	}
	file_put_contents($path, $json."\n");
}

function setEnv(string $content, array $values): string
{
	foreach ($values as $key => $value) {
		$line = "$key=$value";
		$content = preg_match("/^$key=.*$/m", $content)
			? preg_replace("/^$key=.*$/m", $line, $content)
			: rtrim($content)."\n$line\n";
	}

	return $content;
}

// .env and .env.example
$env = [
	'APP_URL' => "https://$domain.test",
	'APP_LOCALE' => 'de',
	'APP_FALLBACK_LOCALE' => 'de',
	'QUEUE_CONNECTION' => 'database',
	'MAIL_MAILER' => 'smtp',
	'MAIL_PORT' => '1025',
	'MAIL_FROM_ADDRESS' => "\"no-reply@$domain\"",
	'MAIL_TO' => '',
	'STATAMIC_PRO_ENABLED' => 'true',
	// Production sets half.
	'STATAMIC_STATIC_CACHING_STRATEGY' => 'null',
];
foreach (['.env', '.env.example'] as $file) {
	if (is_file("$dir/$file")) {
		edit("$dir/$file", fn ($c) => setEnv($c, $env));
	}
}

// Glide: one uncropped preset per width. Ratios are a CSS concern.
edit("$dir/config/statamic/assets.php", fn ($c) => preg_replace(
	"/'presets' => \[.*?\n(\s*)\],/s",
	"'presets' => [\n".
	"\$1    // One uncropped image per width — the natural ratio is kept. Ratios are\n".
	"\$1    // a CSS concern: the caller sets an aspect-ratio class and object-cover.\n".
	"\$1    'md-webp' => ['w' => 768, 'q' => 90, 'fit' => 'max', 'fm' => 'webp'],\n".
	"\$1    'lg-webp' => ['w' => 1280, 'q' => 90, 'fit' => 'max', 'fm' => 'webp'],\n".
	"\$1    'xl-webp' => ['w' => 1920, 'q' => 90, 'fit' => 'max', 'fm' => 'webp'],\n".
	"\$1    'md' => ['w' => 768, 'q' => 90, 'fit' => 'max'],\n".
	"\$1    'lg' => ['w' => 1280, 'q' => 90, 'fit' => 'max'],\n".
	"\$1    'xl' => ['w' => 1920, 'q' => 90, 'fit' => 'max'],\n".
	"\n".
	"\$1    'max_upload_size' => ['w' => 4000, 'h' => 4000, 'fit' => 'max'],\n".
	"\$1],",
	$c,
	1
));

// Static caching: a save can change navs, teasers and globals on any page, so
// flush everything rather than tracking dependencies on a small site.
edit("$dir/config/statamic/static_caching.php", fn ($c) => str_contains($c, "'rules' => 'all'")
	? $c
	: preg_replace("/'rules' => \[\s*\/\/\s*\],/", "'rules' => 'all',", $c, 1));

// composer.json scripts
editJson("$dir/composer.json", function ($data) {
	$publish = '@php artisan vendor:publish --tag=statamic-cp --force';
	$setup = $data['scripts']['setup'] ?? [];
	foreach ([$publish, 'npm run cp:build'] as $step) {
		if (!in_array($step, $setup)) {
			$setup[] = $step;
		}
	}
	$data['scripts']['setup'] = $setup;
	$data['scripts']['lint'] = ['php-cs-fixer fix --dry-run --diff'];
	$data['scripts']['lint:fix'] = ['php-cs-fixer fix'];
	$update = $data['scripts']['post-update-cmd'] ?? [];
	if (!in_array('@php artisan vendor:publish --tag=statamic-cp --ansi --force', $update)) {
		$update[] = '@php artisan vendor:publish --tag=statamic-cp --ansi --force';
	}
	$data['scripts']['post-update-cmd'] = $update;

	return $data;
});

// package.json scripts and dependencies
editJson("$dir/package.json", function ($data) {
	$data['scripts']['cp:dev'] = 'vite --config vite-cp.config.js';
	$data['scripts']['cp:build'] = 'vite build --config vite-cp.config.js';
	$data['dependencies']['@statamic/cms'] = 'file:./vendor/statamic/cms/resources/dist-package';
	$data['dependencies']['alpinejs'] = $data['dependencies']['alpinejs'] ?? '^3';
	$data['dependencies']['@alpinejs/collapse'] = $data['dependencies']['@alpinejs/collapse'] ?? '^3';
	ksort($data['dependencies']);

	return $data;
});

// .gitignore — built assets are committed so the server never runs npm.
edit("$dir/.gitignore", function ($c) {
	$c = preg_replace('#^/public/(build|vendor/statamic)\n#m', '', $c);
	foreach (['/public/cp-hot', '/public/assets', '/public/vendor/statamic/cp-dev', '/users', '.php-cs-fixer.cache'] as $line) {
		if (!preg_match('/^'.preg_quote($line, '/').'$/m', $c)) {
			$c = rtrim($c)."\n$line";
		}
	}

	return $c."\n";
});

echo "Patched $dir\n";
