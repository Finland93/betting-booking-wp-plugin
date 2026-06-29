<?php
/*
Plugin Name: Betting Bookings
Plugin URI: https://github.com/Finland93/betting-booking-wp-plugin
Description: Maintain personal betting-slip bookings on a yearly, monthly and weekly basis. Track stake, rows, potential win, hit count and outcome, and display summaries via shortcodes.
Version: 2.0.0
Author: Finland93
Author URI: https://github.com/Finland93
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: betting-booking
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BETTING_BOOKINGS_VERSION', '2.0.0' );
define( 'BETTING_BOOKINGS_DB_VERSION', '2' );
define( 'BETTING_BOOKINGS_FILE', __FILE__ );
define( 'BETTING_BOOKINGS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class. Keeps everything namespaced and out of the global scope.
 */
final class Betting_Bookings {

	/** @var Betting_Bookings|null */
	private static $instance = null;

	/** @var string Hook suffix of the admin page (set by add_menu_page). */
	private $page_hook = '';

	/** Allowed slip statuses. */
	const STATUSES = array( 'OPEN', 'WON', 'LOST' );

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Lifecycle.
		register_activation_hook( BETTING_BOOKINGS_FILE, array( __CLASS__, 'activate' ) );

		// i18n.
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Make sure the table exists / is up to date even after a manual update.
		add_action( 'plugins_loaded', array( $this, 'maybe_upgrade_db' ) );

		// Admin.
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// AJAX (logged-in admins only — no nopriv handlers on purpose).
		add_action( 'wp_ajax_betting_bookings_get_slip', array( $this, 'ajax_get_slip' ) );
		add_action( 'wp_ajax_betting_bookings_update_slip', array( $this, 'ajax_update_slip' ) );
		add_action( 'wp_ajax_betting_bookings_remove_slip', array( $this, 'ajax_remove_slip' ) );

