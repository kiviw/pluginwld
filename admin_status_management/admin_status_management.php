<?php
/**
 * Plugin Name: Admin Status Management
 * Plugin URI: Your plugin website URL
 * Description: A plugin for managing the status of manual deposit requests in the backend.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: Your website URL
 * Text Domain: admin-status-management
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Admin page to manage deposit requests
function admin_status_management_page_content() {
    ?>
    <div class="wrap">
        <h1>Deposit Requests</h1>
        <?php
        // Add your custom code here to display and manage deposit requests
        // You can show a table of deposit requests with status and provide an option to change the status
        // Handle the status change logic here and update the database accordingly
        // For this example, we'll show the pending deposit requests for the admin
        admin_status_management_table();
        ?>
    </div>
    <?php
}

// Hook the admin page function to an action
add_action('admin_menu', 'register_admin_status_management_page');

// Register the admin page
function register_admin_status_management_page() {
    add_menu_page(
        'Deposit Requests',
        'Deposit Requests',
        'manage_options',
        'admin_status_management',
        'admin_status_management_page_content',
        'dashicons-money',
        30
    );
}

// Function to display the deposit transactions table for admin
function admin_status_management_table() {
    ob_start();
    global $wpdb;
    $table_name = $wpdb->prefix . 'deposit_requests';
    $transactions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");

    if ($transactions) {
        echo '<table class="manual-deposit-transactions-admin">';
        echo '<thead><tr><th>Transaction ID</th><th>Name</th><th>Amount in WLD</th><th>TX Hash</th><th>Status</th><th>Action</th></tr></thead>';
        echo '<tbody>';
        foreach ($transactions as $transaction) {
            echo '<tr>';
            echo '<td>' . $transaction->id . '</td>';
            echo '<td>' . $transaction->mpesa_name . '</td>';
            echo '<td>' . $transaction->amount_in_wld . '</td>';
            echo '<td>' . $transaction->txhash . '</td>';
            echo '<td>' . $transaction->status . '</td>';
            echo '<td><a href="' . admin_url('admin.php?page=admin_status_management&action=confirm&id=' . $transaction->id) . '">Confirm</a></td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>No deposit transactions found.</p>';
    }

    return ob_get_clean();
}

// Admin page actions
add_action('admin_init', 'admin_status_management_actions');
function admin_status_management_actions() {
    if (isset($_GET['page']) && $_GET['page'] === 'admin_status_management') {
        if (isset($_GET['action']) && $_GET['action'] === 'confirm' && isset($_GET['id'])) {
            confirm_manual_deposit($_GET['id']);
        }
    }
}

// Function to manually confirm a deposit
function confirm_manual_deposit($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'deposit_requests';
    $wpdb->update($table_name, array('status' => 'Confirmed'), array('id' => $id));

    // Redirect back to the admin page after confirming the deposit
    wp_redirect(admin_url('admin.php?page=admin_status_management'));
    exit();
}
