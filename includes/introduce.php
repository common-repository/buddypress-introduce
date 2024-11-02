<?php
//error_reporting(E_ALL);
//ini_set('display_errors','On');

// Language file
if ( file_exists( dirname( __FILE__ ) . '/languages/' . get_locale() . '.mo' ) )
	load_textdomain( 'buddypress-introduce', dirname( __FILE__ ) . '/languages/' . get_locale() . '.mo' );

require_once ( dirname( __FILE__ ) . '/introduce-classes.php' );
require_once ( dirname( __FILE__ ) . '/introduce-templatetags.php' );

// Member Buttons
if ( bp_is_active( 'friends' ) ) 
	add_action( 'bp_member_header_actions', 'bp_introduce_add_button' );

/*
* CSS, JS
*/
function bp_introduce_add_js() {
	global $bp;
	
	if ( $bp->current_component == $bp->friends->id && $bp->current_action == $bp->introduce->slug ){
		wp_enqueue_script( 'introduce', plugins_url( '/buddypress-introduce/includes/templates/_inc/introduce.js' ) );
		wp_enqueue_script( 'introduce_autocomplete', plugins_url( '/buddypress-introduce/includes/templates/_inc/jquery.autocomplete.min.js' ) );
	}
}
add_action( 'template_redirect', 'bp_introduce_add_js', 1 );

function bp_introduce_add_css() {
	global $bp;

	if ( $bp->current_component == $bp->friends->id && $bp->current_action == $bp->introduce->slug ){		
		wp_enqueue_style( 'introduce', plugins_url( '/buddypress-introduce/includes/templates/_inc/introduce.css' ) );
		wp_enqueue_style( 'introduce_autocomplete', plugins_url( '/buddypress-introduce/includes/templates/_inc/jquery.autocompletefb.css' ) );
	}
}
add_action( 'template_redirect', 'bp_introduce_add_css', 1 );
	
function bp_introduce_add_button( $potential_friend_id = 0, $friend_status = false ) {
	echo bp_get_introduce_add_button( $potential_friend_id, $friend_status );
}

function bp_get_introduce_add_button( $potential_friend_id = 0, $friend_status = false ) {
	global $bp, $friends_template;

	if ( empty( $potential_friend_id ) )
		$potential_friend_id = bp_get_potential_friend_id( $potential_friend_id );

	$is_friend = bp_is_friend( $potential_friend_id );

	if ( empty( $is_friend ) || $is_friend == 'not_friends' )
		return false;

	switch ( $is_friend ) {
		
		case 'is_friend' :
		
			$name = bp_core_get_username( $potential_friend_id );
			$button = array(
				'id'                => 'introduce',
				'component'         => 'introduce',
				'must_be_logged_in' => true,
				'block_self'        => true,
				'wrapper_class'     => 'introduce-button',
				'wrapper_id'        => 'introduce-button-' . $potential_friend_id,
				'link_href'         => wp_nonce_url( $bp->loggedin_user->domain . $bp->friends->slug . "/" . $bp->introduce->id . '/user/' . $name, 'introduce' ),
				'link_text'         => __( 'Introduce', 'buddypress-introduce' ),
				'link_title'        => __( 'Introduce', 'buddypress-introduce' ),
				'link_id'           => 'friend-' . $potential_friend_id,
				'link_rel'          => 'introduce',
				'link_class'        => 'introduce'
				);
			break;
	}

	// Filter and return the HTML button
	return bp_get_button( apply_filters( 'bp_introduce_get_add_introduce_button', $button ) );
}


/**
 * introduce_screen()
 *
 * Handles the display of the profile page by loading the correct template file.
 *
 * @package BuddyPress matoma-sms
 * @uses bp_core_load_template() Looks for and loads a template file within the current member theme (folder/filename)
 */
