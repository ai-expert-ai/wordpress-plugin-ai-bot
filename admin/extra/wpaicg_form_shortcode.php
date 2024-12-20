<?php
if (!defined('ABSPATH')) exit;
global $wpdb;
$wpaicg_items = array();
$wpaicg_icons = array();

$wpaicg_ai_model = get_option('wpaicg_ai_model', 'gpt-3.5-turbo-16k');
$gpt4_models = ['gpt-4', 'gpt-4-turbo', 'chatgpt-4o-latest'];
$gpt35_models = ['gpt-3.5-turbo', 'gpt-3.5-turbo-16k', 'gpt-3.5-turbo-instruct'];
$gpt3_models = ['text-curie-001', 'text-babbage-001', 'text-ada-001'];
$legacy_models = ['text-davinci-003'];
$custom_models = get_option('wpaicg_custom_models', []);

$table = $wpdb->prefix . 'wpaicg';
$existingValue = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE name = %s", 'wpaicg_settings'), ARRAY_A);

// get user data
$user_id = get_current_user_id(); // Get the current user's ID

$user_model = get_user_meta($user_id, 'model', true); // Get the 'model' user meta
$user_openai_api_key = get_user_meta($user_id, 'openai_api_key', true); // Get the 'openai_api_key' user meta

if (empty($user_model)) {
    $user_model = 'gpt-4';
}

if (empty($user_openai_api_key)) {
    $user_openai_api_key = '';
}


// function my_enqueue_scripts()
// {
//     // Register your script
//     wp_register_script('my-script', get_template_directory_uri() . '/js/my-script.js', array('jquery'), '1.0', true);

//     // Localize the script with new data
//     $script_data_array = array(
//         'ajaxurl' => admin_url('admin-ajax.php'),
//     );
//     wp_localize_script('my-script', 'my_script_data', $script_data_array);

//     // Enqueued script with localized data
//     wp_enqueue_script('my-script');
// }
// add_action('wp_enqueue_scripts', 'my_enqueue_scripts');


function my_enqueue_scripts()
{
    // Get the file modification time to use as version
    $version = file_exists(get_template_directory() . '/js/my-script.js') ? filemtime(get_template_directory() . '/js/my-script.js') : "1.0.0";
    
    // Register your script
    wp_register_script('my-script', get_template_directory_uri() . '/js/my-script.js', array('jquery'), $version, true);

    // Localize the script with new data
    $script_data_array = array(
        'ajaxurl' => admin_url('admin-ajax.php'),
    );
    wp_localize_script('my-script', 'my_script_data', $script_data_array);

    // Enqueued script with localized data
    wp_enqueue_script('my-script');
}
add_action('wp_enqueue_scripts', 'my_enqueue_scripts');


if (file_exists(WPAICG_PLUGIN_DIR . 'admin/data/gptcategories.json')) {
    $wpaicg_file_content = file_get_contents(WPAICG_PLUGIN_DIR . 'admin/data/gptcategories.json');
    $wpaicg_file_content = json_decode($wpaicg_file_content, true);
    if ($wpaicg_file_content && is_array($wpaicg_file_content) && count($wpaicg_file_content)) {
        foreach ($wpaicg_file_content as $key => $item) {
            $wpaicg_categories[$key] = trim($item);
        }
    }
}
if (file_exists(WPAICG_PLUGIN_DIR . 'admin/data/icons.json')) {
    $wpaicg_file_content = file_get_contents(WPAICG_PLUGIN_DIR . 'admin/data/icons.json');
    $wpaicg_file_content = json_decode($wpaicg_file_content, true);
    if ($wpaicg_file_content && is_array($wpaicg_file_content) && count($wpaicg_file_content)) {
        foreach ($wpaicg_file_content as $key => $item) {
            $wpaicg_icons[$key] = trim($item);
        }
    }
}
if (file_exists(WPAICG_PLUGIN_DIR . 'admin/data/gptforms.json')) {
    $wpaicg_file_content = file_get_contents(WPAICG_PLUGIN_DIR . 'admin/data/gptforms.json');
    $wpaicg_file_content = json_decode($wpaicg_file_content, true);
    if ($wpaicg_file_content && is_array($wpaicg_file_content) && count($wpaicg_file_content)) {
        foreach ($wpaicg_file_content as $item) {
            $wpaicg_items[] = $item;
        }
    }
}

