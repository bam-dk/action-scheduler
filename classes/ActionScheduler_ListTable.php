<?php

class ActionScheduler_ListTable extends PP_List_Table {
	protected $package = 'action-scheduler';

	protected $columns = array(
		'hook' => 'Hook',
		'status' => 'Status',
		'args' => 'Arguments',
		'group' => 'Group',
		'recurrence' => 'Recurrence',
		'scheduled' => 'Scheduled Date',
	);

	protected $data_store;

	public function __construct() {
		$this->data_store = ActionScheduler_Store::instance();
		parent::__construct( array(
			'singular' => $this->translate( 'action-scheduler' ),
			'plural'   => $this->translate( 'action-scheduler' ),
			'ajax'     => false,
		) );
	}

	protected function get_recurrence( $item ) {
		$recurrence = $item->get_schedule();
		if ( method_exists( $recurrence, 'interval_in_seconds' ) ) {
			return self::human_interval( $recurrence->interval_in_seconds() );
		}
		return __( 'Non-repeating', 'action-scheduler' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_items_query_limit() {
		return $this->get_items_per_page( $this->package . '_items_per_page', $this->items_per_page );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_items_query_offset() {
		global $wpdb;

		$per_page = $this->get_items_query_limit();
		$current_page = $this->get_pagenum();
		if ( 1 < $current_page ) {
			return $per_page * ( $current_page - 1 );
		}

		return 0;
	}

	public function column_args( array $row ) {
		return '<code>' . json_encode( $row['args'] ) . '</code>';
	}

	public function column_scheduled( $row ) {
		$next_timestamp = $row['scheduled']->next()->format( 'U' );
		echo get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $next_timestamp ), 'Y-m-d H:i:s' );
		if ( gmdate( 'U' ) > $next_timestamp ) {
			printf( __( ' (%s ago)', 'action-scheduler' ), human_time_diff( gmdate( 'U' ), $next_timestamp ) );
		} else {
			echo ' (' . human_time_diff( gmdate( 'U' ), $next_timestamp ) . ')';
		}
	}

	protected function get_status( $item ) {
		if ( $item instanceof ActionScheduler_Action ) {
			return 'Pending';
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function prepare_items() {
		$this->process_bulk_action();

		if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
			// _wp_http_referer is used only on bulk actions, we remove it to keep the $_GET shorter
			wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
			exit;
		}

		$this->prepare_column_headers();

		$per_page = $this->get_items_query_limit();
		$query = array(
			'per_page' => $per_page,
			'offset'   => $this->get_items_query_offset(),
			'status'   => $this->get_request_status(),
		);

		$this->items = array();

		$total_items = $this->data_store->query_actions_count( $query );

		foreach ( $this->data_store->query_actions( $query ) as $id ) {
			$item = $this->data_store->fetch_action( $id );
			$this->items[ $id ] = array(
				'ID'   => $id,
				'hook' => $item->get_hook(),
				'status' => $this->get_status( $item ),
				'args' => $item->get_args(),
				'group' => $item->get_group(),
				'recurrence' => $this->get_recurrence( $item ),
				'scheduled' => $item->get_schedule(),
				'claim'
			);
		}

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );
	}

	public function get_request_status() {
		$statuses = array(
			ActionScheduler_Store::STATUS_PENDING,
			ActionScheduler_Store::STATUS_COMPLETE,
			ActionScheduler_Store::STATUS_FAILED,
		);

		if ( ! empty( $_GET['status'] ) && in_array( $_GET['status'], $statuses ) ) {
			return $_GET['status'];
		}

		return ActionScheduler_Store::STATUS_PENDING;
	}

	public function display_filter_by_status() {
		$statuses = array(
			'pending' => ActionScheduler_Store::STATUS_PENDING,
			'publish' => ActionScheduler_Store::STATUS_COMPLETE,
			'failed'  => ActionScheduler_Store::STATUS_FAILED,
		);

		$li = array();
		foreach ( $statuses as $name => $status ) {
			$total_items = $this->data_store->query_actions_count( compact( 'status' ) );
			if ( 0 === $total_items ) {
				continue;
			}

			if ( $status === $this->get_request_status() ) {
				$li[] =  '<li class="' . esc_attr( $name ) . '">'
					. '<strong>'
						. esc_html( ucfirst( $name ) )
					. "</strong> ($total_items)"
				. '</li>';
				continue;
			}

			$li[] =  '<li class="' . esc_attr( $name ) . '">'
				. '<a href="' . esc_url( add_query_arg( 'status', $status ) )  . '">'
				. esc_html( ucfirst( $name ) )
				. "</a> ($total_items)"
			. '</li>';
		}

		if ( $li ) {
			echo '<ul class="subsubsub">';
			echo implode( " | \n", $li );
			echo '</ul>';
		}
	}

	public function display() {
		$this->display_filter_by_status();

		parent::display();
	}
}