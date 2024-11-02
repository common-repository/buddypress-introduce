<?php
/*
Plugin Name: BuddyPress Introduce
Plugin URI: http://wordpress.org/extend/plugins/buddypress-introduce/
Description: Introduce Plugin
Author: Sven Gak	
Version: 1.1.0.1
Author URI: http://www.sven-gak.de
Site Wide Only: false
Network: true
*/

define ( 'BP_INTRODUCE_PLUGIN_NAME', 'bp-introduce' );
define ( 'BP_INTRODUCE_PLUGIN_SLUG', 'introduce' );
define ( 'BP_INTRODUCE_DB_VERSION', 1 );

/* Define the slug for the component */
function buddypress_introduce_init() {
	require_once ( dirname( __FILE__ ) . '/includes/introduce.php' );
}
add_action( 'bp_init', 'buddypress_introduce_init' );

function buddypress_introduce_setup_globals() {
	global $bp, $wpdb;

	/* For internal identification */
	$bp->introduce->slug = $bp->introduce->id = BP_INTRODUCE_PLUGIN_SLUG;
	$bp->introduce->base_table_name =  $wpdb->base_prefix . "bp_introduce";
	$bp->introduce->user_table_name =  $wpdb->base_prefix . "bp_introduce_user";
	$bp->introduce->format_notification_function = 'bp_introduce_format_notifications';
	
	$friends_link = $bp->loggedin_user->domain . $bp->friends->slug . '/';
	
	if( function_exists('bp_include') ){
		bp_core_new_subnav_item( array( 'name' => __( 'Contact request', 'buddypress-introduce' ), 'slug' => $bp->introduce->slug, 'parent_url' => $friends_link, 'parent_slug' => $bp->friends->slug, 'screen_function' => 'bp_introduce_screen', 'position' => 30 ) );
	}
	
	do_action( 'buddypress_introduce_setup_globals' );
}
add_action( 'wp', 'buddypress_introduce_setup_globals' );
add_action( 'admin_menu', 'buddypress_introduce_setup_globals' );
add_action( 'bp_setup_globals', 'buddypress_introduce_setup_globals' );
add_action( 'friends_setup_nav', 'buddypress_introduce_setup_globals' );

function buddypress_introduce_install() {
	global $wpdb, $bp;

	buddypress_introduce_setup_globals();

	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";

	$sql[] = "CREATE TABLE {$bp->introduce->base_table_name} (
	  		id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			initiator_user_id bigint(20) NOT NULL,
	  		message longtext NOT NULL,
			email_message smallint(1)  NOT NULL,
			date_created datetime NOT NULL,
			state text NOT NULL,
		    KEY initiator_user_id (initiator_user_id),
			KEY email_message (email_message)
	 	   ) {$charset_collate};";
	
	$sql[] = "CREATE TABLE {$bp->introduce->user_table_name} (
	  		id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			introduce_id bigint(20) NOT NULL,
			user_id bigint(20) NOT NULL,
			user_accepted datetime,
			introduce_user_id bigint(20) NOT NULL,
			introduce_user_accepted datetime,
			state smallint(1) NOT NULL,
			KEY introduce_id (introduce_id),
			KEY user_id (user_id),
			KEY introduce_user_id (introduce_user_id),
			KEY state (state)
	 	   ) {$charset_collate};";
	
	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	dbDelta( $sql );
	
	do_action( 'buddypress_introduce_install' );
	update_site_option( 'buddypress-introduce-db-version', BP_INTRODUCE_DB_VERSION );
}

register_activation_hook( __FILE__, 'buddypress_introduce_install' );

function buddypress_introduce_deactivate(){
	if ( !function_exists( 'delete_site_option') )
		return false;
	
	delete_site_option( 'buddypress-introduce-db-version' );
	do_action( 'bp_loader_deactivate' );	
}
register_deactivation_hook( __FILE__, 'buddypress_introduce_deactivate' );



?>