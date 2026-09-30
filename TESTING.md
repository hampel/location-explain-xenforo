# Testing Location Explain

What this add-on touches, what breaks quietly, and how to check it. There is no PHPUnit suite:
the add-on is one option and two template modifications, and the checks under *Automated* cover
the one way it breaks.

## Surfaces

| surface | what it is for |
|---|---|
| option `hampelLocationExplain` | the explanation text, in its own option group; plain text, empty by default |
| modification `hampelLocationExplainRegisterMacros` | appends `explain=` to the location row in `register_macros` |
| modification `hampelLocationExplainAccountDetails` | appends `explain=` to the location row in `account_details` |
| `Setup.php` | queues XF 2.3's post-upgrade file clean-up; nothing else |

No class extensions, code event listeners, templates or phrases outside the option's own.

## Fragile points

- **Both modifications are exact `str_replace` finds against core markup, tabs included.** A core
  template change under a find makes the modification apply 0 times, which XenForo logs as `ok` —
  the explanation disappears and nothing errors. 1.0.1 and 1.0.2 were both this.
- **The registration find includes core's `registrationSetup.requireLocation` condition**, so the
  explanation appears at registration only when the forum requires a location. That is also the
  only case in which core shows the field there, so it is correct, but it means testing the
  registration form needs that option on.
- **The option is output escaped.** HTML typed into it renders as literal text, not markup.
- **An empty option renders no explain line at all**, because XenForo drops an empty `explain`
  attribute. So an unconfigured install looks unchanged, and a blank field on a configured one
  looks the same as a modification that stopped applying.

## Automated

**Each modification applies exactly once on the running forum.** Run against the forum's
database:

```sql
SELECT m.modification_key, l.status, l.apply_count
FROM xf_template_modification m
JOIN xf_template_modification_log l USING (modification_id)
WHERE m.addon_id = 'Hampel/LocationExplain';
```

Both rows want `ok` and `1`. An apply count of `0` with status `ok` is the silent failure above.

**Each find matches a XenForo release before it is installed.** A full release zip carries the
master templates at `upload/src/addons/XF/_data/templates.xml`. From the add-on root, with the
zip paths as arguments:

```bash
python3 - path/to/xenforo_*_full.zip <<'EOF'
import sys, glob, json, os, zipfile, xml.etree.ElementTree as ET
mods = {os.path.basename(f)[:-5]: json.load(open(f))
        for f in glob.glob('_output/template_modifications/public/*.json')}
for z in sys.argv[1:]:
    zf = zipfile.ZipFile(z)
    name = [n for n in zf.namelist() if n.endswith('addons/XF/_data/templates.xml')][0]
    tpl = {t.get('title'): t.text or '' for t in ET.fromstring(zf.read(name)).iter('template')
           if t.get('type') == 'public'}
    print(os.path.basename(z), {k: tpl.get(m['template'], '').count(m['find'])
                                for k, m in mods.items()})
EOF
```

Every count wants `1`. As at 1.0.4 both match once on XenForo 2.1.15, 2.2.19, 2.3.0, 2.3.12 and
2.3.13. To prove the check can fail, run it with a find from an older tag
(`git show 1.0.0:_output/template_modifications/public/hampelLocationExplainAccountDetails.json`):
1.0.0's `account_details` find matches 2.1.15 and nothing from 2.2.19 on.

**The `_output/` round trip is clean.** From the XenForo root:

```bash
php cmd.php xf-dev:import --addon=Hampel/LocationExplain
php cmd.php xf-dev:unused-phrase-finder --addon Hampel/LocationExplain --show-unknown
```

The first should leave the add-on's working tree unchanged; the second wants 0 and 0.

## Needs a human

- **See the explanation on both forms.** With the option set, check `account/account-details`
  while logged in, and `register` while logged out with *Require location* on in the
  registration options. The text sits below the Location field, styled as core's other explain
  lines. With *Require location* off, registration has no Location field and no explanation.
- **The option page reads sensibly** in the admin control panel, under its own option group.
- **The upgrade from the published version on a real forum**, followed by the job queue: the
  upgrade queues XenForo's file clean-up on 2.3, which runs after the upgrade reports success.
