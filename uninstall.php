<?php
/**
 * Uninstall routine: remove all plugin data.
 *
 * Runs only when the plugin is deleted from the Plugins screen.
 *
 * @package PostsToNewsletter
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'ptn_settings' );
delete_option( 'ptn_newsletter_post_ids' );
delete_option( 'ptn_subject' );
delete_option( 'ptn_intro' );
delete_option( 'ptn_template' );
