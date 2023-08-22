<?php
/*
Plugin Name: Betting Bookings
Plugin URI: https://github.com/Finland93/betting-booking-wp-plugin
Description: Maintain betting slip bookings on a yearly, monthly, and weekly basis
Version: 1.0
Author: Finland93
Author URI: https://github.com/Finland93
License: GPL2
Text Domain: betting-booking
*/

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Add the admin menu
add_action('admin_menu', 'betting_bookings_admin_menu');

function betting_bookings_admin_menu()
{
    add_menu_page(
        'Betting Bookings',
        'Betting Bookings',
        'manage_options',
        'betting-bookings',
        'betting_bookings_admin_page',
        'dashicons-tickets', // You can change this icon to something more suitable
        6
    );
}

function betting_bookings_enqueue_scripts()
{
    wp_enqueue_script('jquery');
}
add_action('admin_enqueue_scripts', 'betting_bookings_enqueue_scripts');

function betting_bookings_admin_page(){
global $wpdb;
        // Handle form submission here
        if (isset($_POST['submit_bet'])) {
        // Sanitize and save the data to the database
        $bet_amount = sanitize_text_field($_POST['bet_amount']);
        $row_count = absint($_POST['row_count']);
        $potential_win = sanitize_text_field($_POST['potential_win']);
        $date = sanitize_text_field($_POST['date']);

        // Save the data to the database
        $table_name = $wpdb->prefix . 'betting_bookings';
        $data = array(
            'bet_amount' => $bet_amount,
            'row_count' => $row_count,
            'potential_win' => $potential_win,
            'date' => $date,
            'won_status' => 'OPEN', // Assuming the default status is "OPEN"
            'hit_count' => '0/0',   // Assuming the default hit count is "0/0"
        );
        $wpdb->insert($table_name, $data);

        // After saving, you can redirect to the same page to avoid resubmission
        wp_redirect(admin_url('admin.php?page=betting-bookings'));
        exit;
    }


    // Display the form here
    ?>
    <div class="wrap">
        <h1>Betting Bookings</h1>
        <form method="post" action="">
            <label for="bet_amount">Bet Amount:</label>
            <input type="text" name="bet_amount" id="bet_amount" required>
            <br>

            <label for="row_count">Number of Rows:</label>
            <input type="number" name="row_count" id="row_count" required>
            <br>

            <label for="potential_win">Potential Win:</label>
            <input type="text" name="potential_win" id="potential_win" required>
            <br>

            <label for="date">Date:</label>
            <input type="date" name="date" id="date" required>
            <br>

            <input type="submit" name="submit_bet" value="Add Slip">
        </form>

    </div>
    <?php
    // Display the table here
    $table_name = $wpdb->prefix . 'betting_bookings';
    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC LIMIT 20", ARRAY_A);
    ?>
    <h2>Betting Slips</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Slip ID</th>
                <th>Bet Amount</th>
                <th>Number of Rows</th>
                <th>Potential Win</th>
                <th>Date</th>
                <th>Status</th>
                <th>Hits</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $result) : ?>
                <tr>
                    <td><?php echo $result['id']; ?></td>
                    <td><?php echo $result['bet_amount']; ?></td>
                    <td><?php echo $result['row_count']; ?></td>
                    <td><?php echo $result['potential_win']; ?></td>
                    <td><?php echo $result['date']; ?></td>
                    <td>
                        <select name="won_status">
                            <option value="OPEN" <?php selected($result['won_status'], 'OPEN'); ?>>Open</option>
                            <option value="WON" <?php selected($result['won_status'], 'WON'); ?>>Win</option>
                            <option value="LOST" <?php selected($result['won_status'], 'LOST'); ?>>Loss</option>
                        </select>
                    </td>
                    <td><?php echo $result['hit_count']; ?></td>
                    <td>
                        <button class="button button-primary btn-edit">Edit</button>
                        <button class="button button-secondary btn-remove">Remove</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

<!-- Edit Slip Modal -->
<div id="edit_slip_modal" class="modal">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <h3>Result for Slip (edit before updating)</h3>
        <form id="edit_slip_form">
            <input type="hidden" id="edit_slip_id" value="">
            <label for="edit_won_status">Status:</label>
            <select id="edit_won_status" name="edit_won_status">
                <option value="OPEN">Open</option>
                <option value="WON">Win</option>
                <option value="LOST">Loss</option>
            </select>
            <br>
            <label for="edit_hit_count">Hits:</label>
            <input type="text" id="edit_hit_count" name="edit_hit_count" value="" required>
            <br>
            <input type="submit" class="button button-primary" value="Update">
        </form>
    </div>
</div>
	
	<script>
    jQuery(document).ready(function($) {
        
		// Replace the comma with a dot for the bet amount field
        $('#bet_amount').on('change', function() {
            var value = $(this).val();
            value = value.replace(/,/g, '.');
            $(this).val(value);
        });
		    // Edit button click event
    $('.btn-edit').click(function() {
        var slipId = $(this).closest('tr').find('td:first').text(); // Get the Slip ID from the first <td> of the current row
        var editUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        var data = {
            'action': 'betting_bookings_get_slip',
            'slip_id': slipId
        };

        $.post(editUrl, data, function(response) {
            var slipData = JSON.parse(response);

            // Pre-fill the edit form with the slip data
            $('#edit_slip_id').val(slipData.id);
            $('#edit_won_status').val(slipData.won_status);
            $('#edit_hit_count').val(slipData.hit_count);
            $('#edit_slip_modal').fadeIn();
        });
    });

    // Update button click event
    $('#edit_slip_form').submit(function(event) {
        event.preventDefault();

        var updateUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        var data = {
            'action': 'betting_bookings_update_slip',
            'slip_id': $('#edit_slip_id').val(),
            'won_status': $('#edit_won_status').val(),
            'hit_count': $('#edit_hit_count').val()
        };

        $.post(updateUrl, data, function(response) {
            // Reload the page after updating the slip
            location.reload();
        });
    });


		// Remove button click event
		$('.btn-remove').click(function() {
			var slipId = $(this).closest('tr').find('td:first').text(); // Get the Slip ID from the first <td> of the current row
			var removeUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
			var data = {
				'action': 'betting_bookings_remove_slip',
				'slip_id': slipId
			};

			// Confirm the removal
			if (confirm('Are you sure you want to remove this slip?')) {
				$.post(removeUrl, data, function(response) {
					// Reload the page after removing the slip
					location.reload();
				});
			}
		});
    });
    </script>
    <?php
}
