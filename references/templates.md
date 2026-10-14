# Templates

Antlers for every site template. Blade only for mails and `sitemap.blade.php`.

## File layout

```
resources/views/
├── layout.antlers.html          composes the page from layout/*
├── default.antlers.html         loops the blocks replicator
├── <other templates>.antlers.html
├── sitemap.blade.php
├── mail/…                       Blade mailables
└── components/                  everything else is a partial
    ├── blocks.antlers.html      renders blocks/<type> (one dynamic partial call)
    ├── blocks/<handle>.antlers.html
    ├── layout/{html,head,body,header,main,footer,container,section}.antlers.html
    ├── menu/{container,item}.antlers.html
    ├── menu/buttons/{show,hide}.antlers.html
    ├── headings/{h1,h2,h3,h4}.antlers.html
    ├── media/image.antlers.html
    ├── button.antlers.html
    ├── card.antlers.html        (or cards/<name> when there are several)
    ├── social-media.antlers.html
    ├── icons/<name>.antlers.html
    └── logos/<name>.antlers.html
```

Partials are called as `{{ partial:components/path/name param="value" }}`.
File names are kebab-case.

## Writing components

A partial that takes parameters documents them at the top — parameters only,
no description of what the component is:

```antlers
{{#
	@param compact - Narrow variant (78rem) instead of 96rem.
	@param spacing - Vertical spacing, e.g. py-20 md:py-28.
	@param class - Extra classes.
#}}
```

- **`class` param** — every component that could reasonably be nudged from the
  outside accepts `class` and interpolates it last. Layout concerns (width,
  margin) come from the caller; the component's own look stays inside.
- **Slots** — a component that wraps content is called as a paired tag and
  renders `{{ slot }}`.
- **Icons and logos are partials**, inline SVG with `fill="currentColor"` /
  `stroke="currentColor"`, `aria-hidden="true"` and `class="{{ class }}"`, so
  they take the caller's text colour. Never `<img src="icon.svg">`.
- **Headings** — use `headings/h1`…`h4`; they carry `text-balance`.
- Comment markup sections with `{{# Section name #}}`, not HTML comments.

## Layout

`layout.antlers.html`:

```antlers
{{ partial:components/layout/html }}

{{ partial:components/layout/head }}

{{ partial:components/layout/body }}

	{{ partial:components/layout/header }}

	{{ partial:components/layout/main }}

		{{ template_content }}

	{{ /partial:components/layout/main }}

	{{ partial:components/layout/footer }}

{{ /partial:components/layout/body }}

{{ /partial:components/layout/html }}
```

Variants (e.g. a landing header on the homepage) branch inside the layout with
`{{ if is_homepage }}`.

- **`layout/html`** — `<!doctype html><html lang="{{ site:short_locale }}" class="scroll-smooth overflow-y-scroll">{{ slot }}</html>`.
- **`layout/body`** — `<body>` with the base type (font, default size from the
  token scale, leading, `antialiased`), `min-h-screen flex flex-col`, and the
  Alpine root state: `x-data="{ menu: false }" @keydown.escape.window="menu = false"`.
- **`layout/main`** — `<main class="flex-1">{{ slot }}</main>`.
- **`layout/head`** — charset, viewport,
  `format-detection` off, `csrf-token`, then the SEO block, favicons, a
  `WebSite` JSON-LD, and last `{{ vite src="resources/css/app.css|resources/js/app.js" }}`.
  SEO is computed in Antlers variables at the top:

  ```antlers
  {{ site_name = "<Site name>" }}
  {{ seo_title = is_homepage ? site_name : "{title} — {site_name}" }}
  {{ seo_description = meta_description ?? "<Default description>" }}
  {{ seo_image = "{config:app:url}{og_image ?? '/img/og-image.jpg'}" }}
  ```

  followed by `<title>`, description, `{{ if noindex }}robots noindex{{ /if }}`,
  canonical (`current_url`), Open Graph (`og:locale` `de_CH`, 1200×630 image)
  and Twitter card tags.
- **`layout/header`** — sticky, a container with logo link
  (`aria-label="Zur Startseite"`) and `menu/buttons/show`; includes
  `menu/container` after the header.
- **`layout/footer`** — address global (`{{ address:address }}`), social media,
  `nav:main` and `nav:privacy`, each `<nav>` with a German `aria-label`.

## Container and section

`layout/container` is the **only** place that sets page gutters and max width.
Never write `mx-auto px-…` anywhere else.