function bp_introduce_screen() {
	global $bp;

	do_action( 'bp_introduce_screen' );
	
	if( isset( $bp->action_variables['0'] ) && isset( $bp->action_variables['1'] ) ){
		
		if( $bp->action_variables['0'] == 'user' && !empty( $bp->action_variables['1'] ) ){
			//todo location and display warning
			if ( !wp_verify_nonce( $_REQUEST['_wpnonce'], 'introduce')) die('Security check'); 
			
			if ( isset( $_POST ) && !empty( $_POST) ){
				
				$error = null;
				
				if ( empty( $_POST['message'] ) ) 
					$error = __( 'Please enter your message!', "buddypress-introduce" );
					
				if ( empty( $_POST['send_to_usernames'] ) )
					$error = __( 'Please select a contact!', "buddypress-introduce" );
					
				if( empty( $error )){
					
					if(Introduce::save_form_data( $_POST['send_to_usernames'], $_POST['message'], $_POST['email'] )){
						bp_core_add_message( __( 'The contact request has been successfully launched.', "buddypress-introduce" ) );
						bp_core_redirect( $bp->loggedin_user->domain . $bp->friends->slug, 302 );
					} 
					else bp_core_add_message( __( 'An unexpected error occurred.', "buddypress-introduce" ) , 'error' );
											
				} else {
					bp_core_add_message( $error, 'error' );
				}
			}
						
			add_action( 'bp_template_content', 'bp_introduce_screen_add_user_content');		
		} elseif ( $bp->action_variables['0'] == 'accept' ) {
			//todo location and display warning
			if ( !wp_verify_nonce( $_REQUEST['_wpnonce'], 'introduce_accept')) die('Security check'); 
			
			if( !empty( $bp->action_variables['1'] ) ){
				Introduce_User::user_accept((int)$bp->action_variables['1'] );
				bp_core_redirect( $bp->loggedin_user->domain . $bp->friends->slug . "/" . $bp->introduce->id, 302 );
			}
			
		}elseif ( $bp->action_variables['0'] == 'reject' ) {
			//todo location and display warning
			if ( !wp_verify_nonce( $_REQUEST['_wpnonce'], 'introduce_reject')) die('Security check'); 
			
			if( !empty( $bp->action_variables['1'] ) ){
				Introduce_User::user_reject((int)$bp->action_variables['1'] );
				bp_core_redirect( $bp->loggedin_user->domain . $bp->friends->slug . "/" . $bp->introduce->id, 302 );
			}
		}
	} elseif( isset( $bp->action_variables['0'] ) && $bp->action_variables['0'] == "own"  ) {
		add_action( 'bp_template_content', 'bp_introduce_screen_own_content');		
	} else {
		add_action( 'bp_template_content', 'bp_introduce_screen_content');
	}

	//add_action( 'bp_template_title', 'bp_introduce_screen_title');
	//add_action( 'bp_after_member_body', 'bp_introduce_screen_title' );
	
	bp_core_load_template( apply_filters( 'bp_introduce_template_screen', 'members/single/plugins' ) );
}


function bp_introduce_clear_notifications() {
	global $bp;

	if ( isset( $_GET['new'] ) ){
		bp_core_delete_notifications_for_user_by_type( $bp->loggedin_user->id, $bp->introduce->id, 'introduce_user' );
		bp_core_delete_notifications_for_user_by_type( $bp->loggedin_user->id, $bp->introduce->id, 'user_rejected' );
		bp_core_delete_notifications_for_user_by_type( $bp->loggedin_user->id, $bp->introduce->id, 'user_accept' );
		bp_core_delete_notifications_for_user_by_type( $bp->loggedin_user->id, $bp->introduce->id, 'user_accept_close' );
	}
}
add_action( 'bp_introduce_screen', 'bp_introduce_clear_notifications' );

function bp_introduce_screen_content(){
	include( bp_introduce_template_directory( null, array( 'introduce/index.php' ) ) );
}

function bp_introduce_screen_own_content(){
	include( bp_introduce_template_directory( null, array( 'introduce/own.php' ) ) );
}

function bp_introduce_screen_add_user_content(){
	include( bp_introduce_template_directory( null, array( 'introduce/add-user.php' ) ) );
}


function bp_introduce_template_directory( $found_template, $templates ) {
	global $bp;
	
	if ( $bp->current_component != $bp->friends->id || $bp->current_action != $bp->introduce->slug )
		return $found_template;
	
	foreach ( (array) $templates as $template ) {
		if ( file_exists( STYLESHEETPATH . '/' . $template ) )
			$filtered_templates[] = STYLESHEETPATH . '/' . $template;
		elseif ( file_exists( TEMPLATEPATH . '/' . $template ) )
			$filtered_templates[] = TEMPLATEPATH . '/' . $template;
		else
			$filtered_templates[] = dirname( __FILE__ ) . '/templates/' . $template ;
	}
	
	if( isset($filtered_templates[0]) ) $found_template = $filtered_templates[0];
	
	return apply_filters( 'bp_introduce_template_directory', $found_template );
}
add_filter( 'bp_located_template', 'bp_introduce_template_directory', 10, 2 );


