<?php

abstract class Introduce_Base {
	
	protected $id;
	protected $state;
	
	const state_open = 0;
	const state_close = 1;
	const state_rejected = 2;	
	
	protected function get_state( $type ){
	
		if( $type == "open" )
			$state = self::state_open;
		elseif( $type == "close"  )
			$state = self::state_close;
		elseif( $type == "rejected" )
			$state = self::state_rejected;

		return $state;
	}
}

class Introduce_User extends Introduce_Base {
	
	private $introduce_id;
	private $user_id;
	private $user_accepted;
	private $introduce_user_id;
	private $introduce_user_accepted;
	
	function __construct( $id = null ) {
		if ( $id ) {
			$this->id = $id;
			$this->populate();
		}
	}

	function populate() {
		global $wpdb, $bp;

		if ( $data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->introduce->user_table_name} WHERE id = %d", $this->id ) ) ) {
			$this->introduce_id = $data->introduce_id;
			$this->user_id = $data->user_id;
			$this->user_accepted = $data->user_accepted;
			$this->introduce_user_id = $data->introduce_user_id;
			$this->introduce_user_accepted = $data->introduce_user_accepted;
			$this->state = $data->state;
		}
	}
	
	function save() {
		global $wpdb, $bp;

		do_action( 'introduce_user_before_save', $this );

		if ( $this->id ) {
			$sql = $wpdb->prepare(
				"UPDATE {$bp->introduce->user_table_name} SET
					introduce_id = %d,
					user_id = %d,
					user_accepted = %s,
					introduce_user_id = %d,
					introduce_user_accepted = %s,
					state = %d
				WHERE
					id = %d
				",
				$this->introduce_id,
				$this->user_id,
				$this->user_accepted,
				$this->introduce_user_id,
				$this->introduce_user_accepted,
				$this->state,
				$this->id
				);
		} else {
			$sql = $wpdb->prepare(
				"INSERT INTO {$bp->introduce->user_table_name} (
					introduce_id,
					user_id,
					user_accepted,
					introduce_user_id,
					introduce_user_accepted,
					state
				) VALUES (
					%d, %d, %s, %d, %s, %d
				)",
				$this->introduce_id,
				$this->user_id,
				$this->user_accepted,
				$this->introduce_user_id,
				$this->introduce_user_accepted,
				self::state_open
				);
		}

		if ( false === $wpdb->query($sql) )
			return false;

		if ( !$this->id ) {
			$this->id = $wpdb->insert_id;
		}

		do_action( 'introduce_user_after_save', $this );

		return true;
	}

	function delete() {
		global $wpdb, $bp;

		// Finally remove the project entry from the DB
		if ( !$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->introduce->user_table_name} WHERE id = %d", $this->id ) ) )
			return false;

		return true;
	}
	
	public static function save_user($introduce_id, $user_id, $introduce_user_id ){
	
		$introduce_user = new Introduce_User();
		$introduce_user->introduce_id =$introduce_id;
		$introduce_user->user_id = $introduce_user_id;
		$introduce_user->introduce_user_id = $user_id;
		
		if( !self::check_users($introduce_user_id, $user_id) )
			$introduce_user->save();
		
		return true;
	}
	
	public static function check_users($user_id, $introduce_user_id ){
		global $wpdb, $bp;

		$data = $wpdb->get_row( $sql = $wpdb->prepare( "SELECT * FROM {$bp->introduce->user_table_name} WHERE (( user_id = %d AND introduce_user_id = %d ) OR  ( user_id = %d AND introduce_user_id = %d )) AND state = 0 ", $user_id, $introduce_user_id, $introduce_user_id, $user_id ) ) ;
		
		if(!empty( $data )) return true;
	
		return false;
	}
	
	public static function get_users ( $introduce_id, $user_id = null ){
		global $wpdb, $bp;
		
		if( empty( $user_id ) )
			$data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->introduce->user_table_name} WHERE introduce_id = %d ",  $introduce_id ) ) ;
		else {
			$data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->introduce->user_table_name} WHERE introduce_id = %d AND ( user_id = %d OR introduce_user_id = %d ) AND state = %d ",  $introduce_id, $user_id, $user_id, self::state_open ) ) ;
			
			foreach ( $data as $user ){
				if( friends_check_friendship( $user->user_id, $user->introduce_user_id )){
				
					$foo = new Introduce_User( $user->id );
					$foo->introduce_user_accepted = $foo->user_accepted  = current_time('mysql');
					$foo->state = self::state_close;
					$foo->save();
					
					bp_core_redirect( $bp->loggedin_user->domain . $bp->friends->slug . "/" . $bp->introduce->id, 302 );
						
				}	
			}			
		}
		
		return $data;
	}
		
	public static function user_accept( $id ){
		global $bp;		
		
		if( empty( $bp->loggedin_user->id ) ) return false;
		
		$introduce_user = null;
		
		$user = new Introduce_User( $id );
		$user_id = bp_core_get_userid( $user_name );
		
		if( $bp->loggedin_user->id == $user->user_id  ){
			if ( empty( $user->user_accepted ) || $user->user_accepted == '0000-00-00 00:00:00'){
				$user->user_accepted = current_time('mysql');
				$introduce_user = $user->introduce_user_id;
			}
		} else {
			if(empty( $user->introduce_user_accepted ) || $user->introduce_user_accepted == '0000-00-00 00:00:00'){
				$user->introduce_user_accepted = current_time('mysql');
				$introduce_user = $user->user_id;
			}
		}
		
		// Offen
		if ( $user->state == self::state_open && !empty( $introduce_user ) ) {
			if( !empty( $user->user_accepted ) && $user->user_accepted != '0000-00-00 00:00:00' && 
				!empty( $user->introduce_user_accepted ) && $user->introduce_user_accepted != '0000-00-00 00:00:00' ){
				$user->state = self::state_close;
				
				friends_add_friend( $introduce_user, $bp->loggedin_user->id, true);
				bp_core_add_notification( $bp->loggedin_user->id, $introduce_user, $bp->introduce->id, 'user_accept_close' );
			} else{
				bp_core_add_notification( $bp->loggedin_user->id, $introduce_user, $bp->introduce->id, 'user_accept' );
			}
				
			$user->save();						
			Introduce::close( $user->introduce_id );
			return true;	
		}
		
		return false;
	}
	
	public static function user_reject( $id ){
		global $bp;		
			
		if( empty( $bp->loggedin_user->id ) ) return false;
		
		$introduce_user = null;	
		
		$user = new Introduce_User( $id );
		$user_id = bp_core_get_userid( $user_name );
			
		if( $bp->loggedin_user->id == $user->user_id  ){
			if ( empty( $user->user_accepted ) || $user->user_accepted == '0000-00-00 00:00:00'){
				$user->user_accepted = current_time('mysql');
				$introduce_user = $user->introduce_user_id;
			}
		} else {
			if(empty( $user->introduce_user_accepted ) || $user->introduce_user_accepted == '0000-00-00 00:00:00'){
				$user->introduce_user_accepted = current_time('mysql');
				$introduce_user = $user->user_id;
			}
		}
		
		if ( $user->state == self::state_open ) {
			$user->state = self::state_rejected;	
			$user->save();	
			
			bp_core_add_notification( $bp->loggedin_user->id, $introduce_user, $bp->introduce->id, 'user_rejected' );
			Introduce::close( $user->introduce_id );
			return true;
		}
		
		return false;
	}
}

