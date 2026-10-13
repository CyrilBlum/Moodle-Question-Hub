# Moodle Question Hub Subplugin (`qbank_questionhub`)

Native Moodle subplugin extending the core Question Bank subsystem, enabling teachers to browse, preview, import, export, and publish question banks between independent Moodle installations via the centralized Moodle Question Hub marketplace (`https://hub.cblum.ch`).

## Key Features

- **Dual-Version Compatibility:** Fully verified across **Moodle 4.5.15 LTS (PHP 8.1)** and **Moodle 5.0.11 Stable (PHP 8.2)**.
- **Deep Question Bank UI Integration:**
  - Question Bank tertiary action navigation tab ("Question Hub") in course and quiz contexts.
  - Question row action menu item ("Export to Question Hub") at position 610.
  - Question bank header controls for one-click browsing and category export.
  - Bulk actions integration for publishing selected questions.
- **Zero-Trust Safe XML Pre-flight Validator:**
  - Strict XXE mitigation prohibiting DOCTYPE, ENTITY, and XInclude attacks.
  - In-memory parsing via `LIBXML_NONET`.
  - Pedagogical invariant verification across 7 Swiss Gymnasium question types: `multichoice`, `truefalse`, `shortanswer`, `numerical`, `matching`, `multianswer` (Cloze), and `essay`.
  - Embedded Base64 media integrity checking, size limits, and SVG active script sanitation.
- **Cross-Version Context Normalization:**
  - Normalizes `$module$` context tokens to canonical `$course$/top/...` hierarchy.
  - Handles Base64 embedded media translation to/from Moodle File Storage API (`@@PLUGINFILE@@`).
- **REST API Client:**
  - Built with native Moodle `\curl` honoring system proxies, CA bundles, and timeout configs.
  - Two-tier authentication: Teacher OAuth 2.0 PKCE account linking with institutional PAT fallback.

## Directory Layout

```
qbank_questionhub/
├── version.php                  # Subplugin manifest
├── settings.php                 # Site administration settings
├── view.php                     # Marketplace browser UI
├── publish.php                  # Category & question export/publishing UI
├── oauth_callback.php           # OAuth 2.0 PKCE redirect callback
├── disconnect.php               # Account unlink handler
├── db/
│   ├── access.php               # Capabilities
│   ├── install.xml              # Database tables (tokens, history)
│   └── upgrade.php              # Upgrade handler
├── classes/
│   ├── plugin_feature.php       # QBank extension entrypoint (plugin_features_base)
│   ├── navigation.php           # Tertiary navigation tab
│   ├── question_action.php      # Row action item
│   ├── bulk_publish_action.php  # Bulk action handler
│   ├── api_client.php           # REST API client
│   ├── auth_manager.php         # OAuth PKCE & token management
│   ├── preflight_validator.php   # Zero-trust XML preflight validator
│   ├── diagnostic_report.php    # Diagnostic report model
│   ├── import_result.php        # Import result model
│   ├── package_exporter.php     # XML exporter & context normalizer
│   ├── package_importer.php     # Validated XML package importer
│   ├── output/
│   │   ├── bank_action_button.php
│   │   └── renderer.php
│   └── privacy/provider.php     # GDPR privacy subsystem
├── lang/
│   ├── en/qbank_questionhub.php # English strings
│   └── de/qbank_questionhub.php # Swiss German strings (ss, zero German double-s)
├── templates/
│   ├── hub_browser.mustache
│   ├── package_card.mustache
│   ├── publish_view.mustache
│   └── bank_action_button.mustache
├── cli/
│   ├── export.php               # Headless CLI exporter
│   ├── import.php               # Headless CLI importer
│   └── verify.php               # Self-verification suite
└── tests/                       # Unit & integration tests
```

## Installation

1. Copy or clone this repository to `<moodle_root>/question/bank/questionhub`:
   ```bash
   git clone git@github.com:CyrilBlum/Moodle-Question-Hub.git question/bank/questionhub
   ```
2. Run database upgrade via Moodle CLI:
   ```bash
   php admin/cli/upgrade.php --non-interactive
   ```
3. Purge Moodle caches:
   ```bash
   php admin/cli/purge_caches.php
   ```

## Configuration

Navigate to **Site Administration > Plugins > Question bank plugins > Question Hub**:
- **Hub Base URL:** `https://hub.cblum.ch` (default)
- **OAuth 2.0 Client ID:** `moodle-plugin-client` (default)
- **Institutional Access Token:** Optional system-wide PAT for unlinked teachers.
- **Allow Teacher Account Linking:** Enabled (1).

## License

GNU General Public License v3 or later.
