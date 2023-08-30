<?php

/**
 * @package TaskReminder
 * @version 1.0.0
 */
/*
Plugin Name: Task Reminder
Plugin URI: https://remydelepaule.me
Description: A simple plugin that remind you to do a task on your admin bar. Inspired by Hello Dolly plugin.
Author: Rémy Delepaule
Version: 1.0.0
Author URI: https://remydelepaule.me
*/

add_action('init', 'install_task_reminder');
add_action('wp', 'task_reminder_setup_schedule');
add_action('task_reminder_daily_event', 'reset_task_reminder');
add_action('wp_dashboard_setup', 'dashboard_tasks_widget');
add_action('admin_bar_menu', 'task_of_the_day');

/**
 * Create a table in the database
 */
function install_task_reminder()
{
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'task_reminder';

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        task varchar(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Setup the schedule
 */
function task_reminder_setup_schedule()
{
    if (!wp_next_scheduled('task_reminder_daily_event')) {
        wp_schedule_event(time(), 'daily', 'task_reminder_daily_event');
    }
}

/**
 * Reset the task reminder table to empty in the database every day
 */
function reset_task_reminder()
{
    global $wpdb;
    $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}task_reminder");
}

/**
 * Dashboard widget
 */
function dashboard_tasks_widget()
{
    add_meta_box('dashboard_tasks_widget', 'Task Reminder: add a task for today', 'dashboard_tasks_widget_function', 'dashboard', 'normal', 'high');
}

/**
 * Dashboard widget content: the user can add a task on the input field, then click on the button to add it to the database.
 * The tasks added in the database are displayed in the widget, and the user can delete them
 */
function dashboard_tasks_widget_function()
{
    global $wpdb;

    // Handle form submission and update database
    if (isset($_POST['submit']) && !empty($_POST['task'])) {
        $wpdb->insert(
            $wpdb->prefix . 'task_reminder',
            array(
                'task' => $_POST['task']
            )
        );
    }

    if (isset($_POST['delete']) && !empty($_POST['task_to_delete'])) {
        $wpdb->delete(
            $wpdb->prefix . 'task_reminder',
            array(
                'id' => $_POST['task_to_delete']
            )
        );
    }

    // Retrieve updated data from the database
    $tasks = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}task_reminder");

    // Display the updated data
    echo '<ul>';
    foreach ($tasks as $task) {
        echo '<form style="display: flex; width: 100%; justify-content: space-between;" method="post">';
        echo '<li>' . $task->task . '</li>';
        echo '<input type="hidden" name="task_to_delete" value="' . $task->id . '">';
        echo '<input type="submit" name="delete" value="❌">';
        echo '</form>';
    }
    echo '</ul>';

    // Display the form for adding new tasks
    echo '<form method="post">
        <input type="text" name="task" maxlength="30" autocomplete="off">
        <input class="button button-primary" type="submit" name="submit">
    </form>';
}


/**
 * Take one of the tasks of the day and display it in the wp admin bar.
 *
 * @param WP_Admin_Bar $wp_admin_bar The WordPress admin bar object.
 * @return void
 */
function task_of_the_day($wp_admin_bar)
{
    // get the tasks of the day from the database and convert it to an array of string
    global $wpdb;
    $tasks_of_the_day = $wpdb->get_col("SELECT task FROM {$wpdb->prefix}task_reminder");

    if (empty($tasks_of_the_day)) {
        return;
    }

    $task = $tasks_of_the_day[array_rand($tasks_of_the_day)];

    $args = [
        'id'    => 'task_of_the_day',
        'title' => $task
    ];
    $wp_admin_bar->add_node($args);
}
