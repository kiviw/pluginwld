<?php
/**
 * Plugin Name: Manual Deposit and KSH Disbursement
 * Plugin URI: Your plugin website URL
 * Description: A simple plugin for manual deposit confirmation and KSH disbursement.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: Your website URL
 * Text Domain: manual-deposit-disbursement
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Create custom database table on plugin activation
function create_deposit_requests_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'deposit_requests';

    // Check if the table exists, if not, create it
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            deposit_address varchar(255) NOT NULL,
            amount_in_wld float NOT NULL,
            phone varchar(255) NOT NULL,
            mpesa_name varchar(255) NOT NULL,
            txhash varchar(100) UNIQUE,
            status varchar(20) NOT NULL DEFAULT 'Unconfirmed',
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
register_activation_hook(__FILE__, 'create_deposit_requests_table');

// Register shortcode to display the deposit form
function manual_deposit_form_shortcode() {
    ob_start();
    $deposit_address = '0xbe5d9b4f0b61ed76bbfa821ea465e0c4179f0684'; // Replace this with the actual deposit address

    if (isset($_POST['submit_deposit'])) {
        process_deposit_submission();
    }
    ?>
    <div class="manual-deposit-form">
        <p>Copy the WLD deposit address below and use it to send your WLD from an external wallet:</p>
        <div class="deposit-address"><?php echo $deposit_address; ?></div>

        <form method="post">
            <label for="amount_in_wld">Amount in WLD Sent:</label>
            <input type="number" id="amount_in_wld" name="amount_in_wld" min="0" step="1" required />

            <label for="phone">Your Phone Number (MPESA):</label>
            <input type="text" id="phone" name="phone" required />

            <label for="mpesa_name">Your MPESA Name:</label>
            <input type="text" id="mpesa_name" name="mpesa_name" required />

            <label for="txhash">Transaction Hash:</label>
            <input type="text" id="txhash" name="txhash" required />

            <input type="submit" value="Submit Deposit Request" name="submit_deposit" />
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('manual_deposit_form', 'manual_deposit_form_shortcode');

// Process deposit form submission
function process_deposit_submission() {
    if (isset($_SESSION['form_submitted'])) {
        return;
    }

    $deposit_address = '0xbe5d9b4f0b61ed76bbfa821ea465e0c4179f0684'; // Replace this with the actual deposit address
    $amount_in_wld = isset($_POST['amount_in_wld']) ? floatval($_POST['amount_in_wld']) : 0;
    $phone_number = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    $mpesa_name = isset($_POST['mpesa_name']) ? sanitize_text_field($_POST['mpesa_name']) : '';
    $txhash = isset($_POST['txhash']) ? sanitize_text_field($_POST['txhash']) : '';

    if ($amount_in_wld <= 0 || empty($phone_number) || empty($mpesa_name) || empty($txhash)) {
        echo '<p>Invalid input. Please enter valid WLD amount, phone number, MPESA name, and transaction hash.</p>';
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'deposit_requests';

    $data = array(
        'deposit_address' => $deposit_address,
        'amount_in_wld' => $amount_in_wld,
        'phone' => $phone_number,
        'mpesa_name' => $mpesa_name,
        'txhash' => $txhash,
    );

    $wpdb->insert($table_name, $data, array('%s', '%f', '%s', '%s', '%s'));

    // For this example, we'll just show a success message
    echo '<p>Your deposit request has been submitted. Please wait for confirmation.</p>';
    $_SESSION['form_submitted'] = true;
}

// Register shortcode to display the deposit transactions table for frontend users
function manual_deposit_transactions_user_shortcode() {
    ob_start();
    global $wpdb;
    $table_name = $wpdb->prefix . 'deposit_requests';
    $transactions = $wpdb->get_results("SELECT mpesa_name, amount_in_wld, status FROM $table_name WHERE status IN ('Unconfirmed', 'Confirmed') ORDER BY id DESC");

    if ($transactions) {
        echo '<table class="manual-deposit-transactions-user">';
        echo '<thead><tr><th>Name</th><th>Amount in WLD</th><th>Status</th></tr></thead>';
        echo '<tbody>';
        foreach ($transactions as $transaction) {
            echo '<tr>';
            echo '<td>' . $transaction->mpesa_name . '</td>';
            echo '<td>' . $transaction->amount_in_wld . '</td>';
            echo '<td>' . $transaction->status . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>No deposit transactions found.</p>';
    }

    return ob_get_clean();
}
add_shortcode('manual_deposit_transactions_user', 'manual_deposit_transactions_user_shortcode');