function bp_introduce_get_introduces( $args = '' ) {
	global $bp;

	$defaults = array(
		'type' => 'active', // active, newest, alphabetical, random or popular
		'user_id' => false, // Pass a user_id to limit to only friend connections for this user
		'search_terms' => false, // Limit to users that match these search terms

		'include' => false, // Pass comma separated list of user_ids to limit to only these users
		'per_page' => 20, // The number of results to return per page
		'page' => 1, // The page to return if limiting per page
		'populate_extras' => true, // Fetch the last active, where the user is a friend, total friend count, latest update
	);

	$params = wp_parse_args( $args, $defaults );
	extract( $params, EXTR_SKIP );

	return apply_filters( 'bp_introduce_get_introduces', Introduce::get_introduces( $type, $per_page, $page, $user_id, $include, $search_terms, $populate_extras ), &$params );
}

/*
 * Notification
*/
function bp_introduce_format_notifications( $action, $item_id, $secondary_item_id, $total_items ) {
	global $bp;
	
	$default_url = $bp->loggedin_user->domain . $bp->friends->slug . '/' . $bp->introduce->slug . '/?new' ;
	
	switch ( $action ) {
		case 'introduce_user':
			
			if ( (int)$total_items > 1 ) {
				return apply_filters( 'bp_introduce_multiple_introduce_user_notifications', '<a href="' . $default_url . '" >' . sprintf( __('You have been %s person presented. Here you can confirm or deny the request.', 'buddypress-introduce' ), (int)$total_items ) . '</a>', (int)$total_items );
			} else {
				$initiator_name = bp_core_get_user_displayname( $secondary_item_id );
				$user_fullname = bp_core_get_user_displayname( $item_id );
				return apply_filters( 'bp_introduce_single_introduce_user_notifications', '<a href="' . $default_url . '" >' . sprintf( __( '%s introduce you %s. Here you can confirm or deny the request.', 'buddypress-introduce' ), $initiator_name, $user_fullname ) . '</a>', $user_fullname );
			}
			break;

		case 'user_accept':
			if ( (int)$total_items > 1 ) {
				return apply_filters( 'bp_introduce_multiple_user_accept_notification', '<a href="' . $default_url  . '" >' . sprintf( __('%d people have confirmed the contact request.', 'buddypress-introduce' ), (int)$total_items ) . '</a>', $total_items );
			} else {
				$user_fullname = bp_core_get_user_displayname( $item_id );
				$user_url = bp_core_get_user_domain( $item_id );
				return apply_filters( 'bp_introduce_single_user_accept_notification', '<a href="' . $default_url . '" >' . sprintf( __('%s has confirmed the contact request.', 'buddypress-introduce' ), $user_fullname ) . '</a>', $user_fullname );
			}
			break;
		
		case 'user_accept_close':
			if ( (int)$total_items > 1 ) {
				return apply_filters( 'bp_introduce_multiple_user_accept_close_notification', '<a href="' . $default_url  . '" >' . sprintf( __('%d people have also confirmed the contact request.', 'buddypress-introduce' ), (int)$total_items ) . '</a>', $total_items );
			} else {
				$user_fullname = bp_core_get_user_displayname( $item_id );
				$user_url = bp_core_get_user_domain( $item_id );
				return apply_filters( 'bp_introduce_single_user_accept_close_notification', '<a href="' . $default_url . '" >' . sprintf( __('%s has also confirmed the contact request.', 'buddypress-introduce' ), $user_fullname ) . '</a>', $user_fullname );
			}
			break;
			
		case 'user_rejected':
			if ( (int)$total_items > 1 ) {
				return apply_filters( 'bp_introduce_multiple_user_accept_close_notification', '<a href="' . $default_url  . '" >' . sprintf( __('%d people have rejected the contact request.', 'buddypress-introduce' ), (int)$total_items ) . '</a>', $total_items );
			} else {
				$user_fullname = bp_core_get_user_displayname( $item_id );
				$user_url = bp_core_get_user_domain( $item_id );
				return apply_filters( 'bp_introduce_single_user_accept_close_notification', '<a href="' . $default_url . '" >' . sprintf( __('%s has rejected the contact request.', 'buddypress-introduce' ), $user_fullname ) . '</a>', $user_fullname );
			}
			break;
	}

	do_action( 'bp_introduce_format_notifications', $action, $item_id, $secondary_item_id, $total_items );

	return false;
}

