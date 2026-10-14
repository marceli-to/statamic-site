# Content blocks, fieldsets and blueprints

A page's `blocks` replicator drives everything. Fieldsets differ per project —
what stays the same is how they are named, split and wired.

## Adding a block — three edits

1. **Fieldset** `resources/fieldsets/<handle>.yaml` holding the block's fields.
   Always a real fieldset, even for one field and even if only one block uses
   it — other collections may want the same block later.
2. **Set** under `blocks` in `resources/blueprints/collections/pages/page.yaml`
   that does nothing but import it:

   ```yaml
   <handle>:
     display: 'Seiten-Teaser'
     instructions: 'Ein Abschnitt mit manuell gewählten Seiten-Teasern'
     icon: layout-carousel-horizontal
     fields:
       - import: <handle>
   ```

3. **Partial** `resources/views/components/blocks/<handle-in-kebab-case>.antlers.html`
   (`page_teasers` → `page-teasers.antlers.html`). It opens with
   `layout/container` or `layout/section`, which owns the vertical spacing.

That's it — `components/blocks.antlers.html` resolves the partial from the set
handle, so there is no dispatch list to update:

```antlers
{{ partial :src="'components/blocks/' + (type | replace('_', '-'))" }}
```

**Set handle = fieldset filename = partial name** (partial in kebab-case).
A mismatch throws a missing-view error on the page, and
`tests/Feature/BlocksTest.php` catches it first: for every set in the pages
blueprints it asserts the import, the fieldset and the partial. Run
`php artisan test` after adding a block.

Older projects have an `{{ if type == '…' }}` branch per block in
`blocks.antlers.html`; there it's still a fourth edit.

`default.antlers.html` is just:

```antlers
{{ blocks }}
	{{ partial:components/blocks }}
{{ /blocks }}
```

## Naming

- Handles are `snake_case`, named after what the block **is** for the editor
  (`intro`, `call_to_action`, `testimonials`, `milestones`), not after layout
  (`two_columns`, `grid_left`).
- Blocks that belong to one feature share a prefix: `wish_intro`,
  `wish_steps`, `wish_form`, `wish_grid`.
- Plural for blocks that hold a list (`page_teasers`, `testimonials`), singular
  otherwise.
- Field handles inside a fieldset are plain (`title`, `lead`, `media`, `pages`).
  Fieldsets that get imported next to other fields **prefix their handles** to
  avoid collisions: `button` → `button_label`, `button_link`; `teaser` →
  `teaser_image`, `teaser_text`.
- Fieldset `title:` is the German display name (`title: Seiten-Teaser`).

## Editor-facing text is German

`display`, `instructions`, set names, tab and section names, placeholders.
Instructions tell the editor what happens to their input:
`'Zeilenumbrüche werden übernommen.'`, `'Fett gesetzt, in der linken Spalte.'`,
`'Ohne Text und Ziel wird kein Button ausgegeben.'`.

## Shared fieldsets

Present in every project:

**`editor`** — the one Bard field, imported wherever body text is needed:

```yaml
title: Editor
fields:
  -
    handle: editor
    field:
      type: bard
      display: Text
      buttons:
        - h2
        - h3
        - bold
        - italic
        - unorderedlist
        - removeformat
        - anchor
      toolbar_mode: floating
      link_collections:
        - pages
      container: assets
      remove_empty_nodes: true
      collapse: true
```

Rendered inside `<article>` so the `article …` rules in `app.css` apply.

**`seo`** — `meta_description` (textarea, 'Meta Description') and `noindex`
(toggle, 'Diese Seite von Suchmaschinen ausschliessen'), both
`localizable: false`. Read by `layout/head` and the sitemap.

Common reusable ones, added when a project needs them: `button`
(`button_label`, `button_link` → link to `pages`), `teaser` (`teaser_image`,
`teaser_text` — what a page shows when another page teases it).

Fieldsets import each other: `intro` ends with `- import: editor`.

## Field defaults

- Assets: `container: assets`, `mode: list`, `max_files: 1` for single images.
- Entries: restrict `collections`, `create: false` when picking existing pages.
- Links: restrict `collections` to `pages`.
- Multi-line headings: `textarea` + `| trim | nl2br` in the template, with the
  instruction `'Zeilenumbrüche werden übernommen.'`.
- Replicators and Bard: `collapse: true`.

## Page blueprint

```yaml
title: Seite
tabs:
  main:
    display: Inhalt
    sections:
      -
        fields:
          -
            handle: title
            field:
              type: text
              required: true
              display: Titel
              validate:
                - required
          -
            handle: blocks
            field:
              type: replicator
              display: 'Content Blocks'
              collapse: true
              sets:
                main:
                  display: Main
                  sets:
                    # one set per block, each only importing its fieldset
                <feature>:
                  display: <Feature name>
                  sets:
                    # the feature's prefixed blocks
  settings:
    display: Einstellungen
    sections:
      -
        display: Teaser
        instructions: 'Angaben für den Teaser dieser Seite.'
        fields:
          - import: teaser
      -
        display: SEO
        fields:
          - import: seo
  sidebar:
    display: Sidebar
    sections:
      -
        fields:
          -
            handle: slug
            field:
              type: slug
              localizable: true
              validate: 'max:200'
              show_regenerate: true
      -
        display: Template
        fields:
          -
            handle: template
            field:
              type: template
              display: Template
```

The blueprint stays thin — fields live in fieldsets. Set groups (`main`, plus
one per feature) keep the block picker tidy. Every set has a Statamic `icon`.

Non-page collections (submissions, art works, blog posts) get their own
blueprint under `resources/blueprints/collections/<collection>/`, reusing
`editor`, `seo` and `teaser` where they apply.