class Introduce extends Introduce_Base {
	
	private $initiator_user_id;
	private $message;
	private $email_message;
	private $date_created;
	
	function __construct( $id = null ) {
		if ( $id ) {
			$this->id = $id;
			$this->populate();
		}
	}

	function populate() {
		global $wpdb, $bp;

		if ( $data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->introduce->base_table_name} WHERE id = %d", $this->id ) ) ) {
			$this->initiator_user_id = $data->initiator_user_id;
			$this->message = $data->message;
			$this->email_message = $data->email_message;
			$this->date_created = $data->date_created;
			$this->state = $data->state;
		}
	}
	
	function save() {
		global $wpdb, $bp;

		do_action( 'introduce_before_save', $this );

		if ( $this->id ) {
			$sql = $wpdb->prepare(
				"UPDATE {$bp->introduce->base_table_name} SET
					initiator_user_id = %d,
					message = %s,
					email_message = %d,
					date_created = %s,
					state = %d
				WHERE
					id = %d
				",
				$this->initiator_user_id,
				$this->message,
				$this->email_message,
				$this->date_created,
				$this->state,
				$this->id
				);
		} else {
			$sql = $wpdb->prepare(
				"INSERT INTO {$bp->introduce->base_table_name} (
					initiator_user_id,
					message,
					email_message,
					date_created,
					state
				) VALUES (
					%d, %s, %d, %s, %d
				)",
				$this->initiator_user_id,
				$this->message,
				$this->email_message,
				current_time('mysql'),
				self::state_open
				);
		}
		
		if ( false === $wpdb->query($sql) )
			return false;

		if ( !$this->id ) {
			$this->id = $wpdb->insert_id;
		}

		do_action( 'introduce_after_save', $this );

		return $this;
	}

	function delete() {
		global $wpdb, $bp;
		
		// Finally remove the introduce entry from the DB
		if ( !$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->introduce->base_table_name} WHERE id = %d", $this->id ) ) )
			return false;

		// Finally remove the introduce user entrys from the DB
		if ( !$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->introduce->user_table_name} WHERE introduce_id = %d", $this->id ) ) )
			return false;
		
		return true;
	}
	
	public static function close( $id ){
		global $bp, $wpdb;
			
		$introduce = new Introduce( $id );
		$state = self::state_open;
		
		if( $introduce->state != self::state_open )
			return true;
		
		$sql = "SELECT id FROM {$bp->introduce->user_table_name} WHERE state = {$state} AND introduce_id = {$id} ";
		$result = $wpdb->get_results( $sql );
				
		if( empty( $result )){
			$introduce->state = self::state_close;
			$introduce->save();
		}
		
		return $introduce->state;		
	}
	
	public static function save_form_data($users, $message, $email){
		global $bp;
		
		$add_user = array();
		
		$introduce = new Introduce();
		$introduce->initiator_user_id = $bp->loggedin_user->id;
		$introduce->message = $message;
		$introduce->email_message = !empty( $email ) ? 1 : 0 ;
		$introduce->save();

		$introduce_user_id = bp_core_get_userid( $bp->action_variables['1'] );
		$user_names = explode( " ", $users );
		
		foreach( $user_names as $user_name ){
			$user_id = bp_core_get_userid( $user_name );
			//users are friends
			if( friends_check_friendship ( $introduce_user_id, $user_id ) ) continue;
			//introduce exsists
			if( Introduce_User::check_users ( $introduce_user_id, $user_id )) continue;
			
			Introduce_User::save_user($introduce->id, $introduce_user_id, $user_id);									
			$add_user[] = $user_id; 	
		}
				
		if(empty( $add_user ))
			$introduce->delete();
		else {
			
			if ( $introduce->email_message ) {
				bp_introduce_notification_user_add ( $introduce->initiator_user_id, $introduce_user_id, $add_user,  $introduce->message );
			}
				
			foreach( $add_user as $user_id ) {
				bp_core_add_notification( $introduce_user_id, $user_id, $bp->introduce->id, 'introduce_user',  $introduce->initiator_user_id );
				bp_core_add_notification( $user_id, $introduce_user_id, $bp->introduce->id, 'introduce_user',  $introduce->initiator_user_id );
			}
		}			
		
		return true;
	}
	
	static function get_introduces( $type, $limit = null, $page = 1, $user_id = false, $include = false, $search_terms = false, $populate_extras = true ) {
		global $wpdb, $bp;

		$sql = array();
		
		$state = self::get_state( $type );
		$user_state	= self::state_open;
		
		$sql['head'] = "SELECT DISTINCT b.* ";
		$sql['main_table'] = "FROM {$bp->introduce->base_table_name} as b ";
		$sql['join_table'] = "LEFT JOIN {$bp->introduce->user_table_name} as u ON b.id = u.introduce_id ";
		$sql['where'] = "WHERE (( u.user_id = {$user_id} || u.introduce_user_id = {$user_id} ) AND u.state = {$user_state}) AND b.state = {$state} ";
		$sql['order'] = "ORDER BY b.date_created  ";
		
		if( $type == 'own'){
			unset ( $sql['join_table'] );
			$sql['where'] = "WHERE b.initiator_user_id = {$user_id} ";	
		}
				
		if ( $limit && $page )
			$sql['pagination'] = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
				
		/* Get paginated results */
		$paged_introduce_sql = join( ' ', (array) $sql );
		$paged_introduces = $wpdb->get_results( $paged_introduce_sql );
		
		if ( $limit && $page ){
			unset ( $sql['pagination'] );
			$sql['head'] = "SELECT DISTINCT COUNT(b.id) ";
			$total_introduces = $wpdb->get_var(  join( ' ', (array) $sql ) ) ;
		} else {
			$total_introduces = count( $paged_introduces ) ;
		}
					
		/***
		 * Lets fetch some other useful data in a separate queries, this will be faster than querying the data for every user in a list.
		 * We can't add these to the main query above since only users who have this information will be returned (since the much of the data is in usermeta and won't support any type of directional join)
		 */
		if ( $populate_extras ) {
			foreach ( (array)$paged_introduces as $key => $introduce ){
				$introduce_user_id = $introduce->initiator_user_id == $user_id ? null : $user_id;
				$paged_introduces[$key]->users = Introduce_User::get_users( $introduce->id, $introduce_user_id );
			}
		}
				
		return array( 'introduces' => $paged_introduces, 'total' => $total_introduces );
	}
}

?>