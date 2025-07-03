<?php
/**
 * Handles OpenAI API requests for image alt text generation.
 *
 * @package A8csp_AltTextGenerator
 */

if ( ! class_exists( 'A8csp_AltText_OpenAI_Client' ) ) {
	/**
	 * Class A8csp_AltText_OpenAI_Client
	 *
	 * Provides methods to interact with the OpenAI API for generating image alt text.
	 */
	class A8csp_AltText_OpenAI_Client {
		/**
		 * The prompt details for generating alt text.
		 *
		 * @var array
		 */
		private static $prompt_details = array(
			'Describe this image for use as alt text for accessibility.',
			'Prioritize the most important information first.',
			'Be concise: use a short phrase or sentence.',
			'Do not include phrases like "image of" or "picture of".',
			'Do not include any other commentary or text.',
			'Use proper punctuation for clarity.',
			'Only describe the image if it conveys meaningful information.',
			'If the image appears purely decorative or cannot be described, return an empty string.',
		);

		/**
		 * Get the prompt details for generating alt text.
		 *
		 * @return array
		 */
		public static function get_prompt_details() {
			return self::$prompt_details;
		}

		/**
		 * Get the OpenAI API key from env or WP option.
		 *
		 * @return string|false
		 */
		public static function get_api_key() {
			$api_key = defined( 'OPENAI_API_KEY' ) ? OPENAI_API_KEY : false;
			if ( ! $api_key ) {
				$api_key = get_option( 'alttext_openai_api_key', false );
			}
			return $api_key;
		}

		/**
		 * Set the OpenAI API key in WP options.
		 *
		 * @param string $api_key The API key to set.
		 *
		 * @return void
		 */
		public static function set_api_key( $api_key ) {
			update_option( 'alttext_openai_api_key', $api_key );
		}

		/**
		 * Generate alt text for a base64-encoded image using OpenAI API.
		 *
		 * @param string      $base64_image The base64-encoded image data.
		 * @param string|null $api_key      Optional. The API key to use for this request.
		 *
		 * @return string|WP_Error
		 */
		public static function generate_alt_text( $base64_image, $api_key = null ) {
			if ( ! $api_key ) {
				$api_key = self::get_api_key();
			}

			if ( ! $api_key ) {
				return new \WP_Error( 'no_api_key', 'OpenAI API key not set.' );
			}

			$prompt_details = self::get_prompt_details();

			$endpoint = 'https://api.openai.com/v1/chat/completions';
			$body     = array(
				'model'      => 'gpt-4.1-mini',
				'messages'   => array(
					array(
						'role'    => 'user',
						'content' => array(
							array(
								'type' => 'text',
								'text' => implode( "\n", $prompt_details ),
							),
							array(
								'type'      => 'image_url',
								'image_url' => array( 'url' => 'data:image/jpeg;base64,' . $base64_image ),
							),
						),
					),
				),
				'max_tokens' => 60,
			);

			$args = array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
				'timeout' => 60,
			);

			$response = wp_remote_post( $endpoint, $args );
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$code = wp_remote_retrieve_response_code( $response );
			if ( 200 !== $code ) {
				return new \WP_Error( 'openai_error', 'OpenAI API error: ' . $code . ' ' . wp_remote_retrieve_body( $response ) );
			}

			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( isset( $data['choices'][0]['message']['content'] ) ) {
				return trim( $data['choices'][0]['message']['content'] );
			}

			return new \WP_Error( 'openai_no_content', 'No alt text returned from OpenAI.' );
		}
	}
}
