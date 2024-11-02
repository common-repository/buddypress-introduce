<?php do_action( 'bp_introduce_before_screen_content' ) ?>

<?php if ( bp_introduce_has_introduces( 'per_page=0' ) ) : ?>
	
	<h3><?php _e('Contact request', 'buddypress-introduce'); ?></h3>

	<ul id="friend-list" class="item-list introduce">
		<?php while ( bp_introduce() ) : bp_the_introduce(); ?>
	
			<li class="introduce-head" id="introduce-<?php bp_introduce_id() ?>">	
				<h3><?php _e('From:', 'buddypress-introduce' ) ?> </h3>
				<div class="item-avatar">
					<a href="<?php bp_introduce_initiator_link() ?>"><?php bp_introduce_initiator_avatar() ?></a>
				</div>

				<div class="item">
					<div class="item-title"><a href="<?php bp_introduce_initiator_link() ?>"><?php bp_introduce_initiator_name() ?></a></div>
					<div class="item-desc">
						<?php _e('Message:', 'buddypress-introduce' ) ?>
						<div><?php bp_introduce_message() ?></div>	
					</div>
				</div>
			
				<?php bp_introduce_user() ?>
				
			</li>
			
		<?php endwhile; ?>
		</ul>

	<?php do_action( 'bp_introduce_requests_content' ) ?>

<?php else: ?>

	<div id="message" class="info">
		<p><?php _e( 'You have no pending friendship requests.', 'buddypress-introduce' ); ?></p>
	</div>

<?php endif;?>

<?php if ( bp_introduce_has_introduces( 'type=own&max=1' ) ) : ?>
	<?php bp_introduced_link() ?>
<?php endif;?>

<?php do_action( 'bp_introduce_after_screen_content' ) ?>
