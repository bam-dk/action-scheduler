<?php

/**
 * Class ActionScheduler_AdminView
 * @codeCoverageIgnore
 */
class ActionScheduler_AdminView {

	private static $admin_view = NULL;

	/**
	 * @return ActionScheduler_QueueRunner
	 * @codeCoverageIgnore
	 */
	public static function instance() {

		if ( empty( self::$admin_view ) ) {
			$class = apply_filters('action_scheduler_admin_view_class', 'ActionScheduler_AdminView');
			self::$admin_view = new $class();
		}

		return self::$admin_view;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function init() {
		$self = self::instance();

		if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || false == DOING_AJAX ) ) {
			if ( class_exists( 'WooCommerce' ) ) {
				add_action( 'woocommerce_admin_status_content_action-scheduler', array( $self, 'render_admin_ui' ) );
				add_filter( 'woocommerce_admin_status_tabs', array( $self, 'register_menu_woo_tab' ) );
			} else {
				add_action( 'admin_menu', array( $self, 'register_menu' ) );
			}
			add_action( 'admin_notices', array( $self, 'past_due_actions' ) );
		}
	}

	/**
	 * Trigger API for 3rd parties to handle past due actions.
	 *
	 * This method will check if there are past due actions. Because Action Scheduler is action agnostic,
	 * it will trigger an action with a list of hooks that callbacks can use to handle past due actions
	 * as needed.
	 *
	 * To narrow down possible results, and improve query looking for past due actions, any plugin
	 * must add their hook name to an array through the `action_scheduler_past_due_hooks`
	 * filter.
	 *
	 * The action owner should be listening to the `action_schedule_past_due_action` action, 
	 * and do something to warn the users about how to fix their WP-Cron or to run their actions manually.
	 *
	 * This method is executed on the `admin_notices`, so it is safe to render the admin notification.
	 */
	public function past_due_actions() {

		$action_hooks = apply_filters( 'action_scheduler_past_due_hooks_to_check', array() );

		if ( ! empty( $action_hooks ) ) {
			return;
		}

		$action_ids = ActionScheduler::store()->query_actions( array(
			'date'   => new Datetime( apply_filters( 'action_scheduler_past_due_time', '-5 days' ) ),
			'status' => ActionScheduler_Store::STATUS_PENDING,
			'hook'   => $action_hooks,
		) );

		if ( ! empty( $action_ids ) ) {
			return;
		}

		$actions = array();
		foreach ( $action_ids as $id ) {
			$action = ActionScheduler::store()->fetch_action( $id );
			$actions[ $action->get_hook() ][] = $action;
		}

		do_action( 'action_scheduler_past_due_action', $actions );
	}

	/**
	 * Registers action-scheduler into WooCommerce > System status.
	 */
	public function register_menu_woo_tab( array $tabs ) {
		$tabs[ 'action-scheduler' ] = __( 'Scheduled Actions', 'action-scheduler' );

		return $tabs;
	}

	/**
	 * Registers action-scheduler under Tools.
	 *
	 * This method is called if woocommerce is not activated and we can't register our page
	 * to WooCommerce's System status page.
	 */
	public function register_menu() {
		add_submenu_page(
			'tools.php',
			__( 'Scheduled Actions', 'action-scheduler' ),
			__( 'Scheduled Actions', 'action-scheduler' ),
			'manage_options',
			'action-scheduler',
			array( $this, 'render_admin_ui' )
		);
	}

	/**
	 * Renders the Admin UI
	 */
	public function render_admin_ui() {
		$table = new ActionScheduler_ListTable( ActionScheduler::store(), ActionScheduler::logger() );
		$table->prepare_items();

		echo '<h1>' . __( 'Scheduled Actions', 'action-scheduler' ) . '</h1>';
		$table->display();
	}

	/** Deprecated Functions **/

	public function action_scheduler_post_type_args( $args ) {
		_deprecated_function( __METHOD__, '1.6' );

		return $args;
	}

	/**
	 * Customise the post status related views displayed on the Scheduled Actions administration screen.
	 *
	 * @param array $views An associative array of views and view labels which can be used to filter the 'scheduled-action' posts displayed on the Scheduled Actions administration screen.
	 * @return array $views An associative array of views and view labels which can be used to filter the 'scheduled-action' posts displayed on the Scheduled Actions administration screen.
	 */
	public function list_table_views( $views ) {
		_deprecated_function( __METHOD__, '1.6' );

		return $views;
	}

	/**
	 * Do not include the "Edit" action for the Scheduled Actions administration screen.
	 *
	 * Hooked to the 'bulk_actions-edit-action-scheduler' filter.
	 *
	 * @param array $actions An associative array of actions which can be performed on the 'scheduled-action' post type.
	 * @return array $actions An associative array of actions which can be performed on the 'scheduled-action' post type.
	 */
	public function bulk_actions( $actions ) {
		_deprecated_function( __METHOD__, '1.6' );

		return $actions;
	}

	/**
	 * Completely customer the columns displayed on the Scheduled Actions administration screen.
	 *
	 * Because we can't filter the content of the default title and date columns, we need to recreate our own
	 * custom columns for displaying those post fields. For the column content, @see self::list_table_column_content().
	 *
	 * @param array $columns An associative array of columns that are use for the table on the Scheduled Actions administration screen.
	 * @return array $columns An associative array of columns that are use for the table on the Scheduled Actions administration screen.
	 */
	public function list_table_columns( $columns ) {
		_deprecated_function( __METHOD__, '1.6' );
	}

	/**
	 * Make our custom title & date columns use defaulting title & date sorting.
	 *
	 * @param array $columns An associative array of columns that can be used to sort the table on the Scheduled Actions administration screen.
	 * @return array $columns An associative array of columns that can be used to sort the table on the Scheduled Actions administration screen.
	 */
	public static function list_table_sortable_columns( $columns ) {
		_deprecated_function( __METHOD__, '1.6' );

		return $columns;
	}

	/**
	 * Print the content for our custom columns.
	 *
	 * @param string $column_name The key for the column for which we should output our content.
	 * @param int $post_id The ID of the 'scheduled-action' post for which this row relates.
	 * @return void
	 */
	public static function list_table_column_content( $column_name, $post_id ) {
		_deprecated_function( __METHOD__, '1.6' );
	}

	/**
	 * Hide the inline "Edit" action for all 'scheduled-action' posts.
	 *
	 * Hooked to the 'post_row_actions' filter.
	 *
	 * @param array $actions An associative array of actions which can be performed on the 'scheduled-action' post type.
	 * @return array $actions An associative array of actions which can be performed on the 'scheduled-action' post type.
	 */
	public static function row_actions( $actions, $post ) {
		_deprecated_function( __METHOD__, '1.6' );

		return $actions;
	}

	/**
	 * Run an action when triggered from the Action Scheduler administration screen.
	 *
	 * @codeCoverageIgnore
	 */
	public static function maybe_execute_action() {
		_deprecated_function( __METHOD__, '1.6' );
	}

	/**
	 * Convert an interval of seconds into a two part human friendly string.
	 *
	 * The WordPress human_time_diff() function only calculates the time difference to one degree, meaning
	 * even if an action is 1 day and 11 hours away, it will display "1 day". This funciton goes one step
	 * further to display two degrees of accuracy.
	 *
	 * Based on Crontrol::interval() funciton by Edward Dale: https://wordpress.org/plugins/wp-crontrol/
	 *
	 * @param int $interval A interval in seconds.
	 * @return string A human friendly string representation of the interval.
	 */
	public static function admin_notices() {
		_deprecated_function( __METHOD__, '1.6' );
	}

	/**
	 * Filter search queries to allow searching by Claim ID (i.e. post_password).
	 *
	 * @param string $orderby MySQL orderby string.
	 * @param WP_Query $query Instance of a WP_Query object
	 * @return string MySQL orderby string.
	 */
	public function custom_orderby( $orderby, $query ){
		_deprecated_function( __METHOD__, '1.6' );
	}

	/**
	 * Filter search queries to allow searching by Claim ID (i.e. post_password).
	 *
	 * @param string $search MySQL search string.
	 * @param WP_Query $query Instance of a WP_Query object
	 * @return string MySQL search string.
	 */
	public function search_post_password( $search, $query ) {
		_deprecated_function( __METHOD__, '1.6' );
	}

	/**
	 * Change messages when a scheduled action is updated.
	 *
	 * @param  array $messages
	 * @return array
	 */
	public function post_updated_messages( $messages ) {
		_deprecated_function( __METHOD__, '1.6' );

		return $messages;
	}

}
