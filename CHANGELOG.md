# Change Log

## [1.0.0-alpha.1] - 2026-10-22

First release.

### Added

- `Panel`: drives the WordPress Settings API for one namespace. One option
  backs every tab, only declared controls are saved, and a control locked
  by the active preset renders disabled and cannot be written.
- `Definitions` and `Definition`: controls declared in config under
  `{namespace}.controls`, grouped by tab and section and ordered by
  priority. A declaration may be a closure, or a map of further
  declarations.
- Fifteen control types: text, url, email, textarea, number, checkbox,
  toggle, select, radio, multicheckbox, radio_image, media, sortable,
  multiselect and html. `Fields::register()` adds more.
- Controls hidden by a disabled feature or by the active preset's
  `hidden` list.
- Client-side show and hide rules emitted as data attributes.
- `Admin\Page`, `Admin\View` and `Admin\FieldsView`: an optional tabbed
  settings screen.
- `registerSetting()` takes the capability the form saves under.
- A Pest test suite in `qa/`, a separate Composer project because Pest 5
  needs PHP 8.4. Run with `composer test`.

### Fixed

- A write from code to a registered option, such as `Settings::set()` in
  an admin request, was replaced by the stored value. So was the first
  save of the form, which WordPress sanitizes twice.
- `radio_image` images used the form input name as alt text.
- An array posted to a select, radio or radio_image control raised an
  "Array to string conversion" warning.
