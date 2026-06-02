# Alt Text Generator

Alt Text Generator is a WordPress plugin that generates attachment alt text from
the command line using OpenAI. It has no WordPress admin interface; the plugin
only registers commands when WordPress is running under WP-CLI.

The plugin is intended for accessibility and media-library cleanup workflows. It
can process images that are missing `_wp_attachment_image_alt`, target a single
attachment, overwrite existing alt text when requested, and estimate the OpenAI
usage for a run.

## Repository Contents

- `a8csp-alttext-generator.php` contains the plugin header and loads the WP-CLI
  command class when `WP_CLI` is available.
- `includes/class-a8csp-alttext-cli.php` registers and implements the
  `wp a8csp alttext` commands.
- `includes/class-a8csp-alttext-openai-client.php` stores the alt-text prompt and
  calls the OpenAI chat completions API.
- `composer.json` and `composer.lock` define the PHP development tooling.
- `.phpcs.xml` extends the Team51 PHPCS ruleset and configures WordPress coding
  standards checks.
- `.github/workflows/release.yml` builds a plugin ZIP and attaches it to a
  GitHub release when a release is published.
- `.distignore` lists files that are not intended for a WordPress.org
  distribution package.

No blocks, custom post types, taxonomies, REST routes, shortcodes, wp-admin UI,
or frontend build assets are tracked in this repository.

## Requirements

- A WordPress site with WP-CLI access.
- Image attachments in the WordPress media library.
- An OpenAI API key.
- PHP `>=8.3` and `ext-json` for Composer workflows, as declared in
  `composer.json`.

The plugin sends base64-encoded image data to OpenAI's
`https://api.openai.com/v1/chat/completions` endpoint using the
`gpt-4.1-mini` model. Do not run it against images that should not be sent to
OpenAI.

## Installation

Install and activate the plugin on a WordPress site with WP-CLI:

```sh
wp plugin install https://github.com/a8cteam51/a8csp-alttext-generator/archive/trunk.zip --activate
```

Save an OpenAI API key to `wp-config.php` as the `OPENAI_API_KEY` constant:

```sh
wp a8csp alttext set-api-key <openai-api-key>
```

For a one-off run without saving the key, pass `--api-key=<openai-api-key>` to
the `generate` command.

## WP-CLI Usage

The `generate` command requires either `--all` or `--id=<attachment-id>`.
Without `--limit`, `--all` processes a batch of up to 30 matching images.

```sh
# Generate alt text for up to 30 images missing alt text.
wp a8csp alttext generate --all

# Process every image missing alt text.
wp a8csp alttext generate --all --limit=0

# Generate alt text for one attachment.
wp a8csp alttext generate --id=123

# Preview changes without writing attachment meta.
wp a8csp alttext generate --all --dry-run

# Overwrite existing attachment alt text.
wp a8csp alttext generate --all --force

# Suppress per-image progress output.
wp a8csp alttext generate --all --quiet
```

Additional commands:

```sh
# Count images with missing or empty alt text.
wp a8csp alttext count

# Estimate token usage and cost for images needing alt text.
wp a8csp alttext estimate
```

The estimate command uses the assumptions hard-coded in
`includes/class-a8csp-alttext-cli.php`: 85 image tokens, up to 60 output tokens,
and the input/output prices stored in that file.

## Development

Install dependencies:

```sh
composer run packages-install
```

Run PHP linting:

```sh
composer run lint:php
```

Apply PHP formatting:

```sh
composer run format:php
```

There is no package.json, Node build, PHPUnit configuration, or Composer test
script in the repository. Composer does include development dependencies such as
Team51 configs, WP Browser, WP-CLI i18n tooling, PHPCS, and Roave security
advisories.

## Release

Publishing a GitHub release runs `.github/workflows/release.yml`. The workflow
derives the plugin slug from the repository name, copies the repository contents
except `.git`, `.github`, `plugin`, and `release.zip`, creates a ZIP file, and
uploads that ZIP to the release.

No deployment branch or hosting deployment configuration is tracked.

## Maintenance Notes

- Generated dependency directories and archives, including `vendor/`,
  `node_modules/`, `*.zip`, `*.tar.gz`, and SQL dumps, are ignored.
- The Composer i18n scripts still reference the scaffold slug
  `a8csp-scaffold`; update those scripts before relying on them for this plugin.
- The plugin version is declared in `a8csp-alttext-generator.php`.

## License

GPL-2.0-or-later, as declared in `composer.json`. No standalone license file is
tracked.
