=== Alt Text Generator ===
Contributors: tommusrhodus, automattic
Donate link: https://specialprojects.automattic.com/
Tags: accessibility, images, alt text, openai, seo
Requires at least: 4.5
Tested up to: 6.8.1
Requires PHP: 5.6
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically generate alt text for images in your WordPress media library using OpenAI, improving accessibility and SEO.

== Description ==

Alt Text Generator uses OpenAI to generate concise, accessible alt text for images in your WordPress media library. Improve your site's accessibility and SEO by ensuring all images have meaningful alt text, generated with the latest AI technology.

* Generate alt text for all images missing it, or for a specific image by ID
* Uses OpenAI's GPT models to describe images
* CLI-only: no WordPress admin interface
* Supports dry-run mode, force overwrite, and image limits
* Requires an OpenAI API key
* Estimate OpenAI API token usage and cost for generating alt text, with a detailed cost breakdown for input and output tokens (based on current OpenAI pricing)

== Installation ==

1. Upload `a8csp-alt-text-generator.php` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress (for CLI use only)
3. Set your OpenAI API key using the CLI command:
   `wp a8csp alttext set-api-key <your-api-key>`

== Usage ==

This plugin is operated via WP-CLI. Example commands:

* Generate alt text for all images missing it:
  `wp a8csp alttext generate --all`
* Generate alt text for a specific image by ID:
  `wp a8csp alttext generate --id=123`
* Limit the number of images processed:
  `wp a8csp alttext generate --all --limit=10`
* Force overwrite existing alt text:
  `wp a8csp alttext generate --all --force`
* Dry run (show what would be changed):
  `wp a8csp alttext generate --all --dry-run`
* Count images needing alt text:
  `wp a8csp alttext count`
* Estimate token usage and cost for generating alt text for all images needing it:
  `wp a8csp alttext estimate`

== Frequently Asked Questions ==

= Does this plugin add a UI to WordPress? =
No, it is CLI-only. All operations are performed via WP-CLI commands.

= What API does it use? =
It uses OpenAI's GPT models to generate alt text from image data.

= Is my image data sent to OpenAI? =
Yes, images are encoded and sent to OpenAI for alt text generation. Do not use on sensitive images.

== Changelog ==

= 1.0.0 =
* Initial release. Generate alt text for images using OpenAI via WP-CLI.

== Upgrade Notice ==

= 1.0.0 =
First public release. Use WP-CLI to generate alt text for your images using OpenAI.
