# HBP Settings UI

The admin field and settings-page layer for
[`hbp/settings`](https://github.com/hybopress/settings).

`hbp/settings` resolves a value. This package renders the control that edits
it, and drives the WordPress Settings API around it.

Requires PHP 8.2+, `hbp/settings`, `themehybrid/hybrid-core` ^7.0.

## The consumer loads the config

**This package reads config. It never loads it.** It ships no `config/`
directory: the namespace it reads is the consuming theme's or plugin's. Load
it once from your bootstrap:

```php
use Hybrid\Tools\Config\Loader;

Loader::load( THEME_DIR . '/config', 'child' );
```

Without that, `{namespace}.controls` is empty and the panel renders nothing,
silently.

## Declaring controls

Declarations live at `{namespace}.controls`, keyed by setting key. They are
kept apart from the value namespace so a setting named `controls.x` and a
declaration cannot be confused for each other.

```php
// config/controls.php  →  child.controls.*
return [
    'error_page' => [
        'type'        => 'select',
        'label'       => static fn() => esc_html__( '404 Page', 'child' ),
        'description' => static fn() => esc_html__( 'Shown when nothing matches.', 'child' ),
        'tab'         => 'general',
        'section'     => 'reading',
        'priority'    => 10,
        'feature'     => 'pages.error',
        'choices'     => static fn() => [ 0 => '—' ] + wp_list_pluck( get_pages(), 'post_title', 'ID' ),
    ],
];
```

Every key may be a closure, resolved on read, so a declaration can call
`esc_html__()` or build choices from data that does not exist at config-load
time.

Closures resolve at the **leaf** only. The declaration array itself must be an
array — a closure returning one is skipped, silently.

| Key | Default | Meaning |
|---|---|---|
| `type` | *required* | Control type; see below. Missing throws. |
| `label` | the key | Field label. |
| `description` | `''` | Text under the control. |
| `tab` | `general` | Which tab it appears on. |
| `section` | `default` | Which section within the tab. |
| `priority` | `10` | Sort order. A section sorts by its earliest control. |
| `feature` | `''` | Capability gating visibility. Declared, never inferred. |
| `choices` | `[]` | For select/radio/multicheckbox/radio_image/sortable. |

Common attribute keys pass through to the rendered input: `class`,
`placeholder`, `required`, `disabled`, `readonly`, plus per-type extras
(`min`/`max`/`step` on number, `rows`/`cols`/`maxlength` on textarea,
`maxlength`/`minlength`/`pattern`/`size` on text).

### Control types

`text`, `url`, `email`, `textarea`, `number`, `checkbox`, `toggle`, `select`,
`radio`, `multicheckbox`, `radio_image`, `media`, `sortable`.

`media` stores an attachment ID, not a URL, so the value survives a domain
change. `sortable` stores an ordered array of the enabled choices, so which
items are on and what order they appear in are one value. Both need their tab
to enqueue: `FieldsView::needs( 'media' )` / `needs( 'sortable' )` report it.

Register your own with `Fields::register( $type, $field )` against the
`Contracts\Field` interface.

### Labels

Tab and section headings come from `{namespace}.tabs.{slug}` and
`{namespace}.sections.{slug}`. Either may be a closure. An undeclared label
falls back to the slug, title-cased.

## Visibility

A control is hidden for two independent reasons:

- its `feature` is disabled for this build — otherwise a preset that disables a
  capability still renders settings that cannot affect anything;
- the active preset lists its key under `{namespace}.presets.{active}.hidden`.

Hidden controls are dropped before render, so a section or tab whose every
control is hidden disappears with them rather than printing an empty heading.
The control stays declared under its proper tab and section either way, which
is what lets one preset hide a control another preset shows.

## Rendering

`Panel` drives the Settings API for one namespace:

```php
use function HBP\Settings\Ui\panel;

$panel = panel( 'child' );          // option defaults to "child_settings"

add_action( 'admin_init', [ $panel, 'registerSetting' ] );
add_action( 'admin_init', static fn() => $panel->registerTab( 'general', 'child-settings' ) );
```

The form posts to `options.php`, so core owns the nonce, the capability check,
the redirect and the notice. This package supplies the sections, the fields
and the sanitize callback.

`registerSetting()` is called **once for the whole option**, not once per tab.
`register_setting()` adds a `sanitize_option_{$option}` filter per call and
those filters chain: with one registration per tab, only the first callback
would see the real submission and every later one would be handed the previous
callback's merged array and treat it as posted input.

### Saving

Only declared keys are considered, so a crafted request cannot reach anything
that is not a control. A key absent from the submission is left exactly as
stored — which is what makes one option safe to back many tabs, since the
submission itself says which tab it came from by which keys are present.

A control returning `null` from `sanitize()` has **rejected** the value; the
stored one is left untouched. Out-of-range and unrecognised values are
rejected rather than clamped: a control posting something its declaration
disallows is a bug, and quietly storing a nearby value hides it.

Controls that post nothing when empty — unchecked checkboxes, empty
multi-selects — get a companion hidden input carrying `emptyValue()`, so
"cleared" stays distinguishable from "absent from this form".

## The settings screen (optional)

`Admin\Page` is a tabbed screen over a `Collection` of `Admin\View`. A
consumer with its own options page can ignore it entirely and drive `Panel`
directly; nothing else in the package depends on it.

```php
use HBP\Settings\Ui\Admin\FieldsView;
use HBP\Settings\Ui\Admin\Page;
use Hybrid\Tools\Collection;

$views = new Collection;

foreach ( panel( 'child' )->definitions()->tabs() as $slug ) {
    $views->put( $slug, new FieldsView( panel( 'child' ), $slug, 'child-settings' ) );
}

( new Page( 'child-settings', $views, __( 'Theme Settings', 'child' ) ) )->boot();
```

Only the view being viewed is booted, so a tab's assets never load on another
tab. Not every tab has to be a settings form: an import/export manager or a
theme browser extends `View` too and never touches a `Panel`, which is why the
host knows nothing about fields.

## Helpers

Namespaced; this package exports no global functions. See the `hbp/settings`
README for why, and for how to declare your own short alias.
