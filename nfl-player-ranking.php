<?php
/**
 * Plugin Name: FFAN Player Ranking
 * Description: Create consensus ranks for NFL players by position and tiers with advanced admin management and scheduling.
 * Version: 4.2.0
 * Author: FSAN
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: nfl-player-ranking
 * 
 * Updated: December 2024 - Version 4.2.0 - Aesthetic Updates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Version 4.2.0 - Updated December 2024 with Aesthetic Updates
define('NFL_RANKING_PLUGIN_VERSION', '4.2.0');

// Database table creation function
function nfl_ranking_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Players table - now includes week column
    $table_players = $wpdb->prefix . 'nfl_players';
    $sql_players = "CREATE TABLE $table_players (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        team varchar(50) NOT NULL,
        opponent varchar(50) NOT NULL,
        position varchar(10) NOT NULL,
        week varchar(20) NOT NULL DEFAULT 'week1',
        sort_order int NOT NULL DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX idx_position (position),
        INDEX idx_week (week),
        INDEX idx_position_week (position, week),
        INDEX idx_sort_order (sort_order)
    ) $charset_collate;";

    // Rankings table - now includes week column
    $table_rankings = $wpdb->prefix . 'nfl_rankings';
    $sql_rankings = "CREATE TABLE $table_rankings (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        position varchar(10) NOT NULL,
        week varchar(20) NOT NULL DEFAULT 'week1',
        ranking_data longtext NOT NULL,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_user_position_week (user_id, position, week),
        INDEX idx_position (position),
        INDEX idx_week (week),
        INDEX idx_position_week (position, week),
        INDEX idx_user_id (user_id)
    ) $charset_collate;";

    // NEW: Schedules table for scheduling functionality
    $table_schedules = $wpdb->prefix . 'nfl_schedules';
    $sql_schedules = "CREATE TABLE $table_schedules (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        week varchar(20) NOT NULL,
        start_date datetime NOT NULL,
        end_date datetime NOT NULL,
        is_active tinyint(1) NOT NULL DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_week (week),
        INDEX idx_active (is_active),
        INDEX idx_dates (start_date, end_date)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_players);
    dbDelta($sql_rankings);
    dbDelta($sql_schedules);

    // Insert default schedule if none exists
    $existing_schedules = $wpdb->get_var("SELECT COUNT(*) FROM $table_schedules");
    if ($existing_schedules == 0) {
        nfl_ranking_create_default_schedule();
    }
}

// Create default schedule
function nfl_ranking_create_default_schedule() {
    global $wpdb;
    $table_schedules = $wpdb->prefix . 'nfl_schedules';
    
    $weeks = nfl_ranking_get_weeks();
    $start_date = current_time('mysql');
    
    foreach ($weeks as $week_key => $week_label) {
        $wpdb->insert($table_schedules, array(
            'week' => $week_key,
            'start_date' => $start_date,
            'end_date' => date('Y-m-d H:i:s', strtotime($start_date . ' +7 days')),
            'is_active' => ($week_key === 'offseason') ? 1 : 0
        ));
        $start_date = date('Y-m-d H:i:s', strtotime($start_date . ' +7 days'));
    }
}

// Plugin activation
register_activation_hook(__FILE__, 'nfl_ranking_create_tables');

// Get available weeks
function nfl_ranking_get_weeks() {
    return array(
        'offseason' => 'Offseason',
        'rookies' => 'Rookies',
        'week1' => 'Week 1',
        'week2' => 'Week 2',
        'week3' => 'Week 3',
        'week4' => 'Week 4',
        'week5' => 'Week 5',
        'week6' => 'Week 6',
        'week7' => 'Week 7',
        'week8' => 'Week 8',
        'week9' => 'Week 9',
        'week10' => 'Week 10',
        'week11' => 'Week 11',
        'week12' => 'Week 12',
        'week13' => 'Week 13',
        'week14' => 'Week 14',
        'week15' => 'Week 15',
        'week16' => 'Week 16',
        'week17' => 'Week 17',
        'week18' => 'Week 18'
    );
}

// Get current active week
function nfl_ranking_get_current_week() {
    global $wpdb;
    $table_schedules = $wpdb->prefix . 'nfl_schedules';
    
    $current_time = current_time('mysql');
    $active_week = $wpdb->get_var($wpdb->prepare(
        "SELECT week FROM $table_schedules WHERE start_date <= %s AND end_date >= %s AND is_active = 1 ORDER BY start_date DESC LIMIT 1",
        $current_time, $current_time
    ));
    
    return $active_week ?: 'offseason';
}

// --- Admin Settings and Management ---
function nfl_ranking_admin_menu() {
    add_menu_page(
        'NFL Player Rankings',
        'NFL Rankings',
        'manage_options',
        'nfl-ranking-admin',
        'nfl_ranking_admin_page',
        'dashicons-awards',
        20
    );

    add_submenu_page(
        'nfl-ranking-admin',
        'Manage Players',
        'Manage Players',
        'manage_options',
        'nfl-ranking-admin',
        'nfl_ranking_admin_page'
    );

    add_submenu_page(
        'nfl-ranking-admin',
        'Manage Rankings',
        'Manage Rankings',
        'manage_options',
        'nfl-ranking-rankings',
        'nfl_ranking_rankings_page'
    );

    add_submenu_page(
        'nfl-ranking-admin',
        'Schedule Management',
        'Schedule',
        'manage_options',
        'nfl-ranking-schedule',
        'nfl_ranking_schedule_page'
    );

    add_submenu_page(
        'nfl-ranking-admin',
        'Settings',
        'Settings',
        'manage_options',
        'nfl-ranking-settings',
        'nfl_ranking_settings_page'
    );
}
add_action('admin_menu', 'nfl_ranking_admin_menu');

function nfl_ranking_register_settings() {
    register_setting('nfl_ranking_settings_group', 'nfl_ranking_firebase_config');
    register_setting('nfl_ranking_settings_group', 'nfl_ranking_app_id');
    register_setting('nfl_ranking_settings_group', 'nfl_ranking_upload_limits');
}
add_action('admin_init', 'nfl_ranking_register_settings');

// Handle CSV uploads and player management - FIXED VERSION
function nfl_ranking_handle_admin_actions() {
    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;
    $table_players = $wpdb->prefix . 'nfl_players';
    $table_rankings = $wpdb->prefix . 'nfl_rankings';
    $table_schedules = $wpdb->prefix . 'nfl_schedules';

    // Handle schedule updates
    if (isset($_POST['update_schedule']) && isset($_POST['schedule_data'])) {
        if (!wp_verify_nonce($_POST['schedule_nonce'], 'nfl_ranking_schedule')) {
            wp_die('Security check failed');
        }

        $schedule_data = $_POST['schedule_data'];
        foreach ($schedule_data as $week => $data) {
            $start_date = sanitize_text_field($data['start_date']);
            $end_date = sanitize_text_field($data['end_date']);
            $is_active = isset($data['is_active']) ? 1 : 0;

            $wpdb->replace($table_schedules, array(
                'week' => $week,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'is_active' => $is_active
            ));
        }

        wp_redirect(add_query_arg(array(
            'schedule_updated' => '1'
        ), admin_url('admin.php?page=nfl-ranking-schedule')));
        exit;
    }

    // Handle CSV upload - SIMPLIFIED AND FIXED
    if (isset($_POST['upload_csv']) && isset($_FILES['csv_file'])) {
        // Verify nonce
        if (!wp_verify_nonce($_POST['upload_nonce'], 'nfl_ranking_upload')) {
            wp_die('Security check failed');
        }

        $position = sanitize_text_field($_POST['position']);
        $week = sanitize_text_field($_POST['week']);
        $upload_mode = sanitize_text_field($_POST['upload_mode']);
        $upload_limit = intval($_POST['upload_limit']);

        // SIMPLIFIED VALIDATION - This was the issue!
        if (!in_array($position, ['QB', 'RB', 'WR', 'TE'])) {
            wp_die('Invalid position selected.');
        }

        $file = $_FILES['csv_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(add_query_arg(array(
                'week' => $week,
                'position' => $position,
                'upload_error' => '1'
            ), admin_url('admin.php?page=nfl-ranking-admin')));
            exit;
        }

        // Read and parse CSV
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            wp_redirect(add_query_arg(array(
                'week' => $week,
                'position' => $position,
                'upload_error' => '2'
            ), admin_url('admin.php?page=nfl-ranking-admin')));
            exit;
        }

        // Get headers
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            wp_redirect(add_query_arg(array(
                'week' => $week,
                'position' => $position,
                'upload_error' => '3'
            ), admin_url('admin.php?page=nfl-ranking-admin')));
            exit;
        }

        // Find column indices
        $name_col = -1;
        $team_col = -1;
        $opponent_col = -1;

        foreach ($headers as $index => $header) {
            $header_lower = strtolower(trim($header));
            if (strpos($header_lower, 'name') !== false && $name_col === -1) {
                $name_col = $index;
            }
            if (strpos($header_lower, 'team') !== false && $team_col === -1) {
                $team_col = $index;
            }
            if (strpos($header_lower, 'opponent') !== false && $opponent_col === -1) {
                $opponent_col = $index;
            }
        }

        if ($name_col === -1 || $team_col === -1 || $opponent_col === -1) {
            fclose($handle);
            wp_redirect(add_query_arg(array(
                'week' => $week,
                'position' => $position,
                'upload_error' => '4'
            ), admin_url('admin.php?page=nfl-ranking-admin')));
            exit;
        }

        // Clear existing data if override mode
        if ($upload_mode === 'override') {
            $wpdb->delete($table_players, array('position' => $position, 'week' => $week));
        }

        // Get current max sort order
        $max_sort_order = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(sort_order) FROM $table_players WHERE position=%s AND week=%s", $position, $week
        )) ?: 0;

        // Process CSV data
        $processed = 0;
        $row_count = 0;
        while (($row = fgetcsv($handle)) !== FALSE) {
            if ($upload_limit > 0 && $processed >= $upload_limit) {
                break;
            }

            $name = isset($row[$name_col]) ? trim($row[$name_col]) : '';
            $team = isset($row[$team_col]) ? trim($row[$team_col]) : '';
            $opponent = isset($row[$opponent_col]) ? trim($row[$opponent_col]) : '';

            if (!empty($name) && !empty($team) && !empty($opponent)) {
                $result = $wpdb->insert($table_players, array(
                    'name' => $name,
                    'team' => $team,
                    'opponent' => $opponent,
                    'position' => $position,
                    'week' => $week,
                    'sort_order' => $max_sort_order + $row_count + 1
                ));

                if ($result !== false) {
                    $processed++;
                }
            }
            $row_count++;
        }

        fclose($handle);

        // Redirect with success message
        wp_redirect(add_query_arg(array(
            'week' => $week,
            'position' => $position,
            'uploaded' => $processed
        ), admin_url('admin.php?page=nfl-ranking-admin')));
        exit;
    }

    // Handle bulk delete players
    if (isset($_POST['bulk_delete']) && isset($_POST['selected_players'])) {
        if (!wp_verify_nonce($_POST['delete_nonce'], 'nfl_ranking_bulk_delete')) {
            wp_die('Security check failed');
        }

        $player_ids = array_map('intval', $_POST['selected_players']);
        if (!empty($player_ids)) {
            $placeholders = implode(',', array_fill(0, count($player_ids), '%d'));
            $deleted = $wpdb->query($wpdb->prepare("DELETE FROM $table_players WHERE id IN ($placeholders)", $player_ids));

            wp_redirect(add_query_arg(array(
                'week' => $_GET['week'] ?? 'week1',
                'position' => $_GET['position'] ?? 'QB',
                'deleted' => $deleted
            ), admin_url('admin.php?page=nfl-ranking-admin')));
            exit;
        }
    }

    // Handle bulk delete rankings
    if (isset($_POST['bulk_delete_rankings']) && isset($_POST['selected_rankings'])) {
        if (!wp_verify_nonce($_POST['delete_rankings_nonce'], 'nfl_ranking_bulk_delete_rankings')) {
            wp_die('Security check failed');
        }

        $ranking_ids = array_map('intval', $_POST['selected_rankings']);
        if (!empty($ranking_ids)) {
            $placeholders = implode(',', array_fill(0, count($ranking_ids), '%d'));
            $deleted = $wpdb->query($wpdb->prepare("DELETE FROM $table_rankings WHERE id IN ($placeholders)", $ranking_ids));

            wp_redirect(add_query_arg(array(
                'week' => $_GET['week'] ?? 'week1',
                'position' => $_GET['position'] ?? 'QB',
                'deleted_rankings' => $deleted
            ), admin_url('admin.php?page=nfl-ranking-rankings')));
            exit;
        }
    }
}
add_action('admin_init', 'nfl_ranking_handle_admin_actions');

// NEW: Schedule Management Page
function nfl_ranking_schedule_page() {
    global $wpdb;
    $table_schedules = $wpdb->prefix . 'nfl_schedules';

    // Show messages
    if (isset($_GET['schedule_updated'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Schedule updated successfully!</p></div>';
    }

    // Get all schedules
    $schedules = $wpdb->get_results("SELECT * FROM $table_schedules ORDER BY 
        CASE 
            WHEN week = 'offseason' THEN 0
            WHEN week = 'rookies' THEN 1
            ELSE CAST(SUBSTRING(week, 5) AS UNSIGNED) + 1
        END", ARRAY_A);

    $available_weeks = nfl_ranking_get_weeks();
    $current_week = nfl_ranking_get_current_week();

    ?>
    <div class="wrap">
        <h1>NFL Player Rankings - Schedule Management (v<?php echo NFL_RANKING_PLUGIN_VERSION; ?>)</h1>
        
        <div style="margin-bottom: 20px; padding: 15px; background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 4px;">
            <h3 style="margin-top: 0; color: #0073aa;">Current Active Week: <?php echo $available_weeks[$current_week] ?? $current_week; ?></h3>
            <p style="margin-bottom: 0; color: #666;">
                The active week determines which week is shown by default in the frontend widget. The "Active" checkbox allows manual override of automatic date-based detection.
            </p>
        </div>

        <form method="post" id="schedule-form">
            <?php wp_nonce_field('nfl_ranking_schedule', 'schedule_nonce'); ?>
            
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle">Week Schedule Configuration</h2>
                </div>
                <div class="inside">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 20%;">Week</th>
                                <th style="width: 25%;">Start Date</th>
                                <th style="width: 25%;">End Date</th>
                                <th style="width: 15%;">Active</th>
                                <th style="width: 15%;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $schedule_map = array();
                            foreach ($schedules as $schedule) {
                                $schedule_map[$schedule['week']] = $schedule;
                            }

                            foreach ($available_weeks as $week_key => $week_label): 
                                $schedule = $schedule_map[$week_key] ?? null;
                                $start_date = $schedule ? date('Y-m-d\TH:i', strtotime($schedule['start_date'])) : '';
                                $end_date = $schedule ? date('Y-m-d\TH:i', strtotime($schedule['end_date'])) : '';
                                $is_active = $schedule ? $schedule['is_active'] : 0;
                                
                                $current_time = current_time('timestamp');
                                $start_timestamp = $schedule ? strtotime($schedule['start_date']) : 0;
                                $end_timestamp = $schedule ? strtotime($schedule['end_date']) : 0;
                                
                                $status = 'Not Scheduled';
                                $status_color = '#999';
                                
                                if ($schedule) {
                                    if ($current_time < $start_timestamp) {
                                        $status = 'Upcoming';
                                        $status_color = '#0073aa';
                                    } elseif ($current_time >= $start_timestamp && $current_time <= $end_timestamp) {
                                        $status = 'Current';
                                        $status_color = '#00a32a';
                                    } else {
                                        $status = 'Past';
                                        $status_color = '#d63638';
                                    }
                                }
                            ?>
                                <tr>
                                    <td><strong><?php echo esc_html($week_label); ?></strong></td>
                                    <td>
                                        <input type="datetime-local" 
                                               name="schedule_data[<?php echo $week_key; ?>][start_date]" 
                                               value="<?php echo esc_attr($start_date); ?>"
                                               style="width: 100%;" />
                                    </td>
                                    <td>
                                        <input type="datetime-local" 
                                               name="schedule_data[<?php echo $week_key; ?>][end_date]" 
                                               value="<?php echo esc_attr($end_date); ?>"
                                               style="width: 100%;" />
                                    </td>
                                    <td style="text-align: center;">
                                        <input type="checkbox" 
                                               name="schedule_data[<?php echo $week_key; ?>][is_active]" 
                                               value="1" 
                                               <?php checked($is_active, 1); ?>
                                               onchange="handleActiveChange(this, '<?php echo $week_key; ?>')" />
                                    </td>
                                    <td>
                                        <span style="color: <?php echo $status_color; ?>; font-weight: 600;">
                                            <?php echo $status; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
                        <h4 style="margin-top: 0; color: #856404;">Schedule Configuration Notes:</h4>
                        <ul style="margin-bottom: 0; color: #856404;">
                            <li><strong>Active Week:</strong> Only one week should be marked as active at a time</li>
                            <li><strong>Manual Override:</strong> The "Active" checkbox overrides automatic date-based detection</li>
                            <li><strong>Date Format:</strong> Use your local timezone - dates will be stored in WordPress timezone</li>
                            <li><strong>Current Status:</strong> Shows whether the week is upcoming, current, or past based on dates</li>
                            <li><strong>Frontend Display:</strong> The active week will be the default week shown in the frontend widget</li>
                        </ul>
                    </div>

                    <p class="submit" style="margin-top: 20px;">
                        <input type="submit" name="update_schedule" class="button-primary" value="Update Schedule">
                    </p>
                </div>
            </div>
        </form>
    </div>

    <script>
    function handleActiveChange(checkbox, weekKey) {
        if (checkbox.checked) {
            // Uncheck all other active checkboxes
            const allActiveCheckboxes = document.querySelectorAll('input[name*="[is_active]"]');
            allActiveCheckboxes.forEach(cb => {
                if (cb !== checkbox) {
                    cb.checked = false;
                }
            });
        }
    }

    // Auto-fill end date when start date changes
    document.addEventListener('DOMContentLoaded', function() {
        const startDateInputs = document.querySelectorAll('input[name*="[start_date]"]');
        startDateInputs.forEach(startInput => {
            startInput.addEventListener('change', function() {
                const weekKey = this.name.match(/\[([^\]]+)\]/)[1];
                const endInput = document.querySelector(`input[name="schedule_data[${weekKey}][end_date]"]`);
                
                if (this.value && !endInput.value) {
                    // Auto-set end date to 7 days after start date
                    const startDate = new Date(this.value);
                    startDate.setDate(startDate.getDate() + 7);
                    
                    const year = startDate.getFullYear();
                    const month = String(startDate.getMonth() + 1).padStart(2, '0');
                    const day = String(startDate.getDate()).padStart(2, '0');
                    const hours = String(startDate.getHours()).padStart(2, '0');
                    const minutes = String(startDate.getMinutes()).padStart(2, '0');
                    
                    endInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
                }
            });
        });
    });
    </script>
    <?php
}

function nfl_ranking_admin_page() {
    global $wpdb;
    $table_players = $wpdb->prefix . 'nfl_players';

    $current_week = isset($_GET['week']) ? sanitize_text_field($_GET['week']) : 'week1';
    $current_position = isset($_GET['position']) ? sanitize_text_field($_GET['position']) : 'QB';

    $available_weeks = nfl_ranking_get_weeks();
    if (!array_key_exists($current_week, $available_weeks)) {
        $current_week = 'week1';
    }
    if (!in_array($current_position, ['QB', 'RB', 'WR', 'TE'])) {
        $current_position = 'QB';
    }

    // Get upload limits
    $upload_limits = get_option('nfl_ranking_upload_limits', array());
    $current_limit = isset($upload_limits[$current_position]) ? $upload_limits[$current_position] : 50;

    // Get players for current position and week - ordered by sort_order (upload order)
    $players = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_players WHERE position=%s AND week=%s ORDER BY sort_order ASC, name ASC", $current_position, $current_week
    ));

    // Get player counts for each week/position combination
    $player_counts = [];
    foreach ($available_weeks as $week_key => $week_label) {
        foreach (['QB', 'RB', 'WR', 'TE'] as $pos) {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_players WHERE position=%s AND week=%s", $pos, $week_key
            ));
            $player_counts[$week_key][$pos] = intval($count);
        }
    }

    // Show messages
    if (isset($_GET['uploaded'])) {
        $count = intval($_GET['uploaded']);
        $week_label = $available_weeks[$current_week];
        echo '<div class="notice notice-success is-dismissible"><p>Successfully uploaded ' . $count . ' ' . $current_position . ' players for ' . $week_label . '.</p></div>';
    }

    if (isset($_GET['deleted'])) {
        $count = intval($_GET['deleted']);
        echo '<div class="notice notice-success is-dismissible"><p>Successfully deleted ' . $count . ' players.</p></div>';
    }

    if (isset($_GET['upload_error'])) {
        $error_messages = [
            '1' => 'File upload failed.',
            '2' => 'Could not read CSV file.',
            '3' => 'CSV file appears to be empty or has no valid rows.',
            '4' => 'CSV must contain name, team, and opponent columns. Check your column headers.'
        ];
        $error_code = $_GET['upload_error'];
        $error_message = isset($error_messages[$error_code]) ? $error_messages[$error_code] : 'Unknown upload error.';
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error_message) . '</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>NFL Player Rankings - Manage Players (v<?php echo NFL_RANKING_PLUGIN_VERSION; ?>)</h1>

        <!-- Week Tabs -->
        <div class="nav-tab-wrapper" style="margin: 20px 0;">
            <?php foreach ($available_weeks as $week_key => $week_label): 
                $is_active = ($current_week === $week_key);
                $tab_url = admin_url('admin.php?page=nfl-ranking-admin&week=' . $week_key . '&position=' . $current_position);
            ?>
                <a href="<?php echo esc_url($tab_url); ?>" class="nav-tab <?php echo $is_active ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html($week_label); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div style="display: flex; gap: 20px; margin-top: 20px;">
            <!-- Left Column - Upload Form -->
            <div style="flex: 1;">
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">Upload Players - <?php echo esc_html($available_weeks[$current_week]); ?></h2>
                    </div>
                    <div class="inside">
                        <form method="post" enctype="multipart/form-data">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">Week</th>
                                    <td>
                                        <select name="week" required onchange="updateWeekSelection(this.value)">
                                            <?php foreach ($available_weeks as $week_key => $week_label): ?>
                                                <option value="<?php echo $week_key; ?>" <?php selected($current_week, $week_key); ?>>
                                                    <?php echo $week_label; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Position</th>
                                    <td>
                                        <select name="position" required onchange="updatePositionSelection(this.value)">
                                            <?php foreach (['QB', 'RB', 'WR', 'TE'] as $pos): ?>
                                                <option value="<?php echo $pos; ?>" <?php selected($current_position, $pos); ?>>
                                                    <?php echo $pos; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">CSV File</th>
                                    <td>
                                        <input type="file" name="csv_file" accept=".csv" required>
                                        <p class="description">CSV must contain columns: name, team, opponent</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Upload Mode</th>
                                    <td>
                                        <label><input type="radio" name="upload_mode" value="append" checked> Append to existing</label><br>
                                        <label><input type="radio" name="upload_mode" value="override"> Override existing</label>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Upload Limit</th>
                                    <td>
                                        <input type="number" name="upload_limit" value="<?php echo $current_limit; ?>" min="0" max="200">
                                        <p class="description">Maximum number of players to upload (0=no limit)</p>
                                    </td>
                                </tr>
                            </table>
                            <?php wp_nonce_field('nfl_ranking_upload', 'upload_nonce'); ?>
                            <p class="submit">
                                <input type="submit" name="upload_csv" class="button-primary" value="Upload Players">
                            </p>
                        </form>

                        <!-- Sample CSV Format -->
                        <div style="margin-top: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                            <h4 style="margin-top: 0;">Sample CSV Format:</h4>
                            <pre style="background: white; padding: 10px; border: 1px solid #ccc; border-radius: 3px; font-size: 12px;">name,team,opponent
Josh Allen,Buffalo Bills,Miami Dolphins
Cooper Kupp,Los Angeles Rams,Arizona Cardinals
Jonathan Taylor,Indianapolis Colts,Tennessee Titans
Davante Adams,Las Vegas Raiders,Kansas City Chiefs</pre>
                            <p style="margin-top: 10px; font-size: 12px; color: #666;">
                                <strong>Note:</strong> Headers are case-insensitive. "Name", "Player", "Team", "Opponent" all work.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Players List -->
            <div style="flex: 2;">
                <!-- Position Filter -->
                <div style="margin-bottom: 15px; display: flex; align-items: center; gap: 15px;">
                    <div>
                        <label for="position-filter"><strong>View Position:</strong></label>
                        <select id="position-filter" onchange="updatePositionSelection(this.value)">
                            <?php foreach (['QB', 'RB', 'WR', 'TE'] as $pos): 
                                $pos_count = $player_counts[$current_week][$pos] ?? 0;
                            ?>
                                <option value="<?php echo $pos; ?>" <?php selected($current_position, $pos); ?>>
                                    <?php echo $pos; ?> (<?php echo $pos_count; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">
                            <?php echo $available_weeks[$current_week]; ?> - <?php echo $current_position; ?> Players (<?php echo count($players); ?>)
                        </h2>
                    </div>
                    <div class="inside">
                        <?php if (!empty($players)): ?>
                            <form method="post" id="bulk-delete-form">
                                <div class="tablenav top">
                                    <div class="alignleft actions bulkactions">
                                        <input type="submit" name="bulk_delete" class="button action" value="Delete Selected" onclick="return confirmBulkDelete()">
                                    </div>
                                </div>

                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <td class="manage-column column-cb check-column">
                                                <input type="checkbox" id="cb-select-all-1">
                                            </td>
                                            <th class="manage-column">Order</th>
                                            <th class="manage-column">Name</th>
                                            <th class="manage-column">Team</th>
                                            <th class="manage-column">Opponent</th>
                                            <th class="manage-column">Created</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($players as $index => $player): ?>
                                            <tr>
                                                <th class="check-column">
                                                    <input type="checkbox" name="selected_players[]" value="<?php echo $player->id; ?>">
                                                </th>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><strong><?php echo esc_html($player->name); ?></strong></td>
                                                <td><?php echo esc_html($player->team); ?></td>
                                                <td><?php echo esc_html($player->opponent); ?></td>
                                                <td><?php echo date('M j, Y g:i A', strtotime($player->created_at)); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php wp_nonce_field('nfl_ranking_bulk_delete', 'delete_nonce'); ?>
                            </form>
                        <?php else: ?>
                            <p>No <?php echo $current_position; ?> players found for <?php echo $available_weeks[$current_week]; ?>. Upload a CSV file to get started.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function confirmBulkDelete() {
        var checkedBoxes = document.querySelectorAll('input[name="selected_players[]"]:checked').length;
        if (checkedBoxes === 0) {
            alert('Please select players to delete.');
            return false;
        }
        return confirm('Are you sure you want to delete ' + checkedBoxes + ' selected players? This cannot be undone.');
    }

    function updateWeekSelection(week) {
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('week', week);
        window.location.href = currentUrl.toString();
    }

    function updatePositionSelection(position) {
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('position', position);
        window.location.href = currentUrl.toString();
    }

    jQuery(document).ready(function($) {
        // Handle select all checkbox
        $('#cb-select-all-1').on('change', function() {
            $('input[name="selected_players[]"]').prop('checked', this.checked);
        });

        // Update select all when individual checkboxes change
        $('input[name="selected_players[]"]').on('change', function() {
            var total = $('input[name="selected_players[]"]').length;
            var checked = $('input[name="selected_players[]"]:checked').length;
            $('#cb-select-all-1').prop('checked', total === checked);
        });
    });
    </script>
    <?php
}

// UPDATED RANKINGS MANAGEMENT PAGE WITH SHORTCODE COLUMN
function nfl_ranking_rankings_page() {
    global $wpdb;
    $table_rankings = $wpdb->prefix . 'nfl_rankings';

    $current_week = isset($_GET['week']) ? sanitize_text_field($_GET['week']) : 'week1';
    $current_position = isset($_GET['position']) ? sanitize_text_field($_GET['position']) : 'QB';

    $available_weeks = nfl_ranking_get_weeks();
    if (!array_key_exists($current_week, $available_weeks)) {
        $current_week = 'week1';
    }
    if (!in_array($current_position, ['QB', 'RB', 'WR', 'TE'])) {
        $current_position = 'QB';
    }

    // Get rankings for current position and week with user information
    $rankings = $wpdb->get_results($wpdb->prepare(
        "SELECT r.*, u.display_name, u.user_email, u.user_nicename FROM $table_rankings r JOIN {$wpdb->users} u ON r.user_id = u.ID WHERE r.position=%s AND r.week=%s ORDER BY r.updated_at DESC", $current_position, $current_week
    ));

    // Get ranking counts for each week/position combination
    $ranking_counts = [];
    foreach ($available_weeks as $week_key => $week_label) {
        foreach (['QB', 'RB', 'WR', 'TE'] as $pos) {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_rankings WHERE position=%s AND week=%s", $pos, $week_key
            ));
            $ranking_counts[$week_key][$pos] = intval($count);
        }
    }

    // Show messages
    if (isset($_GET['deleted_rankings'])) {
        $count = intval($_GET['deleted_rankings']);
        echo '<div class="notice notice-success is-dismissible"><p>Successfully deleted ' . $count . ' rankings.</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>NFL Player Rankings - Manage Rankings (v<?php echo NFL_RANKING_PLUGIN_VERSION; ?>)</h1>

        <!-- Week Tabs -->
        <div class="nav-tab-wrapper" style="margin: 20px 0;">
            <?php foreach ($available_weeks as $week_key => $week_label): 
                $is_active = ($current_week === $week_key);
                $tab_url = admin_url('admin.php?page=nfl-ranking-rankings&week=' . $week_key . '&position=' . $current_position);
            ?>
                <a href="<?php echo esc_url($tab_url); ?>" class="nav-tab <?php echo $is_active ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html($week_label); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div style="display: flex; gap: 20px; margin-top: 20px;">
            <!-- Left Column - Statistics -->
            <div style="flex: 1;">
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">Rankings Overview - <?php echo esc_html($available_weeks[$current_week]); ?></h2>
                    </div>
                    <div class="inside">
                        <table class="form-table">
                            <?php foreach (['QB', 'RB', 'WR', 'TE'] as $pos): 
                                $pos_count = $ranking_counts[$current_week][$pos] ?? 0;
                            ?>
                                <tr>
                                    <th scope="row"><?php echo $pos; ?> Rankings</th>
                                    <td>
                                        <span class="count"><?php echo $pos_count; ?></span>
                                        <?php if ($pos === $current_position): ?>
                                            <span style="color: #0073aa; font-weight: bold;"> (Currently Viewing)</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <th scope="row">Total Rankings</th>
                                <td>
                                    <strong><?php echo array_sum($ranking_counts[$current_week] ?? []); ?></strong>
                                </td>
                            </tr>
                        </table>

                        <div style="margin-top: 20px; padding: 15px; background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 4px;">
                            <h4 style="margin-top: 0; color: #0073aa;">Quick Actions</h4>
                            <p style="margin: 10px 0; color: #666;">
                                • Use checkboxes to select multiple rankings<br>
                                • Click "Delete Selected" to remove rankings in bulk<br>
                                • Copy shortcode to embed user rankings anywhere<br>
                                • Click username to view user's WordPress profile
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Rankings List -->
            <div style="flex: 2;">
                <!-- Position Filter -->
                <div style="margin-bottom: 15px; display: flex; align-items: center; gap: 15px;">
                    <div>
                        <label for="position-filter"><strong>View Position:</strong></label>
                        <select id="position-filter" onchange="updatePositionSelection(this.value)">
                            <?php foreach (['QB', 'RB', 'WR', 'TE'] as $pos): 
                                $pos_count = $ranking_counts[$current_week][$pos] ?? 0;
                            ?>
                                <option value="<?php echo $pos; ?>" <?php selected($current_position, $pos); ?>>
                                    <?php echo $pos; ?> (<?php echo $pos_count; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">
                            <?php echo $available_weeks[$current_week]; ?> - <?php echo $current_position; ?> Rankings (<?php echo count($rankings); ?>)
                        </h2>
                    </div>
                    <div class="inside">
                        <?php if (!empty($rankings)): ?>
                            <form method="post" id="bulk-delete-rankings-form">
                                <div class="tablenav top">
                                    <div class="alignleft actions bulkactions">
                                        <input type="submit" name="bulk_delete_rankings" class="button action" value="Delete Selected" onclick="return confirmBulkDeleteRankings()">
                                    </div>
                                </div>

                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <td class="manage-column column-cb check-column">
                                                <input type="checkbox" id="cb-select-all-rankings">
                                            </td>
                                            <th class="manage-column">User</th>
                                            <th class="manage-column">Week</th>
                                            <th class="manage-column">Last Updated</th>
                                            <th class="manage-column">Shortcode</th>
                                            <th class="manage-column">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rankings as $ranking): 
                                            $ranking_data = json_decode($ranking->ranking_data, true);
                                            $player_count = is_array($ranking_data) ? count(array_filter($ranking_data, function($item) {
                                                return isset($item['type']) && $item['type'] === 'player';
                                            })) : 0;

                                            // Generate shortcode
                                            $shortcode = sprintf('[user_nfl_ranking user_id="%d" position="%s" week="%s"]', $ranking->user_id, $ranking->position, $ranking->week);

                                            // User profile URL
                                            $profile_url = admin_url('user-edit.php?user_id=' . $ranking->user_id);
                                        ?>
                                            <tr id="ranking-row-<?php echo $ranking->id; ?>">
                                                <th class="check-column">
                                                    <input type="checkbox" name="selected_rankings[]" value="<?php echo $ranking->id; ?>">
                                                </th>
                                                <td>
                                                    <a href="<?php echo esc_url($profile_url); ?>" target="_blank">
                                                        <strong><?php echo esc_html($ranking->display_name); ?></strong>
                                                    </a>
                                                </td>
                                                <td><?php echo esc_html($available_weeks[$ranking->week] ?? $ranking->week); ?></td>
                                                <td><?php echo date('M j, Y g:i A', strtotime($ranking->updated_at)); ?></td>
                                                <td>
                                                    <input type="text" value="<?php echo esc_attr($shortcode); ?>" readonly style="width: 100%; font-family: monospace; font-size: 11px; background: #f9f9f9; border: 1px solid #ddd; padding: 3px 6px;" onclick="this.select()" title="Click to select shortcode">
                                                    <button type="button" class="button button-small" onclick="copyShortcode(this, '<?php echo esc_js($shortcode); ?>')" style="margin-top: 3px;">
                                                        📋 Copy
                                                    </button>
                                                </td>
                                                <td>
                                                    <button type="button" class="button button-small" onclick="toggleRankingDetails(<?php echo $ranking->id; ?>)">
                                                        View Details
                                                    </button>
                                                    <button type="button" class="button button-small" onclick="confirmSingleDelete(<?php echo $ranking->id; ?>, '<?php echo esc_js($ranking->display_name); ?>')">
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr id="ranking-details-<?php echo $ranking->id; ?>" style="display: none;">
                                                <td colspan="6">
                                                    <div style="padding: 15px; background: #f9f9f9; border: 1px solid #ddd; margin: 5px 0;">
                                                        <h4>Ranking Details for <?php echo esc_html($ranking->display_name); ?></h4>
                                                        <div style="max-height: 300px; overflow-y: auto;">
                                                            <?php if (is_array($ranking_data) && !empty($ranking_data)): 
                                                                $player_rank = 1;
                                                                foreach ($ranking_data as $item):
                                                                    if (isset($item['type'])):
                                                                        if ($item['type'] === 'tier'):
                                                            ?>
                                                                            <div style="background: #e7e7e7; padding: 8px; margin: 5px 0; font-weight: bold; text-align: center; border-radius: 4px;">
                                                                                <?php echo esc_html($item['name'] ?? 'Unknown Tier'); ?>
                                                                            </div>
                                                            <?php
                                                                        elseif ($item['type'] === 'player'):
                                                            ?>
                                                                            <div style="padding: 5px 10px; margin: 2px 0; background: white; border-left: 3px solid #0073aa;">
                                                                                <strong><?php echo $player_rank; ?>.</strong> <?php echo esc_html($item['name'] ?? 'Unknown Player'); ?>
                                                                                <?php if (isset($item['team']) && isset($item['opponent'])): ?>
                                                                                    <span style="color: #666;"> - <?php echo esc_html($item['team']); ?> vs <?php echo esc_html($item['opponent']); ?></span>
                                                                                <?php endif; ?>
                                                                            </div>
                                                            <?php
                                                                            $player_rank++;
                                                                        endif;
                                                                    endif;
                                                                endforeach;
                                                            else:
                                                            ?>
                                                                <p style="color: #666; font-style: italic;">No ranking data available or invalid format.</p>
                                                            <?php endif; ?>
                                                        </div>

                                                        <!-- Shortcode Usage Example -->
                                                        <div style="margin-top: 15px; padding: 10px; background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 4px;">
                                                            <strong>Shortcode Usage:</strong><br>
                                                            <code style="background: white; padding: 2px 6px; border: 1px solid #ccc;"><?php echo esc_html($shortcode); ?></code><br>
                                                            <small style="color: #666;">Paste this shortcode into any post, page, or widget to display this user's ranking.</small>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php wp_nonce_field('nfl_ranking_bulk_delete_rankings', 'delete_rankings_nonce'); ?>
                            </form>
                        <?php else: ?>
                            <p>No <?php echo $current_position; ?> rankings found for <?php echo $available_weeks[$current_week]; ?>.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function copyShortcode(button, shortcode) {
        navigator.clipboard.writeText(shortcode).then(function() {
            button.textContent = '✅ Copied!';
            setTimeout(function() {
                button.innerHTML = '📋 Copy';
            }, 2000);
        }).catch(function() {
            // Fallback for older browsers
            var input = button.previousElementSibling;
            input.select();
            document.execCommand('copy');
            button.textContent = '✅ Copied!';
            setTimeout(function() {
                button.innerHTML = '📋 Copy';
            }, 2000);
        });
    }

    function confirmBulkDeleteRankings() {
        var checkedBoxes = document.querySelectorAll('input[name="selected_rankings[]"]:checked').length;
        if (checkedBoxes === 0) {
            alert('Please select rankings to delete.');
            return false;
        }
        return confirm('Are you sure you want to delete ' + checkedBoxes + ' selected rankings? This cannot be undone.');
    }

    function confirmSingleDelete(rankingId, userName) {
        if (confirm('Are you sure you want to delete the ranking by ' + userName + '? This cannot be undone.')) {
            // Create a temporary form to delete single ranking
            var form = document.createElement('form');
            form.method = 'post';
            form.innerHTML = '<input type="hidden" name="selected_rankings[]" value="' + rankingId + '">' +
                           '<input type="hidden" name="bulk_delete_rankings" value="1">' +
                           '<?php echo wp_nonce_field('nfl_ranking_bulk_delete_rankings', 'delete_rankings_nonce', true, false); ?>';
            document.body.appendChild(form);
            form.submit();
        }
    }

    function toggleRankingDetails(rankingId) {
        var detailsRow = document.getElementById('ranking-details-' + rankingId);
        if (detailsRow.style.display === 'none') {
            detailsRow.style.display = 'table-row';
        } else {
            detailsRow.style.display = 'none';
        }
    }

    function updateWeekSelection(week) {
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('week', week);
        window.location.href = currentUrl.toString();
    }

    function updatePositionSelection(position) {
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('position', position);
        window.location.href = currentUrl.toString();
    }

    jQuery(document).ready(function($) {
        // Handle select all checkbox
        $('#cb-select-all-rankings').on('change', function() {
            $('input[name="selected_rankings[]"]').prop('checked', this.checked);
        });

        // Update select all when individual checkboxes change
        $('input[name="selected_rankings[]"]').on('change', function() {
            var total = $('input[name="selected_rankings[]"]').length;
            var checked = $('input[name="selected_rankings[]"]:checked').length;
            $('#cb-select-all-rankings').prop('checked', total === checked);
        });
    });
    </script>
    <?php
}

function nfl_ranking_settings_page() {
    if (isset($_POST['save_limits'])) {
        $limits = array();
        foreach (['QB', 'RB', 'WR', 'TE'] as $pos) {
            $limits[$pos] = intval($_POST['limit_' . $pos]);
        }
        update_option('nfl_ranking_upload_limits', $limits);
        echo '<div class="notice notice-success is-dismissible"><p>Upload limits saved successfully!</p></div>';
    }

    $upload_limits = get_option('nfl_ranking_upload_limits', array());
    ?>
    <div class="wrap">
        <h1>NFL Player Ranking Settings (v<?php echo NFL_RANKING_PLUGIN_VERSION; ?>)</h1>

        <!-- Upload Limits -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle">Upload Limits by Position</h2>
            </div>
            <div class="inside">
                <form method="post">
                    <table class="form-table">
                        <?php foreach (['QB', 'RB', 'WR', 'TE'] as $pos): ?>
                            <tr>
                                <th scope="row"><?php echo $pos; ?> Limit</th>
                                <td>
                                    <input type="number" name="limit_<?php echo $pos; ?>" value="<?php echo isset($upload_limits[$pos]) ? $upload_limits[$pos] : 50; ?>" min="0" max="200">
                                    <p class="description">Maximum <?php echo $pos; ?> players to upload (0=no limit)</p>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    <p class="submit">
                        <input type="submit" name="save_limits" class="button-primary" value="Save Upload Limits">
                    </p>
                </form>
            </div>
        </div>
    </div>
    <?php
}

// NEW USER RANKING SHORTCODE
function nfl_user_ranking_shortcode($atts = []) {
    // Parse shortcode attributes
    $atts = shortcode_atts([
        'user_id' => 0,
        'position' => 'QB',
        'week' => 'week1',
        'title' => '', // Optional custom title
        'show_user' => 'true' // Show/hide user name
    ], $atts);

    $user_id = intval($atts['user_id']);
    $position = sanitize_text_field($atts['position']);
    $week = sanitize_text_field($atts['week']);
    $custom_title = sanitize_text_field($atts['title']);
    $show_user = $atts['show_user'] !== 'false';

    // Validate inputs
    if ($user_id <= 0) {
        return '<div style="color: red; padding: 10px; border: 1px solid red;">Error: Invalid user_id specified.</div>';
    }

    if (!in_array($position, ['QB', 'RB', 'WR', 'TE'])) {
        return '<div style="color: red; padding: 10px; border: 1px solid red;">Error: Invalid position. Use QB, RB, WR, or TE.</div>';
    }

    global $wpdb;

    // Get user info
    $user = get_user_by('ID', $user_id);
    if (!$user) {
        return '<div style="color: red; padding: 10px; border: 1px solid red;">Error: User not found.</div>';
    }

    // Get user's ranking
    $table_rankings = $wpdb->prefix . 'nfl_rankings';
    $ranking_data = $wpdb->get_var($wpdb->prepare(
        "SELECT ranking_data FROM $table_rankings WHERE user_id=%d AND position=%s AND week=%s", $user_id, $position, $week
    ));

    if (!$ranking_data) {
        $available_weeks = nfl_ranking_get_weeks();
        $week_name = $available_weeks[$week] ?? $week;
        return '<div style="color: #666; padding: 15px; text-align: center; border: 1px solid #ddd; background: #f9f9f9;">No ' . $position . ' ranking found for ' . esc_html($user->display_name) . ' in ' . $week_name . '.</div>';
    }

    $ranking_array = json_decode($ranking_data, true);
    if (!is_array($ranking_array)) {
        return '<div style="color: red; padding: 10px; border: 1px solid red;">Error: Invalid ranking data format.</div>';
    }

    // Get consensus data for differential calculation
    $all_rankings = $wpdb->get_results($wpdb->prepare(
        "SELECT ranking_data FROM $table_rankings WHERE position=%s AND week=%s", $position, $week
    ), ARRAY_A);

    $consensus_ranks = [];
    if (!empty($all_rankings)) {
        // Calculate consensus rankings for differential
        $table_players = $wpdb->prefix . 'nfl_players';
        $players = $wpdb->get_results($wpdb->prepare(
            "SELECT id, name, team, opponent FROM $table_players WHERE position=%s AND week=%s", $position, $week
        ), ARRAY_A);

        $player_stats = [];
        foreach ($players as $player) {
            $player_stats[$player['id']] = [
                'rankSum' => 0,
                'rankCount' => 0
            ];
        }

        foreach ($all_rankings as $ranking_row) {
            $data = json_decode($ranking_row['ranking_data'], true);
            if (!is_array($data)) continue;

            $player_rank = 1;
            foreach ($data as $item) {
                if (isset($item['type']) && $item['type'] === 'player' && isset($item['id'])) {
                    if (isset($player_stats[$item['id']])) {
                        $player_stats[$item['id']]['rankSum'] += $player_rank;
                        $player_stats[$item['id']]['rankCount'] += 1;
                    }
                    $player_rank++;
                }
            }
        }

        $consensus_players = [];
        foreach ($player_stats as $player_id => $stats) {
            if ($stats['rankCount'] > 0) {
                $consensus_players[] = [
                    'id' => $player_id,
                    'avg_rank' => $stats['rankSum'] / $stats['rankCount']
                ];
            }
        }

        usort($consensus_players, function($a, $b) {
            return $a['avg_rank'] <=> $b['avg_rank'];
        });

        foreach ($consensus_players as $index => $player) {
            $consensus_ranks[$player['id']] = $index + 1;
        }
    }

    // Generate output
    $available_weeks = nfl_ranking_get_weeks();
    $week_name = $available_weeks[$week] ?? $week;

    // Determine title
    if ($custom_title) {
        $title = $custom_title;
    } else {
        $title = $show_user ? $position . ' Rankings by ' . esc_html($user->display_name) : $position . ' Rankings';
    }

    ob_start();
    ?>
    <div class="nfl-user-ranking-embed" style="max-width: 800px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #1a1a1a; color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
        <!-- Header -->
        <div style="background: #dc2626; padding: 15px 20px; text-align: center;">
            <h3 style="margin: 0; color: #ffffff; font-size: 18px; font-weight: 600;">
                <?php echo esc_html($title); ?>
            </h3>
            <?php if ($show_user): ?>
                <div style="font-size: 14px; color: rgba(255,255,255,0.9); margin-top: 5px;">
                    <?php echo esc_html($week_name); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Rankings Table -->
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; background: #2d2d2d;">
                <thead>
                    <tr style="background: #1a1a1a;">
                        <th style="padding: 12px; text-align: left; font-weight: 600; color: #ffffff; border-bottom: 1px solid #444;">Rank</th>
                        <th style="padding: 12px; text-align: left; font-weight: 600; color: #ffffff; border-bottom: 1px solid #444;">Player</th>
                        <th style="padding: 12px; text-align: left; font-weight: 600; color: #ffffff; border-bottom: 1px solid #444;">Team</th>
                        <th style="padding: 12px; text-align: left; font-weight: 600; color: #ffffff; border-bottom: 1px solid #444;">Opp</th>
                        <?php if (!empty($consensus_ranks)): ?>
                            <th style="padding: 12px; text-align: center; font-weight: 600; color: #ffffff; border-bottom: 1px solid #444;">Diff</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $player_rank = 1;
                    foreach ($ranking_array as $item):
                        if (!isset($item['type'])) continue;

                        if ($item['type'] === 'tier'):
                    ?>
                            <tr>
                                <td colspan="<?php echo !empty($consensus_ranks) ? '5' : '4'; ?>" style="padding: 0;">
                                    <div style="background: #4a4a4a; color: #d1d5db; font-weight: 700; padding: 12px; text-align: center; border-bottom: 1px solid #666;">
                                        <?php echo esc_html($item['name'] ?? 'Unknown Tier'); ?>
                                    </div>
                                </td>
                            </tr>
                    <?php
                        elseif ($item['type'] === 'player'):
                            // Calculate differential
                            $diff_html = '';
                            if (!empty($consensus_ranks) && isset($item['id'], $consensus_ranks[$item['id']])) {
                                $consensus_rank = $consensus_ranks[$item['id']];
                                $diff = $consensus_rank - $player_rank;
                                if ($diff > 0) {
                                    $diff_html = '<span style="color: #22c55e; font-weight: 600;">+' . $diff . '</span>';
                                } elseif ($diff < 0) {
                                    $diff_html = '<span style="color: #ef4444; font-weight: 600;">' . $diff . '</span>';
                                } else {
                                    $diff_html = '<span style="color: #6b7280; font-weight: 600;">0</span>';
                                }
                            }
                    ?>
                            <tr style="border-bottom: 1px solid #444;">
                                <td style="padding: 12px;">
                                    <span style="background: #dc2626; color: #ffffff; width: 30px; height: 30px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">
                                        <?php echo $player_rank; ?>
                                    </span>
                                </td>
                                <td style="padding: 12px; color: #ffffff; font-weight: 600;">
                                    <?php echo esc_html($item['name'] ?? 'Unknown Player'); ?>
                                </td>
                                <td style="padding: 12px; color: #cccccc;">
                                    <?php echo esc_html($item['team'] ?? ''); ?>
                                </td>
                                <td style="padding: 12px; color: #cccccc;">
                                    <?php echo esc_html($item['opponent'] ?? ''); ?>
                                </td>
                                <?php if (!empty($consensus_ranks)): ?>
                                    <td style="padding: 12px; text-align: center;">
                                        <?php echo $diff_html ?: '-'; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                    <?php
                            $player_rank++;
                        endif;
                    endforeach;
                    ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($consensus_ranks)): ?>
            <!-- Legend -->
            <div style="padding: 15px 20px; background: #2d2d2d; border-top: 1px solid #444; font-size: 12px; color: #999;">
                <strong style="color: #ffffff;">Diff:</strong> Difference from consensus ranking (<span style="color: #22c55e;">+positive</span> = ranked higher than consensus, <span style="color: #ef4444;">negative</span> = ranked lower than consensus)
            </div>
        <?php endif; ?>
    </div>

    <style>
    .nfl-user-ranking-embed tr:hover {
        background-color: rgba(255,255,255,0.05) !important;
    }
    @media (max-width: 768px) {
        .nfl-user-ranking-embed table {
            font-size: 14px;
        }
        .nfl-user-ranking-embed th,
        .nfl-user-ranking-embed td {
            padding: 8px !important;
        }
    }
    </style>
    <?php

    return ob_get_clean();
}
add_shortcode('user_nfl_ranking', 'nfl_user_ranking_shortcode');

// API endpoints for frontend
function nfl_ranking_get_players() {
    global $wpdb;
    $table_players = $wpdb->prefix . 'nfl_players';

    $position = sanitize_text_field($_GET['position']);
    $week = isset($_GET['week']) ? sanitize_text_field($_GET['week']) : nfl_ranking_get_current_week();

    if (!in_array($position, ['QB', 'RB', 'WR', 'TE'])) {
        wp_die('Invalid position');
    }

    $players = $wpdb->get_results($wpdb->prepare(
        "SELECT id, name, team, opponent FROM $table_players WHERE position=%s AND week=%s ORDER BY sort_order ASC, name ASC", $position, $week
    ), ARRAY_A);

    wp_send_json_success($players);
}
add_action('wp_ajax_nfl_get_players', 'nfl_ranking_get_players');
add_action('wp_ajax_nopriv_nfl_get_players', 'nfl_ranking_get_players');

function nfl_ranking_save_user_ranking() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Must be logged in');
    }

    global $wpdb;
    $table_rankings = $wpdb->prefix . 'nfl_rankings';

    $user_id = get_current_user_id();
    $position = sanitize_text_field($_POST['position']);
    $week = isset($_POST['week']) ? sanitize_text_field($_POST['week']) : nfl_ranking_get_current_week();
    $ranking_data = wp_unslash($_POST['ranking_data']);

    if (!in_array($position, ['QB', 'RB', 'WR', 'TE'])) {
        wp_send_json_error('Invalid position');
    }

    $result = $wpdb->replace($table_rankings, array(
        'user_id' => $user_id,
        'position' => $position,
        'week' => $week,
        'ranking_data' => $ranking_data
    ));

    if ($result === false) {
        wp_send_json_error('Failed to save ranking');
    }

    wp_send_json_success('Ranking saved successfully');
}
add_action('wp_ajax_nfl_save_ranking', 'nfl_ranking_save_user_ranking');

function nfl_ranking_delete_user_ranking() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Must be logged in');
    }

    global $wpdb;
    $table_rankings = $wpdb->prefix . 'nfl_rankings';

    $user_id = get_current_user_id();
    $position = sanitize_text_field($_POST['position']);
    $week = isset($_POST['week']) ? sanitize_text_field($_POST['week']) : nfl_ranking_get_current_week();

    if (!in_array($position, ['QB', 'RB', 'WR', 'TE'])) {
        wp_send_json_error('Invalid position');
    }

    $result = $wpdb->delete($table_rankings, array(
        'user_id' => $user_id,
        'position' => $position,
        'week' => $week
    ));

    if ($result === false) {
        wp_send_json_error('Failed to delete ranking');
    }

    wp_send_json_success('Ranking deleted successfully');
}
add_action('wp_ajax_nfl_delete_ranking', 'nfl_ranking_delete_user_ranking');

function nfl_ranking_get_user_ranking() {
    if (!is_user_logged_in()) {
        wp_send_json_success(null);
    }

    global $wpdb;
    $table_rankings = $wpdb->prefix . 'nfl_rankings';

    $user_id = get_current_user_id();
    $position = sanitize_text_field($_GET['position']);
    $week = isset($_GET['week']) ? sanitize_text_field($_GET['week']) : nfl_ranking_get_current_week();

    if (!in_array($position, ['QB', 'RB', 'WR', 'TE'])) {
        wp_send_json_error('Invalid position');
    }

    $ranking = $wpdb->get_var($wpdb->prepare(
        "SELECT ranking_data FROM $table_rankings WHERE user_id=%d AND position=%s AND week=%s", $user_id, $position, $week
    ));

    wp_send_json_success($ranking ? json_decode($ranking, true) : null);
}
add_action('wp_ajax_nfl_get_user_ranking', 'nfl_ranking_get_user_ranking');

function nfl_ranking_get_consensus() {
    global $wpdb;
    $table_rankings = $wpdb->prefix . 'nfl_rankings';

    $position = sanitize_text_field($_GET['position']);
    $week = isset($_GET['week']) ? sanitize_text_field($_GET['week']) : nfl_ranking_get_current_week();

    if (!in_array($position, ['QB', 'RB', 'WR', 'TE'])) {
        wp_send_json_error('Invalid position');
    }

    $rankings = $wpdb->get_results($wpdb->prepare(
        "SELECT r.ranking_data, u.display_name, r.user_id FROM $table_rankings r JOIN {$wpdb->users} u ON r.user_id = u.ID WHERE r.position=%s AND r.week=%s", $position, $week
    ), ARRAY_A);

    wp_send_json_success($rankings);
}
add_action('wp_ajax_nfl_get_consensus', 'nfl_ranking_get_consensus');
add_action('wp_ajax_nopriv_nfl_get_consensus', 'nfl_ranking_get_consensus');

// Frontend shortcode with tier functionality - AESTHETIC UPDATES v4.2.0
function nfl_ranking_shortcode($atts = []) {
    // Enqueue SortableJS
    wp_enqueue_script('sortablejs', 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js', [], '1.15.0', true);

    $current_user = wp_get_current_user();
    $is_logged_in = is_user_logged_in();
    $current_week = nfl_ranking_get_current_week(); // Use current active week
    $available_weeks = nfl_ranking_get_weeks();
    $current_week_label = $available_weeks[$current_week] ?? $current_week;

    ob_start();
    ?>
    <style>
    .nfl-ranking-widget {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        background: transparent;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        color: #ffffff;
    }

    .nfl-main-tabs {
        display: flex;
        background: #2d2d2d;
        border-radius: 8px;
        padding: 4px;
        margin-bottom: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }

    .nfl-main-tabs.hidden {
        display: none;
    }

    .nfl-main-tab {
        flex: 1;
        padding: 12px 20px;
        background: transparent;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
        color: #ffffff;
    }

    .nfl-main-tab.active {
        background: #dc2626;
        color: white;
        box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);
    }

    .nfl-main-tab:hover:not(.active) {
        background: #3d3d3d;
        color: #ffffff;
    }

    .nfl-view-content {
        background: #2d2d2d;
        border-radius: 8px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        min-height: 400px;
    }

    .nfl-view-controls {
        display: flex;
        gap: 20px;
        align-items: center;
        margin-bottom: 25px;
        flex-wrap: wrap;
        justify-content: space-between;
    }

    .nfl-position-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .nfl-position-btn {
        padding: 10px 20px;
        background: #1a1a1a;
        color: #ffffff;
        border: 2px solid #444;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s ease;
        min-width: 80px;
    }

    .nfl-position-btn:hover {
        background: #3d3d3d;
        border-color: #dc2626;
    }

    .nfl-position-btn.active {
        background: #dc2626;
        border-color: #dc2626;
        color: #ffffff;
    }

    .nfl-view-selector {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .nfl-view-selector label {
        font-weight: 600;
        color: #ffffff;
        font-size: 14px;
    }

    .nfl-select {
        padding: 10px 15px;
        border: 2px solid #444;
        border-radius: 6px;
        background: #1a1a1a;
        color: #ffffff;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        min-width: 120px;
    }

    .nfl-select:focus {
        outline: none;
        border-color: #dc2626;
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.2);
    }

    .nfl-loading {
        text-align: center;
        padding: 40px;
        color: #ffffff;
        font-size: 16px;
    }

    .nfl-loading:before {
        content: '⏳';
        display: block;
        font-size: 24px;
        margin-bottom: 10px;
    }

    .nfl-message {
        padding: 12px 20px;
        border-radius: 6px;
        margin-bottom: 20px;
        font-weight: 500;
    }

    .nfl-message.success {
        background: #166534;
        color: #dcfce7;
        border: 1px solid #16a34a;
    }

    .nfl-message.error {
        background: #991b1b;
        color: #fecaca;
        border: 1px solid #dc2626;
    }

    .nfl-ranking-container {
        max-width: 800px;
        margin: 0 auto;
    }

    .nfl-sortable-list {
        min-height: 200px;
        padding: 10px;
        border: 2px dashed #444;
        border-radius: 8px;
        background: #1a1a1a;
    }

    .nfl-item {
        margin-bottom: 4px;
        padding: 8px 15px;
        background: #3d3d3d;
        border-radius: 8px;
        cursor: move;
        transition: all 0.3s ease;
        border: 1px solid #555;
        position: relative;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .nfl-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        border-color: #dc2626;
    }

    .nfl-item.sortable-ghost {
        opacity: 0.5;
        background: #dc2626;
    }

    .nfl-item.sortable-drag {
        transform: rotate(5deg);
        box-shadow: 0 8px 25px rgba(0,0,0,0.5);
    }

    .nfl-player {
        background: #3d3d3d;
        border-left: 4px solid #dc2626;
    }

    .nfl-tier {
        background: #4a4a4a;
        border-left: 4px solid #6b7280;
        font-weight: 700;
        text-align: center;
        color: #d1d5db;
    }

    .nfl-rank-number {
        width: 40px;
        height: 40px;
        background: #dc2626;
        color: #ffffff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 16px;
        flex-shrink: 0;
    }

    .nfl-tier .nfl-rank-number {
        background: transparent;
        border: 2px solid #6b7280;
        color: #d1d5db;
    }

    .nfl-item-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex: 1;
    }

    .nfl-item-info {
        flex: 1;
    }

    .nfl-item-name {
        font-weight: 600;
        font-size: 16px;
        margin-bottom: 4px;
        color: #ffffff;
    }

    .nfl-item-details {
        font-size: 14px;
        color: #999;
        display: inline;
    }

    .nfl-item-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .nfl-drag-handle {
        color: #999;
        cursor: grab;
        font-size: 24px;
        padding: 10px;
        touch-action: none;
        user-select: none;
    }

    .nfl-drag-handle:active {
        cursor: grabbing;
    }

    .nfl-actions {
        margin-top: 25px;
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .nfl-user-info {
        text-align: center;
        margin-top: 15px;
        margin-bottom: 20px;
        padding: 10px;
        background: #1a1a1a;
        border-radius: 6px;
        border: 1px solid #444;
    }

    .nfl-user-info strong {
        color: #dc2626;
    }

    .nfl-btn {
        padding: 12px 24px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .nfl-btn-primary {
        background: #dc2626;
        color: white;
    }

    .nfl-btn-primary:hover {
        background: #b91c1c;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
    }

    .nfl-btn-danger {
        background: #dc2626;
        color: white;
    }

    .nfl-btn-danger:hover {
        background: #b91c1c;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
    }

    .nfl-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none !important;
        box-shadow: none !important;
    }

    .nfl-consensus-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .nfl-consensus-table th,
    .nfl-consensus-table td {
        padding: 8px 12px;
        text-align: left;
        border-bottom: 1px solid #444;
    }

    .nfl-consensus-table th {
        background: #1a1a1a;
        font-weight: 600;
        color: #ffffff;
        position: sticky;
        top: 0;
    }

    .nfl-consensus-table tbody tr:hover {
        background: #3d3d3d;
    }

    .nfl-consensus-table tbody td {
        color: #ffffff;
    }

    .nfl-consensus-rank {
        width: 60px;
        height: 30px;
        background: #dc2626;
        color: white;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 15px;
        font-weight: 700;
        font-size: 14px;
    }

    .nfl-consensus-tier {
        background: #4a4a4a;
        color: #d1d5db;
        font-weight: 700;
        padding: 4px 16px;
        border-radius: 6px;
        text-align: center;
        margin: 0;
    }

    .nfl-differential {
        font-weight: 600;
        font-size: 14px;
        text-align: center;
    }

    .nfl-differential.positive {
        color: #22c55e;
    }

    .nfl-differential.negative {
        color: #ef4444;
    }

    .nfl-differential.neutral {
        color: #6b7280;
    }

    .nfl-empty-state {
        text-align: center;
        padding: 40px;
        color: #ffffff;
    }

    .nfl-empty-state h3 {
        margin-bottom: 10px;
        color: #ffffff;
    }

    .nfl-login-notice {
        background: #dc2626;
        color: #ffffff;
        border: 1px solid #dc2626;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        text-align: center;
        font-weight: 500;
    }

    .nfl-consensus-header {
        color: #ffffff;
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 20px;
        text-align: center;
    }

    @media (max-width: 768px) {
        .nfl-ranking-widget {
            padding: 15px;
            margin: 10px;
        }

        .nfl-view-controls {
            flex-direction: column;
            align-items: stretch;
        }

        .nfl-position-buttons {
            justify-content: center;
        }

        .nfl-view-selector {
            margin-left: 0;
            align-self: center;
        }

        .nfl-main-tabs {
            flex-direction: column;
        }

        .nfl-actions {
            flex-direction: column;
        }

        .nfl-btn {
            justify-content: center;
        }

        .nfl-consensus-table {
            font-size: 14px;
        }

        .nfl-consensus-table th,
        .nfl-consensus-table td {
            padding: 6px 8px;
        }

        .nfl-drag-handle {
            font-size: 28px;
            padding: 15px;
        }
    }
    </style>

    <div class="nfl-ranking-widget" id="nfl-widget">
        <!-- Main Tabs at Top Level -->
        <div class="nfl-main-tabs" id="nfl-main-tabs">
            <button class="nfl-main-tab active" onclick="nflWidget.switchMainView('consensus')" id="consensus-main-tab">
                Consensus Rankings
            </button>
            <?php if ($is_logged_in): ?>
                <button class="nfl-main-tab" onclick="nflWidget.switchMainView('ranking')" id="ranking-main-tab">
                    My Ranking
                </button>
            <?php endif; ?>
        </div>

        <!-- Consensus View -->
        <div id="consensus-main-view" class="nfl-view-content">
            <div class="nfl-view-controls">
                <div class="nfl-position-buttons">
                    <button class="nfl-position-btn active" data-position="QB" onclick="nflWidget.switchPosition('QB')">QB</button>
                    <button class="nfl-position-btn" data-position="RB" onclick="nflWidget.switchPosition('RB')">RB</button>
                    <button class="nfl-position-btn" data-position="WR" onclick="nflWidget.switchPosition('WR')">WR</button>
                    <button class="nfl-position-btn" data-position="TE" onclick="nflWidget.switchPosition('TE')">TE</button>
                </div>
                <div class="nfl-view-selector">
                    <label>Filter by Ranker:</label>
                    <select class="nfl-select" id="consensus-ranker-selector" onchange="nflWidget.switchRanker(this.value)">
                        <option value="consensus">Consensus</option>
                        <!-- Individual rankers will be loaded here -->
                    </select>
                </div>
            </div>

            <div id="nfl-loading-consensus" class="nfl-loading" style="display: none;">
                Loading consensus rankings...
            </div>

            <div id="nfl-consensus-content">
                <!-- Consensus will be loaded here -->
            </div>
        </div>

        <!-- My Ranking View -->
        <?php if ($is_logged_in): ?>
            <div id="ranking-main-view" class="nfl-view-content" style="display: none;">
                <div class="nfl-view-controls">
                    <div class="nfl-position-buttons">
                        <button class="nfl-position-btn active" data-position="QB" onclick="nflWidget.switchPosition('QB')">QB</button>
                        <button class="nfl-position-btn" data-position="RB" onclick="nflWidget.switchPosition('RB')">RB</button>
                        <button class="nfl-position-btn" data-position="WR" onclick="nflWidget.switchPosition('WR')">WR</button>
                        <button class="nfl-position-btn" data-position="TE" onclick="nflWidget.switchPosition('TE')">TE</button>
                    </div>
                    <div class="nfl-view-selector">
                        <label>Week:</label>
                        <select class="nfl-select" id="ranking-week-selector" onchange="nflWidget.switchWeek(this.value)">
                            <?php 
                            foreach ($available_weeks as $week_key => $week_label):
                                $selected = ($week_key === $current_week) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $week_key; ?>" <?php echo $selected; ?>><?php echo $week_label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div id="nfl-loading-ranking" class="nfl-loading" style="display: none;">
                    Loading players...
                </div>

                <div class="nfl-ranking-container">
                    <div id="nfl-sortable-list" class="nfl-sortable-list">
                        <!-- Items will be loaded here -->
                    </div>
                </div>

                <!-- User Info Display - Moved below table -->
                <div class="nfl-user-info">
                    Submitting rankings as: <strong><?php echo esc_html($current_user->display_name); ?></strong>
                </div>

                <div class="nfl-actions">
                    <button class="nfl-btn nfl-btn-primary" onclick="nflWidget.saveRanking()" id="save-btn">
                        💾 Save Ranking
                    </button>
                    <button class="nfl-btn nfl-btn-danger" onclick="nflWidget.deleteRanking()" id="delete-btn">
                        🗑️ Delete Ranking
                    </button>
                </div>

                <div id="nfl-message"></div>
            </div>
        <?php else: ?>
            <div id="ranking-main-view" class="nfl-view-content" style="display: none;">
                <div class="nfl-login-notice">
                    Please log in to create your own rankings.
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
    const nflWidget = {
        currentMainView: 'consensus',
        currentPosition: 'QB',
        currentWeek: '<?php echo $current_week; ?>', // Use the current active week
        currentRanker: 'consensus',
        players: [],
        rankedItems: [],
        allRankings: [],
        consensusRanks: {},
        sortableInstance: null,
        isLoading: false,

        config: {
            isLoggedIn: <?php echo $is_logged_in ? 'true' : 'false'; ?>,
            userName: '<?php echo $is_logged_in ? esc_js($current_user->display_name) : ''; ?>',
            userId: <?php echo $is_logged_in ? intval($current_user->ID) : 0; ?>,
            ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
            availableWeeks: <?php echo json_encode($available_weeks); ?>
        },

        init() {
            // Hide main tabs for non-logged users
            if (!this.config.isLoggedIn) {
                document.getElementById('nfl-main-tabs').classList.add('hidden');
            }

            // Set the week selector to current week
            const weekSelector = document.getElementById('ranking-week-selector');
            if (weekSelector) {
                weekSelector.value = this.currentWeek;
            }

            // Load both players and consensus data immediately
            this.loadPlayers();
            this.loadConsensus();
        },

        async loadPlayers() {
            this.setLoading(true, 'ranking');
            try {
                const response = await fetch(`${this.config.ajaxUrl}?action=nfl_get_players&position=${this.currentPosition}&week=${this.currentWeek}`);
                const data = await response.json();

                if (data.success) {
                    this.players = data.data || [];
                    if (this.config.isLoggedIn) {
                        await this.loadUserRanking();
                    }
                    if (this.currentMainView === 'ranking') {
                        this.render();
                    }
                } else {
                    this.showMessage('Failed to load players', 'error');
                }
            } catch (error) {
                console.error('Error loading players:', error);
                this.showMessage('Error loading players', 'error');
            } finally {
                this.setLoading(false, 'ranking');
            }
        },

        async loadUserRanking() {
            if (!this.config.isLoggedIn) return;

            try {
                const response = await fetch(`${this.config.ajaxUrl}?action=nfl_get_user_ranking&position=${this.currentPosition}&week=${this.currentWeek}`);
                const data = await response.json();

                if (data.success && data.data) {
                    this.rankedItems = data.data;
                } else {
                    // Initialize with standard tiers + players
                    this.initializeStandardTiers();
                }
            } catch (error) {
                console.error('Error loading user ranking:', error);
                this.initializeStandardTiers();
            }
        },

        initializeStandardTiers() {
            // Create standard tier structure
            this.rankedItems = [
                { type: 'tier', id: 'tier-1', name: 'Tier 1' },
                { type: 'tier', id: 'tier-2', name: 'Tier 2' },
                { type: 'tier', id: 'tier-3', name: 'Tier 3' },
                { type: 'tier', id: 'tier-4', name: 'Tier 4' },
                { type: 'tier', id: 'tier-5', name: 'Tier 5' },
                { type: 'tier', id: 'tier-rest', name: 'The Rest' }
            ];

            // Add all players after the tiers
            this.players.forEach(player => {
                this.rankedItems.push({
                    type: 'player',
                    id: player.id,
                    ...player
                });
            });
        },

        async loadConsensus() {
            this.setLoading(true, 'consensus');
            try {
                const response = await fetch(`${this.config.ajaxUrl}?action=nfl_get_consensus&position=${this.currentPosition}&week=${this.currentWeek}`);
                const data = await response.json();

                if (data.success) {
                    this.allRankings = data.data || [];
                    this.calculateConsensusRanks();
                    this.populateRankerSelector();
                    // ALWAYS render consensus immediately
                    this.renderConsensus();
                } else {
                    this.showMessage('Failed to load consensus', 'error');
                    this.renderConsensus(); // Render empty state
                }
            } catch (error) {
                console.error('Error loading consensus:', error);
                this.showMessage('Error loading consensus', 'error');
                this.renderConsensus(); // Render empty state
            } finally {
                this.setLoading(false, 'consensus');
            }
        },

        calculateConsensusRanks() {
            const playerStats = {};

            // Initialize stats for all players
            this.players.forEach(player => {
                const key = `player-${player.id}`;
                playerStats[key] = {
                    id: player.id,
                    name: player.name,
                    team: player.team,
                    opponent: player.opponent,
                    type: 'player',
                    rankSum: 0,
                    rankCount: 0,
                    minRank: Infinity,
                    maxRank: -Infinity
                };
            });

            // Process each ranking
            this.allRankings.forEach(ranking => {
                const rankingData = JSON.parse(ranking.ranking_data);

                // Calculate player-only ranks for statistics
                let playerRankCounter = 1;
                rankingData.forEach((item, overallIndex) => {
                    if (item.type === 'player') {
                        const key = `player-${item.id}`;
                        if (playerStats[key]) {
                            playerStats[key].rankSum += playerRankCounter;
                            playerStats[key].rankCount += 1;
                            playerStats[key].minRank = Math.min(playerStats[key].minRank, playerRankCounter);
                            playerStats[key].maxRank = Math.max(playerStats[key].maxRank, playerRankCounter);
                        }
                        playerRankCounter++;
                    }
                });
            });

            // Calculate consensus ranks (only players)
            const consensusPlayers = Object.values(playerStats)
                .filter(item => item.rankCount > 0)
                .map(item => ({
                    ...item,
                    averageRank: item.rankSum / item.rankCount
                }))
                .sort((a, b) => a.averageRank - b.averageRank);

            // Store consensus ranks for differential calculation
            this.consensusRanks = {};
            consensusPlayers.forEach((player, index) => {
                this.consensusRanks[player.id] = index + 1;
            });
        },

        populateRankerSelector() {
            const selector = document.getElementById('consensus-ranker-selector');
            if (!selector) return;
            
            selector.innerHTML = '<option value="consensus">Consensus</option>';

            this.allRankings.forEach(ranking => {
                const option = document.createElement('option');
                option.value = ranking.user_id;
                option.textContent = ranking.display_name;
                selector.appendChild(option);
            });
        },

        async saveRanking() {
            if (!this.config.isLoggedIn) {
                this.showMessage('You must be logged in to save rankings', 'error');
                return;
            }

            this.setLoading(true, 'ranking');

            const formData = new FormData();
            formData.append('action', 'nfl_save_ranking');
            formData.append('position', this.currentPosition);
            formData.append('week', this.currentWeek);
            formData.append('ranking_data', JSON.stringify(this.rankedItems));

            try {
                const response = await fetch(this.config.ajaxUrl, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.showMessage('✅ Ranking saved successfully!', 'success');
                    setTimeout(() => this.showMessage('', ''), 3000);
                } else {
                    this.showMessage(data.data || 'Failed to save ranking', 'error');
                }
            } catch (error) {
                console.error('Error saving ranking:', error);
                this.showMessage('Error saving ranking', 'error');
            } finally {
                this.setLoading(false, 'ranking');
            }
        },

        async deleteRanking() {
            if (!this.config.isLoggedIn) return;

            if (!confirm('Are you sure you want to delete your ranking for ' + this.currentPosition + '?')) {
                return;
            }

            this.setLoading(true, 'ranking');

            const formData = new FormData();
            formData.append('action', 'nfl_delete_ranking');
            formData.append('position', this.currentPosition);
            formData.append('week', this.currentWeek);

            try {
                const response = await fetch(this.config.ajaxUrl, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.showMessage('✅ Ranking deleted successfully!', 'success');
                    this.initializeStandardTiers();
                    this.render();
                    setTimeout(() => this.showMessage('', ''), 3000);
                } else {
                    this.showMessage(data.data || 'Failed to delete ranking', 'error');
                }
            } catch (error) {
                console.error('Error deleting ranking:', error);
                this.showMessage('Error deleting ranking', 'error');
            } finally {
                this.setLoading(false, 'ranking');
            }
        },

        switchMainView(view) {
            this.currentMainView = view;

            // Update main tabs
            document.querySelectorAll('.nfl-main-tab').forEach(tab => tab.classList.remove('active'));
            document.getElementById(view + '-main-tab').classList.add('active');

            // Show/hide main views
            document.getElementById('consensus-main-view').style.display = view === 'consensus' ? 'block' : 'none';
            document.getElementById('ranking-main-view').style.display = view === 'ranking' ? 'block' : 'none';

            // Load appropriate content
            if (view === 'consensus') {
                this.renderConsensus();
            } else if (view === 'ranking') {
                this.render();
            }
        },

        switchPosition(position) {
            this.currentPosition = position;

            // Update all position buttons in both views
            document.querySelectorAll('.nfl-position-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll(`[data-position="${position}"]`).forEach(btn => btn.classList.add('active'));

            this.loadPlayers();
            this.loadConsensus();
        },

        switchWeek(week) {
            this.currentWeek = week;
            this.loadPlayers();
        },

        switchRanker(ranker) {
            this.currentRanker = ranker;
            this.renderConsensus();
        },

        setLoading(loading, view) {
            const loadingId = view === 'consensus' ? 'nfl-loading-consensus' : 'nfl-loading-ranking';
            const loadingElement = document.getElementById(loadingId);
            if (loadingElement) {
                loadingElement.style.display = loading ? 'block' : 'none';
            }

            // Update buttons
            const buttons = document.querySelectorAll('.nfl-btn');
            buttons.forEach(btn => btn.disabled = loading);
        },

        showMessage(text, type) {
            const messageDiv = document.getElementById('nfl-message');
            if (messageDiv) {
                if (text) {
                    messageDiv.innerHTML = `<div class="nfl-message ${type}">${text}</div>`;
                } else {
                    messageDiv.innerHTML = '';
                }
            }
        },

        getRankNumber(index, item) {
            if (item.type === 'tier') {
                return '';
            }

            // Count only players before this index
            let playerCount = 0;
            for (let i = 0; i < index; i++) {
                if (this.rankedItems[i].type === 'player') {
                    playerCount++;
                }
            }
            return playerCount + 1;
        },

        render() {
            const container = document.getElementById('nfl-sortable-list');
            if (!container) return;

            if (this.rankedItems.length === 0) {
                container.innerHTML = `
                    <div class="nfl-empty-state">
                        <h3>No ${this.currentPosition} players available</h3>
                        <p>Contact an administrator to add players for ranking.</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = this.rankedItems.map((item, index) => {
                const rankNumber = this.getRankNumber(index, item);

                if (item.type === 'tier') {
                    return `
                        <div class="nfl-item nfl-tier" data-id="${item.id}" data-type="tier">
                            <div class="nfl-rank-number">T</div>
                            <div class="nfl-item-content">
                                <div class="nfl-item-info">
                                    <div class="nfl-item-name">${item.name}</div>
                                </div>
                                <div class="nfl-item-actions">
                                    <span class="nfl-drag-handle">⋮⋮⋮</span>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    return `
                        <div class="nfl-item nfl-player" data-id="${item.id}" data-type="player">
                            <div class="nfl-rank-number">${rankNumber}</div>
                            <div class="nfl-item-content">
                                <div class="nfl-item-info">
                                    <div class="nfl-item-name">${item.name} <span class="nfl-item-details">- ${item.team} vs ${item.opponent}</span></div>
                                </div>
                                <div class="nfl-item-actions">
                                    <span class="nfl-drag-handle">⋮⋮⋮</span>
                                </div>
                            </div>
                        </div>
                    `;
                }
            }).join('');

            this.initializeSortable();
        },

        renderConsensus() {
            const container = document.getElementById('nfl-consensus-content');
            if (!container) return;

            const weekLabel = this.config.availableWeeks[this.currentWeek] || this.currentWeek;

            if (this.allRankings.length === 0) {
                container.innerHTML = `
                    <div class="nfl-empty-state">
                        <h3>No consensus data available</h3>
                        <p>Consensus rankings will appear once users submit their rankings.</p>
                    </div>
                `;
                return;
            }

            // If showing individual ranker
            if (this.currentRanker !== 'consensus') {
                const selectedRanking = this.allRankings.find(r => r.user_id == this.currentRanker);
                if (selectedRanking) {
                    const rankingData = JSON.parse(selectedRanking.ranking_data);

                    let consensusHtml = `
                        <div class="nfl-consensus-header">${weekLabel} ${this.currentPosition} Rankings by ${selectedRanking.display_name}</div>
                        <table class="nfl-consensus-table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Player</th>
                                    <th>Team</th>
                                    <th>Opp</th>
                                    <th>Diff</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;

                    let playerRank = 1;
                    rankingData.forEach((item) => {
                        if (item.type === 'tier') {
                            consensusHtml += `
                                <tr>
                                    <td colspan="5">
                                        <div class="nfl-consensus-tier">${item.name}</div>
                                    </td>
                                </tr>
                            `;
                        } else {
                            // Calculate differential from consensus
                            const consensusRank = this.consensusRanks[item.id];
                            let diffHtml = '-';
                            if (consensusRank) {
                                const diff = consensusRank - playerRank;
                                if (diff > 0) {
                                    diffHtml = `<span class="nfl-differential positive">+${diff}</span>`;
                                } else if (diff < 0) {
                                    diffHtml = `<span class="nfl-differential negative">${diff}</span>`;
                                } else {
                                    diffHtml = `<span class="nfl-differential neutral">0</span>`;
                                }
                            }

                            consensusHtml += `
                                <tr>
                                    <td><span class="nfl-consensus-rank">${playerRank}</span></td>
                                    <td><strong>${item.name}</strong></td>
                                    <td>${item.team}</td>
                                    <td>${item.opponent}</td>
                                    <td>${diffHtml}</td>
                                </tr>
                            `;
                            playerRank++;
                        }
                    });

                    consensusHtml += `
                            </tbody>
                        </table>
                    `;

                    container.innerHTML = consensusHtml;
                    return;
                }
            }

            // Show consensus (existing logic)
            const playerStats = {};
            const itemPositionStats = {};

            // Initialize stats for all players
            this.players.forEach(player => {
                const key = `player-${player.id}`;
                playerStats[key] = {
                    id: player.id,
                    name: player.name,
                    team: player.team,
                    opponent: player.opponent,
                    type: 'player',
                    rankSum: 0,
                    rankCount: 0,
                    minRank: Infinity,
                    maxRank: -Infinity
                };

                itemPositionStats[key] = {
                    id: player.id,
                    name: player.name,
                    team: player.team,
                    opponent: player.opponent,
                    type: 'player',
                    positionSum: 0,
                    positionCount: 0
                };
            });

            // Collect all unique tiers
            const allTiers = new Set();
            this.allRankings.forEach(ranking => {
                const rankingData = JSON.parse(ranking.ranking_data);
                rankingData.forEach(item => {
                    if (item.type === 'tier') {
                        allTiers.add(item.name);
                    }
                });
            });

            // Initialize stats for tiers
            allTiers.forEach(tierName => {
                const key = `tier-${tierName}`;
                itemPositionStats[key] = {
                    id: key,
                    name: tierName,
                    type: 'tier',
                    positionSum: 0,
                    positionCount: 0
                };
            });

            // Process each ranking
            this.allRankings.forEach(ranking => {
                const rankingData = JSON.parse(ranking.ranking_data);

                // Part 1: Calculate player-only ranks for statistics
                let playerRankCounter = 1;
                rankingData.forEach((item, overallIndex) => {
                    if (item.type === 'player') {
                        const key = `player-${item.id}`;
                        if (playerStats[key]) {
                            playerStats[key].rankSum += playerRankCounter;
                            playerStats[key].rankCount += 1;
                            playerStats[key].minRank = Math.min(playerStats[key].minRank, playerRankCounter);
                            playerStats[key].maxRank = Math.max(playerStats[key].maxRank, playerRankCounter);
                        }
                        playerRankCounter++;
                    }
                });

                // Part 2: Calculate overall positions for sorting
                rankingData.forEach((item, overallIndex) => {
                    const overallPosition = overallIndex + 1;
                    let key;
                    if (item.type === 'player') {
                        key = `player-${item.id}`;
                    } else {
                        key = `tier-${item.name}`;
                    }

                    if (itemPositionStats[key]) {
                        itemPositionStats[key].positionSum += overallPosition;
                        itemPositionStats[key].positionCount += 1;
                    }
                });
            });

            // Calculate averages and combine data
            const allItemsWithPositions = Object.values(itemPositionStats)
                .filter(item => item.positionCount > 0)
                .map(item => {
                    const result = {
                        ...item,
                        averagePosition: item.positionSum / item.positionCount
                    };

                    // Add player statistics if it's a player
                    if (item.type === 'player' && playerStats[`player-${item.id}`]) {
                        const stats = playerStats[`player-${item.id}`];
                        result.averageRank = stats.rankSum / stats.rankCount;
                        result.minRank = stats.minRank;
                        result.maxRank = stats.maxRank;
                        result.rankCount = stats.rankCount;
                    }

                    return result;
                });

            // Sort by average overall position
            allItemsWithPositions.sort((a, b) => a.averagePosition - b.averagePosition);

            // Render the consensus table with proper player ranking
            let consensusHtml = `
                <div class="nfl-consensus-header">${weekLabel} ${this.currentPosition} Consensus Rankings</div>
                <table class="nfl-consensus-table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Player</th>
                            <th>Team</th>
                            <th>Opp</th>
                            <th>Avg</th>
                            <th>High</th>
                            <th>Low</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            let playerRankCounter = 1;
            allItemsWithPositions.forEach((item, index) => {
                if (item.type === 'tier') {
                    consensusHtml += `
                        <tr>
                            <td colspan="7" style="padding: 0;">
                                <div class="nfl-consensus-tier">${item.name}</div>
                            </td>
                        </tr>
                    `;
                } else {
                    consensusHtml += `
                        <tr>
                            <td><span class="nfl-consensus-rank">${playerRankCounter}</span></td>
                            <td><strong>${item.name}</strong></td>
                            <td>${item.team}</td>
                            <td>${item.opponent}</td>
                            <td>${item.averageRank ? item.averageRank.toFixed(1) : 'N/A'}</td>
                            <td>${item.minRank !== Infinity ? item.minRank : 'N/A'}</td>
                            <td>${item.maxRank !== -Infinity ? item.maxRank : 'N/A'}</td>
                        </tr>
                    `;
                    playerRankCounter++;
                }
            });

            consensusHtml += `
                    </tbody>
                </table>
            `;

            container.innerHTML = consensusHtml;
        },

        initializeSortable() {
            const container = document.getElementById('nfl-sortable-list');
            if (!container || this.rankedItems.length === 0) return;

            if (this.sortableInstance) {
                this.sortableInstance.destroy();
            }

            this.sortableInstance = Sortable.create(container, {
                animation: 200,
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                handle: '.nfl-drag-handle',
                onEnd: (evt) => {
                    const item = this.rankedItems.splice(evt.oldIndex, 1)[0];
                    this.rankedItems.splice(evt.newIndex, 0, item);
                    this.render(); // Re-render to update rank numbers
                }
            });
        }
    };

    // Initialize when SortableJS is loaded
    function initNFLWidget() {
        if (typeof Sortable !== 'undefined') {
            nflWidget.init();
        } else {
            setTimeout(initNFLWidget, 100);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNFLWidget);
    } else {
        initNFLWidget();
    }
    </script>
    <?php

    return ob_get_clean();
}
add_shortcode('nfl_player_ranking', 'nfl_ranking_shortcode');

?>