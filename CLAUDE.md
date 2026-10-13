# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

Location Explain is a XenForo add-on (`Hampel/LocationExplain`, requires XF 2.2.8+) that adds
admin-configured explanatory text beneath the Location field on the registration form and the
account details page. The repository root is the add-on directory,
`src/addons/Hampel/LocationExplain/` inside a XenForo install. There are no Composer dependencies
and no tests, and the only PHP is `Setup.php` and a `Listener.php` that declares it has no secrets:
**the add-on is one option and two template modifications in `_output/`.**

## The explanation is an `explain` attribute added to core's own textbox

The option is `hampelLocationExplain`, a plain string in its own `hampelLocationExplain` option
group. Empty is the default, and XF renders no explain line for an empty attribute, so an
unconfigured install looks unchanged.

| modification | template | what it changes |
|---|---|---|
| `hampelLocationExplainRegisterMacros` | `register_macros` | the location `<xf:textboxrow>` inside core's `registrationSetup.requireLocation` test |
| `hampelLocationExplainAccountDetails` | `account_details` | the location `<xf:textboxrow>` |

Both are `str_replace` modifications that append
`explain="{{ $xf.options.hampelLocationExplain }}"` to the row. **The registration find includes
the surrounding `<xf:if>`**, so the explanation appears at registration only when the forum
requires a location — which is also the only case in which core shows the field there.

### A modification that stops matching fails silently

**Both finds are exact core markup, tabs included**, and XF logs a non-matching modification as
`ok` with an apply count of 0 — the explanation just disappears and nothing errors. 1.0.1 existed
because XF 2.2.8 changed `account_details` under the find, so treat an XF upgrade as a reason to
recheck. The apply count is the check: *Appearance > Template modifications* in the admin control
panel, or the `xf_template_modification_log` table. Each should apply exactly once.
`TESTING.md` has that query and a script that counts each `find` against a XenForo release zip
before it is installed; there is no PHPUnit suite, by decision.

**Prefer editing a modification in the admin control panel and exporting** over hand-editing its
JSON: the `find` and `replace` values carry escaped tabs and newlines, and the hashes in
`_metadata.json` must agree with the files.

## `Setup.php` guards a 2.3-only call

`install()`, `upgrade()` and `uninstall()` do nothing; options, phrases and modifications come from
`_output/`. `postUpgrade()` calls `enqueuePostUpgradeCleanUp()` only when
`\XF::$versionId >= 2030000`, because that method does not exist on XF 2.2 and the add-on still
installs there.

## `Listener.php` declares that the option is not a secret

**It answers the `hampel_admin_api_redaction` code event with an empty entry for this add-on's id.**
An add-on that reports a forum's configuration with the secrets removed fires that event, and shows
an add-on's text settings only once the add-on has said which of them are secrets. The option here
is not one — its text is printed on public pages — and the add-on reads no `config.php` keys.

**Revisit the declaration when an option is added or renamed.** There is no suite to catch it going
stale. It is not a dependency: on a forum where nothing defines the event the listener never fires,
and nothing goes in `addon.json`'s `require`. The method takes `array &$declarations` and declares
no return type, because `void` needs PHP 7.1 and XenForo 2.2 still allows 7.0.

## Commands

XF commands run through `cmd.php` at the root of the XenForo install, four levels up from this
directory:

```bash
php ../../../../cmd.php xf-dev:import --addon=Hampel/LocationExplain    # after hand-editing _output/
php ../../../../cmd.php xf-addon:export Hampel/LocationExplain          # DB -> _output/, this add-on only
php ../../../../cmd.php xf:addon-upgrade Hampel/LocationExplain         # after a version bump
php ../../../../cmd.php xf-addon:build-release Hampel/LocationExplain   # release zip into _releases/
```

**Never run `xf-dev:export`** — its `--addon` defaults to every add-on on the install, and it
overwrites `_output/` from the database. `xf-addon:export` above is the scoped equivalent, and
`build-release` runs it itself.

The install's `AGENTS.md` covers the `_output/` workflow, version bumps and releases; the
`xenforo-addon-release` and `xenforo-addon-audit` skills, where available, carry the release order
and the full checklist.

## Packaging

**`build.json` moves every root `*.md` to the zip root**, where it is the first thing a downloader
sees. A dev-only root file — `TESTING.md`, this file, `CLAUDE.local.md` — stays out of the release
only if an `rm -fv` in `build.json` deletes it *before* that `mv`; after it, the `rm` matches
nothing and the file ships. `git archive` (GitHub's "Download ZIP") is a separate surface, handled
by `export-ignore` in `.gitattributes`, so a new dev-only file needs adding in both places.

Machine-specific notes go in `CLAUDE.local.md`, which is gitignored; this file carries nothing that
would not hold for any clone.
