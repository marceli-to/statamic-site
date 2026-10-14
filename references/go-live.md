# Go-live checklist

Work through it top to bottom before switching DNS. Built assets
(`public/build`, `public/vendor/app`, `public/vendor/statamic`) are committed,
so the server never runs npm.

## Content and SEO

- [ ] Every page has a `meta_description`; the fallback in `layout/head` is set.
- [ ] `noindex` is off on every page that should be found.
- [ ] `public/img/og-image.jpg` (1200×630) exists and looks right when shared.
- [ ] Favicons in `public/`: `favicon.ico`, `favicon.svg`, `favicon-96x96.png`,
      `apple-touch-icon.png`, `site.webmanifest`.
- [ ] `public/robots.txt` points at `https://<domain>/sitemap.xml`.
- [ ] `/sitemap.xml` lists the right pages with absolute production URLs.
- [ ] `nav:privacy` holds Impressum and Datenschutz.
- [ ] Every image has alt text.
- [ ] The 404 page renders.

## Production `.env`

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<domain>
MAIL_TO=                                # empty: no catch-all in production
STATAMIC_STATIC_CACHING_STRATEGY=half
STATAMIC_GIT_ENABLED=false              # unless the client edits live and git sync is wanted
```

Plus real `MAIL_*` credentials, `MAIL_FROM_ADDRESS` on the client's domain and
`STATAMIC_LICENSE_KEY`.

## Server

- [ ] Cron entry for the scheduler — it drains the queue, which carries mail
      **and static-cache invalidation**. Without it, CP edits never show on the
      cached site:

      * * * * * cd /path/to/site && php artisan schedule:run >> /dev/null 2>&1

- [ ] Document root is `public/`.
- [ ] `storage/` and `bootstrap/cache/` writable.
- [ ] `php artisan migrate --force` (the queue tables).
- [ ] `php artisan storage:link` if any disk uses `storage/app/public`.
- [ ] HTTPS on, `www` redirect decided.

## Deploy steps

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php please stache:warm
php please static:clear && php please static:warm
```

## Static caching

`half` caches every rendered page in the application cache and serves it
without touching Antlers. Statamic swaps CSRF tokens into cached pages, so
forms keep working. Every CP save queues an invalidation that flushes all
cached pages (`rules => 'all'`); the cron run picks it up within a minute.

- [ ] Click through every page once with caching on.
- [ ] Submit every form with caching on.
- [ ] Anything that must differ per visitor (random order, personalised bits)
      is wrapped in `{{ nocache }}…{{ /nocache }}` or excluded under
      `exclude.urls` in `config/statamic/static_caching.php`.

## Final checks

- [ ] `composer lint` and `php artisan test` pass.
- [ ] Lighthouse on the homepage and one long page: no CLS from images, no
      render-blocking font surprises.
- [ ] A test mail reaches a real inbox from production (not just Mailpit).
- [ ] The client has a CP account with the right role.
