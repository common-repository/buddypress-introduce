<?php do_action( 'bp_introduce_before_add_user_screen_content' ) ?>

<form action="" method="post" id="invite-event-form" class="standard-form">
	<h3><?php _e('Introduce this user', 'buddypress-introduce'); ?></h3>
	<ul class="item-list">
		<li>
			<div class="item-avatar">
				<a href="<?php bp_introduce_user_link() ?>"><?php bp_introduce_user_avatar() ?></a>
			</div>

			<div class="item">
				<div class="item-title"><a href="<?php bp_introduce_user_link() ?>"><?php bp_introduce_user_name() ?></a></div>
				<div class="item-meta"><span class="activity"><?php bp_introduce_user_last_active() ?></span></div>
			</div>

		</li>
	</ul>
	
	<?php wp_nonce_field( 'introduce' ) ?>
		
	<h3><?php _e('Please  search here your contact', 'buddypress-introduce'); ?>  &nbsp; <span class="ajax-loader"></span> </h3>
	<ul class="first acfb-holder">
		<li>
			<?php bp_introduce_get_recipient_tabs() ?>
			<div style="clear: both;"><input type="text" name="invitations" class="send-to-input" id="send-to-input" /></div>
		</li>
	</ul>
		
	<input type="hidden" name="send_to_usernames" id="send-to-usernames" value="<?php bp_introduce_recipient_usernames(); ?>" class="<?php bp_introduce_recipient_usernames() ?>" />
					
	<h3><?php _e('Your message', 'buddypress-introduce'); ?></h3>
	<div>
		<textarea name="message"><?php bp_introduce_user_message() ?></textarea>
	</div>
	<div>
		<label><input type="checkbox" name="email" <?php bp_introduce_user_email_state() ?> /> <?php bp_introduce_user_name() ?> <?php _e('informed by e-mail', 'buddypress-introduce'); ?></label>
	</div>
		
	<div class="dir-submit">
		<input type="submit" value="<?php _e( 'Send Introduction', 'buddypress-introduce' ) ?>" id="send-invites" name="send-invites" />
	</div>
		
</form>

<?php do_action( 'bp_introduce_after_add_user_screen_content' ) ?>
