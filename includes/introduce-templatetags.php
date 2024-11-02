<?php

class BP_Introduce_Template {
	
	var $current_introduce = -1;
	var $introduce_count;
	var $introduces;
	var $introduce;

	var $in_the_loop;

	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_introduce_count;

	function __construct( $type, $page_number, $per_page, $max, $user_id, $search_terms, $include, $populate_extras ) {
		global $bp;
		
		$this->pag_page = isset( $_REQUEST['upage'] ) ? intval( $_REQUEST['upage'] ) : $page_number;
		$this->pag_num  = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $per_page;
		$this->type     = $type;

		$this->introduces = bp_introduce_get_introduces( array( 'type' => $this->type, 'per_page' => $this->pag_num, 'page' => $this->pag_page, 'user_id' => $user_id, 'include' => $include, 'search_terms' => $search_terms, 'populate_extras' => $populate_extras ) );

		if ( !$max || $max >= (int)$this->introduces['total'] )
			$this->total_introduce_count = (int)$this->introduces['total'];
		else
			$this->total_introduce_count = (int)$max;

		$this->introduces = $this->introduces['introduces'];

		if ( $max ) {
			if ( $max >= count($this->introduces) ) {
				$this->introduce_count = count( $this->introduces );
			} else {
				$this->introduce_count = (int)$max;
			}
		} else {
			$this->introduce_count = count( $this->introduces );
		}

		if ( (int)$this->total_introduce_count && (int)$this->pag_num ) {
			$this->pag_links = paginate_links( array(
				'base'      => add_query_arg( 'upage', '%#%' ),
				'format'    => '',
				'total'     => ceil( (int)$this->total_introduce_count / (int)$this->pag_num ),
				'current'   => (int)$this->pag_page,
				'prev_text' => '&larr;',
				'next_text' => '&rarr;',
				'mid_size'  => 1
			) );
		}
	}

	function has_introduces() {
		if ( $this->introduce_count )
			return true;

		return false;
	}

	function next_introduce() {
		$this->current_introduce++;
		$this->introduce = $this->introduces[$this->current_introduce];

		return $this->introduce;
	}

	function rewind_introduces() {
		$this->current_introduce = -1;
		if ( $this->introduce_count > 0 ) {
			$this->introduce = $this->introduces[0];
		}
	}

	function introduces() {
		if ( $this->current_introduce + 1 < $this->introduce_count ) {
			return true;
		} elseif ( $this->current_introduce + 1 == $this->introduce_count ) {
			do_action('loop_end');
			// Do some cleaning up after the loop
			$this->rewind_introduces();
		}

		$this->in_the_loop = false;
		return false;
	}

	function the_introduce() {
		global $introduce, $bp;

		$this->in_the_loop = true;
		$this->introduce = $this->next_introduce();

		if ( 0 == $this->current_introduce ) // loop has just started
			do_action('loop_start');
	}
}


function bp_the_introduce() {
	global $introduce_template;
	return $introduce_template->the_introduce();
}

function bp_introduce() {
	global $introduce_template;
	return $introduce_template->introduces();
}

function bp_introduce_has_introduces( $args = '' ) {
	global $bp, $introduce_template;

	// User filtering
	if ( !empty( $bp->displayed_user->id ) )
		$user_id = $bp->displayed_user->id;

	// Pass a filter if ?s= is set.
	if ( isset( $_REQUEST['s'] ) && !empty( $_REQUEST['s'] ) )
		$search_terms = $_REQUEST['s'];
	
	// type: open ( default ) | close | rejected | own 
	$defaults = array(
		'type' => 'open',
		'page' => 1,
		'per_page' => 20,
		'max' => false,
		'include' => false, // Pass a user_id or comma separated list of user_ids to only show these users
		'user_id' => $user_id, // Pass a user_id to only show friends of this user
		'search_terms' => false, // Pass search_terms to filter users by their profile data
		'populate_extras' => true // Fetch usermeta? Friend count, last active etc.
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r );

	if ( $max ) {
		if ( $per_page > $max )
			$per_page = $max;
	}
	
	$introduce_template = new BP_Introduce_Template( $type, $page, $per_page, $max, $user_id, $search_terms, $include, (bool)$populate_extras );
	//print_r( $introduce_template );
	return apply_filters( 'bp_has_introduces', $introduce_template->has_introduces(), &$introduce_template );
}