```antlers
{{#
	@param compact - Narrow variant (78rem) instead of 96rem.
	@param spacing - Vertical spacing, e.g. py-20 md:py-28.
	@param class - Extra classes.
#}}
<div class="{{ if compact }}max-w-[78rem]{{ else }}max-w-[96rem]{{ /if }} mx-auto px-20 md:px-32 lg:px-44 {{ spacing ?? '' }} {{ class }}">
	{{ slot }}
</div>
```

Widths and gutters come from the design; the shape stays.

`layout/section` wraps a block that needs full-bleed content (an edge-to-edge
image or slider) plus contained parts, and owns the vertical rhythm via named
steps:

```antlers
{{#
	@param spacing - Vertical rhythm: 'none' (default), 'tight', 'snug' or 'loose'.
	@param class - Extra classes.
#}}
<section class="{{ if spacing == 'tight' }}py-20 md:py-28 lg:py-36{{ elseif spacing == 'snug' }}py-30 md:py-42 lg:py-54{{ elseif spacing == 'loose' }}py-40 md:py-56 lg:py-72{{ /if }} {{ class }}">
	{{ slot }}
</section>
```

Every block opens with a container or a section. Keep the project's rhythm to
these few named steps rather than inventing per-block padding.

## Images — `media/image`

```antlers
{{#
	@param image - Asset reference
	@param alt - Alt text
	@param sizes - CSS sizes attribute (default: "100vw")
	@param class - Classes for the img, e.g. "aspect-square md:aspect-[16/7] object-cover"
	@param wrapperClass - Classes for the figure wrapper
	@param caption - Optional caption
	@param captionClass - Classes for the figcaption
	@param eager - Load eagerly (default: false)
#}}
{{ sizes = sizes ?? '100vw' }}
<figure class="{{ wrapperClass }}">
	<picture>
		<source
			type="image/webp"
			srcset="{{ glide:image preset="md-webp" }} 768w,
			        {{ glide:image preset="lg-webp" }} 1280w,
			        {{ glide:image preset="xl-webp" }} 1920w"
			sizes="{{ sizes }}">
		<img
			src="{{ glide:image preset="lg" }}"
			srcset="{{ glide:image preset="md" }} 768w,
			        {{ glide:image preset="lg" }} 1280w,
			        {{ glide:image preset="xl" }} 1920w"
			sizes="{{ sizes }}"
			{{ if image:width }}width="{{ image:width }}" height="{{ image:height }}"{{ /if }}
			alt="{{ alt }}"
			class="block w-full h-auto {{ class ?? '' }}"
			loading="{{ eager ? 'eager' : 'lazy' }}"
			decoding="async">
	</picture>
	{{ if caption }}
		<figcaption class="{{ captionClass }}">
			{{ caption | nl2br }}
		</figcaption>
	{{ /if }}
</figure>
```

`width` / `height` come from the asset so the browser reserves the space before
the file loads (no layout shift); an aspect class still overrides the ratio.

Call it with `:image="field" :alt="field:alt"`, the aspect class, and a real
`sizes` whenever the image isn't full width. `eager="true"` for the first image
above the fold. SVGs and videos are handled in the block itself
(`media:extension == 'svg'`, `media:is_video`) since Glide can't resize them.

## Button

`button` renders `<a>` when `href` is set, otherwise `<button type="button">`.
Params: `href`, `variant` (`primary` default / `secondary`), `on` (Alpine
`@click` expression), `label` (aria-label), `class`, slot for the label. The
base classes include `btn`, which exempts it from the `article a:not(.btn)`
underline rule. Shared classes live in Antlers variables
(`button_base`, `button_variant`) at the top of the partial.

## Menu

Alpine, driven by the `menu` flag on `<body>`:

- `menu/buttons/show` — `x-show="!menu"`, `@click="menu = !menu"`,
  `aria-label="Menü öffnen"`, burger icon.
- `menu/buttons/hide` — `x-show="menu"`, `aria-label="Menü schliessen"`, cross icon.
- `menu/container` — `<nav aria-label="Hauptnavigation" x-show="menu" x-cloak x-transition…>`
  with logo, hide button and `{{ nav:main }}{{ partial:components/menu/item }}{{ /nav:main }}`.
- `menu/item` — `<li>` with the link, `aria-current="page"` when
  `is_current || is_active`, and `x-on:click="menu = false"`.

Transitions include `motion-reduce:duration-0`.

## Language and accessibility

German `aria-label`s on every icon-only link or button, every `<nav>`, and
links whose text alone is ambiguous (`"{{ title }} – mehr erfahren"`). External
links get `target="_blank" rel="noopener noreferrer"`.
