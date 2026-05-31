<?php
/**
 * Minimal Campaign Monitor (Create Send) API client (raw HTTP, no SDK).
 *
 * @package PostsToNewsletter
 */

namespace PostsToNewsletter\Integrations;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Lists clients/lists and creates DRAFT campaigns (Campaign Monitor fetches the
 * HTML from the supplied HtmlUrl). Never sends.
 */
class CampaignMonitorClient {

	private const BASE = 'https://api.createsend.com/api/v3.3/';

	/**
	 * API key.
	 *
	 * @var string
	 */
	private string $key;

	/**
	 * Constructor.
	 *
	 * @param string $api_key Account-level API key.
	 */
	public function __construct( string $api_key ) {
		$this->key = trim( $api_key );
	}

	/**
	 * Whether a key is present.
	 *
	 * @return bool
	 */
	public function is_configured(): bool {
		return '' !== $this->key;
	}

	/**
	 * List clients as id => name.
	 *
	 * @return array<string, string>|WP_Error
	 */
	public function get_clients() {
		$res = $this->request( 'GET', 'clients.json' );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$out = array();
		foreach ( (array) $res as $client ) {
			$out[ (string) $client['ClientID'] ] = (string) $client['Name'];
		}
		return $out;
	}

	/**
	 * List a client's subscriber lists as id => name.
	 *
	 * @param string $client_id Client ID.
	 * @return array<string, string>|WP_Error
	 */
	public function get_lists( string $client_id ) {
		$res = $this->request( 'GET', 'clients/' . rawurlencode( $client_id ) . '/lists.json' );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$out = array();
		foreach ( (array) $res as $list ) {
			$out[ (string) $list['ListID'] ] = (string) $list['Name'];
		}
		return $out;
	}

	/**
	 * Create a DRAFT campaign. Campaign Monitor fetches the HTML from $html_url.
	 *
	 * @param string                $client_id Client ID.
	 * @param array<string, mixed>  $args      Name/Subject/FromName/FromEmail/ReplyTo/ListIDs.
	 * @param string                $html_url  Public URL of the campaign HTML.
	 * @return array{id:string}|WP_Error
	 */
	public function create_draft( string $client_id, array $args, string $html_url ) {
		$body = array(
			'Name'      => $args['Name'] ?? '',
			'Subject'   => $args['Subject'] ?? '',
			'FromName'  => $args['FromName'] ?? '',
			'FromEmail' => $args['FromEmail'] ?? '',
			'ReplyTo'   => $args['ReplyTo'] ?? '',
			'HtmlUrl'   => $html_url,
			'ListIDs'   => array_values( (array) ( $args['ListIDs'] ?? array() ) ),
		);

		$res = $this->request( 'POST', 'campaigns/' . rawurlencode( $client_id ) . '.json', $body );
		if ( is_wp_error( $res ) ) {
			return $res;
		}

		// The campaign ID is returned as a bare JSON string.
		return array( 'id' => is_string( $res ) ? $res : (string) ( $res['id'] ?? '' ) );
	}

	/**
	 * Perform an API request.
	 *
	 * @param string                    $method HTTP method.
	 * @param string                    $path   Path after the base URL.
	 * @param array<string, mixed>|null $body   JSON body.
	 * @return mixed|WP_Error Decoded JSON (array or scalar) or WP_Error.
	 */
	private function request( string $method, string $path, ?array $body = null ) {
		if ( ! $this->is_configured() ) {
			return new WP_Error( 'ptn_cm', __( 'Campaign Monitor API key is missing.', 'posts-to-newsletter' ) );
		}

		$args = array(
			'method'  => $method,
			'timeout' => 20,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $this->key . ':x' ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- HTTP Basic auth.
				'Content-Type'  => 'application/json',
			),
		);
		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( self::BASE . $path, $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			$detail = is_array( $data ) ? ( $data['Message'] ?? '' ) : '';
			/* translators: 1: HTTP status code, 2: error detail. */
			return new WP_Error( 'ptn_cm', sprintf( __( 'Campaign Monitor error (HTTP %1$d): %2$s', 'posts-to-newsletter' ), $code, $detail ) );
		}

		return $data;
	}
}
