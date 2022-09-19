<?php

defined( 'ABSPATH' ) || die( 'No soup for you' );

/**
 * Blocks in database
 */
class Bu_Blocks_In_Db extends Bu_Plugin_Base {

	/**
	 * Submenu hook
	 *
	 * @var string
	 */
	private $hook;

	/**
	 * Plugin name
	 *
	 * @var string
	 */
	private $plugin_name = 'blocks-in-db';

	/**
	 * Required privilege
	 * manage_network_users for WordPress multisite
	 * manage_options for single site
	 *
	 * @var string
	 */
	private $privilege;

	/**
	 * Setup function
	 *
	 * @return void
	 */
	public function _setup() {
		add_action( 'admin_menu', array( $this, 'register_blocks_in_db' ) );
		add_action( 'init', [ $this, 'load_textdomain' ]);
	}

	/**
	 * Register blocks in database, set actions and filters
	 *
	 * @return void
	 */
	public function register_blocks_in_db() {
		global $wp_version;
		$this->privilege = is_multisite() ? 'manage_network_users' : 'manage_options';
		$this->hook      = add_submenu_page(
			'tools.php',
			__( 'Blocks in Database', 'blocks-in-db' ),
			__( 'Blocks in Database', 'blocks-in-db' ),
			$this->privilege,
			'blocks-in-db-page',
			[ $this, 'blocks_in_db_page' ]
		);
		add_action( "load-{$this->hook}", [ $this, 'add_screen_options' ] );
		add_filter( 'set-screen-option', [ $this, 'set_screen_option', 10, 3 ] );
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'blocks-in-db', false, dirname( plugin_basename( __FILE__ ) ) . '/../languages' );
	}

	/**
	 * Add number of rows listed as a screen option, default 20.
	 */
	public function add_screen_options() {
		$option = 'per_page';
		$args   = array(
			'label'   => __( '#Blocks', 'blocks-in-db' ),
			'default' => 20,
			'option'  => 'blocks_per_page',
		);
		add_screen_option( $option, $args );
	}

	/**
	 * Set number of rows to display
	 *
	 * @param string $status Status
	 * @param string $option Option
	 * @param string $value  Number of rows
	 */
	public function set_screen_option( $status, $option, $value ) {
		if ( 'blocks_per_page' === $option ) {
			return $value;
		}
	}

	/**
	 * If empty list, give a warning
	 */
	public function no_items() {
		_e( 'No blocks', 'blocks-in-db' );
	}

	/**
	 * Test for superadmin privs and render the table
	 */
	public function blocks_in_db_page() {
		if ( ! current_user_can( $this->privilege ) ) {
			return;
		}
		require_once plugin_dir_path( __FILE__ ) . '/class-bu-blocks-in-db-table.php';
		?>
		<div class="wrap">
			<h2><?php _e( 'Blocks in Database', 'blocks-in-db' ); ?></h2>
			<?php echo esc_html( $this->blocks_in_db() ); ?>
		</div>
		<?php
	}

	/**
	 * Render the table
	 */
	public function blocks_in_db() {
		global $wpdb;

		$blocks_in_db_table = new Bu_Blocks_In_Db_Table();
		$blocks_in_db_table->prepare_items();
		$blocks_in_db_table->views(); ?>
		<form id="activity-filter" method="GET">
			<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
			<?php
			$blocks_in_db_table->display();
			?>
		</form>
		<?php
	}
}