		// Front-end shortcodes.
		add_shortcode( 'TotalBetAmount', array( $this, 'sc_total_bet_amount' ) );
		add_shortcode( 'BetsRatio', array( $this, 'sc_bets_ratio' ) );
		add_shortcode( 'MonthlyWinnings', array( $this, 'sc_monthly_winnings' ) );
		add_shortcode( 'YearlyWinnings', array( $this, 'sc_yearly_winnings' ) );
		add_shortcode( 'HighestXAmountWin', array( $this, 'sc_highest_win' ) );
		add_shortcode( 'OverallScore', array( $this, 'sc_overall_score' ) );
	}

	/* ---------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------- */

	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'betting_bookings';
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'betting-booking', false, dirname( plugin_basename( BETTING_BOOKINGS_FILE ) ) . '/languages' );
	}

	/** Currency symbol shown next to amounts (filterable, no settings page needed). */
	private function currency() {
		return apply_filters( 'betting_bookings_currency', get_option( 'betting_bookings_currency', '€' ) );
	}

	/** Normalise a user-entered amount: accept comma decimals, strip junk. */
	private function parse_amount( $raw ) {
		$raw = str_replace( ',', '.', trim( (string) $raw ) );
		$raw = preg_replace( '/[^0-9.\-]/', '', $raw );
		return round( (float) $raw, 2 );
	}

	private function money( $amount ) {
		return $this->currency() . ' ' . number_format_i18n( (float) $amount, 2 );
	}

	/* ---------------------------------------------------------------------
	 * Activation / DB
	 * ------------------------------------------------------------------- */

	public static function activate() {
		self::create_table();
		update_option( 'betting_bookings_db_version', BETTING_BOOKINGS_DB_VERSION );
	}

	public function maybe_upgrade_db() {
		if ( get_option( 'betting_bookings_db_version' ) !== BETTING_BOOKINGS_DB_VERSION ) {
			self::create_table();
			update_option( 'betting_bookings_db_version', BETTING_BOOKINGS_DB_VERSION );
		}
	}

	private static function create_table() {
		global $wpdb;
		$table           = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		// NOTE: dbDelta is whitespace-sensitive — keep two spaces before PRIMARY KEY.
		$sql = "CREATE TABLE {$table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			bet_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
			row_count INT(11) NOT NULL DEFAULT 0,
			potential_win DECIMAL(12,2) NOT NULL DEFAULT 0.00,
			bet_date DATE NOT NULL,
			won_status VARCHAR(10) NOT NULL DEFAULT 'OPEN',
			hit_count VARCHAR(20) NOT NULL DEFAULT '0/0',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY bet_date (bet_date),
			KEY won_status (won_status)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/* ---------------------------------------------------------------------
	 * Admin page
	 * ------------------------------------------------------------------- */

	public function admin_menu() {
		$this->page_hook = add_menu_page(
			__( 'Betting Bookings', 'betting-booking' ),
			__( 'Betting Bookings', 'betting-booking' ),
			'manage_options',
			'betting-bookings',
			array( $this, 'render_admin_page' ),
			'dashicons-tickets-alt',
			6
		);
	}

	public function enqueue_admin_assets( $hook ) {
		if ( $hook !== $this->page_hook ) {
			return;
		}

		wp_enqueue_style(
			'betting-bookings-admin',
			BETTING_BOOKINGS_URL . 'assets/admin.css',
			array(),
			BETTING_BOOKINGS_VERSION
		);

		wp_enqueue_script(
			'betting-bookings-admin',
			BETTING_BOOKINGS_URL . 'assets/admin.js',
			array( 'jquery' ),
			BETTING_BOOKINGS_VERSION,
			true
		);

		wp_localize_script(
			'betting-bookings-admin',
			'BettingBookings',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'betting_bookings_ajax' ),
				'i18n'    => array(
					'confirmRemove' => __( 'Are you sure you want to remove this slip?', 'betting-booking' ),
					'error'         => __( 'Something went wrong. Please try again.', 'betting-booking' ),
				),
			)
		);
	}

	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'betting-booking' ) );
		}

		global $wpdb;
		$table = self::table_name();

		// ---- Handle "currency" mini-setting (own nonce) ----
		if ( isset( $_POST['save_currency'] ) && check_admin_referer( 'betting_bookings_settings', 'betting_bookings_settings_nonce' ) ) {
			$symbol = sanitize_text_field( wp_unslash( $_POST['betting_bookings_currency'] ?? '€' ) );
			update_option( 'betting_bookings_currency', $symbol !== '' ? $symbol : '€' );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Currency saved.', 'betting-booking' ) . '</p></div>';
		}

		// ---- Handle "add slip" (own nonce, classic POST + redirect on success) ----
		if ( isset( $_POST['submit_bet'] ) && check_admin_referer( 'betting_bookings_add', 'betting_bookings_add_nonce' ) ) {
			$bet_amount    = $this->parse_amount( $_POST['bet_amount'] ?? '' );
			$row_count     = absint( $_POST['row_count'] ?? 0 );
			$potential_win = $this->parse_amount( $_POST['potential_win'] ?? '' );
			$bet_date      = sanitize_text_field( wp_unslash( $_POST['bet_date'] ?? '' ) );

			// Validate the date (expects YYYY-MM-DD from <input type="date">).
			$ts = strtotime( $bet_date );
			if ( false === $ts ) {
				$bet_date = current_time( 'Y-m-d' );
			} else {
				$bet_date = gmdate( 'Y-m-d', $ts );
			}

			$inserted = $wpdb->insert(
				$table,
				array(
					'bet_amount'    => $bet_amount,
					'row_count'     => $row_count,
					'potential_win' => $potential_win,
					'bet_date'      => $bet_date,
					'won_status'    => 'OPEN',
					'hit_count'     => '0/' . $row_count,
				),
				array( '%f', '%d', '%f', '%s', '%s', '%s' )
			);

			// Post/Redirect/Get to avoid resubmission.
			$redirect = add_query_arg(
				array(
					'page'    => 'betting-bookings',
					'created' => $inserted ? '1' : '0',
				),
				admin_url( 'admin.php' )
			);
			wp_safe_redirect( $redirect );
			exit;
		}

		if ( isset( $_GET['created'] ) ) {
			$ok = '1' === $_GET['created'];
			printf(
				'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
				$ok ? 'success' : 'error',
				$ok ? esc_html__( 'Slip added.', 'betting-booking' ) : esc_html__( 'Could not add the slip.', 'betting-booking' )
			);
		}

		// ---- Fetch slips (paginated) ----
		$per_page = 20;
		$paged    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$offset   = ( $paged - 1 ) * $per_page;

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL
		$slips = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY bet_date DESC, id DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL
				$per_page,
				$offset
			),
			ARRAY_A
		);
		$total_pages = max( 1, (int) ceil( $total / $per_page ) );

		$currency = $this->currency();
		?>
		<div class="wrap betting-bookings">
			<h1><?php esc_html_e( 'Betting Bookings', 'betting-booking' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Track your personal betting slips. Use the shortcodes to display summaries on any page or post.', 'betting-booking' ); ?>
			</p>

			<div class="bb-grid">
				<div class="bb-col">
					<h2><?php esc_html_e( 'Add a slip', 'betting-booking' ); ?></h2>
					<form method="post" action="" class="bb-add-form">
						<?php wp_nonce_field( 'betting_bookings_add', 'betting_bookings_add_nonce' ); ?>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="bet_amount"><?php esc_html_e( 'Stake', 'betting-booking' ); ?></label></th>
								<td><input type="text" inputmode="decimal" name="bet_amount" id="bet_amount" class="regular-text" required> <span class="bb-cur"><?php echo esc_html( $currency ); ?></span></td>
							</tr>
							<tr>
								<th scope="row"><label for="row_count"><?php esc_html_e( 'Number of rows', 'betting-booking' ); ?></label></th>
								<td><input type="number" min="0" step="1" name="row_count" id="row_count" class="small-text" required></td>
							</tr>
							<tr>
								<th scope="row"><label for="potential_win"><?php esc_html_e( 'Potential win (total payout)', 'betting-booking' ); ?></label></th>
								<td><input type="text" inputmode="decimal" name="potential_win" id="potential_win" class="regular-text" required> <span class="bb-cur"><?php echo esc_html( $currency ); ?></span></td>
							</tr>
							<tr>
								<th scope="row"><label for="bet_date"><?php esc_html_e( 'Date', 'betting-booking' ); ?></label></th>
								<td><input type="date" name="bet_date" id="bet_date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" required></td>
							</tr>
						</table>
						<?php submit_button( __( 'Add Slip', 'betting-booking' ), 'primary', 'submit_bet' ); ?>
					</form>

					<hr>
					<h2><?php esc_html_e( 'Settings', 'betting-booking' ); ?></h2>
					<form method="post" action="" class="bb-settings-form">
						<?php wp_nonce_field( 'betting_bookings_settings', 'betting_bookings_settings_nonce' ); ?>
						<label for="betting_bookings_currency"><?php esc_html_e( 'Currency symbol', 'betting-booking' ); ?></label>
						<input type="text" name="betting_bookings_currency" id="betting_bookings_currency" value="<?php echo esc_attr( $currency ); ?>" class="small-text" maxlength="8">
						<?php submit_button( __( 'Save', 'betting-booking' ), 'secondary', 'save_currency', false ); ?>
					</form>
				</div>

				<div class="bb-col bb-shortcodes-help">
					<h2><?php esc_html_e( 'Shortcodes', 'betting-booking' ); ?></h2>
					<ul>
						<li><code>[TotalBetAmount]</code> — <?php esc_html_e( 'Total stake this month.', 'betting-booking' ); ?></li>
						<li><code>[BetsRatio]</code> — <?php esc_html_e( 'Hit rate this month.', 'betting-booking' ); ?></li>
						<li><code>[MonthlyWinnings]</code> — <?php esc_html_e( 'Net profit/loss this month.', 'betting-booking' ); ?></li>
						<li><code>[YearlyWinnings]</code> — <?php esc_html_e( 'Net profit/loss this year.', 'betting-booking' ); ?></li>
						<li><code>[HighestXAmountWin]</code> — <?php esc_html_e( 'Biggest single win.', 'betting-booking' ); ?></li>
						<li><code>[OverallScore]</code> — <?php esc_html_e( 'Win/loss record (all time).', 'betting-booking' ); ?></li>
					</ul>
					<p class="description"><?php esc_html_e( 'Net profit = payout − stake for won slips, and −stake for lost slips.', 'betting-booking' ); ?></p>
				</div>
			</div>

			<h2><?php esc_html_e( 'Betting Slips', 'betting-booking' ); ?></h2>
			<table class="wp-list-table widefat fixed striped bb-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'ID', 'betting-booking' ); ?></th>
						<th><?php esc_html_e( 'Stake', 'betting-booking' ); ?></th>
						<th><?php esc_html_e( 'Rows', 'betting-booking' ); ?></th>
						<th><?php esc_html_e( 'Potential win', 'betting-booking' ); ?></th>
						<th><?php esc_html_e( 'Date', 'betting-booking' ); ?></th>
						<th><?php esc_html_e( 'Status', 'betting-booking' ); ?></th>
						<th><?php esc_html_e( 'Hits', 'betting-booking' ); ?></th>
						<th><?php esc_html_e( 'Net', 'betting-booking' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'betting-booking' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $slips ) ) : ?>
					<tr><td colspan="9"><?php esc_html_e( 'No slips yet. Add your first one above.', 'betting-booking' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $slips as $slip ) :
						$net = $this->slip_net( $slip );
						?>
						<tr data-id="<?php echo esc_attr( $slip['id'] ); ?>">
							<td><?php echo esc_html( $slip['id'] ); ?></td>
							<td><?php echo esc_html( $this->money( $slip['bet_amount'] ) ); ?></td>
							<td><?php echo esc_html( $slip['row_count'] ); ?></td>
							<td><?php echo esc_html( $this->money( $slip['potential_win'] ) ); ?></td>
							<td><?php echo esc_html( $slip['bet_date'] ); ?></td>
							<td><span class="bb-badge bb-badge-<?php echo esc_attr( strtolower( $slip['won_status'] ) ); ?>"><?php echo esc_html( $this->status_label( $slip['won_status'] ) ); ?></span></td>
							<td class="bb-hits"><?php echo esc_html( $slip['hit_count'] ); ?></td>
							<td class="bb-net <?php echo $net >= 0 ? 'bb-pos' : 'bb-neg'; ?>"><?php echo esc_html( $this->money( $net ) ); ?></td>
							<td>
								<button type="button" class="button button-primary btn-edit"><?php esc_html_e( 'Edit', 'betting-booking' ); ?></button>
								<button type="button" class="button button-link-delete btn-remove"><?php esc_html_e( 'Remove', 'betting-booking' ); ?></button>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>

			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav"><div class="tablenav-pages">
					<?php
					echo wp_kses_post(
						paginate_links(
							array(
								'base'      => add_query_arg( 'paged', '%#%' ),
								'format'    => '',
								'current'   => $paged,
								'total'     => $total_pages,
								'prev_text' => '&laquo;',
								'next_text' => '&raquo;',
							)
						)
					);
					?>
				</div></div>
			<?php endif; ?>
		</div>

		<!-- Edit Slip Modal -->
		<div id="bb_edit_modal" class="bb-modal" aria-hidden="true">
			<div class="bb-modal-content" role="dialog" aria-modal="true" aria-labelledby="bb_modal_title">
				<button type="button" class="bb-modal-close" aria-label="<?php esc_attr_e( 'Close', 'betting-booking' ); ?>">&times;</button>
				<h3 id="bb_modal_title"><?php esc_html_e( 'Update slip result', 'betting-booking' ); ?></h3>
				<form id="bb_edit_form">
					<input type="hidden" id="bb_edit_id" value="">
					<input type="hidden" id="bb_edit_rows" value="">
					<p>
						<label for="bb_edit_status"><?php esc_html_e( 'Status', 'betting-booking' ); ?></label><br>
						<select id="bb_edit_status">
							<option value="OPEN"><?php esc_html_e( 'Open', 'betting-booking' ); ?></option>
							<option value="WON"><?php esc_html_e( 'Win', 'betting-booking' ); ?></option>
							<option value="LOST"><?php esc_html_e( 'Loss', 'betting-booking' ); ?></option>
						</select>
					</p>
					<p>
						<label for="bb_edit_hits"><?php esc_html_e( 'Hits (out of rows)', 'betting-booking' ); ?></label><br>
						<input type="number" id="bb_edit_hits" min="0" step="1" value="0">
						<span class="bb-rows-hint"></span>
					</p>
					<p>
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Update', 'betting-booking' ); ?></button>
						<span class="bb-spinner" aria-hidden="true"></span>
					</p>
				</form>
			</div>
		</div>
		<?php
	}

	private function status_label( $status ) {
		switch ( $status ) {
			case 'WON':
				return __( 'Win', 'betting-booking' );
			case 'LOST':
				return __( 'Loss', 'betting-booking' );
			default:
				return __( 'Open', 'betting-booking' );
		}
	}

	/** Net result of a single slip: payout − stake if won, −stake if lost, else 0. */
	private function slip_net( $slip ) {
		$stake  = (float) $slip['bet_amount'];
		$payout = (float) $slip['potential_win'];
		if ( 'WON' === $slip['won_status'] ) {
			return round( $payout - $stake, 2 );
		}
		if ( 'LOST' === $slip['won_status'] ) {
			return round( -$stake, 2 );
		}
		return 0.0;
	}

	/* ---------------------------------------------------------------------
	 * AJAX handlers (all verify nonce + capability)
	 * ------------------------------------------------------------------- */

	private function verify_ajax() {
		check_ajax_referer( 'betting_bookings_ajax', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'betting-booking' ) ), 403 );
		}
	}

	public function ajax_get_slip() {
		$this->verify_ajax();
		global $wpdb;

		$id = isset( $_POST['slip_id'] ) ? absint( $_POST['slip_id'] ) : 0;
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid slip.', 'betting-booking' ) ), 400 );
		}

		$table = self::table_name();
		$slip  = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), // phpcs:ignore WordPress.DB.PreparedSQL
			ARRAY_A
		);

		if ( ! $slip ) {
			wp_send_json_error( array( 'message' => __( 'Slip not found.', 'betting-booking' ) ), 404 );
		}

		$parts = explode( '/', $slip['hit_count'] );
		$hits  = isset( $parts[0] ) ? absint( $parts[0] ) : 0;

		wp_send_json_success(
			array(
				'id'         => (int) $slip['id'],
				'won_status' => $slip['won_status'],
				'hits'       => $hits,
				'row_count'  => (int) $slip['row_count'],
			)
		);
	}

	public function ajax_update_slip() {
		$this->verify_ajax();
		global $wpdb;

		$id     = isset( $_POST['slip_id'] ) ? absint( $_POST['slip_id'] ) : 0;
		$status = isset( $_POST['won_status'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['won_status'] ) ) ) : 'OPEN';
		$hits   = isset( $_POST['hits'] ) ? absint( $_POST['hits'] ) : 0;

		if ( ! $id || ! in_array( $status, self::STATUSES, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid data.', 'betting-booking' ) ), 400 );
		}

		$table = self::table_name();
		$rows  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT row_count FROM {$table} WHERE id = %d", $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		if ( $hits > $rows ) {
			$hits = $rows;
		}

		$updated = $wpdb->update(
			$table,
			array(
				'won_status' => $status,
				'hit_count'  => $hits . '/' . $rows,
			),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			wp_send_json_error( array( 'message' => __( 'Update failed.', 'betting-booking' ) ), 500 );
		}

		wp_send_json_success( array( 'message' => __( 'Updated.', 'betting-booking' ) ) );
	}

	public function ajax_remove_slip() {
		$this->verify_ajax();
		global $wpdb;

		$id = isset( $_POST['slip_id'] ) ? absint( $_POST['slip_id'] ) : 0;
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid slip.', 'betting-booking' ) ), 400 );
		}

		$deleted = $wpdb->delete( self::table_name(), array( 'id' => $id ), array( '%d' ) );
		if ( false === $deleted ) {
			wp_send_json_error( array( 'message' => __( 'Delete failed.', 'betting-booking' ) ), 500 );
		}

		wp_send_json_success( array( 'message' => __( 'Removed.', 'betting-booking' ) ) );
	}

	/* ---------------------------------------------------------------------
	 * Shortcodes
	 * ------------------------------------------------------------------- */

	private function month_range() {
		$start = current_time( 'Y-m-01' );
		$end   = current_time( 'Y-m-t' );
		return array( $start, $end );
	}

	private function year_range() {
		$year = current_time( 'Y' );
		return array( $year . '-01-01', $year . '-12-31' );
	}

	/** Net profit/loss across a date range. */
	private function net_between( $start, $end ) {
		global $wpdb;
		$table = self::table_name();
		$net   = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(
					CASE
						WHEN won_status = 'WON' THEN potential_win - bet_amount
						WHEN won_status = 'LOST' THEN -bet_amount
						ELSE 0
					END
				),0) FROM {$table} WHERE bet_date BETWEEN %s AND %s", // phpcs:ignore WordPress.DB.PreparedSQL
				$start,
				$end
			)
		);
		return (float) $net;
	}

	public function sc_total_bet_amount() {
		global $wpdb;
		$table              = self::table_name();
		list( $start, $end ) = $this->month_range();
		$total              = (float) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(bet_amount),0) FROM {$table} WHERE bet_date BETWEEN %s AND %s", // phpcs:ignore WordPress.DB.PreparedSQL
				$start,
				$end
			)
		);
		return '<span class="bb-stat bb-total-bet">' . esc_html( $this->money( $total ) ) . '</span>';
	}

	public function sc_bets_ratio() {
		global $wpdb;
		$table              = self::table_name();
		list( $start, $end ) = $this->month_range();

		$won = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE won_status = 'WON' AND bet_date BETWEEN %s AND %s", $start, $end ) // phpcs:ignore WordPress.DB.PreparedSQL
		);
		$settled = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE won_status IN ('WON','LOST') AND bet_date BETWEEN %s AND %s", $start, $end ) // phpcs:ignore WordPress.DB.PreparedSQL
		);

		$ratio = $settled > 0 ? round( ( $won / $settled ) * 100, 1 ) : 0;
		return '<span class="bb-stat bb-ratio">' . esc_html( number_format_i18n( $ratio, 1 ) ) . '%</span>';
	}

	public function sc_monthly_winnings() {
		list( $start, $end ) = $this->month_range();
		$net                = $this->net_between( $start, $end );
		$cls                = $net >= 0 ? 'bb-pos' : 'bb-neg';
		return '<span class="bb-stat bb-monthly ' . esc_attr( $cls ) . '">' . esc_html( $this->money( $net ) ) . '</span>';
	}

	public function sc_yearly_winnings() {
		list( $start, $end ) = $this->year_range();
		$net                = $this->net_between( $start, $end );
		$cls                = $net >= 0 ? 'bb-pos' : 'bb-neg';
		return '<span class="bb-stat bb-yearly ' . esc_attr( $cls ) . '">' . esc_html( $this->money( $net ) ) . '</span>';
	}

	public function sc_highest_win() {
		global $wpdb;
		$table = self::table_name();
		$max   = (float) $wpdb->get_var(
			"SELECT COALESCE(MAX(potential_win),0) FROM {$table} WHERE won_status = 'WON'" // phpcs:ignore WordPress.DB.PreparedSQL
		);
		return '<span class="bb-stat bb-highest">' . esc_html( $this->money( $max ) ) . '</span>';
	}

	public function sc_overall_score() {
		global $wpdb;
		$table = self::table_name();
		$won   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE won_status = 'WON'" );  // phpcs:ignore WordPress.DB.PreparedSQL
		$lost  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE won_status = 'LOST'" ); // phpcs:ignore WordPress.DB.PreparedSQL

		return sprintf(
			'<span class="bb-stat bb-score"><span class="bb-pos">%1$s</span> – <span class="bb-neg">%2$s</span></span>',
			esc_html( $won ),
			esc_html( $lost )
		);
	}
}

Betting_Bookings::instance();
