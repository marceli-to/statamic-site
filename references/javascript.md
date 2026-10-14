# JavaScript

Alpine for small UI state, plain modules for behaviors, a Vue island only where
a page needs a real app.

## File layout

```
resources/js/
├── app.js                   entry: Alpine, behaviors, Vue islands
├── cp.js                    Control Panel: custom fieldtype registration
├── behaviors/<name>.js      one DOM behavior per file (slider, reveal, shy…)
├── components/<Name>.vue    Vue island roots and their children
├── components/form/Form*.vue
├── components/icons/Icon*.vue
└── composables/use*.js      only when an island needs shared logic
```

Imports use the `@/` alias (`resources/js`), set in `vite.config.js` and
`jsconfig.json`.

## app.js

```js
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import { createApp } from 'vue';
import initSliders from '@/behaviors/slider';
import initReveals from '@/behaviors/reveal';
import ContactForm from '@/components/ContactForm.vue';

window.Alpine = Alpine;
Alpine.plugin(collapse);
Alpine.start();

initSliders();
initReveals();

const el = document.getElementById('contact-form');

if (el) {
	createApp(ContactForm, {
		title: el.dataset.title ?? '',
	}).mount(el);
}
```

## Behaviors

One file per behavior in `behaviors/`, named after what it does (`slider.js`,
`reveal.js`, `shy.js`). Each **default-exports one `init…()` function** that:

- finds its targets by a class or `data-` attribute the markup opts into
  (`.reveal`, `[data-swiper-scope]`, `[data-shy]`),
- returns early when there are none,
- wires everything up — no work at import time.

```js
export default function initReveals() {
	const targets = document.querySelectorAll('.reveal, .reveal-group > *');

	if (!targets.length) {
		return;
	}

	// …
}
```

- Constants at the top in `UPPER_CASE` (`const SPEED = 900;`).
- Respect `prefers-reduced-motion` in anything that animates.
- JS only flips state (a class like `is-revealed`, a data attribute, a CSS
  custom property); the look lives in CSS — Tailwind `data-*` variants in the
  markup or a feature partial in `resources/css/partials/`.
- Several instances per page are scoped by a wrapper attribute
  (`data-swiper-scope="testimonials"`), whose value can pick a preset.
- Third-party CSS a behavior needs is imported in the behavior
  (`import 'swiper/css';`).

## Alpine

For state that lives in the markup: menu open/closed, collapses
(`@alpinejs/collapse`), toggles. The menu flag sits on `<body>`
(`x-data="{ menu: false }"`). Use `x-cloak` on anything hidden initially.
If Alpine logic grows beyond a few expressions, it's a behavior or an island.

## Vue islands

- `<script setup>`, Composition API.
- The Antlers block renders a mount point with an id and passes data through
  `data-*` attributes; `app.js` mounts only if the element exists.
- Form primitives under `components/form/` (`FormInput`, `FormLabel`,
  `FormCheckbox`, `FormTextarea`…), styled with the same tokens as the site.
- The island posts to an API route (`routes/api.php`), validated by a Form
  Request with German messages.

## Control Panel

`cp.js` registers custom fieldtypes; otherwise it stays a stub:

```js
// Custom Control Panel JavaScript — register custom fieldtypes / components here.
//
// Statamic.booting(() => {
// 	Statamic.$components.register('example-fieldtype', Example);
// });
```

Built separately with `npm run cp:build` / `cp:dev` (see `setup.md`).