function bp_introduce_user_object(){
	global $bp, $introduce_user;
	
	if( empty( $introduce_user ) ){
		$user_id = bp_core_get_userid_from_nicename( $bp->action_variables['1'] );
		$introduce_user = new BP_Core_User( $user_id );
	}
}

function bp_introduce_user_link(){
	echo bp_introduce_get_user_link();
}

function bp_introduce_get_user_link(){
	global $introduce_user;
	bp_introduce_user_object();
	return apply_filters( 'bp_introduce_get_user_link', bp_core_get_user_domain( $introduce_user->id,$introduce_user->user_nicename, $introduce_user->user_login ) );
}

function bp_introduce_user_avatar( $args = null ){
	echo bp_introduce_get_user_avatar( $args );
}

function bp_introduce_get_user_avatar( $args = null ){
	global $introduce_user;
	bp_introduce_user_object();
	
	$defaults = array(
		'type' => 'full',
		'width' => 75,
		'height' => 75,
		'class' => 'avatar',
		'id' => false,
		'alt' => __( 'Member avatar', 'buddypress-introduce' )
		);
	
	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	
	return apply_filters( 'bp_introduce_get_user_avatar', bp_core_fetch_avatar( array( 'item_id' => $introduce_user->id, 'type' => $type, 'alt' => $alt, 'css_id' => $id, 'class' => $class, 'width' => $width, 'height' => $height, 'email' => $introduce_user->user_email ) ) );
}

function bp_introduce_user_name(){
	echo bp_introduce_get_user_name();
}

function bp_introduce_get_user_name(){
	global $introduce_user;
	bp_introduce_user_object();

	return apply_filters( 'bp_introduce_get_user_name', bp_core_get_user_displayname($introduce_user->id) );
}

function bp_introduce_user_last_active(){
	echo bp_introduce_get_user_last_active();
}

function bp_introduce_get_user_last_active(){
	global $introduce_user;
	bp_introduce_user_object();
	return apply_filters( 'bp_introduce_get_user_last_active', $introduce_user->last_active);
}

function bp_introduce_user_message(){
	echo $_POST['message'];
}

function bp_introduce_user_email_state(){
	if($_POST['email'])
		echo 'checked="checked"';
}

function bp_introduce_get_recipient_tabs() {
	global $bp;

	if ( isset( $_POST['send_to_usernames'] ) ) {
		
		$data = explode(" ",  $_POST['send_to_usernames'] );
		
		foreach( $data as $user_name ){		
			$user_id = bp_core_get_userid( $user_name );

			if ( $user_id ) {
				?>
				<li id="un-<?php echo $user_name ?>" class="friend-tab">
					<span>
						<?php echo bp_core_fetch_avatar( array( 'item_id' => $user_id, 'type' => 'thumb', 'width' => 15, 'height' => 15 ) ) ?>
						<?php echo bp_core_get_user_displayname ( $user_id ) ?>
					</span>
					<span class="p">X</span>
				</li>
				<?php
			}
		}
	}
}

function bp_introduce_recipient_usernames() {
	echo bp_introduce_get_recipient_usernames();
}

function bp_introduce_get_recipient_usernames() {
	return apply_filters( 'bp_introduce_get_recipient_usernames', $_POST['send_to_usernames'] );
}

function bp_introduce_id() {
	echo bp_introduce_get_id();
}

function bp_introduce_get_id() {
	global $introduce_template;
	return apply_filters( 'bp_introduce_get_id', $introduce_template->introduce->id );
}

function bp_introduce_initiator_link(){
	echo bp_introduce_get_initiator_link();
}

function bp_introduce_get_initiator_link(){
	global $introduce_template;	
	$initiator = bp_core_get_core_userdata( $introduce_template->introduce->initiator_user_id );
	return apply_filters( 'bp_introduce_get_initiator_link', bp_core_get_user_domain( $initiator->ID, $initiator->user_nicename, $initiator->user_login ));
}

function bp_introduce_initiator_avatar( $args = null){
	echo bp_introduce_get_initiator_avatar( $args );
}

function bp_introduce_get_initiator_avatar( $args = null ){
	global $introduce_template;	
	
	$defaults = array(
		'type' => 'full',
		'width' => 50,
		'height' => 50,
		'class' => 'avatar',
		'id' => false,
		'alt' => __( 'Member avatar', 'buddypress-introduce' )
		);
	
	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	
	return apply_filters( 'bp_introduce_get_initiator_avatar', bp_core_fetch_avatar( array( 'item_id' => $introduce_template->introduce->initiator_user_id, 'type' => $type, 'alt' => $alt, 'css_id' => $id, 'class' => $class, 'width' => $width, 'height' => $height ) ) );
}

