<?php
	$user_id = get_current_user_id();
	$pkg = get_user_meta( $user_id, 'rem_latest_package', true );
	if ($pkg != '' && is_array($pkg)) {
		$_pkg = wc_get_product( $pkg['woo_product_id'] );
?>
	<table class="table table-striped">
	 	<tr>
	 		<th><?php _e( 'Your Package is : ', '' ) ?></th>
	 		<td><?php echo '<b><a href="'.get_permalink( $pkg['woo_product_id'] ).'" >'.$_pkg->name.'</a></b>'; ?></td>
	 	</tr>
	 	<tr>
	 		<th><?php echo _e( 'Properties Allow : ', '' ); ?></th>
	 		<td><?php echo 	$pkg['count']; ?></td>
	 	</tr>
	 	<tr>
	 		<th><?php echo _e( 'Your Properties : ', '' ); ?></th>
	 		<td><?php echo count_user_posts( $user_id, 'rem_property' ); ?></td>
	 	</tr>
	 	<tr>
	 		<th><?php echo _e( 'Purchased Date : ', '' ); ?></th>
	 		<td><?php printf( _x( '%s ago', '%s = human-readable time difference', 'woo-estato' ), human_time_diff( $pkg['time'], current_time( 'timestamp' ) ) ); ?></td>
	 	</tr>
	 	<tr>
	 		<th><?php echo _e( 'Valid Till : ', '' ); ?></th>
	 		<td>
	 			<?php 
		 			$settings = get_option( 'rem_woo_packages' );
					$s_type = $settings['subscription_type'];
					$valid_time = '';
					if ($s_type == 'monthly') {
						$valid_time = strtotime('+30 days', $pkg['time']);
					}
					if ($s_type == 'annually') {
						$valid_time = strtotime('+365 days', $pkg['time']);
					}									
					echo date('d-M-Y', $valid_time);
	 			?>
	 		</td>
	 	</tr>
	</table>
<?php } else {
			echo __( 'No Package Purchased', 'woo-estato' );
} ?>