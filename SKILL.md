---
name: statamic-site
description: House pattern for building Statamic 6 sites (Laravel 13, Tailwind 4, Vite, Antlers, Alpine, Vue islands). Use when setting up a new Statamic site, adding a content block / fieldset / blueprint, adding a template component or JS behavior, or touching the Tailwind setup in one of these projects.
---

# Statamic site

The house pattern for Statamic sites, distilled from meinewunschbank.ch (the
reference), kunsthaus.ch and valentinafrutig.ch. It is a guide, not a starter:
content model, fieldsets and blocks differ per project; the structure, tooling
and naming do not.

Where an existing project disagrees with this guide, the project wins for that
project — don't retrofit unless asked. New projects follow the guide.

## Stack

Statamic 6 on Laravel 13, PHP 8.3+, flat-file content (sqlite only for the
queue). Tailwind 4 configured in CSS only, Vite, Alpine for small UI state, a
Vue 3 island only where a page needs a real app (a form, a generator). Antlers
for every site template; Blade only for mails and the sitemap. Local dev on
Herd (`https://<domain>.test`).

## Workflows

**New site** — follow `references/setup.md` top to bottom, then
`references/css.md` for the Tailwind setup and `references/templates.md` for
the layout skeleton. Finish by writing the project's own `CLAUDE.md` (see the
end of `setup.md`).

**Add a content block** — `references/blocks.md`. Four edits, always: fieldset,
blueprint set, partial, branch in `blocks.antlers.html`.

**Add a component / partial** — `references/templates.md`.

**Add front-end behavior** — `references/javascript.md`.

**Styling questions** (spacing, font sizes, breakpoints, colours, images) —
`references/css.md`.

## Rules that apply everywhere

- **Tabs** for indentation in Antlers, CSS, JS, Vue and PHP. YAML and JSON stay
  on spaces. `.editorconfig` and `.php-cs-fixer.php` carry this.
- **German** for everything an editor or visitor sees: CP labels, instructions,
  set names, UI copy, `aria-label`s.
- **English** for code comments, and brief: say why, never what. No header
  comment on components; only comment the line that would otherwise look like
  a mistake.
- **Commits**: English, imperative, sentence case, no prefix, no trailing
  period, the why when it isn't obvious — *"Drop the menu's blur so it doesn't
  stack with the header"*.
- **Spacing is 1:1 with pixels** (`p-16` = 16px) via a single `--spacing`
  token — never a generated `spacing.css` list.
- **Font sizes come from the token scale** in `partials/font-sizes.css`, not
  arbitrary values.
- **Breakpoints are `md:` and `lg:` only**, plus `max-md:` for mobile-only
  overrides. Mobile first.
- **Set handle = fieldset filename = partial name** (partial in kebab-case).
- Project-specific features (custom generators, Runway resources, moderation
  flows) are not part of the pattern — build them per project.