function bp_introduce_initiator_name(){
	echo bp_introduce_get_initiator_name();
}

function bp_introduce_get_initiator_name(){
	global $introduce_template;	
	//$initiator = bp_core_get_core_userdata( );
	return apply_filters( 'bp_introduce_get_initiator_link', bp_core_get_user_displayname( $introduce_template->introduce->initiator_user_id ));
}

function bp_introduce_date_created(){
	echo bp_introduce_get_date_created();
}

function bp_introduce_get_date_created(){
	global $introduce_template;	
	return apply_filters( 'bp_introduce_get_initiator_link', mysql2date("d.m.Y", $introduce_template->introduce->date_created, "DATETIME" ));
}

function bp_introduce_message(){
	echo bp_introduce_get_message();
}

function bp_introduce_get_message(){
	global $introduce_template;	
	return apply_filters( 'bp_introduce_get_initiator_link', $introduce_template->introduce->message );
}

function bp_introduce_user( $own = false ){
	echo bp_introduce_get_user( $own );
}

function _bp_introduce_get_user_html( $user_id, $user_accepted ){
	
	$html = array();
	
	$html[] = '<div class="item-avatar">';
	$html[] = bp_introduce_get_avatar( "id=" . $user_id );
	$html[] = '</div>';
		
	$user_data = bp_core_get_core_userdata($user_id );

	$html[] = '<div class="item">';
	$html[] = '<div class="item-title"><a href="' .  bp_core_get_user_domain( $user_data->ID, $user_data->user_nicename, $user_data->user_login ) . '">' . bp_core_get_user_displayname( $user_id ) . '</a></div>';
	$html[] = '<div class="item-meta"><span class="activity">' . bp_last_activity( $user_id, false ) . '</span></div>';
	$html[] = '</div>';
	
	if( isset( $user_accepted ) && $user_accepted != "0000-00-00 00:00:00"){
		$html[] = "<em><small>";
		$html[] = sprintf( __( '%s accepted the request.', 'buddypress-introduce' ), bp_core_time_since(  $user_accepted ) );
		$html[] = "</small></em>";
	}
	
	return implode( $html, "\n" );
}


function bp_introduce_get_user( $own = false ){
	global $introduce_template, $bp;	
	$html = array();	
	
	$introduce = $introduce_template->introduce;

	foreach( $introduce->users as $user){
		
		$html[] ="<li>";
	
		if($bp->loggedin_user->id == $user->user_id || $bp->loggedin_user->id == $user->introduce_user_id ){
			
			$display_buttons = true;
			$introduce_user_id = null;
			
			if( $bp->loggedin_user->id == $user->user_id ){
				if( isset($user->user_accepted) && $user->user_accepted != "0000-00-00 00:00:00")
					$display_buttons = false;
				
				$introduce_user_id = $user->introduce_user_id;
				
			} else {
				if( isset($user->introduce_user_accepted) && $user->introduce_user_accepted != "0000-00-00 00:00:00")
					$display_buttons = false;
				
				$introduce_user_id = $user->user_id;
			}
			
			$html[] = '<div class="item-avatar">';
			$html[] = bp_introduce_get_avatar( "id=" . $introduce_user_id );
			$html[] = '</div>';
			
			
			$initiator_ud = bp_core_get_core_userdata( $introduce_user_id );

			$html[] = '<div class="item">';
			$html[] = '<div class="item-title"><a href="' .  bp_core_get_user_domain( $initiator_ud->ID, $initiator_ud->user_nicename, $initiator_ud->user_login ) . '">' . bp_core_get_user_displayname( $introduce_user_id ) . '</a></div>';
			$html[] = '<div class="item-meta"><span class="activity">' . bp_last_activity( $introduce_user_id, false ) . '</span></div>';
			$html[] = '</div>';
			
			$html[] = '<div class="action">';

			if( $display_buttons ){
				$html[] = '<a class="button introduce-accept" href="' . bp_introduce_get_accept_request_link( $user->id, $initiator_ud ) . '">' . __( 'Accept', 'buddypress-introduce' ) . '</a> &nbsp;';
				$html[] = '<a class="button introduce-reject" href="' . bp_introduce_get_reject_request_link( $user->id, $initiator_ud ) . '">' . __( 'Reject', 'buddypress-introduce' ) . '</a>';
			} else {
				$html[] = "<em><small>";
				$html[] = __( 'You accepted the request.', 'buddypress-introduce' );
				$html[] = "<br />";
				$html[] = sprintf( __( 'Waiting for %s', 'buddypress-introduce' ),  bp_core_get_user_displayname( $introduce_user_id ));
				$html[] = "</small></em>";
			}
			do_action( 'bp_introduce_requests_item_action' );
			
			$html[] = '</div>';
		} elseif( $own ) {
			
			$class = friends_check_friendship($user->introduce_user_id, $user->user_id) ? "friends" : null;
			$class .= " state-" . $user->state;
			
			$html[] = sprintf( '<div class="own-introduce %s" >', $class );
				$html[] = sprintf( '<div class="left %s %s" >', $class,  isset( $user->introduce_user_accepted ) && $user->introduce_user_accepted != "0000-00-00 00:00:00" ? " accepted" : null );
					$html[] = _bp_introduce_get_user_html( $user->introduce_user_id, $user->introduce_user_accepted );
				$html[] = '</div>';
				$html[] = sprintf( '<div class="right %s %s">', $class,  isset( $user->user_accepted ) && $user->user_accepted != "0000-00-00 00:00:00" ? " accepted" : null );
					$html[] = _bp_introduce_get_user_html( $user->user_id, $user->user_accepted );
				$html[] = '</div>';
			$html[] = '</div>';			
			
		}
		
		$html[] ="</li>";
	}
			
	return implode( $html, "\n" );
}

