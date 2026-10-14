# CSS and Tailwind

Tailwind 4, configured entirely in CSS. There is no `tailwind.config.js`.

## File layout

```
resources/css/
├── app.css              entry: imports, @source, @theme tokens, base rules
├── cp.css               Control Panel styles (usually empty)
└── partials/
    ├── fonts.css        @font-face or a Typekit @import
    ├── font-sizes.css   the type scale
    └── <feature>.css    one per feature that needs real CSS (menu, reveal, slider…)
```

There is **no `spacing.css`** anymore — see Spacing below. In older projects it
is a 500-line generated list; don't carry it into new ones.

## app.css

```css
@import "./partials/fonts.css";
@import "tailwindcss";
@import "./partials/font-sizes.css";
@import "./partials/menu.css";

@source "../views";
@source "../../content";
@source "../js/**/*.vue";
@source "../**/*.js";

/* Automatic source detection also walks public/, which holds the published
   CP bundle and would bloat the site stylesheet with its classes. */
@source not "../../public";

@theme {
	/* 1:1 with pixels: p-16 = 16px, w-232 = 232px. No upper limit. */
	--spacing: 0.0625rem;

	/* md and lg only. */
	--breakpoint-sm: initial;
	--breakpoint-xl: initial;
	--breakpoint-2xl: initial;

	--font-sans: '<Family>', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;

	--color-brand: #…;
	/* … */
}

[x-cloak] {
	@apply hidden!;
}

p:not(:last-child),
ul:not(:last-child) {
	@apply mb-16 md:mb-20 lg:mb-24;
}

strong, b {
	@apply font-bold;
}

/* Bard renders its own headings, so they bypass the heading components. */
article h2,
article h3 {
	@apply text-balance;
}

article ul {
	@apply list-disc m-0 ml-20 md:ml-24 lg:ml-28;
}

article li {
	@apply list-item;
}

article a:not(.btn) {
	@apply underline underline-offset-2 hover:no-underline;
}
```

`fonts.css` is imported before `tailwindcss` so its `@import url(…)` (Typekit)
stays at the top of the output. Feature partials come after.

## Spacing

`--spacing: 0.0625rem` makes every numeric spacing/sizing utility a pixel
value: `p-16`, `gap-12`, `mt-120`, `w-232`, `size-24`, `inset-x-20`. Tailwind
computes `calc(var(--spacing) * N)`, so any integer works, and quarter steps
too (`p-0.5` = 0.5px). Don't add `--spacing-*` tokens.

## Breakpoints

Only `md:` (48rem) and `lg:` (64rem). Base styles are mobile; `md:` and `lg:`
step up. `max-md:` handles mobile-only overrides (e.g. a reordered footer
grid). `sm`, `xl` and `2xl` are reset in `@theme`, so `xl:` classes simply
don't generate — if a design truly needs a wide step, add a named breakpoint
deliberately rather than restoring the defaults.

## Font sizes — token scale

All font sizes come from `partials/font-sizes.css`. No arbitrary
`text-[18px]`. If the design has a size the scale doesn't, add a token.

```css
@theme {
	/* Only the design's sizes exist — Tailwind's defaults are cleared. */
	--text-*: initial;

	--text-xs: 0.875rem;   /* 14px */
	--text-sm: 1rem;       /* 16px */
	--text-md: 1.125rem;   /* 18px */
	--text-lg: 1.375rem;   /* 22px */
	--text-xl: 1.75rem;    /* 28px */
	--text-2xl: 2.125rem;  /* 34px */
	--text-3xl: 2.75rem;   /* 44px */
}
```

- Names are t-shirt sizes, ascending; values in rem with the px in a comment.
  Build the list from the sizes actually used in the design, smallest to
  largest, one token per distinct size.
- Responsive type is a combination of tokens per breakpoint:
  `text-md md:text-lg lg:text-xl`.
- A size with a fixed line height gets a companion:
  `--text-xl--line-height: 1.2;`. Otherwise set `leading-*` at the call site.
- The body's default size is set on `<body>` in `layout/body` with tokens,
  since `text-base` no longer exists.

## Colours

Named tokens in `@theme`, named after the colour, not the role, except
`brand`: `--color-brand`, `--color-snow`, `--color-ink`, `--color-mist`,
`--color-sand` … Tints of an existing token via `color-mix()` with a suffix:
`--color-linen-soft: color-mix(in srgb, var(--color-linen) 65%, var(--color-snow));`.
List the project's tokens in its `CLAUDE.md`.

## Fonts

Self-hosted files live in `resources/fonts/`, referenced relatively from
`partials/fonts.css` so Vite fingerprints them:

```css
/* URLs are relative to this partial so Vite resolves them — an absolute
   /fonts/… path 404s in dev, where laravel-vite-plugin sets publicDir: false. */
@font-face {
	font-family: '<Family>';
	src: url('../../fonts/<family>-regular.woff2') format('woff2'),
	     url('../../fonts/<family>-regular.woff') format('woff');
	font-weight: 400;
	font-style: normal;
	font-display: swap;
}
```

Typekit projects use `@import url('https://use.typekit.net/<id>.css');` instead.

## Images

Images are stored uncropped and cropped by CSS. Glide has one preset per width
(`md` / `lg` / `xl` = 768 / 1280 / 1920, plus `-webp` twins) at `fit: max` with
only a width, so a file is never upscaled. The ratio comes from an aspect class passed to
`media/image` with `object-cover` — `aspect-square`, or an arbitrary ratio such
as `aspect-[16/7]` — varying per breakpoint where the design does. Never add
per-ratio `crop_focal` presets.

## Feature CSS

When something needs more than utilities (keyframes, staggered reveals, a
third-party override), it gets its own partial. Patterns from the reference:

- Pre-reveal states are wrapped in `@media (scripting: enabled)` so no-JS
  visitors and crawlers never see invisible content.
- Every animation has a `@media (prefers-reduced-motion: reduce)` override.
- Use the standalone `translate` / `scale` properties rather than `transform`,
  so they can't clash with Tailwind transform utilities on the same element.
- Third-party CSS that should be overridable by utilities is imported into a
  layer: `@import "vendor/style.css" layer(components);`.