global $wpdb;
if (isset($atts) && is_array($atts) && isset($atts['id']) && !empty($atts['id'])) {
    $wpaicg_item_id = sanitize_text_field($atts['id']);
    $wpaicg_item = false;
    $wpaicg_custom = isset($atts['custom']) && $atts['custom'] == 'yes' ? true : false;
    if (count($wpaicg_items) && !$wpaicg_custom) {
        foreach ($wpaicg_items as $wpaicg_prompt) {
            if (isset($wpaicg_prompt['id']) && $wpaicg_prompt['id'] == $wpaicg_item_id) {
                $wpaicg_item = $wpaicg_prompt;
                $wpaicg_item['type'] = 'json';
            }
        }
    }
    if ($wpaicg_custom) {
        $sql = "SELECT p.ID as id,p.post_title as title, p.post_content as description";
        $wpaicg_meta_keys = array('prompts', 'editor', 'fields', 'response', 'category', 'savememorykey', 'engine', 'max_tokens', 'temperature', 'top_p', 'best_of', 'frequency_penalty', 'presence_penalty', 'stop', 'color', 'icon', 'bgcolor', 'header', 'dans', 'ddraft', 'dclear', 'dnotice', 'generate_text', 'noanswer_text', 'draft_text', 'clear_text', 'stop_text', 'cnotice_text', 'download_text', 'ddownload');
        foreach ($wpaicg_meta_keys as $wpaicg_meta_key) {
            $sql .= ", (" . $wpdb->prepare(
                "SELECT " . $wpaicg_meta_key . ".meta_value FROM " . $wpdb->postmeta . " " . $wpaicg_meta_key . " WHERE " . $wpaicg_meta_key . ".meta_key=%s AND p.ID=" . $wpaicg_meta_key . ".post_id LIMIT 1",
                'wpaicg_form_' . $wpaicg_meta_key
            ) . ") as " . $wpaicg_meta_key;
        }
        $sql .= $wpdb->prepare(" FROM " . $wpdb->posts . " p WHERE p.post_type = 'wpaicg_form' AND p.post_status='publish' AND p.ID=%d ORDER BY p.post_date DESC", $wpaicg_item_id);
        $wpaicg_item = $wpdb->get_row($sql, ARRAY_A);
        if ($wpaicg_item) {
            $wpaicg_item['type'] = 'custom';
        }
    }
    if ($wpaicg_item) {
        $id = $wpaicg_item['id'];
        $wpaicg_savememorykey = isset($wpaicg_item['savememorykey']) && !empty($wpaicg_item['color']) ? $wpaicg_item['savememorykey'] : 'False';
        $wpaicg_item_categories = array();
        $wpaicg_item_categories_name = array();
        if (isset($wpaicg_item['category']) && !empty($wpaicg_item['category'])) {
            $wpaicg_item_categories = array_map('trim', explode(',', $wpaicg_item['category']));
        }
        $wpaicg_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M320 0c17.7 0 32 14.3 32 32V96H480c35.3 0 64 28.7 64 64V448c0 35.3-28.7 64-64 64H160c-35.3 0-64-28.7-64-64V160c0-35.3 28.7-64 64-64H288V32c0-17.7 14.3-32 32-32zM208 384c-8.8 0-16 7.2-16 16s7.2 16 16 16h32c8.8 0 16-7.2 16-16s-7.2-16-16-16H208zm96 0c-8.8 0-16 7.2-16 16s7.2 16 16 16h32c8.8 0 16-7.2 16-16s-7.2-16-16-16H304zm96 0c-8.8 0-16 7.2-16 16s7.2 16 16 16h32c8.8 0 16-7.2 16-16s-7.2-16-16-16H400zM264 256c0-22.1-17.9-40-40-40s-40 17.9-40 40s17.9 40 40 40s40-17.9 40-40zm152 40c22.1 0 40-17.9 40-40s-17.9-40-40-40s-40 17.9-40 40s17.9 40 40 40zM48 224H64V416H48c-26.5 0-48-21.5-48-48V272c0-26.5 21.5-48 48-48zm544 0c26.5 0 48 21.5 48 48v96c0 26.5-21.5 48-48 48H576V224h16z"/></svg>';
        if (isset($wpaicg_item['icon']) && !empty($wpaicg_item['icon']) && isset($wpaicg_icons[$wpaicg_item['icon']]) && !empty($wpaicg_icons[$wpaicg_item['icon']])) {
            $wpaicg_icon = $wpaicg_icons[$wpaicg_item['icon']];
        }
        $wpaicg_icon_color = isset($wpaicg_item['color']) && !empty($wpaicg_item['color']) ? $wpaicg_item['color'] : '#19c37d';
        $wpaicg_engine = $user_model;
        $wpaicg_max_tokens = isset($wpaicg_item['max_tokens']) && !empty($wpaicg_item['max_tokens']) ? $wpaicg_item['max_tokens'] : $this->wpaicg_max_tokens;
        $wpaicg_temperature = isset($wpaicg_item['temperature']) && !empty($wpaicg_item['temperature']) ? $wpaicg_item['temperature'] : $this->wpaicg_temperature;
        $wpaicg_top_p = isset($wpaicg_item['top_p']) && !empty($wpaicg_item['top_p']) ? $wpaicg_item['top_p'] : $this->wpaicg_top_p;
        $wpaicg_best_of = isset($wpaicg_item['best_of']) && !empty($wpaicg_item['best_of']) ? $wpaicg_item['best_of'] : $this->wpaicg_best_of;
        $wpaicg_frequency_penalty = isset($wpaicg_item['frequency_penalty']) && !empty($wpaicg_item['frequency_penalty']) ? $wpaicg_item['frequency_penalty'] : $this->wpaicg_frequency_penalty;
        $wpaicg_presence_penalty = isset($wpaicg_item['presence_penalty']) && !empty($wpaicg_item['presence_penalty']) ? $wpaicg_item['presence_penalty'] : $this->wpaicg_presence_penalty;
        $wpaicg_stop = isset($wpaicg_item['stop']) && !empty($wpaicg_item['stop']) ? $wpaicg_item['stop'] : $this->wpaicg_stop;
        $wpaicg_generate_text = isset($wpaicg_item['generate_text']) && !empty($wpaicg_item['generate_text']) ? $wpaicg_item['generate_text'] : esc_html__('Generate', 'gpt3-ai-content-generator');
        $wpaicg_draft_text = isset($wpaicg_item['draft_text']) && !empty($wpaicg_item['draft_text']) ? $wpaicg_item['draft_text'] : esc_html__('Save Draft', 'gpt3-ai-content-generator');
        $wpaicg_noanswer_text = isset($wpaicg_item['noanswer_text']) && !empty($wpaicg_item['noanswer_text']) ? $wpaicg_item['noanswer_text'] : esc_html__('Number of Answers', 'gpt3-ai-content-generator');
        $wpaicg_clear_text = isset($wpaicg_item['clear_text']) && !empty($wpaicg_item['clear_text']) ? $wpaicg_item['clear_text'] : esc_html__('Clear', 'gpt3-ai-content-generator');
        $wpaicg_stop_text = isset($wpaicg_item['stop_text']) && !empty($wpaicg_item['stop_text']) ? $wpaicg_item['stop_text'] : esc_html__('Stop', 'gpt3-ai-content-generator');
        $wpaicg_cnotice_text = isset($wpaicg_item['cnotice_text']) && !empty($wpaicg_item['cnotice_text']) ? $wpaicg_item['cnotice_text'] : esc_html__('Please register to save your result', 'gpt3-ai-content-generator');
        $wpaicg_download_text = isset($wpaicg_item['download_text']) && !empty($wpaicg_item['download_text']) ? $wpaicg_item['download_text'] : __('Download', 'gpt3-ai-content-generator');
        $wpaicg_stop_lists = '';
        if (is_array($wpaicg_stop) && count($wpaicg_stop)) {
            foreach ($wpaicg_stop as $item_stop) {
                if ($item_stop === "\n") {
                    $item_stop = '\n';
                }
                $wpaicg_stop_lists = empty($wpaicg_stop_lists) ? $item_stop : ',' . $item_stop;
            }
        }
        if (count($wpaicg_item_categories)) {
            foreach ($wpaicg_item_categories as $wpaicg_item_category) {
                if (isset($wpaicg_categories[$wpaicg_item_category]) && !empty($wpaicg_categories[$wpaicg_item_category])) {
                    $wpaicg_item_categories_name[] = $wpaicg_categories[$wpaicg_item_category];
                }
            }
        }
        if (is_user_logged_in()) {
            wp_enqueue_editor();
        }
        $wpaicg_show_setting = false;
        if (isset($atts['settings']) && $atts['settings'] == 'yes') {
            $wpaicg_show_setting = true;
        }
?>
        <style>
            .wpaicg-prompt-item {}

            .wpaicg-prompt-head {
                display: flex;
                align-items: center;
                padding-bottom: 10px;
                border-bottom: 1px solid #b1b1b1;
            }

            .wpaicg-prompt-icon {
                color: #fff;
                width: 100px;
                height: 100px;
                margin-right: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 5px;
            }

            .wpaicg-prompt-icon svg {
                fill: currentColor;
                width: 50px;
                height: 50px;
            }

            .wpaicg-prompt-head p {
                margin: 5px 0;
            }

            .wpaicg-prompt-head strong {
                font-size: 20px;
                display: block;
            }

            .wpaicg-prompt-content {
                padding: 10px 0;
            }

            .wpaicg-grid-three {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                grid-column-gap: 20px;
                grid-row-gap: 20px;
                grid-template-rows: auto auto;
            }

            .wpaicg-grid-2 {
                grid-column: span 2/span 1;
            }

            .wpaicg-grid-1 {
                grid-column: span 1/span 1;
            }

            .wpaicg-prompt-item .wpaicg-prompt-sample {
                display: block;
                position: relative;
                font-size: 13px;
            }

            .wpaicg-prompt-item .wpaicg-prompt-sample:hover .wpaicg-prompt-response {
                display: block;
            }

            .wpaicg-prompt-title {
                display: block;
                width: 100%;
                margin-bottom: 20px;
            }

            .wpaicg-prompt-result {
                width: 100%;
            }

            .wpaicg-prompt-max-lines {
                display: inline-block;
                width: auto;
                border: 1px solid #8f8f8f;
                margin-left: 10px;
                padding: 5px 10px;
                border-radius: 3px;
                font-size: 15px;
            }

            .wpaicg-generate-button {
                margin-left: 10px;
            }

            .wpaicg-button {
                padding: 5px 10px;
                background: #424242;
                border: 1px solid #343434;
                border-radius: 4px;
                color: #fff;
                font-size: 15px;
                position: relative;
                display: inline-flex;
                align-items: center;
            }

            .wpaicg-button:disabled {
                background: #505050;
                border-color: #999;
            }

            .wpaicg-button:hover:not(:disabled),
            .wpaicg-button:focus:not(:disabled) {
                color: #fff;
                background-color: #171717;
                text-decoration: none;
            }

            .wpaicg-prompt-item .wpaicg-prompt-response {
                background: #333;
                border: 1px solid #444;
                position: absolute;
                border-radius: 3px;
                color: #fff;
                padding: 5px;
                width: 320px;
                bottom: calc(100% + 5px);
                left: -100px;
                z-index: 99;
                display: none;
                font-size: 13px;
            }

            .wpaicg-prompt-item h3 {
                font-size: 25px;
                margin: 0 0 20px 0px;
            }

            .wpaicg-prompt-item .wpaicg-prompt-response:after,
            .wpaicg-prompt-item .wpaicg-prompt-response:before {
                top: 100%;
                left: 50%;
                border: solid transparent;
                content: "";
                height: 0;
                width: 0;
                position: absolute;
                pointer-events: none;
            }

            .wpaicg-prompt-item .wpaicg-prompt-response:before {
                border-color: rgba(68, 68, 68, 0);
                border-top-color: #444;
                border-width: 7px;
                margin-left: -7px;
            }

            .wpaicg-prompt-item .wpaicg-prompt-response:after {
                border-color: rgba(51, 51, 51, 0);
                border-top-color: #333;
                border-width: 6px;
                margin-left: -6px;
            }

            .wpaicg-prompt-item .wpaicg-prompt-field>strong {
                display: inline-flex;
                width: 50%;
                font-size: 13px;
                align-items: center;
                flex-wrap: wrap;
            }

            .wpaicg-prompt-item .wpaicg-prompt-field>strong>small {
                font-size: 12px;
                font-weight: normal;
                display: block;
            }

            .wpaicg-prompt-item .wpaicg-prompt-field>input,
            .wpaicg-prompt-item .wpaicg-prompt-field>select {
                border: 1px solid #8f8f8f;
                padding: 5px 10px;
                border-radius: 3px;
                font-size: 15px;
                display: inline-block;
                width: 50%;
            }

            .wpaicg-prompt-flex-center {
                display: flex;
                align-items: center;
            }

            .wpaicg-prompt-field {
                margin-bottom: 10px;
                display: flex;
            }

            .wpaicg-mb-10 {
                margin-bottom: 10px;
            }

            .wpaicg-loader {
                width: 20px;
                height: 20px;
                border: 2px solid #FFF;
                border-bottom-color: transparent;
                border-radius: 50%;
                display: inline-block;
                box-sizing: border-box;
                animation: wpaicg_rotation 1s linear infinite;
            }

            .wpaicg-button .wpaicg-loader {
                float: right;
                margin-left: 5px;
                margin-top: 2px;
            }

            .wpaicg-form-field {
                margin-bottom: 10px;
            }

            .wpaicg-form-field .ai-description-label{
                font-size: 16px;
            }

            /* --------------------------- start -------------------------- */

            .wpaicg_sync_finetune {
                color: #2271b1;
                transition-property: border, background, color;
                transition-duration: .05s;
                transition-timing-function: ease-in-out;
                font-size: 13px;
                line-height: 1.4em;
            }

            .wpcgai_help_link {
                display: inline-block;
                margin-left: 0.5em;
                font-family: "Open Sans", sans-serif;
                font-size: 14px;
                color: #3399FF;
                text-decoration: none;
                transition: color 0.2s ease-out;
                font-weight: 600;
            }

            .wp-die-message,
            p {
                font-size: 13px;
                line-height: 1.5;
                margin: 1em 0;
            }


            .wpcgai_form_row {
                align-items: center;
                justify-content: flex-start;
                /* This will align all elements to the left */
                margin-bottom: 0.5em;
            }

            .regular-text {
                width: 25em;
            }

            .wp-core-ui .button-primary {
                background: #2271b1;
                border-color: #2271b1;
                color: #fff;
                text-decoration: none;
                text-shadow: none;
            }

            .wpcgai_label,
            .wpcgai_help_link {
                flex: 1;
                /* This will make all elements have equal width */
            }

            .wpcgai_label {
                font-family: "Open Sans", sans-serif;
                font-size: 14px;
                font-weight: 500;
                color: #333;
                margin-right: 0.5em;
                width: 30%;
                display: inline-block;
            }

            /* Select styles are based on the default button in buttons.css */
            #wpaicg_ai_model {
                font-size: 14px;
                line-height: 2;
                /* 28px */
                color: #2c3338;
                border-color: #8c8f94;
                box-shadow: none;
                border-radius: 3px;
                padding: 0 8px 0 24px;
                min-height: 30px;
                max-width: 25rem;
                -webkit-appearance: none;
                /* The SVG is arrow-down-alt2 from Dashicons. */
                background: #fff url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2220%22%20height%3D%2220%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22M5%206l5%205%205-5%202%201-7%207-7-7%202-1z%22%20fill%3D%22%23555%22%2F%3E%3C%2Fsvg%3E') no-repeat left 5px top 55%;
                background-size: 16px 16px;
                cursor: pointer;
                vertical-align: middle;
            }


            .wpcgai_form_row>input {
                font-family: Arial, Helvetica, sans-serif;
                font-size: 14px;
                line-height: 2;
                padding: 0 8px;
                box-shadow: 0 0 0 transparent;
                border-radius: 4px;
                border: 1px solid #8c8f94;
                background-color: #fff;
                color: #2c3338;
                min-height: 30px;
                max-width: 25rem;
            }

            .wpcgai_form_row>input:focus {
                border-color: #2271b1;
                box-shadow: 0 0 0 1px #2271b1;
                outline: 2px solid transparent;
            }

            #wpaicg_ai_model:focus {
                border-color: #2271b1;
                box-shadow: 0 0 0 1px #2271b1;
                outline: 2px solid transparent;
            }

            @keyframes wpaicg_rotation {
                0% {
                    transform: rotate(0deg);
                }

                100% {
                    transform: rotate(360deg);
                }
            }
        </style>
        <?php
        $wpaicg_fields = [];
        if ($wpaicg_item['fields'] !== '') {
            if (is_string($wpaicg_item['fields'])) {
                if (strpos($wpaicg_item['fields'], '\"') !== false) {
                    $wpaicg_item['fields'] = str_replace('\"', '&quot;', $wpaicg_item['fields']);
                }
                if (strpos($wpaicg_item['fields'], "\'") !== false) {
                    $wpaicg_item['fields'] = str_replace('\\', '', $wpaicg_item['fields']);
                }
            }
            $wpaicg_fields = $wpaicg_item['type'] == 'custom' ? json_decode($wpaicg_item['fields'], true) : $wpaicg_item['fields'];
        }
        $wpaicg_response_type = isset($wpaicg_item['editor']) && $wpaicg_item['editor'] == 'div' ? 'div' : 'textarea';
        $kses_defaults = wp_kses_allowed_html('post');
        $svg_args = array(
            'svg'   => array(
                'class'           => true,
                'aria-hidden'     => true,
                'aria-labelledby' => true,
                'role'            => true,
                'xmlns'           => true,
                'width'           => true,
                'height'          => true,
                'viewbox'         => true // <= Must be lower case!
            ),
            'g'     => array('fill' => true),
            'title' => array('title' => true),
            'path'  => array(
                'd'               => true,
                'fill'            => true
            )
        );
        $allowed_tags = array_merge($kses_defaults, $svg_args);
        $randomFormID = rand(100000, 999999);
        ?>
        <div class="wpaicg-prompt-item wpaicg-playground-shortcode asdf" style="<?php echo isset($wpaicg_item['bgcolor']) && !empty($wpaicg_item['bgcolor']) ? 'background-color:' . esc_html($wpaicg_item['bgcolor']) : ''; ?>">
            <div class="wpaicg-prompt-head" style="<?php echo isset($wpaicg_item['header']) && $wpaicg_item['header'] == 'no' ? 'display: none;' : ''; ?>">
                <div class="wpaicg-prompt-icon" style="background: <?php echo esc_html($wpaicg_icon_color) ?>"><?php echo wp_kses($wpaicg_icon, $allowed_tags) ?></div>
                <div class="wpaicg-prompt-titlendescription">
                    <strong><?php echo isset($wpaicg_item['title']) && !empty($wpaicg_item['title']) ? esc_html($wpaicg_item['title']) : '' ?></strong>
                    
                    <?php
                    if (isset($wpaicg_item['description']) && !empty($wpaicg_item['description'])) {
                        echo '<p>' . esc_html($wpaicg_item['description']) . '</p>';
                    }
                    ?>
                </div>
            </div>
            <div class="wpaicg-prompt-content">
                <?php if ($id != 118) : ?>
                    <form data-source="form" data-id="<?php echo esc_html($randomFormID) ?>" method="post" action="" class="wpaicg-prompt-form" id="wpaicg-prompt-form">
                        <?php
                        if ($wpaicg_show_setting) :
                        ?>
                            <div class="wpaicg-grid-three">

                                <div class="wpaicg-grid-2">
                                <?php
                            endif;
                                ?>
                                <div class="wpaicg-mb-10">
                                    <textarea style="display: none" class="wpaicg-prompt-title" id="wpaicg-prompt-title" rows="8"><?php echo esc_html($wpaicg_item['prompts']) ?></textarea>
                                    <textarea style="display: none" name="title" class="wpaicg-prompt-title-filled" id="wpaicg-prompt-title-filled" rows="8"><?php echo esc_html($wpaicg_item['prompts']) ?></textarea>
                                    <?php
                                    if ($wpaicg_fields && is_array($wpaicg_fields) && count($wpaicg_fields)) {
                                        foreach ($wpaicg_fields as $key => $wpaicg_field) {
                                    ?>
                                            <div class="wpaicg-form-field">
                                                <label class="ai-input-label"><strong><?php echo esc_html(@$wpaicg_field['label']) ?></strong></label><br>
                                                <?php
                                                if (isset($wpaicg_field['description']) && !empty($wpaicg_field['description'])) {
                                                    $field_description = $wpaicg_field['description'];
                                                    $special_char = "shinydev19680903";
                                                    $parts = explode($special_char, $field_description);
                                                    foreach ($parts as $part) {
                                                ?>
                                                        <label class="ai-description-label"><?php echo esc_html($part) ?></label><br>
                                                <?php
                                                    }
                                                }
                                                ?>
                                                <?php
                                                if ($wpaicg_field['type'] == 'select') {
                                                    $wpaicg_field_options = [];
                                                    if (isset($wpaicg_field['options'])) {
                                                        if ($wpaicg_item['type'] == 'custom') {
                                                            $wpaicg_field_options = explode("|", $wpaicg_field['options']);
                                                        } else {
                                                            $wpaicg_field_options = $wpaicg_field['options'];
                                                        }
                                                    }
                                                ?>
                                                    <select required id="wpaicg-form-field-<?php echo esc_html($key) ?>" class="wpaicg-form-field-<?php echo esc_html($key) ?>" name="<?php echo esc_html($wpaicg_field['id']) ?>" data-label="<?php echo esc_html(@$wpaicg_field['label']) ?>" data-type="<?php echo esc_html(@$wpaicg_field['type']) ?>" data-min="<?php echo isset($wpaicg_field['min']) ? esc_html($wpaicg_field['min']) : '' ?>" data-max="<?php echo isset($wpaicg_field['max']) ? esc_html($wpaicg_field['max']) : '' ?>">
                                                        <?php
                                                        foreach ($wpaicg_field_options as $wpaicg_field_option) {
                                                            echo '<option value="' . esc_html($wpaicg_field_option) . '">' . esc_html($wpaicg_field_option) . '</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                <?php
                                                } elseif ($wpaicg_field['type'] == 'checkbox' || $wpaicg_field['type'] == 'radio') {
                                                    $wpaicg_field_options = [];
                                                    if (isset($wpaicg_field['options'])) {
                                                        if ($wpaicg_item['type'] == 'custom') {
                                                            $wpaicg_field_options = explode("|", $wpaicg_field['options']);
                                                        } else {
                                                            $wpaicg_field_options = $wpaicg_field['options'];
                                                        }
                                                    }
                                                ?>
                                                    <div id="wpaicg-form-field-<?php echo esc_html($key) ?>" class="wpaicg-form-field-<?php echo esc_html($key) ?>">
                                                        <?php
                                                        foreach ($wpaicg_field_options as $wpaicg_field_option) :
                                                        ?>
                                                            <label><input name="<?php echo esc_html($wpaicg_field['id']) . ($wpaicg_field['type'] == 'checkbox' ? '[]' : '') ?>" value="<?php echo esc_html($wpaicg_field_option) ?>" type="<?php echo esc_html($wpaicg_field['type']) ?>">&nbsp;<?php echo esc_html($wpaicg_field_option) ?></label>&nbsp;&nbsp;&nbsp;
                                                        <?php
                                                        endforeach;
                                                        ?>
                                                    </div>
                                                <?php
                                                } elseif ($wpaicg_field['type'] == 'textarea') {
                                                ?>
                                                    <textarea<?php echo isset($wpaicg_field['rows']) && !empty($wpaicg_field['rows']) ? ' rows="' . esc_html($wpaicg_field['rows']) . '"' : ''; ?><?php echo isset($wpaicg_field['cols']) && !empty($wpaicg_field['cols']) ? ' rows="' . esc_html($wpaicg_field['cols']) . '"' : ''; ?> required id="wpaicg-form-field-<?php echo esc_html($key) ?>" class="wpaicg-form-field-<?php echo esc_html($key) ?>" name="<?php echo esc_html($wpaicg_field['id']) ?>" data-label="<?php echo esc_html(@$wpaicg_field['label']) ?>" data-type="<?php echo esc_html(@$wpaicg_field['type']) ?>" type="<?php echo esc_html(@$wpaicg_field['type']) ?>" data-min="<?php echo isset($wpaicg_field['min']) ? esc_html($wpaicg_field['min']) : '' ?>" data-max="<?php echo isset($wpaicg_field['max']) ? esc_html($wpaicg_field['max']) : '' ?>"></textarea>
                                                    <?php
                                                } else {
                                                    ?>
                                                        <input required id="wpaicg-form-field-<?php echo esc_html($key) ?>" class="wpaicg-form-field-<?php echo esc_html($key) ?>" name="<?php echo esc_html($wpaicg_field['id']) ?>" data-label="<?php echo esc_html(@$wpaicg_field['label']) ?>" data-type="<?php echo esc_html(@$wpaicg_field['type']) ?>" type="<?php echo esc_html(@$wpaicg_field['type']) ?>" data-min="<?php echo isset($wpaicg_field['min']) ? esc_html($wpaicg_field['min']) : '' ?>" data-max="<?php echo isset($wpaicg_field['max']) ? esc_html($wpaicg_field['max']) : '' ?>">
                                                    <?php
                                                }
                                                    ?>
                                            </div>
                                    <?php
                                        }
                                    }
                                    ?>
                                    <div class="wpaicg-prompt-flex-center">
                                        <div style="<?php echo isset($wpaicg_item['dans']) && $wpaicg_item['dans'] == 'no' ? 'display:none' : '' ?>">
                                            <strong><?php echo esc_html($wpaicg_noanswer_text); ?></strong>
                                            <select class="wpaicg-prompt-max-lines" id="wpaicg-prompt-max-lines">
                                                <?php
                                                for ($i = 1; $i <= 10; $i++) {
                                                    echo '<option value="' . esc_html($i) . '">' . esc_html($i) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <button style="<?php echo isset($wpaicg_item['dans']) && $wpaicg_item['dans'] == 'no' ? 'margin-left:0' : '' ?>" class="wpaicg-button wpaicg-generate-button" id="wpaicg-generate-button"><?php echo esc_html($wpaicg_generate_text); ?></button>
                                        &nbsp;<button data-id="<?php echo esc_html($randomFormID) ?>" type="button" class="wpaicg-button wpaicg-prompt-stop-generate" id="wpaicg-prompt-stop-generate" style="display: none"><?php echo esc_html($wpaicg_stop_text); ?></button>
                                        <strong class="wpaicg-prompt-savememorykey" style="display: none;"><?php echo isset($wpaicg_item['savememorykey']) && !empty($wpaicg_item['savememorykey']) ? esc_html($wpaicg_item['savememorykey']) : '' ?></strong>
                                    </div>
                                </div>
                                <div class="mb-5">
                                    <?php
                                    if ($wpaicg_response_type == 'textarea') :
                                        if (is_user_logged_in()) {
                                            wp_editor('', 'wpaicg-prompt-result-' . $randomFormID, array('media_buttons' => true, 'textarea_name' => 'wpaicg-prompt-result-' . $randomFormID));
                                        } else {
                                    ?>
                                            <textarea class="wpaicg-prompt-result-<?php echo esc_html($randomFormID) ?>" id="wpaicg-prompt-result-<?php echo esc_html($randomFormID) ?>" rows="12"></textarea>
                                            <?php
                                            if (isset($wpaicg_item['dnotice']) && $wpaicg_item['dnotice'] == 'no') :
                                            else :
                                            ?>
                                                <a style="font-size: 13px;" href="<?php echo site_url('wp-login.php?action=register') ?>"><?php echo esc_html($wpaicg_cnotice_text) ?></a>
                                            <?php
                                            endif;
                                            ?>
                                            <?php
                                        }
                                    else :
                                        echo '<div id="wpaicg-prompt-result-' . esc_html($randomFormID) . '"></div>';
                                        if (!is_user_logged_in()) {
                                            if (isset($wpaicg_item['dnotice']) && $wpaicg_item['dnotice'] == 'no') {
                                            } else {
                                            ?>
                                                <a style="font-size: 13px;" href="<?php echo site_url('wp-login.php?action=register') ?>"><?php echo esc_html($wpaicg_cnotice_text) ?></a>
                                    <?php
                                            }
                                        }
                                    endif;
                                    ?>
                                </div>
                                <div class="wpaicg-prompt-save-result" id="wpaicg-prompt-save-result" style="display: none;margin-top: 10px;">
                                    <?php
                                    if (is_user_logged_in()) :
                                        if (isset($wpaicg_item['ddraft']) && $wpaicg_item['ddraft'] == 'no') :
                                        else :
                                    ?>
                                            <button data-id="<?php echo esc_html($randomFormID) ?>" type="button" class="wpaicg-button wpaicg-prompt-save-draft" id="wpaicg-prompt-save-draft"><?php echo esc_html($wpaicg_draft_text); ?></button>
                                        <?php
                                        endif;
                                    endif;
                                    if (isset($wpaicg_item['dclear']) && $wpaicg_item['dclear'] == 'no') :
                                    else :
                                        ?>
                                        <button data-id="<?php echo esc_html($randomFormID) ?>" type="button" class="wpaicg-button wpaicg-prompt-clear" id="wpaicg-prompt-clear"><?php echo esc_html($wpaicg_clear_text); ?></button>
                                    <?php
                                    endif;
                                    ?>
                                    <?php
                                    if (isset($wpaicg_item['ddownload']) && $wpaicg_item['ddownload'] == 'no') :
                                    else :
                                    ?>
                                        <button data-id="<?php echo esc_html($randomFormID) ?>" type="button" class="wpaicg-button wpaicg-prompt-download"><?php echo esc_html($wpaicg_download_text); ?></button>
                                    <?php
                                    endif;
                                    ?>
                                </div>
                                <?php
                                if ($wpaicg_show_setting) :
                                ?>
                                </div>
                                <div class="wpaicg-grid-1">
                                <?php
                                endif;
                                ?>
                                <div class="wpaicg-mb-10 wpaicg-prompt-item" style="<?php echo !$wpaicg_show_setting ? 'display:none' : '' ?>">
                                    <h3><?php echo esc_html__('Settings', 'gpt3-ai-content-generator') ?></h3>
                                    <div class="wpaicg-prompt-field wpaicg-prompt-engine">
                                        <strong><?php echo esc_html__('Engine', 'gpt3-ai-content-generator') ?>: </strong>
                                        <select name="engine">
                                            <optgroup label="GPT-4">
                                                <?php foreach ($gpt4_models as $model) : ?>
                                                    <option<?php echo $model == $wpaicg_engine ? ' selected' : '' ?> value="<?php echo esc_attr($model); ?>"><?php echo esc_html($model); ?></option>
                                                    <?php endforeach; ?>
                                            </optgroup>
                                            <!-- <optgroup label="GPT-3.5">
                                                <?php foreach ($gpt35_models as $model) : ?>
                                                    <option<?php echo $model == $wpaicg_engine ? ' selected' : '' ?> value="<?php echo esc_attr($model); ?>"><?php echo esc_html($model); ?></option>
                                                    <?php endforeach; ?>
                                            </optgroup>
                                            <optgroup label="GPT-3">
                                                <?php foreach ($gpt3_models as $model) : ?>
                                                    <option<?php echo $model == $wpaicg_engine ? ' selected' : '' ?> value="<?php echo esc_attr($model); ?>"><?php echo esc_html($model); ?></option>
                                                    <?php endforeach; ?>
                                            </optgroup>
                                            <optgroup label="Legacy Models">
                                                <?php foreach ($legacy_models as $model) : ?>
                                                    <option<?php echo $model == $wpaicg_engine ? ' selected' : '' ?> value="<?php echo esc_attr($model); ?>"><?php echo esc_html($model); ?></option>
                                                    <?php endforeach; ?>
                                            </optgroup>
                                            <optgroup label="Custom Models">
                                                <?php foreach ($custom_models as $model) : ?>
                                                    <option<?php echo $model == $wpaicg_engine ? ' selected' : '' ?> value="<?php echo esc_attr($model); ?>"><?php echo esc_html($model); ?></option>
                                                    <?php endforeach; ?>
                                            </optgroup> -->
                                        </select>
                                    </div>
                                    <div class="wpaicg-prompt-field"><strong><?php echo esc_html__('Token', 'gpt3-ai-content-generator') ?>: </strong><input id="wpaicg-prompt-max_tokens" class="wpaicg-prompt-max_tokens" name="max_tokens" type="text" value="<?php echo esc_html($wpaicg_max_tokens); ?>"></div>
                                    <div class="wpaicg-prompt-field"><strong><?php echo esc_html__('Temp', 'gpt3-ai-content-generator') ?>: </strong><input id="wpaicg-prompt-temperature" class="wpaicg-prompt-temperature" name="temperature" type="text" value="<?php echo esc_html($wpaicg_temperature) ?>"></div>
                                    <div class="wpaicg-prompt-field"><strong><?php echo esc_html__('TP', 'gpt3-ai-content-generator') ?>: </strong><input id="wpaicg-prompt-top_p" class="wpaicg-prompt-top_p" type="text" name="top_p" value="<?php echo esc_html($wpaicg_top_p) ?>"></div>
                                    <div class="wpaicg-prompt-field"><strong><?php echo esc_html__('BO', 'gpt3-ai-content-generator') ?>: </strong><input id="wpaicg-prompt-best_of" class="wpaicg-prompt-best_of" name="best_of" type="text" value="<?php echo esc_html($wpaicg_best_of) ?>"></div>
                                    <div class="wpaicg-prompt-field"><strong><?php echo esc_html__('FP', 'gpt3-ai-content-generator') ?>: </strong><input id="wpaicg-prompt-frequency_penalty" class="wpaicg-prompt-frequency_penalty" name="frequency_penalty" type="text" value="<?php echo esc_html($wpaicg_frequency_penalty) ?>"></div>
                                    <div class="wpaicg-prompt-field"><strong><?php echo esc_html__('PP', 'gpt3-ai-content-generator') ?>: </strong><input id="wpaicg-prompt-presence_penalty" class="wpaicg-prompt-presence_penalty" name="presence_penalty" type="text" value="<?php echo esc_html($wpaicg_presence_penalty) ?>"></div>
                                    <div class="wpaicg-prompt-field"><strong><?php echo esc_html__('Stop', 'gpt3-ai-content-generator') ?>:<small><?php echo esc_html__('separate by commas', 'gpt3-ai-content-generator') ?></small></strong><input class="wpaicg-prompt-stop" id="wpaicg-prompt-stop" type="text" name="stop" type="text" value="<?php echo esc_html($wpaicg_stop_lists) ?>"></div>
                                    <div class="wpaicg-prompt-field"><input id="wpaicg-prompt-post_title" class="wpaicg-prompt-post_title" type="hidden" name="post_title" value="<?php echo esc_html($wpaicg_item['title']) ?>"></div>
                                    <div class="wpaicg-prompt-field wpaicg-prompt-sample"><?php echo esc_html__('Sample Response', 'gpt3-ai-content-generator') ?><div class="wpaicg-prompt-response"><?php echo esc_html(@$wpaicg_item['response']) ?></div>
                                    </div>
                                </div>
                                <?php
                                if ($wpaicg_show_setting) :
                                ?>
                                </div>
                            </div>
                        <?php
                                endif;
                        ?>
                    </form>
                <?php else : ?>
                    <div class="wpaicg-template-form">
                        <form method="post" action="" class="wpaicg-openai-settings-modal-content-form">
                            <div style="padding: 0px 20px; margin-top: 10px; width: 100%;" class="wpaicg-mb-12">
                                <div class="mb-5">
                                    <!-- Display dropdown for OpenAI -->
                                    <label class="wpcgai_label" for="wpaicg_ai_model"><?php echo esc_html__('Model', 'gpt3-ai-content-generator') ?>:</label>
                                    <select class="regular-text" id="wpaicg_ai_model" name="wpaicg_ai_model">
                                        <optgroup label="GPT-4">
                                            <?php foreach ($gpt4_models as $model) : ?>
                                                <option value="<?php echo esc_attr($model); ?>" <?php selected($model, $user_model); ?>><?php echo esc_html($model); ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                        <!-- <optgroup label="GPT-3.5">
                                            <?php foreach ($gpt35_models as $model) : ?>
                                                <option value="<?php echo esc_attr($model); ?>" <?php selected($model, $user_model); ?>><?php echo esc_html($model); ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                        <optgroup label="GPT-3">
                                            <?php foreach ($gpt3_models as $model) : ?>
                                                <option value="<?php echo esc_attr($model); ?>" <?php selected($model, $user_model); ?>><?php echo esc_html($model); ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                        <optgroup label="Legacy Models">
                                            <?php foreach ($legacy_models as $model) : ?>
                                                <option value="<?php echo esc_attr($model); ?>" <?php selected($model, $user_model); ?>><?php echo esc_html($model); ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                        <optgroup label="Custom Models">
                                            <?php foreach ($custom_models as $model) : ?>
                                                <option value="<?php echo esc_attr($model); ?>" <?php selected($model, $user_model); ?>><?php echo esc_html($model); ?></option>
                                            <?php endforeach; ?>
                                        </optgroup> -->
                                    </select>
                                    <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/ai-engine/openai/gpt-models#model-configuration" target="_blank">?</a>
                                    <a class="wpaicg_sync_finetune" href="javascript:void(0)"><?php echo esc_html__('Sync', 'gpt3-ai-content-generator') ?></a>
                                </div>
                                <div class="wpcgai_form_row wpaicg_beta_notice" style="<?php echo $user_model == 'gpt-4-32k' || $user_model == 'gpt-4' ? '' : 'display:none' ?>">
                                    <p><?php echo sprintf(esc_html__('Please note that GPT-4 is currently in limited beta, which means that access to the GPT-4 API from OpenAI is available only through a waiting list and is not open to everyone yet. You can sign up for the waiting list at %shere%s.', 'gpt3-ai-content-generator'), '<a href="https://openai.com/waitlist/gpt-4-api" target="_blank">', '</a>') ?></p>
                                </div>
                                <div style="margin-top: 8px;" class="wpcgai_form_row">
                                    <label class="wpcgai_label"><?php echo esc_html__('Api Key', 'gpt3-ai-content-generator') ?>:</label>
                                    <input type="text" class="regular-text" id="label_api_key" name="wpaicg_settings[api_key]" value="<?php
                                                                                                                                        echo  esc_html($user_openai_api_key);
                                                                                                                                        ?>">
                                    <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/ai-engine/openai/api-key#how-to-generate-an-openai-api-key" target="_blank">?</a>
                                    <a class="wpcgai_help_link" href="https://beta.openai.com/account/api-keys" target="_blank"><?php echo esc_html__('Get Your Api Key', 'gpt3-ai-content-generator') ?></a>
                                </div>
                                <div class="wp-core-ui" style="margin-top: 10px; display: flex; justify-content: right;"><input type="submit" value="<?php echo esc_html__('Save', 'gpt3-ai-content-generator') ?>" name="wpaicg_submit" class="button button-primary button-large"></div>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <script>
            var wpaicg_prompt_logged = <?php echo is_user_logged_in() ? 'true' : 'false' ?>;
            window['wpaicgForm<?php echo esc_html($randomFormID) ?>'] = {
                fields: <?php echo json_encode($wpaicg_fields, JSON_UNESCAPED_UNICODE) ?>,
                type: '<?php echo esc_html($wpaicg_item['type']) ?>',
                response: '<?php echo esc_html($wpaicg_response_type) ?>',
                logged_in: <?php echo is_user_logged_in() ? 'true' : 'false' ?>,
                event: '<?php echo esc_html(add_query_arg('wpaicg_stream', 'yes', site_url() . '/index.php')); ?>',
                ajax: '<?php echo admin_url('admin-ajax.php') ?>',
                post: '<?php echo admin_url('post.php') ?>',
                sourceID: '<?php echo esc_html(get_the_ID()) ?>',
                nonce: '<?php echo esc_html(wp_create_nonce('wpaicg-formlog')) ?>',
                ajax_nonce: '<?php echo esc_html(wp_create_nonce('wpaicg-ajax-nonce')) ?>',
                id: <?php echo esc_html($wpaicg_item_id) ?>,
                name: '<?php echo isset($wpaicg_item['title']) && !empty($wpaicg_item['title']) ? esc_html($wpaicg_item['title']) : '' ?>'
            };

            jQuery(document).ready(function($) {
                $(document).on('click', '#wpaicg_ai_model', function(e) {
                    $user_model = e.target.value
                    if ($(this).val() === 'gpt-4' || $(this).val() === 'gpt-4-32k') {
                        $('.wpaicg_beta_notice').show();
                    } else {
                        $('.wpaicg_beta_notice').hide();
                    }
                })
                $(document).on('submit', '.wpaicg-openai-settings-modal-content-form', function(e) {
                    e.preventDefault();

                    var form = $(e.currentTarget);
                    var model = form.find('#wpaicg_ai_model').val();
                    var openai_api_key = form.find('#label_api_key').val();
                    var ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php', 'relative')); ?>';

                    $.ajax({
                        url: ajaxurl, // This points to admin-ajax.php in a WordPress environment
                        type: 'POST',
                        data: {
                            action: 'save_user_data', // This is the AJAX action we'll set up in PHP
                            model: model,
                            openai_api_key: openai_api_key
                        },
                        success: function(response) {
                            // Handle successful response here
                            alert('Successfully saved.');
                        },
                        error: function(error) {
                            // Handle error here
                            console.error(error);
                            alert(error);
                        }
                    });
                });

            })
        </script>
<?php
    }
}
