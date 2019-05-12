<?php

class REM_WOO_ESTATO {
	
	function __construct()
	{
		add_action( 'admin_notices', array($this, 'check_if_rem_activated') );
		add_filter( 'admin_menu', array($this, 'menu_pages') );
		add_action( 'admin_enqueue_scripts', array($this, 'admin_scripts' ) );
		add_action( 'wp_ajax_wcp_rem_save_woo_estato', array($this, 'rem_save_woo_estato' ) );

		add_action( 'woocommerce_order_status_completed', array($this, 'order_completed' ), 10, 1);

		add_filter( 'rem_property_publish_status', array($this, 'limit_user_properties_frontend'), 20, 2 );
		add_filter( 'admin_print_footer_scripts', array( $this, 'remove_capability_publish' ) );
		add_filter( 'post_submitbox_start', array( $this, 'display_alert' ) );
		add_filter( 'rem_create_property_after_submit', array( $this, 'display_alert_front' ) );

		add_shortcode( 'rem_packages', array( $this, 'show_pakage_detail' ) );
	}

	function check_if_rem_activated() {
		if (!class_exists('WCP_Real_Estate_Management')) { ?>
		    <div class="notice notice-info is-dismissible">
		        <p>Please install and activate <a target="_blank" href="https://wordpress.org/plugins/real-estate-manager/">Real Estate Manager</a> for using <strong>Woo Estato</strong></p>
		    </div>
		<?php }
	}

	function admin_scripts($check){
        if ($check == 'rem_property_page_rem_woo_estato') {
        	wp_enqueue_script( 'sweet-alerts', REM_URL . '/assets/admin/js/sweetalert.min.js' , array('jquery'));
            wp_enqueue_style( 'rem-bs-css', REM_URL . '/assets/admin/css/bootstrap.min.css' );
            wp_enqueue_script( 'rem-woo-admin', plugin_dir_url( __FILE__ ).'js/admin.js', array('jquery') );
        }

        if ($check == 'rem_property_page_rem_woo_estato_subs') {
        	wp_enqueue_script( 'sweet-alerts', REM_URL . '/assets/admin/js/sweetalert.min.js' , array('jquery'));
            wp_enqueue_style( 'rem-bs-css', REM_URL . '/assets/admin/css/bootstrap.min.css' );
            wp_enqueue_script( 'rem-woo-subs', plugin_dir_url( __FILE__ ).'js/subscriptions.js', array('jquery') );
        }
	}

	function menu_pages($settings){
	    add_submenu_page( 'edit.php?post_type=rem_property', 'Real Estate Manager - WooCommerce Addon', __( 'Woo Estato Subscriptions', 'real-estate-manager' ), 'manage_options', 'rem_woo_estato_subs', array($this, 'render_woo_estato_subs') );
	    add_submenu_page( 'edit.php?post_type=rem_property', 'Real Estate Manager - WooCommerce Addon', __( 'Woo Estato Settings', 'real-estate-manager' ), 'manage_options', 'rem_woo_estato', array($this, 'render_woo_estato') );
	}

	function render_woo_estato(){
		include 'templates/settings.php';
	}

	function render_woo_estato_subs(){
		include 'templates/subscriptions.php';
	}

	function rem_save_woo_estato(){
		if (isset($_REQUEST)) {
			$resp = array(
				'status' => 'success',
				'message' => '',
			);
			$data_to_save = array(
				'subscription_type' => sanitize_text_field( $_REQUEST['subscription_type'] ),
				'packages' => array(),
			);
			if (isset($_REQUEST['packages']) && is_array($_REQUEST['packages'])) {
				foreach ($_REQUEST['packages'] as $pkg) {
					$data_to_save['packages'][] = array(
						'count' => sanitize_text_field( $pkg['count'] ),
						'woo_product_id' => sanitize_text_field( $pkg['woo_product_id'] ),
					);
				}
			}
			if (update_option( 'rem_woo_packages', $data_to_save )) {
				// geting all users
				$all_users = get_users( $args );
				foreach ($all_users as $user) {
					$user_pkg = $user->rem_latest_package;
					$user_id = $user->ID;
					if ( !empty($user_pkg) ) {
						$woo_product_id = $user_pkg['woo_product_id'];
						$time = $user_pkg['time'];
						// adding data into user's meta
						$new_data = get_option('rem_woo_packages', true);
						if ( is_array($new_data['packages']) ) {
							foreach ( $new_data['packages'] as $pkg ) {
								if ( $pkg['woo_product_id'] == $woo_product_id ) {
									$data = array(
										'time' 	=> $time,
										'count' => sanitize_text_field( $pkg['count'] ),
										'woo_product_id' => sanitize_text_field( $pkg['woo_product_id'] ),
									);
									update_user_meta( $user_id, 'rem_latest_package', $data );
								}
							}
						}
					}
				}

				$resp['status'] = 'success';
				$resp['title'] = __( 'Settings Saved!', 'woo-estato' );
				$resp['message'] = __( 'All settings are saved in database successfully', 'woo-estato' );
			} else {
				$resp['status'] = 'error';
				$resp['title'] = __( 'Oops!', 'woo-estato' );
				$resp['message'] = __( 'There was some error saving your settings, please try again', 'woo-estato' );
			}
			echo json_encode($resp);
			die(0);
		}
	}

