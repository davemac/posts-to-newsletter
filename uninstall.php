<?php
/**
 * Uninstall routine: remove all plugin data.
 *
 * Runs only when the plugin is deleted from the Plugins screen.
 *
 * @package Cnl
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'cnl_settings' );
delete_option( 'cnl_newsletter_post_ids' );
