# Pico Content Editor

A WYSIWYG content editor for [Pico CMS](http://picocms.org).

- live editing with [ContentTools]
- save edited content
- supports authentification with [PicoUsers]

## Installation

Copy the `PicoContentEditor` directory to the `plugins/` directory of your Pico Project.

## Settings

The settings are stored in Pico config file.

```php
$config['PicoContentEditor.debug'] = false; // if true, outputs the requests to the console
```

## Usage

Include the editor files in your pages by adding the following Twig tag at the end of your theme, before the closing `</body>`.

```twig
{{ content_editor }}
```

On your pages content, add editable blocks with the attributes `data-editable`, `data-name` and `end-editable`.

```html
---
Title: A page with editable content
---

The following content is editable :

<div data-editable data-name="pages-first-content">
    <p>Edit me!</p>
</div end-editable>

This one too : 

<div data-editable data-name="pages-secondary-content">
    <ul>
        <li>One</li>
        <li>Two</li>
        <li>Three</li>
    </ul>
</div end-editable>
```

Every content inside those tags will be editable by visiting the page.

The blocks `name` should be unique accross a single page.

## Authentification

To restrict edition to authentified users, install the [PicoUsers] plugin.

If PicoUsers is detected, saving pages will be restricted to the user or group with the `PicoContentEditor/save` right. For example :

```php
$config['users'] = array(
    'admin' => '$2y$10$Ym/XYzM9GsCzv3xFTiCea..8.F3xY/BpQISqW6/q3H41SmIK1reZe'
);
$config['rights'] = array(
    'PicoContentEditor/save' => 'admin',
);
```

And you may want to include the editor in your theme only for users with enough rights :

```twig
{% if user_has_right('PicoContentEditor/save') %}{{ content_editor }}{% endif %}
```

[ContentTools]: http://getcontenttools.com
[PicoUsers]: https://github.com/nliautaud/pico-users