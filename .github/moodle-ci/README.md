# Plugin-specific Configuration

if the plugin requires specific $CFG array settings, you can add them to 
```extra-config.php``` to pass moodle-plugin-ci test/installation.

# Plugin-specific PHPCS exclusions

`phpcs-exclusions.xml` in this folder is where developers can add
plugin-specific PHPCS exceptions (disable a sniff, restrict it to certain
paths, or adjust its settings) without touching the shared Moodle ruleset.

## Why this file exists

`moodle-plugin-ci phpcs` calls PHP_CodeSniffer with `--standard=moodle`
hard-coded on the command line. Because the standard is set explicitly,
PHPCS will **not** auto-discover a plain `phpcs.xml` dropped in the plugin
root. This file must be passed in explicitly instead.

## One-time setup

In your workflow file (typically `.github/workflows/moodle-ci.yml`), find
the PHPCS step and point `--standard` at this file:

```yaml
- name: Moodle Code Checker
  if: ${{ !cancelled() }}
  run: >
    moodle-plugin-ci phpcs
    --standard=$(pwd)/.github/moodle-ci/phpcs-exclusions.xml
    --max-warnings 0
```

That's it — from then on, everyone can edit `phpcs-exclusions.xml` without
touching the workflow again.

## Adding an exception

Open `phpcs-exclusions.xml` and add a `<rule>` entry under the marker
comment at the bottom. The file has commented-out examples for the most
common cases:

- Disable a sniff entirely (project-wide)
- Disable a sniff only for specific files/paths (`exclude-pattern`)
- Adjust a sniff's settings instead of turning it off (e.g. allow a longer
  line length)
- Exclude whole directories from all checks (e.g. bundled third-party code)

Find the exact sniff code to use from your failing CI run's output — it's
shown in parentheses at the end of each reported line, e.g.
`(Generic.Files.LineLength)` or `(Squiz.Arrays.ArrayBracketSpacing)`.

**Please add a short comment above each exception explaining why it's
needed** — it saves the next person (often future-you) from having to
reverse-engineer the reason later.

## One-off exceptions

For a single line or block rather than a project-wide rule, prefer inline
suppression directly in the code instead of editing this file:

```php
$x = 'a deliberately long line that should not be wrapped'; // phpcs:ignore Generic.Files.LineLength
```

```php
// phpcs:disable Squiz.Arrays.ArrayBracketSpacing
$x = array (1, 2, 3);
// phpcs:enable Squiz.Arrays.ArrayBracketSpacing
```
