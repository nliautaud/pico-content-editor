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

Include the editor files by adding the following tag at the end of your theme, before the closing `</body>`.

```twig
{{ content_editor }}
```

Define editable regions in your pages by using HTML blocks with the attributes `data-editable`, `data-name` and a closing comment `end-editable`.

```html
---
Title: A page with editable content
---

The following content is editable :

<div data-editable data-name="pages-first-content">
    <p>Edit me!</p>
</div><!--end editable-->

This one too, and will be converted back to markdown on saving :

<div data-editable data-name="pages-secondary-content" markdown=1>
    - One
    - Two
    - Three

    This content will be saved in *markdown*.
</div><!--end editable-->
```

Every content inside those tags will be editable by visiting the page.

> `data-name` should be unique accross a single output.

You can create editable blocks in themes, you just have to specify the source file path with `data-src`.

```html
<footer id="footer">
    <div class="inner">
        <div class="social">
            {% for social in meta.social %}
                <a href="{{ social.url }}" title="{{ social.title }}"><span class="icon-{{ social.icon }}"></span></a>
            {% endfor %}
        </div>
        <div data-editable data-name="footer" data-src="themes/mytheme/footer.twig">
            <p>Edit me !</p>
        </div><!--end editable-->
    </div>
</footer>
```

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