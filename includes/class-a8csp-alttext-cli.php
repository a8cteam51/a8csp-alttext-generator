<?php

if ( ! class_exists( 'A8csp_AltText_CLI' ) ) {
	/**
	 * WP CLI command for generating alt text for images.
	 */
	class A8csp_AltText_CLI {
		/**
		 * Register the command with WP CLI.
		 */
		public static function register() {
			\WP_CLI::add_command(
				'a8csp alttext generate',
				array( __CLASS__, 'generate' ),
				array(
					'before_invoke' => function () {
						require_once __DIR__ . '/class-a8csp-alttext-openai-client.php';
					},
				)
			);

			\WP_CLI::add_command( 'a8csp alttext set-api-key', array( __CLASS__, 'set_api_key' ) );

			\WP_CLI::add_command( 'a8csp alttext count', array( __CLASS__, 'count' ) );

			\WP_CLI::add_command(
				'a8csp alttext estimate',
				array( __CLASS__, 'estimate' ),
				array(
					'before_invoke' => function () {
						require_once __DIR__ . '/class-a8csp-alttext-openai-client.php';
					},
				)
			);
		}

		/**
		 * Generate alt text for images.
		 *
		 * ## OPTIONS
		 *
		 * [--all]
		 * : Process all images without alt text.
		 *
		 * [--id=<id>]
		 * : Process a specific image by attachment ID.
		 *
		 * [--limit=<n>]
		 * : Limit number of images processed. Default is 30.
		 *
		 * [--dry-run]
		 * : Show what would be changed, but don't update.
		 *
		 * [--force]
		 * : Overwrite existing alt text.
		 *
		 * [--quiet]
		 * : Suppress detailed output.
		 *
		 * [--api-key=<key>]
		 * : Use this OpenAI API key for this run only (does not save it).
		 *
		 * @when after_wp_load
		 *
		 * @param array $args Positional arguments.
		 * @param array $assoc_args Associative arguments.
		 */
		public static function generate( $args, $assoc_args ) {
			$all     = isset( $assoc_args['all'] );
			$id      = isset( $assoc_args['id'] ) ? intval( $assoc_args['id'] ) : null;
			$limit   = isset( $assoc_args['limit'] ) ? intval( $assoc_args['limit'] ) : 30;
			$dry_run = isset( $assoc_args['dry-run'] );
			$force   = isset( $assoc_args['force'] );
			$quiet   = isset( $assoc_args['quiet'] );
			$verbose = ! $quiet;
			$api_key = isset( $assoc_args['api-key'] ) ? $assoc_args['api-key'] : null;

			if ( ! $all && ! $id ) {
				\WP_CLI::error( 'You must specify either --all or --id.' );
			}

			$attachments = array();
			if ( $id ) {
				$attachment = get_post( $id );
				if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
					\WP_CLI::error( 'Attachment not found.' );
				}
				$attachments[] = $attachment;
			} else {
				$args  = array(
					'post_type'      => 'attachment',
					'post_mime_type' => 'image',
					'posts_per_page' => $limit > 0 ? $limit : -1,
					'post_status'    => 'inherit',
					'fields'         => 'ids',
					'meta_query'     => $force
						? array(
							array(
								'key'     => '_wp_attachment_image_alt',
								'compare' => 'EXISTS',
							),
						)
						: array(
							'relation' => 'OR',
							array(
								'key'     => '_wp_attachment_image_alt',
								'compare' => 'NOT EXISTS',
							),
							array(
								'key'     => '_wp_attachment_image_alt',
								'value'   => '',
								'compare' => '=',
							),
						),
				);
				$query = new \WP_Query( $args );
				foreach ( $query->posts as $attachment_id ) {
					$attachments[] = get_post( $attachment_id );
				}
			}

			if ( empty( $attachments ) ) {
				\WP_CLI::success( 'No images found to process.' );
				return;
			}

			foreach ( $attachments as $attachment ) {
				$alt = get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true );
				if ( $alt && ! $force ) {
					if ( $verbose ) {
						\WP_CLI::log( "Skipping #{$attachment->ID} (already has alt text)" );
					}
					continue;
				}

				$image_url = wp_get_attachment_image_src( $attachment->ID, 'large' );
				if ( ! is_array( $image_url ) || ! isset( $image_url[0] ) ) {
					\WP_CLI::warning( "Could not get image data for #{$attachment->ID}" );
					continue;
				}

				$base64 = base64_encode( wp_remote_get( $image_url[0] )['body'] );
				if ( $verbose ) {
					\WP_CLI::log( "Generating alt text for #{$attachment->ID}..." );
				}

				$alt_text = \A8csp_AltText_OpenAI_Client::generate_alt_text( $base64, $api_key );
				if ( is_wp_error( $alt_text ) ) {
					\WP_CLI::warning( "OpenAI error for #{$attachment->ID}: " . $alt_text->get_error_message() );
					continue;
				}

				if ( $dry_run ) {
					\WP_CLI::log( "[Dry run] Would set alt text for #{$attachment->ID}: $alt_text" );
				} else {
					update_post_meta( $attachment->ID, '_wp_attachment_image_alt', $alt_text );
					\WP_CLI::success( "Set alt text for #{$attachment->ID}:" . PHP_EOL . "$alt_text" . PHP_EOL . 'Edit screen URL: ' . admin_url( "post.php?post={$attachment->ID}&action=edit" ) );
				}
			}
		}

		/**
		 * Set the OpenAI API key in wp-config.php as a constant.
		 *
		 * ## OPTIONS
		 *
		 * <api_key>
		 * : The OpenAI API key to set as a constant in wp-config.php.
		 *
		 * @when after_wp_load
		 *
		 * @param array $args Positional arguments.
		 * @param array $assoc_args Associative arguments.
		 *
		 * @return void
		 */
		public static function set_api_key( $args, $assoc_args ) {
			if ( empty( $args[0] ) ) {
				\WP_CLI::error( 'You must provide an API key.' );
			}

			WP_CLI::run_command( 'config set OPENAI_API_KEY ' . $args[0] . ' --type=constant' );

			\WP_CLI::success( 'OPENAI_API_KEY constant set in wp-config.php.' );
		}

		/**
		 * Count images that need alt text (no alt text or empty alt text).
		 *
		 * ## EXAMPLES
		 *
		 *     wp a8csp alttext count
		 *
		 * @when after_wp_load
		 *
		 * @param array $args Positional arguments.
		 * @param array $assoc_args Associative arguments.
		 */
		public static function count( $args, $assoc_args ) {
			$args  = array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'posts_per_page' => -1,
				'post_status'    => 'inherit',
				'fields'         => 'ids',
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => '_wp_attachment_image_alt',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_wp_attachment_image_alt',
						'value'   => '',
						'compare' => '=',
					),
				),
			);
			$query = new \WP_Query( $args );
			$count = count( $query->posts );
			\WP_CLI::success( "$count images need alt text." );
		}

		/**
		 * Estimate total OpenAI token usage for generating alt text for all images that need it.
		 *
		 * ## EXAMPLES
		 *
		 *     wp a8csp alttext estimate
		 *
		 * @when after_wp_load
		 *
		 * @param array $args Positional arguments.
		 * @param array $assoc_args Associative arguments.
		 */
		public static function estimate( $args, $assoc_args ) {
			$args  = array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'posts_per_page' => -1,
				'post_status'    => 'inherit',
				'fields'         => 'ids',
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => '_wp_attachment_image_alt',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_wp_attachment_image_alt',
						'value'   => '',
						'compare' => '=',
					),
				),
			);
			$query = new \WP_Query( $args );
			$count = count( $query->posts );
			// Estimate tokens: prompt + image + max_tokens (output)
			$prompt_details = \A8csp_AltText_OpenAI_Client::get_prompt_details();
			$prompt         = implode( "\n", $prompt_details );
			// Rough estimate: 1 token ~= 4 chars (for English text)
			$prompt_tokens = (int) ceil( strlen( $prompt ) / 4 );
			// OpenAI counts image as 85 tokens (per docs, for vision models)
			$image_tokens     = 85;
			$output_tokens    = 60; // max_tokens in request
			$tokens_per_image = $prompt_tokens + $image_tokens + $output_tokens;
			$total_tokens     = $tokens_per_image * $count;

			// Pricing details
			$input_price_per_million  = 0.40;
			$output_price_per_million = 1.60;

			$input_tokens_per_image  = $prompt_tokens + $image_tokens;
			$output_tokens_per_image = $output_tokens;

			$total_input_tokens  = $input_tokens_per_image * $count;
			$total_output_tokens = $output_tokens_per_image * $count;

			$input_cost  = ( $total_input_tokens / 1000000 ) * $input_price_per_million;
			$output_cost = ( $total_output_tokens / 1000000 ) * $output_price_per_million;
			$total_cost  = $input_cost + $output_cost;

			$input_cost_fmt  = number_format( $input_cost, 4 );
			$output_cost_fmt = number_format( $output_cost, 4 );
			$total_cost_fmt  = number_format( $total_cost, 4 );

			\WP_CLI::success( "$count images need alt text.\nEstimated total OpenAI tokens for generation: $total_tokens (about $tokens_per_image per image).\n\nCost breakdown (approx):\n- Input: $total_input_tokens tokens × \\$0.40/1M = \\$$input_cost_fmt\n- Output: $total_output_tokens tokens × \\$1.60/1M = \\$$output_cost_fmt\n\nEstimated total cost: \\$$total_cost_fmt\n" );
		}
	}
}
