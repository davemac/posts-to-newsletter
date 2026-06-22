<?php
/**
 * Uninstall routine: remove all plugin data.
 *
 * Runs only when the plugin is deleted from the Plugins screen.
 *
 * @package PostsToNewsletter
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Current (dmc_ptn_) option keys, plus the migration flag.
delete_option( 'dmc_ptn_settings' );
delete_option( 'dmc_ptn_newsletter_post_ids' );
delete_option( 'dmc_ptn_subject' );
delete_option( 'dmc_ptn_intro' );
delete_option( 'dmc_ptn_template' );
delete_option( 'dmc_ptn_migrated' );

// Pre-1.0 keys, in case the plugin is removed before the one-time migration ran.
delete_option( 'ptn_settings' );
delete_option( 'ptn_newsletter_post_ids' );
delete_option( 'ptn_subject' );
delete_option( 'ptn_intro' );
delete_option( 'ptn_template' );
