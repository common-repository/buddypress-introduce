<?php do_action( 'bp_introduce_before_screen_content' ) ?>

<?php if ( bp_introduce_has_introduces( bp_ajax_querystring( 'buddypress-introduce' ) . 'type=own'  ) ) : ?>
	
	<h3><?php _e('Introduced', 'buddypress-introduce'); ?></h3>

	<div id="pag-top" class="introduced-pagination">

		<div class="pag-count" id="introduced-dir-count-top">
			<?php bp_introduced_pagination_count() ?>
		</div>

		<div class="pagination-links" id="introduced-dir-pag-top">
			<?php bp_introduced_pagination_links() ?>
		</div>

	</div>
	
	<ul id="friend-list" class="item-list introduced">
		<?php while ( bp_introduce() ) : bp_the_introduce(); ?>
	
			<li class="introduce-head" id="introduce-<?php bp_introduce_id() ?>">	
				
				<div class="item">
					<div class="item-desc">
						<?php _e('Message:', 'buddypress-introduce' ) ?>
						<div><?php bp_introduce_message() ?></div>	
					</div>
				</div>
			
				<?php bp_introduce_user( true ) ?>
				
			</li>
			
		<?php endwhile; ?>
		</ul>

	<?php do_action( 'bp_introduce_requests_content' ) ?>

<?php else: ?>

	<div id="message" class="info">
		<p><?php _e( 'You have no pending friendship requests.', 'buddypress-introduce' ); ?></p>
	</div>

<?php endif;?>

<?php do_action( 'bp_introduce_after_screen_content' ) ?>
