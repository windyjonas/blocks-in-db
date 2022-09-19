<?php
defined( 'ABSPATH' ) || die( 'No soup for you' );

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * List of posts where block is used
 */
class Bu_Blocks_In_Db_Table extends WP_List_Table {
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'blocks_in_database',
				'plural'   => 'blocks_in_database',
				'ajax'     => true,
			]
		);
	}

	/**
	 * Get columns
	 *
	 * @return array columns
	 */
	public function get_columns() {
		$columns = [
			'ID'            => __( 'ID', 'blocks-in-db' ),
			'post_title'    => __( 'Title', 'blocks-in-db' ),
			'post_author'   => __( 'User', 'blocks-in-db' ),
			'post_date'     => __( 'Post date', 'blocks-in-db' ),
		];
		return $columns;
	}

	/**
	 * Get column defaults
	 *
	 * @param  object $item        Item
	 * @param  string $column_name Column name
	 * @return string              Column default
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'ID':
			case 'post_title':
			case 'post_author':
			case 'post_date':
				return $item->$column_name;
			default:
				return var_export( $item, true );
		}
	}

	/**
	 * Username column
	 *
	 * @param  object $item Item
	 * @return void
	 */
	public function column_post_author( $item ) {
		$userdata = get_userdata( $item->post_author );
		echo '<a href="' . get_edit_user_link( $item->post_author ) . '">' . esc_attr( $userdata->display_name ) . '</a> ';
	}

	/**
	 * ID column
	 *
	 * @param  object $item Item
	 * @return void
	 */
	public function column_ID( $item ) {

		echo '<a href="' . get_edit_post_link( $item->ID) . '">' . esc_attr( $item->ID ) . '</a> ';
	}

	/**
	 * Get list of sortable columns
	 *
	 * @return array Sortable columns
	 */
	public function get_sortable_columns() {
		$sortable = [
			'id'          => [ 'ID', false ],
			'post_author' => [ 'post_author', false ],
			'post_title'  => [ 'post_title', false ],
			'post_date'   => [ 'post_date', false ],
		];
		return $sortable;
	}

	/**
	 * Get the data, here's where the magic happens
	 *
	 * @return void
	 */
	public function prepare_items() {
		global $wpdb, $_wp_column_headers;
		$screen = get_current_screen();

		/**
		 * Handle request parameters
		 */
		$ptype     = ( isset( $_REQUEST['ptype'] ) && ! empty( $_REQUEST['ptype'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['ptype'] ) ) : 'all' );
		$blockname = ( isset( $_REQUEST['blockname'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['blockname'] ) ) : '' );

		$prepare_vars = [];

		/**
		 * Block search
		 */
		$search_clause = '';
		if ( ! empty( $blockname ) ) {
			$search_clause  = "and post_content like '%wp:" . $blockname . "%'";
		} else {
			$search_clause  = "and 1=2";
		}

		$ptype_clause = '';
		if ( 'all' !== $ptype ) {
			$ptype_clause   = 'and post_type = %s';
			$prepare_vars[] = $ptype;
		} else {
			$args         = [ 'public' => true ];
			$post_types   = get_post_types( $args );
			$ptype_clause = sprintf(
				'and post_type IN (%s)',
				implode(
					',',
					array_map(
						function( $a ) {
							return "'" . $a . "'";
						},
						$post_types
					)
				)
			);
		}

		/**
		 * Setup the query
		 */
		$query = 'select ID, post_title, post_author, post_date '
			. "from {$wpdb->posts} "
			. "where post_status='publish' {$search_clause} {$ptype_clause}";


		/**
		 * Handle the ordering
		 */
		$orderby = ! empty( $_GET['orderby'] ) ? esc_sql( $_GET['orderby'] ) : 'ID';
		$order   = ! empty( $_GET['order'] ) ? esc_sql( $_GET['order'] ) : 'DESC';
		if ( ! empty( $orderby ) && ! empty( $order ) ) {
			$query .= ' ORDER BY ' . $orderby . ' ' . $order;
		}

		if ( false !== strpos( $query, '%s' ) ) {
			$totalitems = $wpdb->query( $wpdb->prepare( $query, $prepare_vars ) );
		} else {
			$totalitems = $wpdb->query( $query );
		}
		if ( empty( $per_page ) || $per_page < 1 ) {
			$per_page = $screen->get_option( 'per_page', 'default' );
		}

		/**
		 * Paging
		 */
		$paged = ! empty( $_GET['paged'] ) ? esc_sql( $_GET['paged'] ) : '';
		if ( empty( $paged ) || ! is_numeric( $paged ) || $paged <= 0 ) {
			$paged = 1;
		}

		$totalpages = ceil( $totalitems / $per_page );

		if ( ! empty( $paged ) && ! empty( $per_page ) ) {
			$offset = ( $paged - 1 ) * $per_page;
			$query .= ' LIMIT '. (int) $offset . ',' . (int) $per_page;
		}

		/* -- Register the pagination -- */
		$this->set_pagination_args(
			[
				'total_items' => $totalitems,
				'total_pages' => $totalpages,
				'per_page' => $per_page,
			]
		);

		$columns = $this->get_columns();
		$hidden = [];
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = [ $columns, $hidden, $sortable ];

		/**
		 * Fetch the items
		 */
		if ( false !== strpos( $query, '%s' ) ) {
			$this->items = $wpdb->get_results( $wpdb->prepare( $query, $prepare_vars ) );
		} else {
			$this->items = $wpdb->get_results( $query );
		}
	}

	/**
	 * Quick links to post type views
	 *
	 */
	public function get_views() {
		$views   = [];
		$current = ( ! empty( $_REQUEST['ptype'] ) ? $_REQUEST['ptype'] : 'all' );

		//All link
		$class        = ( 'all' === $current ? ' class="current"' : '' );
		$all_url      = remove_query_arg( 'ptype' );
		$views['all'] = "<a href='{$all_url }' {$class} >All</a>";

		$args       = [ 'public' => true ];
		$post_types = get_post_types( $args );
		foreach ( $post_types  as $post_type ) {
			$name           = $post_type;
			$url            = add_query_arg( 'ptype', $name );
			$class          = ( $current == $name ? ' class="current"' : '' );
			$ucname         = ucfirst( $name );
			$views[ $name ] = "<a href='{$url}' {$class} >{$ucname}</a>";
		}
		return $views;
	}

	/**
	 * Filter form items
	 * post type, start date and end date
	 *
	 * @param  string $which Which
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' != $which ) {
			return;
		}
		$ptype     = isset( $_GET['ptype'] ) ? sanitize_text_field( wp_unslash( $_GET['ptype'] ) ) : '';
		$blockname = isset( $_GET['blockname'] ) ? sanitize_text_field( wp_unslash( $_GET['blockname'] ) ) : '';
		?>
		<div class="alignleft actions">
			<select name="blockname" id="blockname">
			<?php
				$trans_key = 'bidb-list2';
				$data = get_transient( $trans_key );
				if ( false === $data ) {
					$request = new WP_REST_Request('GET', '/wp/v2/block-types');
					$response = rest_do_request($request);
					if ( $response->is_error() ) {
						// Convert to a WP_Error object.
						$error = $response->as_error();
						$message = $response->get_error_message();
						$error_data = $response->get_error_data();
						$status = isset( $error_data['status'] ) ? $error_data['status'] : 500;
						wp_die( printf( '<p>An error occurred: %s (%d)</p>', $message, $error_data ) );
					}
					$data = $response->get_data();
					set_transient( $trans_key, $data, 1 * HOUR_IN_SECONDS );
				}

				foreach( $data as $blocktype ) : ?>
					<option value="<?php echo $blocktype['name']; ?>" <?php selected($blockname, $blocktype['name'] ); ?>><?php echo $blocktype['name']; ?></option>
					<?php
				endforeach; ?>
			</select>
			<label class="screen-reader-text" for="ptype"><?php esc_html_e( 'Post type&hellip;', 'blocks-in-db' ); ?></label>
			<select name="ptype" id="ptype">
				<option <?php selected( $ptype, '' ); ?> value=''><?php esc_html_e( 'Post type&hellip;', 'blocks-in-db' ); ?></option>
				<?php
				$args       = [ 'public' => true ];
				$post_types = get_post_types( $args );
				foreach ( $post_types  as $post_type ) : ?>
					<option <?php selected( $ptype, $post_type ); ?> value="<?php echo $post_type; ?>"><?php echo ucfirst( $post_type ); ?></option>
				<?php endforeach ?>
			</select>
			<?php submit_button( __( 'Search', 'blocks-in-db' ), 'button', false, false, [ 'id' => 'post-query-submit' ] ); ?>
		</div>
		<?php
	}

}