function bp_introduce_screen_notification_settings() {
	global $current_user; ?>
	<table class="notification-settings zebra" id="bp_introduce-notification-settings">
		<thead>
			<tr>
				<th class="icon"></th>
				<th class="title"><?php _e( 'Introduce', 'buddypress-introduce' ) ?></th>
				<th class="yes"><?php _e( 'Yes', 'buddypress-introduce' ) ?></th>
				<th class="no"><?php _e( 'No', 'buddypress-introduce' )?></th>
			</tr>
		</thead>

		<tbody>
			<tr>
				<td></td>
				<td><?php _e( 'A user can inform you of contact requests.', 'buddypress-introduce' ) ?></td>
				<td class="yes"><input type="radio" name="notifications[introduce_notification_user_add]" value="yes" <?php if ( !get_user_meta( $current_user->id, 'introduce_notification_user_add', true ) || 'yes' == get_user_meta( $current_user->id, 'introduce_notification_user_add', true ) ) { ?>checked="checked" <?php } ?>/></td>
				<td class="no"><input type="radio" name="notifications[introduce_notification_user_add]" value="no" <?php if ( get_user_meta( $current_user->id, 'introduce_notification_user_add', true ) == 'no' ) { ?>checked="checked" <?php } ?>/></td>
			</tr>
			
			<?php do_action( 'bp_introduce_screen_notification_settings' ); ?>
		</tbody>
	</table>
<?php
}
add_action( 'bp_notification_settings', 'bp_introduce_screen_notification_settings' );

function bp_introduce_notification_user_add( $initiator_id, $user_id, $users , $message ) {
	global $bp;

	$initiator_name = bp_core_get_user_displayname( $initiator_id );

	if ( 'no' == get_user_meta( (int)$user_id, 'introduce_notification_user_add', true ) )
		return false;

	$ud = get_userdata( $user_id );
	$initiator_ud = get_userdata( $initiator_id );
	
	$user_names = null;
	
	$ii = 0;
	
	foreach( $users as $user ) {
		$user_names .= bp_core_get_user_displayname( $user );
		
		if(count($users) > 1 && $ii < count($users)){
			
		if ( (count($users)-1) == ($ii+1)) $user_names .= __(' and ', 'buddypress-introduce' );
			elseif( ($ii + 1) < count($users) ) $user_names .= ', ';
		}
		
		$ii++;
	}

	$all_requests_link = bp_core_get_user_domain( $user_id ) . BP_FRIENDS_SLUG . '/introduce/';
	$settings_link = bp_core_get_user_domain( $user_id ) .  BP_SETTINGS_SLUG . '/notifications';

	$initiator_link = bp_core_get_user_domain( $initiator_id );

	// Set up and send the message
	$to       = $ud->user_email;
	$sitename = wp_specialchars_decode( get_blog_option( BP_ROOT_BLOG, 'blogname' ), ENT_QUOTES );
	
$text_persons = _n( __( 'the following person', 'buddypress-introduce'), __('following people', 'buddypress-introduce'), count( $users ) );
$subject  = '[' . $sitename . '] ' . sprintf( __( '%s has introduced you %s.', 'buddypress-introduce' ), $initiator_name, $text_persons );

	$message = sprintf( __(
	"%s has introduced you %s.
		
%s
		
Here you can see all open requests: %s

To view %s's Profile: %s

---------------------
", 'buddypress-introduce' ), $initiator_name, $text_persons, $user_names,  $all_requests_link,$initiator_name, $initiator_link );

	$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );

	/* Send the message */
	$to = apply_filters( 'friends_notification_new_request_to', $to );
	$subject = apply_filters( 'friends_notification_new_request_subject', $subject, $initiator_name );
	$message = apply_filters( 'friends_notification_new_request_message', $message, $initiator_name, $initiator_link, $all_requests_link );

	wp_mail( $to, $subject, $message );
}

/*
 * Ajax
*/

function bp_introduce_autocomplete_friends()
{
	global $wpdb, $bp;
	
	$search = like_escape( $wpdb->escape( $_GET['q'] ) );
	$data = friends_search_friends( $_GET['q'], $bp->loggedin_user->id, $_GET['limit'] );

	foreach ( (array)$data['friends'] as $friend ) {
		if( isset($bp->action_variables['1']) && $bp->action_variables['1'] == bp_core_get_username( $friend ))
			continue;
		
		echo bp_core_fetch_avatar( array( 'item_id' => $friend, 'type' => 'thumb', 'width' => 15, 'height' => 15 ) ) .' &nbsp;'. bp_core_get_user_displayname( $friend ) .' ('. bp_core_get_username( $friend ) .')
		';
	}
	
	die();
}
add_action( 'wp_ajax_bp_introduce_autocomplete_friends', 'bp_introduce_autocomplete_friends' );

?>