function bp_introduce_accept_request_link(){
	echo bp_introduce_get_accept_request_link();
}

function bp_introduce_get_accept_request_link( $id, $user_data ){
	global $bp;
	return wp_nonce_url( $bp->loggedin_user->domain . $bp->friends->slug . "/" . $bp->introduce->id . '/accept/' . $id, 'introduce_accept' );
}

function bp_introduce_reject_request_link(){
	echo bp_introduce_get_reject_request_link();
}

function bp_introduce_get_reject_request_link( $id, $user_data ){
	global $bp;
	return wp_nonce_url( $bp->loggedin_user->domain . $bp->friends->slug . "/" . $bp->introduce->id . '/reject/' . $id, 'introduce_reject' );
}

function bp_introduce_avatar( $args = null ){
	echo bp_introduce_get_avatar( $args );
}

function bp_introduce_get_avatar( $args = null ){

	$defaults = array(
		'type' => 'full',
		'width' => 50,
		'height' => 50,
		'class' => 'avatar',
		'id' => false,
		'alt' => __( 'Member avatar', 'buddypress-introduce' )
		);
	
	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	
	
	return apply_filters( 'bp_introduce_get_avatar', bp_core_fetch_avatar( array( 'item_id' => $id, 'type' => $type, 'alt' => $alt, 'css_id' => $id, 'class' => $class, 'width' => $width, 'height' => $height ) ) );
}

function bp_introduced_link(){
	echo bp_introduced_get_link();
}

function bp_introduced_get_link(){
	global $bp;
	
	$html = array();
	$html[] = '<div class="own-introduce">';
	$html[] = '<a href="' . $bp->loggedin_user->domain . $bp->friends->slug . "/" . $bp->introduce->id . '/own/' . '">' . __( 'Introduced', 'buddypress-introduce' ) . '</a>';
	$html[] = '</div>';
	
	return apply_filters( 'bp_introduced_get_link', implode( $html, "\n" ), $html );
}

function bp_introduced_pagination_count() {
	global $bp, $introduce_template;

	$start_num = intval( ( $introduce_template->pag_page - 1 ) * $introduce_template->pag_num ) + 1;
	$from_num = bp_core_number_format( $start_num );
	$to_num = bp_core_number_format( ( $start_num + ( $introduce_template->pag_num - 1 ) > $introduce_template->total_introduce_count ) ? $introduce_template->total_introduce_count : $start_num + ( $introduce_template->pag_num - 1 ) );
	$total = bp_core_number_format( $introduce_template->total_introduce_count );

	echo sprintf( __( 'Viewing introduced %1$s to %2$s (of %3$s introduced)', 'buddypress-introduce' ), $from_num, $to_num, $total );

	?><span class="ajax-loader"></span><?php
}

function  bp_introduced_pagination_links() {
	echo bp_introduced_get_pagination_links();
}

function bp_introduced_get_pagination_links() {
	global $introduce_template;
	return apply_filters( 'bp_introduced_get_pagination_links', $introduce_template->pag_links );
}

?>