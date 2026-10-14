# Setting up a new site

The fixed parts — tooling, config, layout skeleton, shared fieldsets, sitemap,
scheduler, the blocks test — come from `scaffold/` via `scripts/scaffold.sh`.
Don't retype them; run the script and then spend the time on the
project-specific parts (section 4).

## 1. Create the project in Herd

Sites live in `~/Jamon.digital/Webroot/`, which Herd has parked: every folder
there is served as `<folder>.test`, no `herd link`. Name the folder after the
production domain, TLD included.

```bash
cd ~/Jamon.digital/Webroot
composer create-project statamic/statamic <domain>
herd secure <domain>
```

`herd secure` has to come before the first `npm run dev`: laravel-vite-plugin
looks for Herd's certificate for `<folder>.test` and serves the dev server over
HTTPS with it. Without one it quietly falls back to plain HTTP, and the site
gets mixed-content errors.

PHP comes from Herd's global version (8.4). Only `herd isolate <version>` if
production needs something else. No database service — sqlite only holds the
queue.

## 2. Lay the scaffold over it

```bash
~/.claude/skills/statamic-site/scripts/scaffold.sh ~/Jamon.digital/Webroot/<domain> <domain> "<Site name>"
```

What it does:

- removes the starter's `site.css`, `site.js`, `home` template and home entry;
- copies `scaffold/` into the project and fills in `__DOMAIN__` /
  `__SITE_NAME__`;
- runs `scripts/patch.php`, which edits files the scaffold can't own:
  `.env` / `.env.example` (`APP_URL=https://<domain>.test`, `APP_LOCALE=de`,
  database queue, Mailpit on 1025, Pro on, static caching `null`), Glide
  presets, static-cache invalidation `rules => 'all'`, composer scripts
  (`setup`, `lint`, `lint:fix`, CP publish on update), package.json (`cp:dev`,
  `cp:build`, Alpine, `@statamic/cms`) and `.gitignore`;
- requires php-cs-fixer, installs npm packages, migrates, publishes CP assets,
  builds both bundles.

Both scripts are safe to read before running; `patch.php` is idempotent.

Then:

```bash
cd ~/Jamon.digital/Webroot/<domain>
php please make:user --super
git init && git add -A && git commit -m "Set up the site"
composer dev        # server, queue, logs, vite (Laravel's `artisan dev`)
php artisan test    # includes the blocks test
```

Set `MAIL_TO` in `.env` to the dev catch-all.

## 3. What the scaffold contains

```
.editorconfig  .npmrc  .php-cs-fixer.php  jsconfig.json
vite.config.js  vite-cp.config.js
config/site.php                      mail_to (MAIL_TO)
app/Providers/AppServiceProvider.php mail catch-all, CP Vite assets
app/Http/Controllers/SitemapController.php
routes/web.php                       /sitemap.xml
routes/console.php                   scheduler drains the queue every minute
public/robots.txt
resources/css/app.css, cp.css, partials/{fonts,font-sizes}.css
resources/js/app.js, cp.js, behaviors/
resources/views/layout, default, errors/404, sitemap.blade.php
resources/views/components/blocks + blocks/editor
resources/views/components/layout/{html,head,body,main,header,footer,container,section}
resources/views/components/menu/…, headings/h1–h4, media/image, button,
    social-media, icons/{burger,cross,facebook,instagram,linkedin}, logos/logo
resources/fieldsets/{editor,seo,button,teaser}.yaml
resources/blueprints/collections/pages/page.yaml, assets/assets.yaml,
    globals/{address,social_media}.yaml
content/collections/pages.yaml + home entry, navigation/{main,privacy},
    globals/{address,social_media}
tests/Feature/BlocksTest.php
```

The scaffold copies are the source of truth for these files. If a convention
changes, change the scaffold file and the matching reference doc together.

## 4. Project-specific work

In this order:

1. **Design tokens** in `app.css` (`--color-*`, `--font-*`) and the type scale
   in `partials/font-sizes.css` — see `css.md`.
2. **Fonts** into `resources/fonts/` and `partials/fonts.css`.
3. **Logo** in `components/logos/logo.antlers.html` (inline SVG,
   `currentColor`), favicons into `public/`, `public/img/og-image.jpg`
   (1200×630).
4. **Header, footer, menu** to the design. Keep their structure, change the
   classes.
5. **Container widths and gutters** in `layout/container`, rhythm steps in
   `layout/section`.
6. **Blocks** one by one — see `blocks.md`.
7. **Globals**: trim `social_media` to the networks the client uses (blueprint,
   `social-media` partial, icons).
8. **Default meta description** in `layout/head` (`seo_description` fallback).
9. **Vue** only if the site gets an island — see `javascript.md`.

## 5. Project CLAUDE.md and README

Every project gets a `CLAUDE.md` with what this skill can't know: the colour
tokens, the type scale, which blocks exist, project-only features, and
anything that deviates from the skill. Point at the skill for the rest instead
of repeating it. The README covers setup (`composer setup`,
`php please make:user --super`, `herd secure`), the production cron entry and
how the content model fits together.
