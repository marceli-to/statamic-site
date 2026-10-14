---
id: home
blueprint: page
title: Startseite
blocks:
  -
    id: home-editor
    type: editor
    enabled: true
    editor:
      -
        type: heading
        attrs:
          level: 2
        content:
          -
            type: text
            text: __SITE_NAME__
      -
        type: paragraph
        content:
          -
            type: text
            text: 'Diese Seite wurde mit dem statamic-site Scaffold aufgesetzt.'
---
