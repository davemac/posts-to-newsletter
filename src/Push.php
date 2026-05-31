<?php
/**
 * REST endpoints that create a DRAFT campaign on Mailchimp or Campaign Monitor.
 *
 * @package Cnl
 */

namespace Cnl;

use Cnl\Integrations\MailchimpClient;
use Cnl\Integrations\CampaignMonitorClient;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the platform HTML and pushes it to the platform as a draft.
 */
class Push {

	private const CAPABILITY = 'edit_others_posts';

	/**
	 * Settings provider.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Renderer.
	 *
	 * @var Renderer
	 */
	private Renderer $renderer;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings.
	 * @param Renderer $renderer Renderer.
	 */
	public function __construct( Settings $settings, Renderer $renderer ) {
		$this->settings = $settings;
		$this->renderer = $renderer;
	}

	/**
	 * Register the push routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$can = static function () {
			return current_user_can( self::CAPABILITY );
		};

		register_rest_route(
			Curation::REST_NS,
			'/push/mailchimp',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'push_mailchimp' ),
				'permission_callback' => $can,
			)
		);

		register_rest_route(
			Curation::REST_NS,
			'/push/campaignmonitor',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'push_campaignmonitor' ),
				'permission_callback' => $can,
			)
		);
	}

	/**
	 * Create a Mailchimp draft.
	 *
	 * @return WP_REST_Response
	 */
	public function push_mailchimp(): WP_REST_Response {
		$key      = $this->settings->mailchimp_key();
		$audience = (string) $this->settings->get( 'mailchimp_audience_id' );

		if ( '' === $key || '' === $audience ) {
			return $this->error( __( 'Add a Mailchimp API key and choose an audience in Settings first.', 'posts-to-newsletter' ) );
		}

		$client = new MailchimpClient( $key );
		$result = $client->create_draft(
			$audience,
			array(
				'subject_line' => $this->subject(),
				'title'        => $this->subject(),
				'from_name'    => (string) $this->settings->get( 'from_name' ),
				'reply_to'     => (string) $this->settings->get( 'reply_to' ),
			),
			$this->renderer->render( 'mailchimp' )
		);

		if ( is_wp_error( $result ) ) {
			return $this->error( $result->get_error_message() );
		}

		return new WP_REST_Response( array( 'ok' => true, 'url' => $result['url'] ), 200 );
	}

	/**
	 * Create a Campaign Monitor draft (CM fetches the HTML from our public URL).
	 *
	 * @return WP_REST_Response
	 */
	public function push_campaignmonitor(): WP_REST_Response {
		$key       = $this->settings->cm_key();
		$client_id = (string) $this->settings->get( 'cm_client_id' );
		$list_id   = (string) $this->settings->get( 'cm_list_id' );

		if ( '' === $key || '' === $client_id || '' === $list_id ) {
			return $this->error( __( 'Add a Campaign Monitor API key, client and list in Settings first.', 'posts-to-newsletter' ) );
		}

		$html_url = add_query_arg( array( Renderer::PLATFORM_VAR => 'campaignmonitor' ), home_url( '/cnl-newsletter/' ) );

		$client = new CampaignMonitorClient( $key );
		$result = $client->create_draft(
			$client_id,
			array(
				'Name'      => $this->subject(),
				'Subject'   => $this->subject(),
				'FromName'  => (string) $this->settings->get( 'from_name' ),
				'FromEmail' => (string) $this->settings->get( 'from_email' ),
				'ReplyTo'   => (string) $this->settings->get( 'reply_to' ),
				'ListIDs'   => array( $list_id ),
			),
			$html_url
		);

		if ( is_wp_error( $result ) ) {
			return $this->error( $result->get_error_message() );
		}

		return new WP_REST_Response(
			array(
				'ok'      => true,
				'message' => __( 'Draft created in Campaign Monitor. Open Campaign Monitor to review and send.', 'posts-to-newsletter' ),
			),
			200
		);
	}

	/**
	 * Build the subject line: the lead article title, else the site name.
	 *
	 * @return string
	 */
	private function subject(): string {
		$ids = array_values( array_filter( array_map( 'absint', (array) get_option( Curation::SELECTION, array() ) ) ) );
		if ( ! empty( $ids ) ) {
			$title = get_the_title( $ids[0] );
			if ( '' !== $title ) {
				return $title;
			}
		}
		return (string) $this->settings->get( 'site_name' );
	}

	/**
	 * A 400 error response with a message.
	 *
	 * @param string $message Message.
	 * @return WP_REST_Response
	 */
	private function error( string $message ): WP_REST_Response {
		return new WP_REST_Response( array( 'ok' => false, 'error' => $message ), 400 );
	}
}
