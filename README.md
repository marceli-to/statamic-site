# statamic-site

A Claude Code skill: the house pattern for Statamic 6 sites (Laravel 13,
Tailwind 4, Vite, Antlers, Alpine, Vue islands).

## Install

```bash
git clone https://github.com/marceli-to/statamic-site.git ~/.claude/skills/statamic-site
```

## Contents

- `SKILL.md` — when to use it, workflows, rules that apply everywhere
- `scaffold/` — the files every site starts with, laid over a fresh install
- `scripts/scaffold.sh` — copies the scaffold, fills placeholders, installs and builds
- `scripts/patch.php` — edits `.env`, Glide presets, static caching, composer/npm scripts, `.gitignore`
- `references/setup.md` — new project with Herd, then the project-specific work
- `references/css.md` — Tailwind 4 setup, spacing, type scale, breakpoints, images
- `references/templates.md` — layout skeleton and component conventions
- `references/blocks.md` — content blocks, fieldsets, blueprints
- `references/javascript.md` — behaviors, Alpine, Vue islands, CP
- `references/go-live.md` — launch checklist, production `.env`, static caching
