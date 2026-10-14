# Setting up a new site

Reference implementation: meinewunschbank.ch. When in doubt, copy from there.

## 1. Install

```bash
composer create-project statamic/statamic <domain>
cd <domain>
herd secure
```

Laravel 13, Statamic 6. Statamic Pro on (`STATAMIC_PRO_ENABLED=true`).

`.env.example`:

```dotenv
APP_URL=https://<domain>.test
APP_LOCALE=de
DB_CONNECTION=sqlite
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=database
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_TO="<dev catch-all>"
STATAMIC_PRO_ENABLED=true
```

Flat-file install: users in `/users` (gitignored — password hashes), roles in
`resources/users/roles.yaml`. sqlite holds only `jobs` / `failed_jobs`, so mail
needs a worker locally (`composer dev`) and the scheduler in production.

## 2. Composer

`require-dev` adds `friendsofphp/php-cs-fixer` (Pint can't do tabs).

Scripts — keep the Laravel defaults and set these:

```json
"setup": [
    "composer install",
    "@php -r \"file_exists('.env') || copy('.env.example', '.env');\"",
    "@php artisan key:generate",
    "@php artisan migrate --force",
    "@php artisan vendor:publish --tag=statamic-cp --force",
    "npm install --ignore-scripts",
    "npm run build",
    "npm run cp:build"
],
"dev": [
    "Composer\\Config::disableProcessTimeout",
    "npx concurrently -c \"#93c5fd,#c4b5fd,#fb7185,#fdba74\" \"php artisan serve\" \"php artisan queue:listen --tries=1 --timeout=0\" \"php artisan pail --timeout=0\" \"npm run dev\" --names=server,queue,logs,vite --kill-others"
],
"lint": ["php-cs-fixer fix --dry-run --diff"],
"lint:fix": ["php-cs-fixer fix"],
"post-update-cmd": [
    "@php artisan vendor:publish --tag=laravel-assets --ansi --force",
    "@php artisan vendor:publish --tag=statamic-cp --ansi --force"
]
```

## 3. npm

`package.json`:

```json
{
    "private": true,
    "type": "module",
    "scripts": {
        "build": "vite build",
        "dev": "vite",
        "cp:dev": "vite --config vite-cp.config.js",
        "cp:build": "vite build --config vite-cp.config.js"
    },
    "devDependencies": {
        "@tailwindcss/vite": "^4",
        "concurrently": "^9",
        "laravel-vite-plugin": "^3",
        "tailwindcss": "^4",
        "vite": "^8"
    },
    "dependencies": {
        "@alpinejs/collapse": "^3",
        "@statamic/cms": "file:./vendor/statamic/cms/resources/dist-package",
        "@vitejs/plugin-vue": "^6",
        "alpinejs": "^3",
        "vue": "^3"
    }
}
```

Add `swiper` only when a slider is needed. Drop Vue and `@vitejs/plugin-vue`
if the site has no island.

`.npmrc`:

```ini
ignore-scripts=true
audit=true
```

## 4. Vite

`vite.config.js`:

```js
import { defineConfig } from 'vite';
import { fileURLToPath, URL } from 'node:url';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
	plugins: [
		laravel({
			input: ['resources/css/app.css', 'resources/js/app.js'],
			refresh: true,
		}),
		vue(),
		tailwindcss(),
	],
	resolve: {
		alias: {
			'@': fileURLToPath(new URL('./resources/js', import.meta.url)),
		},
	},
	server: {
		watch: {
			ignored: ['**/storage/framework/views/**'],
		},
	},
});
```

`vite-cp.config.js` (Control Panel assets — custom fieldtypes, CP styles):

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import statamic from '@statamic/cms/vite-plugin';

export default defineConfig({
	plugins: [
		statamic(),
		laravel({
			input: ['resources/css/cp.css', 'resources/js/cp.js'],
			hotFile: 'public/cp-hot',
			buildDirectory: 'vendor/app',
		}),
	],
});
```

`jsconfig.json` so editors resolve `@/`:

```json
{
    "compilerOptions": {
        "baseUrl": ".",
        "paths": { "@/*": ["resources/js/*"] }
    },
    "exclude": ["node_modules", "public"]
}
```

## 5. Editor config and code style

`.editorconfig`:

```ini
root = true

[*]
charset = utf-8
end_of_line = lf
indent_style = tab
indent_size = 2
insert_final_newline = true
trim_trailing_whitespace = true

[*.md]
trim_trailing_whitespace = false

# YAML forbids tabs.
[*.{yml,yaml}]
indent_style = space
indent_size = 2

# Written by composer and npm, which indent with spaces.
[*.json]
indent_style = space
indent_size = 4

[package.json]
indent_size = 2
```

`.php-cs-fixer.php`: copy from meinewunschbank.ch — PSR-12, close to Pint's
`laravel` preset, `->setIndent("\t")`. Finder covers `app`, `config`,
`database`, `routes`, `tests`; excludes `*.blade.php`.

## 6. App wiring

`AppServiceProvider::boot()`:

```php
// Outside production, funnel ALL outgoing mail to MAIL_TO (e.g. Mailpit)
// so real recipient addresses are never contacted from dev/staging.
if (!$this->app->isProduction() && ($catchAll = config('mail.to'))) {
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
```

`config/mail.php` gets `'to' => env('MAIL_TO'),`.

**Sitemap** — `routes/web.php` registers
`Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');`.
The invokable `SitemapController` queries the routable collections
(`COLLECTIONS = ['pages']`), keeps published, non-private, non-`noindex`
entries that have a URL, maps to `loc` / `lastmod`, sorts by `loc`, and renders
`resources/views/sitemap.blade.php` as `application/xml`. Copy both from
meinewunschbank.ch and adjust `COLLECTIONS`.

`.gitignore` additions: `/public/cp-hot`, `/users`, `/public/assets`,
`.php-cs-fixer.cache`, plus any collection that holds submissions
(`/content/collections/<name>/*` with a `.gitkeep` exception).

## 7. Content model baseline

Every site starts with:

- **Asset container** `assets` on a `local` disk rooted at `public_path('assets')`,
  URL `/assets`. Asset blueprint has one field, `alt` (Alt-Text, German
  instructions).
- **Glide presets** (`config/statamic/assets.php`) — one uncropped preset per
  width, ratio is a CSS concern (see `css.md` → Images):

  ```php
  // One uncropped image per width — the natural ratio is kept. Ratios are
  // a CSS concern: the caller sets an aspect-ratio class and object-cover,
  // which also lets the ratio change per breakpoint from a single file.
  'md-webp' => ['w' => 768, 'h' => 10000, 'q' => 90, 'fit' => 'max', 'fm' => 'webp'],
  'lg-webp' => ['w' => 1280, 'h' => 10000, 'q' => 90, 'fit' => 'max', 'fm' => 'webp'],
  'xl-webp' => ['w' => 1920, 'h' => 10000, 'q' => 90, 'fit' => 'max', 'fm' => 'webp'],
  'md' => ['w' => 768, 'h' => 10000, 'q' => 90, 'fit' => 'max'],
  'lg' => ['w' => 1280, 'h' => 10000, 'q' => 90, 'fit' => 'max'],
  'xl' => ['w' => 1920, 'h' => 10000, 'q' => 90, 'fit' => 'max'],

  'max_upload_size' => ['w' => 4000, 'h' => 4000, 'fit' => 'max'],
  ```

  Keep `generate_presets_on_upload` on.
- **Collection** `pages` (title `Seiten`, `template: default`,
  `route: '{parent_uri}/{slug}'`, `structure: root: true`). Blueprint `page` —
  see `blocks.md`.
- **Navigations** `main` (Hauptnavigation) and `privacy` (legal links), both on
  `pages`.
- **Globals** `address` (one Bard field `address`: bold, removeformat, anchor)
  and `social_media` (url text fields per network, only the ones the design
  uses).
- **Fieldsets** `editor` and `seo` — see `blocks.md`.

## 8. Project CLAUDE.md and README

Every project gets a `CLAUDE.md` covering what this skill can't know: the
colour token names, the font-size scale, which blocks exist, any project-only
feature. Point at this skill for the rest rather than repeating it. The README
documents setup (`composer setup`, `statamic:make:user --super`,
`herd secure`), the cron entry for the scheduler, and how the content model
fits together.
