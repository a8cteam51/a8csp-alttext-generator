<?php
/**
 * Plugin Name: Alt Text Generator
 * Description: Generates alt text for images in the WordPress media library using OpenAI.
 * Version: 1.0.0
 * Author: Automattic Special Projects
 * Author URI: https://specialprojects.automattic.com/
 * Text Domain: a8csp-alt-text-generator
 * Domain Path: /languages
 * Contributors: tommusrhodus
 *
 * @package A8csp_AltTextGenerator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Load CLI command if WP CLI is present
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once __DIR__ . '/includes/class-a8csp-alttext-cli.php';
	A8csp_AltText_CLI::register();
}