	function order_completed($order_id){
		$order = wc_get_order( $order_id );
		$user_id = $order->get_user_id();
		$order_items = $order->get_items();		
		$existing_settings = get_option( 'rem_woo_packages' );
		// Loop through all purchased items to find the rem package item
		if (is_array($order_items)) {
			foreach( $order_items as $item_id => $item_product ){

			    $product_id = $item_product->get_product_id();

			    if (isset($existing_settings['packages']) && is_array($existing_settings['packages']) ) {
			    	foreach ($existing_settings['packages'] as $index => $pkg) {
			    		if ( $pkg['woo_product_id'] == $product_id ) {
			    			
							$this->set_agent_package($user_id, $pkg);
			    		}
			    	}
				}
			}
		}
	}

	function set_agent_package($user_id, $pkg){
		$rem_packages = get_user_meta( $user_id, 'rem_packages', true );

		$data = array(
			'time' 	=> time(),
			'count' => sanitize_text_field( $pkg['count'] ),
			'woo_product_id' => sanitize_text_field( $pkg['woo_product_id'] ),
		);
		update_user_meta( $user_id, 'rem_latest_package', $data );

		if ($rem_packages != '' && is_array($rem_packages)) {
			$rem_packages[] = $data;
		} else {
			$rem_packages = array($data);
		}

		update_user_meta( $user_id, 'rem_packages', $rem_packages );
	}

	function limit_user_properties_frontend($status, $agent_id){
		if ($this->can_publish_properties($agent_id) && $this->is_subscription_valid($agent_id)) {
			return $status;
		} else {
			return 'draft';
		}
	}

	function remove_capability_publish(){
		global $post;

		$currID = is_user_logged_in() ? get_current_user_id() : 0;
		$user = wp_get_current_user();
		if ( $currID && in_array( 'rem_property_agent', (array) $user->roles ) && $currID != 1) {
			if (isset($post->ID) && get_post_status( $post->ID ) != 'publish') {
				if (!$this->can_publish_properties($currID) || !$this->is_subscription_valid($currID)) {
					?>
					<script type="text/javascript">
						jQuery(document).ready(function($){$('#publish').remove();});
					</script>
					<?php
				}
			}
		}
	}

	function display_alert(){
		global $post;
		$currID = is_user_logged_in() ? get_current_user_id() : 0;
		$user = wp_get_current_user();
		if ( $currID && in_array( 'rem_property_agent', (array) $user->roles ) && $currID != 1) {
			if (isset($post->ID) && get_post_status( $post->ID ) != 'publish') {
				if (!$this->can_publish_properties($currID) || !$this->is_subscription_valid($currID)) {
					_e( 'Your limit to publish properties is over.', 'woo-estato' );
				}
			}
		}
	}

	function display_alert_front(){
		$currID = is_user_logged_in() ? get_current_user_id() : 0;
		$user = wp_get_current_user();
		if ( $currID && in_array( 'rem_property_agent', (array) $user->roles ) && $currID != 1) {
			if (1) {
				if (!$this->can_publish_properties($currID) || !$this->is_subscription_valid($currID)) {
					_e( 'Your limit to publish properties is over.', 'woo-estato' );
				}
			}
		}
	}

	function can_publish_properties($agent_id){
		$pkg_data = get_user_meta( $agent_id, 'rem_latest_package', true );
		if ($pkg_data != '' && is_array($pkg_data)) {
			$max_properties = $pkg_data['count'];
			$active_properties = count_user_posts( $agent_id, 'rem_property' );
			if ($active_properties<$max_properties) {
				return true;
			} else {
				return false;
			}
		} else {
			return true;
		}
	}

	function is_subscription_valid($agent_id){
		$pkg_data = get_user_meta( $agent_id, 'rem_latest_package', true );
		if ($pkg_data != '' && is_array($pkg_data)) {
			$settings = get_option( 'rem_woo_packages' );
			$s_type = $settings['subscription_type'];
			$valid_time = '';
			if ($s_type == 'monthly') {
				$valid_time = strtotime('+30 days', $pkg_data['time']);
			}
			if ($s_type == 'annually') {
				$valid_time = strtotime('+365 days', $pkg_data['time']);
			}
			if( $valid_time < time() ){
			    // Date is passed
			    return false;
			} else {
			    // date is in the future
			    return true;
			}
		} else {
			return true;
		}
	}

	function show_pakage_detail(){
		ob_start();
		include 'templates/display_details.php';
		return ob_get_clean();
	}
}
?>