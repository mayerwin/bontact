<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class BONT_Settings {

	/**
	 * @var array
	 */
	protected $_form_messages = array();
	
	/**
	 * @var string
	 */
	protected $_option_slug;
	
	protected $_api_login_url = 'https://dashboard.bontact.com/api/bontactapi.aspx?func=login&username=%s&password=%s';
	
	protected function _do_redirect_option_page( $message_code = null ) {
		$return_url = add_query_arg( 'page', $this->_option_slug, admin_url( 'admin.php' ) );
		
		if ( ! is_null( $message_code ) )
			$return_url = add_query_arg( 'message', $message_code, $return_url );
		
		wp_redirect( $return_url );
		die();
	}
	
	protected function _get_logout_link() {
		$url = add_query_arg( array( 'page' => $this->_option_slug, 'bont-action' => 'logout' ), admin_url( 'options-general.php' ) );
		return wp_nonce_url( $url, 'bontact_logout_' . get_current_user_id() );
	}
	
	public function admin_init() {
		if ( ! current_user_can( 'manage_options' ) )
			return;

		if ( ! empty( $_REQUEST['bont-action'] ) ) {
			if ( 'logout' === $_REQUEST['bont-action'] ) {
				check_admin_referer( 'bontact_logout_' . get_current_user_id() );
				delete_option( $this->_option_slug );
				$this->_do_redirect_option_page( 5 );
			}

			if ( 'login' === $_REQUEST['bont-action'] ) {
				if ( empty( $_REQUEST['nonce'] ) || ! check_ajax_referer( 'bontact_' . $_REQUEST['bont-action'] . '_' . get_current_user_id(), 'nonce', false ) ) {
					$this->_do_redirect_option_page( 6 );
				}

				$response = wp_remote_get( sprintf( $this->_api_login_url, $_POST['bontact']['username'], $_POST['bontact']['password'] ), array( 'sslverify' => false, 'timeout' => 30 ) );

				if ( is_wp_error( $response ) || 200 !== (int) $response['response']['code'] ) {
					$this->_do_redirect_option_page( 4 );
				}

				$data_return = json_decode( $response['body'] );
				if ( is_null( $data_return ) || '200' !== $data_return->code ) {
					$this->_do_redirect_option_page( 3 );
				}

				$options = get_option( $this->_option_slug, array() );
				$options['token'] = $data_return->token;
				$options['username'] = $_POST['bontact']['username'];
				$options['password'] = $_POST['bontact']['password'];

				update_option( $this->_option_slug, $options );
				
				$this->_do_redirect_option_page( 2 );
			}
		}
	}
	
	public function bontact_setting_content() {
		$username = $this->get_option( 'username' );
		$password = $this->get_option( 'password' );
		?>
		<!-- Create a header in the default WordPress 'wrap' container -->
		<div class="wrap bontact-admin-wrap">
			<div id="icon-themes" class="icon32"></div>
			<h2><?php _e( 'Bontact Widget & Dashboard', 'bontact' ); ?></h2>
			
			<?php if ( ! empty( $_GET['message'] ) && ! empty( $this->_form_messages[ $_GET['message'] ] ) ) : ?>
			<div class="<?php echo $this->_form_messages[ $_GET['message'] ]['status']; ?>"><p><?php echo $this->_form_messages[ $_GET['message'] ]['msg']; ?></p></div>
			<?php endif; ?>
			<?php if ( ! empty( $username ) && ! empty( $password ) ) : ?>
				<h3><?php _e( 'Bontact Widget:', 'bontact' ); ?></h3>
				
				
				<p><?php _e( 'You are logged in as', 'bontact' ); ?> <strong><?php echo $username; ?></strong></p>
				<p><?php _e( 'Update your widget settings, manage your conversations, monitor your visitors, and much more with Bontact\'s dashboard:', 'bontact' ); ?></p>
				<p class="bont-get-space">
					<a class="button-primary" href="http://dashboard.bontact.com/html/chatDashboard.aspx" target="_blank"><?php _e( 'Access Your Dashboard', 'bontact' ); ?></a><br /><br />
					<?php _e( 'The Bontact Dashboard offers full functionality and also allows you to provide new reps with their own password.', 'bontact' ); ?>
				</p>

				<p><?php _e( sprintf( 'Need help? Contact us at <a href="mailto:%1$s">%1$s</a> and we\'ll provide you with any information you need. You can also contact us through our own Bontact widget on bontact.com.', 'support@bontact.com' ), 'bontact' ); ?></p>

				<hr />
				
				<h3><?php _e( 'Follow us:', 'bontact' ); ?></h3>
				<div>
					<ul class="bont-ul-circle">
						<li><a href="https://www.facebook.com/bontact" target="_blank"><?php _e( 'Facebook', 'bontact' ); ?></a></li>
						<li><a href="https://twitter.com/bontact" target="_blank"><?php _e( 'Twitter', 'bontact' ); ?></a></li>
						<li><a href="http://www.youtube.com/user/Bontact" target="_blank"><?php _e( 'YouTube', 'bontact' ); ?></a></li>
					</ul>
				</div>

				<hr class="bont-get-space" />
				
				<p><a class="button" href="<?php echo $this->_get_logout_link(); ?>" onclick="return confirm('<?php _e( 'Are you sure you want to disconnect your Bontact account from your WordPress site?', 'bontact' ); ?>');"><?php _e( 'Disconnect your Bontact account from your WordPress site', 'bontact' ); ?></a></p>
			<?php else : ?>
				
				<h3><?php _e( 'Bontact Log In:', 'bontact' ); ?></h3>
				<p class="description">
					<?php printf( __( '<a href="%s" target="_blank">click here</a> to sign up for a free account.', 'bontact' ), 'http://bontact.com/sign-up-free/' ); ?>
				</p>
				<form action="" method="post">
					<input type="hidden" name="page" value="<?php echo $this->_option_slug; ?>" />
					<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'bontact_login_' . get_current_user_id() ); ?>" />
					<input type="hidden" name="bont-action" value="login" />
					<table class="form-table">
						<tr valign="top">
							<th scope="row">
								<label for="bontact_username"><?php _e( 'Username (email):', 'bontact' ); ?></label>
							</th>
							<td>
								<input id="bontact_username" type="text" name="bontact[username]" value="" autocomplete="off" />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">
								<label for="bontact_password"><?php _e( 'Password:', 'bontact' ); ?></label>
							</th>
							<td>
								<input id="bontact_password" type="password" name="bontact[password]" value="" autocomplete="off" />
							</td>
						</tr>
					</table>
					<div class="submit">
						<input type="submit" name="type-submit" class="button button-primary" value="<?php _e( 'Login', 'bontact' ); ?>" />
					</div>
				</form>
			<?php endif; ?>
		</div><!-- /.wrap -->
	<?php
	}
	
	public function admin_menu() {
		add_menu_page( 'Bontact Settings', 'Bontact', 'manage_options', 'bontact-settings', array( &$this, 'bontact_setting_content' ), plugins_url( 'assets/images/logo-bontact-16x16.png', BONTACT_BASE ) );
	}
	
	public function get_option( $key ) {
		$options = get_option( $this->_option_slug );
		return isset( $options[ $key ] ) ? $options[ $key ] : '';
	}
	
	public function __construct() {
		$this->_form_messages = array(
			'', // Just skip from zero array.
			array(
				'msg' => __( 'Registration was successful.', 'bontact' ),
				'status' => 'updated',
			),
			array(
				'msg' => __( 'Login was successful.', 'bontact' ),
				'status' => 'updated',
			),
			array(
				'msg' => __( 'Invalid login.', 'bontact' ),
				'status' => 'error',
			),
			array(
				'msg' => __( 'Error with API server.', 'bontact' ),
				'status' => 'error',
			),
			array(
				'msg' => __( 'Your logout was successful.', 'bontact' ),
				'status' => 'updated',
			),
			array(
				'msg' => __( 'Action expired.', 'bontact' ),
				'status' => 'error',
			),
		);
		$this->_option_slug = 'bontact-settings';
		
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
	}
	
}