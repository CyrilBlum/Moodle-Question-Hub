# AGENTS.md – qbank_questionhub (Moodle plugin)

This repo is the Moodle plugin of the Moodle Question Hub project. The full project knowledge (architecture, server layout,
deploy and test procedures, pitfalls) lives in the marketplace repo:
`CyrilBlum/Moodle-Question-Hub-Marketplace` -> `AGENTS.md` (also copied to `Moodle Question Hub/AGENTS.md` locally).

Plugin quick facts:
- Component `qbank_questionhub`, installed at `question/bank/questionhub` on Moodle 5.0 and 4.5.
- Entry points: `view.php` (Hub browser, import, my packages, edit/withdraw), `publish.php` (export + publish),
  `oauth_callback.php`, `disconnect.php`; logic in `classes/` (`api_client`, `package_exporter`, `package_importer`,
  `preflight_validator`, `auth_manager`).
- Hub API response lists are under `data` (not `packages`). Do not use a global `$` in templates; open modals with
  `data-toggle` + `data-bs-toggle`. Escape once only (Mustache escapes).
- No PHP locally: lint on the server with `php8.2 -l` / `php8.1 -l`; after deploying run `admin/cli/purge_caches.php`.
- Language strings in `lang/en` and `lang/de` (Swiss Standard German: «ss», formal «Sie»).
- Moodle `qformat_default::try_importing_using_qtypes()` derives the question-type XML handler name from `get_class($this)`. A subclass of `qformat_xml` must keep dispatching to `import_from_xml`, or CodeRunner and gapselect imports report unsupported types.
- Git: work directly on `main` (no feature branches, for simplicity); small commits, push.
