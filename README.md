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

> To restrict edition to authentified users, use the [PicoUsers] plugin. In that case you don't need to include the editor file for non-logged users and may use :
> ```twig
> {% if user %}{{ content_editor }}{% endif %}
> ```

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

[ContentTools]: http://getcontenttools.com
[PicoUsers]: https://github.com/nliautaud/pico-users