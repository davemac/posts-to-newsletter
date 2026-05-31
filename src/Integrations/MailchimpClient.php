<?php
/**
 * Minimal Mailchimp Marketing API client (raw HTTP, no SDK).
 *
 * @package PostsToNewsletter
 */

namespace PostsToNewsletter\Integrations;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Lists audiences and creates DRAFT campaigns. Never sends.
 */
class MailchimpClient {

	/**
	 * API key.
	 *
	 * @var string
	 */
	private string $key;

	/**
	 * Datacenter (e.g. us13), derived from the key suffix.
	 *
	 * @var string
	 */
	private string $dc;

	/**
	 * Constructor.
	 *
	 * @param string $api_key Mailchimp API key (…-us13).
	 */
	public function __construct( string $api_key ) {
		$this->key = trim( $api_key );
		$parts     = explode( '-', $this->key );
		$this->dc  = count( $parts ) > 1 ? end( $parts ) : '';
	}

	/**
	 * Whether the key looks usable.
	 *
	 * @return bool
	 */
	public function is_configured(): bool {
		return '' !== $this->key && '' !== $this->dc;
	}

	/**
	 * List audiences as id => name.
	 *
	 * @return array<string, string>|WP_Error
	 */
	public function get_audiences() {
		$res = $this->request( 'GET', 'lists?count=100&fields=lists.id,lists.name' );
		if ( is_wp_error( $res ) ) {
			return $res;
		}

		$out = array();
		foreach ( (array) ( $res['lists'] ?? array() ) as $list ) {
			$out[ (string) $list['id'] ] = (string) $list['name'];
		}
		return $out;
	}

	/**
	 * Create a DRAFT campaign with the given HTML.
	 *
	 * @param string                $list_id  Audience ID.
	 * @param array<string, string> $settings subject_line/title/from_name/reply_to.
	 * @param string                $html     Full email HTML.
	 * @return array{web_id:int, url:string}|WP_Error
	 */
	public function create_draft( string $list_id, array $settings, string $html ) {
		$created = $this->request(
			'POST',
			'campaigns',
			array(
				'type'       => 'regular',
				'recipients' => array( 'list_id' => $list_id ),
				'settings'   => array(
					'subject_line' => $settings['subject_line'] ?? '',
					'title'        => $settings['title'] ?? '',
					'from_name'    => $settings['from_name'] ?? '',
					'reply_to'     => $settings['reply_to'] ?? '',
				),
			)
		);
		if ( is_wp_error( $created ) ) {
			return $created;
		}

		$id     = (string) ( $created['id'] ?? '' );
		$web_id = (int) ( $created['web_id'] ?? 0 );
		if ( '' === $id ) {
			return new WP_Error( 'ptn_mc', __( 'Mailchimp did not return a campaign ID.', 'posts-to-newsletter' ) );
		}

		$content = $this->request( 'PUT', 'campaigns/' . rawurlencode( $id ) . '/content', array( 'html' => $html ) );
		if ( is_wp_error( $content ) ) {
			return $content;
		}

		return array(
			'web_id' => $web_id,
			'url'    => sprintf( 'https://%s.admin.mailchimp.com/campaigns/edit?id=%d', $this->dc, $web_id ),
		);
	}

	/**
	 * Perform an API request.
	 *
	 * @param string                    $method HTTP method.
	 * @param string                    $path   Path after /3.0/.
	 * @param array<string, mixed>|null $body   JSON body.
	 * @return array<string, mixed>|WP_Error
	 */
	private function request( string $method, string $path, ?array $body = null ) {
		if ( ! $this->is_configured() ) {
			return new WP_Error( 'ptn_mc', __( 'Mailchimp API key is missing or malformed (expected a key ending in -usXX).', 'posts-to-newsletter' ) );
		}

		$args = array(
			'method'  => $method,
			'timeout' => 20,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( 'anystring:' . $this->key ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- HTTP Basic auth.
				'Content-Type'  => 'application/json',
			),
		);
		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( "https://{$this->dc}.api.mailchimp.com/3.0/{$path}", $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			$detail = is_array( $data ) ? ( $data['detail'] ?? $data['title'] ?? '' ) : '';
			/* translators: 1: HTTP status code, 2: error detail. */
			return new WP_Error( 'ptn_mc', sprintf( __( 'Mailchimp error (HTTP %1$d): %2$s', 'posts-to-newsletter' ), $code, $detail ) );
		}

		return is_array( $data ) ? $data : array();
	}
}
