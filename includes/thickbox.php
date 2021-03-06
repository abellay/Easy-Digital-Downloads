<?php

function edd_media_button($context) {
	global $pagenow, $typenow;
	$output = '';

	/** Only run in post/page creation and edit screens */
	if ( in_array( $pagenow, array( 'post.php', 'page.php', 'post-new.php', 'post-edit.php' ) ) && $typenow != 'download' ) {
		$img = '<img src="' . EDD_PLUGIN_URL . 'includes/images/media-button.png" alt="' . __('Insert Download', 'edd') . '"/>';
		$output = '<a href="#TB_inline?width=640&inlineId=choose-download" class="thickbox" title="' . __('Insert Download', 'edd') . '">' . $img . '</a>';
	}
	return $context . $output;
}
add_filter('media_buttons_context', 'edd_media_button');


function edd_admin_footer_for_thickbox() {
	global $pagenow, $typenow;

	/** Only run in post/page creation and edit screens */
		
	if ( in_array( $pagenow, array( 'post.php', 'page.php', 'post-new.php', 'post-edit.php' ) ) && $typenow != 'download' ) {
		$downloads = get_posts(array('post_type' => 'download', 'posts_per_page' => -1));
		
		?>
		<script type="text/javascript">
			function insertDownload() {
				var id = jQuery('#select-edd-download').val();
				var style = jQuery('#select-edd-style').val();
				if(jQuery('#select-edd-color').is(':visible')) {
					var color = jQuery('#select-edd-color').val();
				} else {
					var color = '';
				}
				var text = jQuery('#edd-text').val();
				
				/** Return early if no slider is selected */
				if ( '' == id ) {
					alert('<?php _e("You must choose a download", "edd"); ?>');
					return;
				}

				/** Send the shortcode to the editor */
				window.send_to_editor('[purchase_link id="' + id + '" style="' + style + '" color="' + color + '" text="' + text + '"]');
			}
			jQuery(document).ready(function($){
				$('#select-edd-style').change(function() {
					if($(this).val() == 'button') {
						$('#edd-color-choice').slideDown();
					} else {
						$('#edd-color-choice').slideUp();
					}
				});
			});
		</script>

		<div id="choose-download" style="display: none;">
			<div class="wrap" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;">
			<?php
			if($downloads) {
			?>
				<p><?php _e('Use the form below to insert the short code for purchasing a download.', 'edd'); ?></p>
				<div>
					<select id="select-edd-download" style="clear: both; display: block; margin-bottom: 1em;">
						<option value=""><?php _e('Choose a download', 'edd'); ?></option>
						<?php
							foreach ( $downloads as $download )
								echo '<option value="' . absint( $download->ID ) . '">' . esc_attr( $download->post_title ) . '</option>';
						?>
					</select>
				</div>
				<div>
					<select id="select-edd-style" style="clear: both; display: block; margin-bottom: 1em;">
						<option value=""><?php _e('Choose a style', 'edd'); ?></option>
						<?php
							$styles = array('button', 'text link');
							foreach ( $styles as $style )
								echo '<option value="' . $style . '">' . $style . '</option>';
						?>
					</select>
				</div>
				<div id="edd-color-choice" style="display: none;">
					<select id="select-edd-color" style="clear: both; display: block; margin-bottom: 1em;">
						<option value=""><?php _e('Choose a button color', 'edd'); ?></option>
						<?php
							$colors = edd_get_button_colors();
							foreach ( $colors as $color )
								echo '<option value="' . str_replace(' ', '_', $color) . '">' . $color . '</option>';
						?>
					</select>
				</div>
				<div>
					<input type="text" class="regular-text" id="edd-text" value="" placeholder="<?php _e('Link text . . .', 'edd'); ?>"/>
				</div>
				<p class="submit">
					<input type="button" id="edd-insert-download" class="button-primary" value="<?php _e('Insert Download', 'edd'); ?>" onclick="insertDownload();" />
					<a id="edd-cancel-download-insert" class="button-secondary" onclick="tb_remove();" title="<?php _e('Cancel', 'edd'); ?>"><?php _e('Cancel', 'edd'); ?></a>
				</p>
				<p><?php _e('Button Styles', 'edd'); ?></p>
				<p><img src="<?php echo EDD_PLUGIN_URL; ?>includes/images/button-previews.jpg"/></p>
			</div>
		</div>
		<?php
		} else {
		
		}
	}
}
add_action('admin_footer', 'edd_admin_footer_for_thickbox');