<?php

add_action('wp_ajax_save_user_data', 'save_user_data');
add_action('wp_ajax_nopriv_save_user_data', 'save_user_data');

function save_user_data()
{
    // Make sure you're logged in
    if (!is_user_logged_in()) {
        wp_die('You must be logged in to save data.');
    }

    // Get current user
    $current_user = wp_get_current_user();

    // Get submitted data
    $model = $_POST['model'];
    $openai_api_key = $_POST['openai_api_key'];

    // Save the data as user meta
    update_user_meta($current_user->ID, 'model', $model);
    update_user_meta($current_user->ID, 'openai_api_key', $openai_api_key);

    echo 'Data saved successfully!';

    wp_die(); // this is required to terminate immediately and return a proper response
}
