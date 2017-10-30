# Pico Content Editor

A WYSIWYG content editor for [Pico CMS](http://picocms.org).

- live editing with [ContentTools]
- save to pages or themes files
- pages metadata editor
- images upload
- authentification with [PicoUsers]

## Installation

Copy the `PicoContentEditor` directory to the `plugins/` directory of your Pico Project.

## Settings

The settings are stored in Pico config file.

```php
$config['PicoContentEditor.debug'] = false; // if true, outputs the requests to the console
$config['PicoContentEditor.lang'] = 'fr'; // language code of a supported language
```

The languages supported are listed in the *[translations/](https://github.com/nliautaud/pico-content-editor/tree/master/PicoContentEditor/assets/ContentTools/translations)* directory.

## Usage

Include the editor files by adding the following tag at the end of your theme, before the closing `</body>`.

```twig
{{ content_editor }}
```

Define editable regions in your pages by using HTML blocks with the attributes `data-editable`, `data-name` and a closing comment `end-editable`. `data-name` should be unique accross a single output.

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

## Metadata editor

To add an editor for the pages metadata, use the following tag after the opening of `<body>` :

```twig
{{ content_editor_meta }}
```

An editable text area will contain the page frontmatter.

## Editable regions in themes and templates

You can create editable blocks in themes, just point to the source file with the attribute `data-src`.

For exemple, the following code could be the content of a `footer.twig` file in your theme.

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

## Fixed editable elements

To make fixed elements with an editable inner content, use `data-fixture` instead of `data-editable` :

```html
<h1 data-fixture data-name="editable-header" data-src="themes/mytheme/header.twig">
Edit me !
</h1><!--end editable-->
```

Only inline tools will be allowed in this context : **Bold**, *Italic*, ...

Unrecognized tags can be defined with `data-ce-tag`, for example for a fixed editable link and a fixed editable image :

```html
<a data-fixture data-name="my-editable-link" data-ce-tag="p" href="/test">
Editable link
</a><!--end editable-->

<div data-fixture data-name="my-hero-image" data-ce-tag="img-fixture"
     style="background-image: url('image.png');">
    <img src="image.png" alt="Some image">
</div><!--end editable-->
```

## Files upload

Files are uploaded to `images/` by default.

You can define a custom location in the Pico config file with :

```php
$config['PicoContentEditor.uploadpath'] = 'assets';
```

## Authentification

If the [PicoUsers] plugin is installed and detected, actions are automatically restricted to authorized users.

|Right|Desc|
|:-|:-|
|`PicoContentEditor`| All rights below.
|`PicoContentEditor/save`| Editing regions in pages and themes source files.
|`PicoContentEditor/upload`| Uploading files on the server.

Configuration example of [PicoUsers] :

```php
$config['users'] = array(
    'admin' => '$2y$10$Ym/XYzM9GsCzv3xFTiCea..8.F3xY/BpQISqW6/q3H41SmIK1reZe',
    'editors' => array(
        'bill' => '$2y$10$INwdOkshW6dhyVJbZYVm1..PxKc1CQTRG5jF.UaynOPDC6aukfkaa'
    )
);
$config['rights'] = array(
    'PicoContentEditor' => 'admin',
    'PicoContentEditor/save' => 'editors',
);
```

[ContentTools]: http://getcontenttools.com
[PicoUsers]: https://github.com/nliautaud/pico-users