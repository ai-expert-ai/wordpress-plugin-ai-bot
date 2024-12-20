<?php
if (!defined('ABSPATH')) exit;
global $wpdb;
$table = $wpdb->prefix . 'wpaicg';
$existingValue = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE name = %s", 'wpaicg_settings'), ARRAY_A);
$wpaicg_provider = get_option('wpaicg_provider', 'OpenAI');
$azure_deployment_name = get_option('wpaicg_azure_deployment', '');
$wpaicg_categories = array();
$wpaicg_items = array();
$wpaicg_icons = array();

// Define the model categories and their members.
$gpt4_models = ['gpt-4', 'gpt-4-turbo', 'chatgpt-4o-latest'];
$gpt35_models = ['gpt-3.5-turbo', 'gpt-3.5-turbo-16k', 'gpt-3.5-turbo-instruct'];
$gpt3_models = ['text-curie-001', 'text-babbage-001', 'text-ada-001'];
$legacy_models = ['text-davinci-003'];
$custom_models = get_option('wpaicg_custom_models', []);

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

$wpaicg_authors = array('default' => array('name' => 'AI Power', 'count' => 0));
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
            $item['type'] = 'json';
            $item['author'] = 'default';
            $wpaicg_authors['default']['count'] += 1;
            $wpaicg_items[] = $item;
        }
    }
}

$sql = "SELECT p.ID as id,p.post_title as title,p.post_author as author, p.post_content as description";
$wpaicg_meta_keys = array('fields', 'editor', 'prompts', 'response','savememorykey' ,'category', 'engine', 'max_tokens', 'temperature', 'top_p', 'best_of', 'frequency_penalty', 'presence_penalty', 'stop', 'color', 'icon', 'bgcolor', 'header', 'dans', 'ddraft', 'dclear', 'dnotice', 'generate_text', 'noanswer_text', 'draft_text', 'clear_text', 'stop_text', 'cnotice_text', 'download_text', 'ddownload');

foreach ($wpaicg_meta_keys as $wpaicg_meta_key) {
    $sql .= $wpdb->prepare(
        ", (SELECT {$wpaicg_meta_key}.meta_value FROM {$wpdb->postmeta} {$wpaicg_meta_key} WHERE {$wpaicg_meta_key}.meta_key = %s AND p.ID = {$wpaicg_meta_key}.post_id LIMIT 1) as {$wpaicg_meta_key}",
        "wpaicg_form_{$wpaicg_meta_key}"
    );
}

$sql .= $wpdb->prepare(" FROM {$wpdb->posts} p WHERE p.post_type = %s AND p.post_status = 'publish' ORDER BY p.post_date DESC", 'wpaicg_form');
$wpaicg_custom_templates = $wpdb->get_results($sql, ARRAY_A);
if ($wpaicg_custom_templates && is_array($wpaicg_custom_templates) && count($wpaicg_custom_templates)) {
    foreach ($wpaicg_custom_templates as $wpaicg_custom_template) {
        $wpaicg_custom_template['type'] = 'custom';
        $wpaicg_items[] = $wpaicg_custom_template;
        if (!isset($wpaicg_authors[$wpaicg_custom_template['author']])) {
            $prompt_author = get_user_by('ID', $wpaicg_custom_template['author']);
            $wpaicg_authors[$wpaicg_custom_template['author']] = array('name' => $prompt_author->display_name, 'count' => 1);
        } else {
            $wpaicg_authors[$wpaicg_custom_template['author']]['count'] += 1;
        }
    }
}
$wpaicg_per_page = 36;
wp_enqueue_editor();
wp_enqueue_script('wp-color-picker');
wp_enqueue_style('wp-color-picker');
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
?>
<style>
    .wpaicg-template-icon {
        width: 70px;
        height: 70px;
        border-radius: 3px;
        display: flex;
        justify-content: center;
        align-items: center;
        color: #fff;
    }

    .wpaicg-template-icon svg {
        fill: currentColor;
        width: 50px;
        height: 50px;
    }

    .wpaicg-template-item {
        cursor: pointer;
        height: 100px;
        position: relative;
    }

    .wpaicg-template-content {
        margin-left: 10px;
        flex: 1;
    }

    .wpaicg-template-content p {
        margin: 5px 0;
        font-size: 12px;
        height: 36px;
        overflow: hidden;
    }

    .wpaicg_modal {
        position: relative;
        top: 5%;
        height: 90%;
    }

    .disappear-item {
        position: absolute;
        top: -10000px;
    }

    .wpaicg-template-items {
        position: relative;
        overflow-y: hidden;
    }

    .wpaicg-paginate .page-numbers {
        background: #e5e5e5;
        margin-right: 5px;
        cursor: pointer;
    }

    .wpaicg-paginate .page-numbers.current {
        background: #fff;
    }

    .wpaicg-template-settings>div {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .wpaicg-template-settings>div>strong {
        display: inline-block;
        width: 50%;
    }

    .wpaicg-template-settings>div>strong>small {
        font-weight: normal;
        display: block;
    }

    .wpaicg-template-settings>div>input,
    .wpaicg-template-settings>div>select {
        width: 48%;
        margin: 0;
    }

    .wpaicg-template-settings .wpaicg-template-sample {
        display: block;
        position: relative;
    }

    .wpaicg-template-settings .wpaicg-template-sample:hover .wpaicg-template-response {
        display: block;
    }

    .wpaicg-template-settings .wpaicg-template-response {
        background: #333;
        border: 1px solid #444;
        position: absolute;
        border-radius: 3px;
        color: #fff;
        padding: 5px;
        width: 100%;
        bottom: calc(100% + 5px);
        right: calc(50% - 55px);
        z-index: 99;
        display: none;
    }

    .wpaicg-template-settings .wpaicg-template-response:after,
    .wpaicg-template-settings .wpaicg-template-response:before {
        top: 100%;
        left: 50%;
        border: solid transparent;
        content: "";
        height: 0;
        width: 0;
        position: absolute;
        pointer-events: none;
    }

    .wpaicg-template-settings .wpaicg-template-response:before {
        border-color: rgba(68, 68, 68, 0);
        border-top-color: #444;
        border-width: 7px;
        margin-left: -7px;
    }

    .wpaicg-template-settings .wpaicg-template-response:after {
        border-color: rgba(51, 51, 51, 0);
        border-top-color: #333;
        border-width: 6px;
        margin-left: -6px;
    }

    .wpaicg_modal_content {
        max-height: calc(100% - 103px);
        overflow-y: auto;
    }

    .wpaicg_notice_text {
        padding: 10px;
        text-align: left;
        margin-bottom: 12px;
    }

    .wpaicg-create-template {
        width: 100%;
        display: block !important;
        margin-bottom: 10px !important;
    }

    .wpaicg-template-icons {}

    .wpaicg-template-icons span {
        padding: 10px;
        border-radius: 4px;
        border: 1px solid #ccc;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        margin-right: 5px;
        margin-bottom: 5px;
        cursor: pointer;
        color: #333;
    }

    .wpaicg-template-icons span svg {
        fill: currentColor;
        width: 30px;
        height: 30px;
    }

    .wpaicg-template-icons span.icon_selected {
        background: #343434;
        color: #fff;
    }

    .wp-picker-holder {
        position: absolute;
        z-index: 99;
    }

    .wp-picker-container {
        position: relative;
    }

    .wpaicg-template-action {
        position: absolute;
        right: 0;
        top: 37px;
        display: none;
    }

    .wpaicg-template-item:hover .wpaicg-template-action {
        display: block;
    }

    .wpaicg-template-action-edit {}

    .wpaicg-template-action-delete {
        background: #9d0000 !important;
        border-color: #9b0000 !important;
        color: #fff !important;
    }

    .wpaicg-template-form-field {
        display: block;
        padding: 10px;
        background: #d7d7d7;
        position: relative;
        border-radius: 4px;
        margin-bottom: 5px;
    }

    .wpaicg-template-form-prompt {
        display: block;
        padding: 10px;
        background: #d7d7d7;
        position: relative;
        border-radius: 4px;
        margin-bottom: 5px;
    }

    .wpaicg-template-form-field .wpaicg-grid {
        grid-row-gap: 0;
    }

    .wpaicg-template-form-field input {
        display: block;
        width: 100%;
    }

    .wpaicg-field-delete {
        position: absolute;
        top: 4px;
        right: 5px;
        width: 20px;
        height: 20px;
        display: flex;
        justify-content: center;
        align-items: center;
        background: #b00;
        border-radius: 2px;
        color: #fff;
        font-size: 20px;
        font-family: Arial, serif;
        cursor: pointer;
    }

    .wpaicg-prompt-delete{
        position: absolute;
        top: 4px;
        right: 5px;
        width: 20px;
        height: 20px;
        display: flex;
        justify-content: center;
        align-items: center;
        background: #b00;
        border-radius: 2px;
        color: #fff;
        font-size: 20px;
        font-family: Arial, serif;
        cursor: pointer;
    }

    .wpaicg-form-fields input[type=text],
    .wpaicg-form-fields select,
    .wpaicg-form-fields input[type=url],
    .wpaicg-form-fields input[type=email],
    .wpaicg-form-fields input[type=number] {
        width: 50%;
        margin-bottom: 10px;
    }

    .wpaicg-modal-tabs {
        margin: 0;
        display: flex;
    }

    .wpaicg-modal-tabs li {
        padding: 12px 15px;
        border-top-left-radius: 3px;
        border-top-right-radius: 3px;
        background: #2271b1;
        margin-bottom: 0;
        margin-right: 5px;
        border-top: 1px solid #2271b1;
        border-left: 1px solid #2271b1;
        border-right: 1px solid #2271b1;
        cursor: pointer;
        position: relative;
        top: 1px;
        color: #fff;
    }

    .wpaicg-modal-tabs li.wpaicg-active {
        background: #fff;
        color: #333;
    }

    .wpaicg-modal-tab-content {
        border: 1px solid #ccc;
    }

    .wpaicg-modal-tab {
        padding: 10px;
    }

    .wpaicg-template-item:before {
        display: none
    }

    .use-memory-key-label {
        cursor: pointer; 
    }
</style>
<div class="wpaicg-template-form-prompt-default" style="display: none">
    <div class="wpaicg-template-form-prompt">
        <div class="wpaicg-mb-10">
            <strong class="wpaicg-d-block mb-5 wpaicg-create-template-prompt-title-label"><?php echo esc_html__('Prompt1', 'gpt3-ai-content-generator') ?></strong>
            <textarea rows="5" name="prompts[0]" required class="regular-text wpaicg-w-100 wpaicg-create-template-prompt-content" placeholder="Enter the prompt here."></textarea>
        </div>
        <span class="wpaicg-prompt-delete">&times;</span>
    </div>
</div>
<div class="wpaicg-template-form-field-default" style="display: none">
    <div class="wpaicg-template-form-field">
        <div class="wpaicg-grid">
            <div class="">
                <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Label', 'gpt3-ai-content-generator') ?></strong>
                <input type="text" name="fields[0][label]" required class="wpaicg-create-template-field-label">
            </div>
            <div class="wpaicg-grid-2">
                <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('ID', 'gpt3-ai-content-generator') ?></strong>
                <input placeholder="example_field" type="text" name="fields[0][id]" required class="wpaicg-create-template-field-id">
                <small><?php echo esc_html__('Add this field in your prompt with following format', 'gpt3-ai-content-generator') ?>: {example_field}</small>
            </div>
            <div class="">
                <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Type', 'gpt3-ai-content-generator') ?></strong>
                <select name="fields[0][type]" class="wpaicg-create-template-field-type">
                    <option value="text"><?php echo esc_html__('Text', 'gpt3-ai-content-generator') ?></option>
                    <option value="select"><?php echo esc_html__('Drop-Down', 'gpt3-ai-content-generator') ?></option>
                    <option value="number"><?php echo esc_html__('Number', 'gpt3-ai-content-generator') ?></option>
                    <option value="email"><?php echo esc_html__('Email', 'gpt3-ai-content-generator') ?></option>
                    <option value="url"><?php echo esc_html__('URL', 'gpt3-ai-content-generator') ?></option>
                    <option value="textarea"><?php echo esc_html__('Textarea', 'gpt3-ai-content-generator') ?></option>
                    <option value="checkbox"><?php echo esc_html__('Checkbox', 'gpt3-ai-content-generator') ?></option>
                    <option value="radio"><?php echo esc_html__('Radio', 'gpt3-ai-content-generator') ?></option>
                    <option value="pdf"><?php echo esc_html__('PDF', 'gpt3-ai-content-generator') ?></option>
                </select>
            </div>
            <div class="wpaicg-create-template-field-min-main">
                <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Min', 'gpt3-ai-content-generator') ?></strong>
                <input placeholder="<?php echo esc_html__('Optional', 'gpt3-ai-content-generator') ?>" type="number" name="fields[0][min]" class="wpaicg-create-template-field-min">
            </div>
            <div class="wpaicg-create-template-field-max-main">
                <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Max', 'gpt3-ai-content-generator') ?></strong>
                <input placeholder="<?php echo esc_html__('Optional', 'gpt3-ai-content-generator') ?>" type="number" name="fields[0][min]" class="wpaicg-create-template-field-max">
            </div>
            <div class="wpaicg-create-template-field-rows-main" style="display: none">
                <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Rows', 'gpt3-ai-content-generator') ?></strong>
                <input placeholder="<?php echo esc_html__('Optional', 'gpt3-ai-content-generator') ?>" type="number" name="fields[0][rows]" class="wpaicg-create-template-field-rows">
            </div>
            <div class="wpaicg-create-template-field-cols-main" style="display: none">
                <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Cols', 'gpt3-ai-content-generator') ?></strong>
                <input placeholder="<?php echo esc_html__('Optional', 'gpt3-ai-content-generator') ?>" type="number" name="fields[0][cols]" class="wpaicg-create-template-field-cols">
            </div>
        </div>

        <div class="wpaicg-create-template-field-options-main" style="display: none">
            <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Options', 'gpt3-ai-content-generator') ?></strong>
            <textarea name="fields[0][options]" class="wpaicg-create-template-field-options wpaicg-w-100"></textarea>
            <small><?php echo esc_html__('Separate by', 'gpt3-ai-content-generator') ?> "|"</small>
        </div>
        <div class="">
            <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Description', 'gpt3-ai-content-generator') ?></strong>
            <textarea rows="4" name="fields[0][description]" required class="regular-text wpaicg-w-100 wpaicg-create-template-field-description" placeholder="Enter the description of this field here."></textarea>
        </div>
        <span class="wpaicg-field-delete">&times;</span>
    </div>
</div>
<div class="wpaicg-create-template-content" style="display: none">
    <?php
    wp_nonce_field('wpaicg_formai_save');
    ?>
    <input type="hidden" name="action" value="wpaicg_update_template">
    <input type="hidden" name="id" value="" class="wpaicg-create-template-id">
    <ul class="wpaicg-modal-tabs">
        <li class="wpaicg-active" data-target="properties"><?php echo esc_html__('Properties', 'gpt3-ai-content-generator') ?></li>
        <li data-target="ai-engine"><?php echo esc_html__('AI Engine', 'gpt3-ai-content-generator') ?></li>
        <li data-target="fields"><?php echo esc_html__('Fields', 'gpt3-ai-content-generator') ?></li>
        <li data-target="style"><?php echo esc_html__('Style', 'gpt3-ai-content-generator') ?></li>
        <li data-target="frontend"><?php echo esc_html__('Frontend', 'gpt3-ai-content-generator') ?></li>
        <li data-target="prompt"><?php echo esc_html__('Prompt', 'gpt3-ai-content-generator') ?></li>
    </ul>
    <div class="wpaicg-modal-tab-content wpaicg-mb-10">
        <div class="wpaicg-modal-tab wpaicg-modal-tab-properties">
            <div class="wpaicg-grid wpaicg-mb-10">
                <div class="wpaicg-grid-3">
                    <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Title', 'gpt3-ai-content-generator') ?></strong>
                    <input type="text" name="title" required class="regular-text wpaicg-w-100 wpaicg-create-template-title">
                </div>
                <div class="wpaicg-grid-3">
                    <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Category', 'gpt3-ai-content-generator') ?></strong>
                    <select name="category" class="wpaicg-w-100 wpaicg-create-template-category">
                        <?php
                        foreach ($wpaicg_categories as $key => $wpaicg_category) {
                            echo '<option value="' . esc_html($key) . '">' . esc_html($wpaicg_category) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="wpaicg-mb-10">
                <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Description', 'gpt3-ai-content-generator') ?></strong>
                <input type="text" name="description" required class="regular-text wpaicg-w-100 wpaicg-create-template-description">
            </div>
            <!-- <div class="wpaicg-mb-10">
                <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Prompt', 'gpt3-ai-content-generator') ?></strong>
                <textarea name="prompt" required class="regular-text wpaicg-w-100 wpaicg-create-template-prompt"></textarea>
            </div> -->
            <div class="wpaicg-mb-10">
                <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Sample Response', 'gpt3-ai-content-generator') ?></strong>
                <textarea name="response" class="regular-text wpaicg-w-100 wpaicg-create-template-response"></textarea>
            </div>
            <div class="wpaicg-mb-10">
                <div class="wpaicg-d-flex wpaicg-align-items-center">
                    <strong class="wpaicg-d-block mb-5 use-memory-key-label" for="savememorykey"><?php echo esc_html__('Save Memory Key', 'gpt3-ai-content-generator') ?></strong>
                    <input type="checkbox" id="savememorykey" class="wpaicg-create-template-savememorykey" name="savememorykey" style="margin-left: 10px;" checked value="True" />
                </div>
            </div>
        </div>
        <div class="wpaicg-modal-tab wpaicg-modal-tab-ai-engine" style="display: none">
            <div class="wpaicg-grid wpaicg-mb-10">
                <div class="wpaicg-grid-2">
                    <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Engine', 'gpt3-ai-content-generator') ?></strong>
                    <?php if ($wpaicg_provider == 'OpenAI') : ?>
                        <!-- Display dropdown for OpenAI -->
                        <select name="engine" class="wpaicg-w-100 wpaicg-create-template-engine" required>
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
                    <?php else : ?>
                        <!-- Display readonly text field for AzureAI -->
                        <input type="text" name="engine" class="wpaicg-w-100 wpaicg-create-template-engine" readonly value="<?php echo esc_html($azure_deployment_name); ?>" />
                    <?php endif; ?>

                </div>
                <div>
                    <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Max Tokens', 'gpt3-ai-content-generator') ?></strong>
                    <input type="text" name="max_tokens" class="regular-text wpaicg-w-100 wpaicg-create-template-max_tokens">
                </div>
                <div>
                    <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Temperature', 'gpt3-ai-content-generator') ?></strong>
                    <input type="text" name="temperature" class="regular-text wpaicg-w-100 wpaicg-create-template-temperature">
                </div>
                <div>
                    <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Top P', 'gpt3-ai-content-generator') ?></strong>
                    <input type="text" name="top_p" class="regular-text wpaicg-w-100 wpaicg-create-template-top_p">
                </div>
                <div>
                    <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Best Of', 'gpt3-ai-content-generator') ?></strong>
                    <input type="text" name="best_of" class="regular-text wpaicg-w-100 wpaicg-create-template-best_of">
                </div>
                <div>
                    <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('F. Penalty', 'gpt3-ai-content-generator') ?></strong>
                    <input type="text" name="frequency_penalty" class="regular-text wpaicg-w-100 wpaicg-create-template-frequency_penalty">
                </div>
                <div>
                    <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('P. Penalty', 'gpt3-ai-content-generator') ?></strong>
                    <input type="text" name="presence_penalty" class="regular-text wpaicg-w-100 wpaicg-create-template-presence_penalty">
                </div>
                <div>
                    <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Stop', 'gpt3-ai-content-generator') ?></strong>
                    <input type="text" name="stop" class="regular-text wpaicg-w-100 wpaicg-create-template-stop">
                </div>
            </div>
        </div>
        <div class="wpaicg-modal-tab wpaicg-modal-tab-fields" style="display: none">
            <div class="wpaicg-mb-10">
                <div class="mb-5 wpaicg-d-flex wpaicg-align-items-center"><strong class=""><?php echo esc_html__('Fields', 'gpt3-ai-content-generator') ?></strong><button type="button" class="wpaicg-create-form-field button button-primary button-small wpaicg-align-items-center" style="display: inline-flex;margin-left: 5px;"><span class="dashicons dashicons-plus"></span></button></div>
                <div class="wpaicg-template-fields"></div>
            </div>
        </div>
        <div class="wpaicg-modal-tab wpaicg-modal-tab-style" style="display: none">
            <div class="wpaicg-grid wpaicg-mb-10">
                <div class="wpaicg-grid-2">
                    <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Icon Color', 'gpt3-ai-content-generator') ?></strong>
                    <input type="text" name="color" class="regular-text wpaicg-w-100 wpaicg-create-template-color">
                </div>
                <div class="wpaicg-grid-2">
                    <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Background Color', 'gpt3-ai-content-generator') ?></strong>
                    <input type="text" name="bgcolor" class="regular-text wpaicg-w-100 wpaicg-create-template-bgcolor">
                </div>
            </div>
            <div class="wpaicg-mb-10">
                <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Icon', 'gpt3-ai-content-generator') ?></strong>
                <input type="hidden" class="wpaicg-create-template-icon" name="icon" value="robot">
                <div class="wpaicg-template-icons">
                    <?php
                    foreach ($wpaicg_icons as $key => $wpaicg_icon) {
                        echo '<span data-key="' . esc_html($key) . '">' . wp_kses($wpaicg_icon, $allowed_tags) . '</span>';
                    }
                    ?>
                </div>
            </div>
        </div>
        <div class="wpaicg-modal-tab wpaicg-modal-tab-frontend" style="display: none">
            <h3><strong><?php echo esc_html__('Response', 'gpt3-ai-content-generator') ?></strong></h3>
            <div class="wpaicg-grid wpaicg-mb-10">
                <div class="wpaicg-grid-1">
                    <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Result', 'gpt3-ai-content-generator') ?></strong>
                    <select name="editor" class="wpaicg-w-100 wpaicg-create-template-editor">
                        <option value="textarea"><?php echo esc_html__('Text Editor', 'gpt3-ai-content-generator') ?></option>
                        <option value="div"><?php echo esc_html__('Inline', 'gpt3-ai-content-generator') ?></option>
                    </select>
                </div>
            </div>
            <h3><strong><?php echo esc_html__('Display', 'gpt3-ai-content-generator') ?></strong></h3>
            <div class="wpaicg-grid wpaicg-mb-10">
                <div class="wpaicg-grid-1">
                    <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Header', 'gpt3-ai-content-generator') ?></strong>
                    <select name="header" class="wpaicg-w-100 wpaicg-create-template-header">
                        <option value=""><?php echo esc_html__('Yes', 'gpt3-ai-content-generator') ?></option>
                        <option value="no"><?php echo esc_html__('No', 'gpt3-ai-content-generator') ?></option>
                    </select>
                </div>
                <div class="wpaicg-grid-1">
                    <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Num. Answers', 'gpt3-ai-content-generator') ?></strong>
                    <select name="dans" class="wpaicg-w-100 wpaicg-create-template-dans">
                        <option value=""><?php echo esc_html__('Yes', 'gpt3-ai-content-generator') ?></option>
                        <option value="no"><?php echo esc_html__('No', 'gpt3-ai-content-generator') ?></option>
                    </select>
                </div>
                <div class="wpaicg-grid-1">
                    <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Draft Button', 'gpt3-ai-content-generator') ?></strong>
                    <select name="ddraft" class="wpaicg-w-100 wpaicg-create-template-ddraft">
                        <option value=""><?php echo esc_html__('Yes', 'gpt3-ai-content-generator') ?></option>
                        <option value="no"><?php echo esc_html__('No', 'gpt3-ai-content-generator') ?></option>
                    </select>
                </div>
                <div class="wpaicg-grid-1">
                    <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Clear Button', 'gpt3-ai-content-generator') ?></strong>
                    <select name="dclear" class="wpaicg-w-100 wpaicg-create-template-dclear">
                        <option value=""><?php echo esc_html__('Yes', 'gpt3-ai-content-generator') ?></option>
                        <option value="no"><?php echo esc_html__('No', 'gpt3-ai-content-generator') ?></option>
                    </select>
                </div>
                <div class="wpaicg-grid-1">
                    <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Notice', 'gpt3-ai-content-generator') ?></strong>
                    <select name="dnotice" class="wpaicg-w-100 wpaicg-create-template-dnotice">
                        <option value=""><?php echo esc_html__('Yes', 'gpt3-ai-content-generator') ?></option>
                        <option value="no"><?php echo esc_html__('No', 'gpt3-ai-content-generator') ?></option>
                    </select>
                </div>
                <div class="wpaicg-grid-1">
                    <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Download', 'gpt3-ai-content-generator') ?></strong>
                    <select name="ddownload" class="wpaicg-w-100 wpaicg-create-template-ddownload">
                        <option value=""><?php echo esc_html__('Yes', 'gpt3-ai-content-generator') ?></option>
                        <option value="no"><?php echo esc_html__('No', 'gpt3-ai-content-generator') ?></option>
                    </select>
                </div>
            </div>
            <h3><strong><?php echo esc_html__('Custom Text', 'gpt3-ai-content-generator') ?></strong></h3>
            <div class="wpaicg-grid wpaicg-mb-10">
                <div class="wpaicg-grid-2">
                    <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Generate Button', 'gpt3-ai-content-generator') ?></strong>
                    <input value="<?php echo esc_html__('Generate', 'gpt3-ai-content-generator') ?>" type="text" name="generate_text" class="regular-text wpaicg-w-100 wpaicg-create-template-generate_text">
                </div>
                <div class="wpaicg-grid-2">
                    <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Num. Answers Text', 'gpt3-ai-content-generator') ?></strong>
                    <input value="<?php echo esc_html__('Number of Answers', 'gpt3-ai-content-generator') ?>" type="text" name="noanswer_text" class="regular-text wpaicg-w-100 wpaicg-create-template-noanswer_text">
                </div>
                <div class="wpaicg-grid-2">
                    <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Draft Text', 'gpt3-ai-content-generator') ?></strong>
                    <input value="<?php echo esc_html__('Save Draft', 'gpt3-ai-content-generator') ?>" type="text" name="draft_text" class="regular-text wpaicg-w-100 wpaicg-create-template-draft_text">
                </div>
                <div class="wpaicg-grid-2">
                    <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Clear Text', 'gpt3-ai-content-generator') ?></strong>
                    <input value="<?php echo esc_html__('Clear', 'gpt3-ai-content-generator') ?>" type="text" name="clear_text" class="regular-text wpaicg-w-100 wpaicg-create-template-clear_text">
                </div>
                <div class="wpaicg-grid-2">
                    <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Stop Text', 'gpt3-ai-content-generator') ?></strong>
                    <input value="<?php echo esc_html__('Stop', 'gpt3-ai-content-generator') ?>" type="text" name="stop_text" class="regular-text wpaicg-w-100 wpaicg-create-template-stop_text">
                </div>
                <div class="wpaicg-grid-2">
                    <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Notice Text', 'gpt3-ai-content-generator') ?></strong>
                    <input value="<?php echo esc_html__('Please register to save your result', 'gpt3-ai-content-generator') ?>" type="text" name="cnotice_text" class="regular-text wpaicg-w-100 wpaicg-create-template-cnotice_text">
                </div>
                <div class="wpaicg-grid-2">
                    <strong class="wpaicg-d-block mb-5"><?php echo esc_html__('Download Text', 'gpt3-ai-content-generator') ?></strong>
                    <input value="<?php echo esc_html__('Download', 'gpt3-ai-content-generator') ?>" type="text" name="download_text" class="regular-text wpaicg-w-100 wpaicg-create-template-download_text'">
                </div>
            </div>
        </div>
        <div class="wpaicg-modal-tab wpaicg-modal-tab-prompt" style="display: none">
            <div class="wpaicg-mb-10">
                <div class="mb-5 wpaicg-d-flex wpaicg-align-items-center"><strong class=""><?php echo esc_html__('Prompts', 'gpt3-ai-content-generator') ?></strong><button type="button" class="wpaicg-create-form-prompt button button-primary button-small wpaicg-align-items-center" style="display: inline-flex;margin-left: 5px;"><span class="dashicons dashicons-plus"></span></button></div>
                <div class="wpaicg-template-prompts"></div>
            </div>
        </div>
    </div>
    <button class="button button-primary wpaicg-create-template-save"><?php echo esc_html__('Save', 'gpt3-ai-content-generator') ?></button>
</div>
<?php
if (isset($_GET['update_template']) && !empty($_GET['update_template'])) :
?>
    <p style="padding: 6px 12px;border: 1px solid green;border-radius: 3px;background: lightgreen;">
        <strong><?php echo esc_html__('Success', 'gpt3-ai-content-generator') ?>:</strong> <?php echo esc_html__('Congrats! Your template created! You can add this shortcode to your page', 'gpt3-ai-content-generator') ?>: [wpaicg_form id=<?php echo esc_html($_GET['update_template']) ?> custom=yes]
    </p>
<?php
endif;
?>
<div class="wpaicg_template">
    <div class="wpaicg-grid">
        <div class="wpaicg-grid-1">
            <button class="button button-primary wpaicg-create-template" type="button"><?php echo esc_html__('Design Your Form', 'gpt3-ai-content-generator') ?></button>
            <strong><?php echo esc_html__('Author', 'gpt3-ai-content-generator') ?></strong>
            <ul class="wpaicg-list wpaicg-mb-10 wpaicg-authors">
                <?php
                if (count($wpaicg_authors)) {
                    foreach ($wpaicg_authors as $key => $wpaicg_author) {
                ?>
                        <li><label><input type="checkbox" value="<?php echo esc_attr($key) ?>">&nbsp;<?php echo esc_html($wpaicg_author['name']) ?> (<?php echo esc_html($wpaicg_author['count']) ?>)</label></li>
                <?php
                    }
                }
                ?>
            </ul>
            <!-- <strong><?php echo esc_html__('Category', 'gpt3-ai-content-generator') ?></strong>
            <ul class="wpaicg-list wpaicg-categories">
                <?php
                if (count($wpaicg_categories)) {
                    foreach ($wpaicg_categories as $wpaicg_category) {
                ?>
                        <li><label><input type="checkbox" value="<?php echo sanitize_title($wpaicg_category) ?>">&nbsp;<?php echo esc_html($wpaicg_category) ?></label></li>
                <?php
                    }
                }
                ?>
            </ul> -->
        </div>
        <div class="wpaicg-grid-5">
            <div class="wpaicg-mb-10">
                <input class="wpaicg-w-100 wpaicg-d-block wpaicg-template-search" type="text" placeholder="<?php echo esc_html__('Search Template', 'gpt3-ai-content-generator') ?>">
            </div>
            <div class="wpaicg-grid-three wpaicg-template-items">
                <?php
                if (count($wpaicg_items)) :
                    foreach ($wpaicg_items as $wpaicg_item) :
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
                        $wpaicg_engine = isset($wpaicg_item['engine']) && !empty($wpaicg_item['engine']) ? $wpaicg_item['engine'] : $this->wpaicg_engine;
                        if ($wpaicg_provider == 'Azure') {
                            $wpaicg_engine = get_option('wpaicg_azure_deployment', '');
                        }

                        $wpaicg_max_tokens = isset($wpaicg_item['max_tokens']) && !empty($wpaicg_item['max_tokens']) ? $wpaicg_item['max_tokens'] : $this->wpaicg_max_tokens;
                        $wpaicg_temperature = isset($wpaicg_item['temperature']) && !empty($wpaicg_item['temperature']) ? $wpaicg_item['temperature'] : $this->wpaicg_temperature;
                        $wpaicg_top_p = isset($wpaicg_item['top_p']) && !empty($wpaicg_item['top_p']) ? $wpaicg_item['top_p'] : $this->wpaicg_top_p;
                        $wpaicg_best_of = isset($wpaicg_item['best_of']) && !empty($wpaicg_item['best_of']) ? $wpaicg_item['best_of'] : $this->wpaicg_best_of;
                        $wpaicg_frequency_penalty = isset($wpaicg_item['frequency_penalty']) && !empty($wpaicg_item['frequency_penalty']) ? $wpaicg_item['frequency_penalty'] : $this->wpaicg_frequency_penalty;
                        $wpaicg_presence_penalty = isset($wpaicg_item['presence_penalty']) && !empty($wpaicg_item['presence_penalty']) ? $wpaicg_item['presence_penalty'] : $this->wpaicg_presence_penalty;
                        $wpaicg_stop = isset($wpaicg_item['stop']) && !empty($wpaicg_item['stop']) ? $wpaicg_item['stop'] : $this->wpaicg_stop;
                        $wpaicg_stop_lists = '';
                        if (is_array($wpaicg_stop) && count($wpaicg_stop)) {
                            foreach ($wpaicg_stop as $item_stop) {
                                if ($item_stop === "\n") {
                                    $item_stop = '\n';
                                }
                                $wpaicg_stop_lists = empty($wpaicg_stop_lists) ? $item_stop : ',' . $item_stop;
                            }
                        } elseif (is_array($wpaicg_stop) && !count($wpaicg_stop)) {
                            $wpaicg_stop_lists = '';
                        } else {
                            $wpaicg_stop_lists = $wpaicg_stop;
                        }
                        if (count($wpaicg_item_categories)) {
                            foreach ($wpaicg_item_categories as $wpaicg_item_category) {
                                if (isset($wpaicg_categories[$wpaicg_item_category]) && !empty($wpaicg_categories[$wpaicg_item_category])) {
                                    $wpaicg_item_categories_name[] = $wpaicg_categories[$wpaicg_item_category];
                                }
                            }
                        }
                        $wpaicg_fields = isset($wpaicg_item['fields']) && !empty($wpaicg_item['fields']) ? $wpaicg_item['fields'] : '';
                        if (!empty($wpaicg_fields) && !is_array($wpaicg_fields) && strpos($wpaicg_fields, '\"') !== false) {
                            $wpaicg_fields = str_replace('\"', '[QUOTE]', $wpaicg_fields);
                        }
                        if (!empty($wpaicg_fields) && !is_array($wpaicg_fields) && strpos($wpaicg_fields, "\'") !== false) {
                            $wpaicg_fields = str_replace('\\', '', $wpaicg_fields);
                        }
                ?>
                        <div 
                            id="wpaicg-template-item-<?php echo esc_html($wpaicg_item['id']) ?>" 
                            data-fields="<?php echo esc_html($wpaicg_item['type'] == 'custom' ? $wpaicg_fields : json_encode($wpaicg_fields, JSON_UNESCAPED_UNICODE)) ?>" 
                            data-title="<?php echo esc_html($wpaicg_item['title']) ?>" 
                            data-type="<?php echo esc_html($wpaicg_item['type']) ?>" 
                            data-id="<?php echo esc_html($wpaicg_item['id']) ?>" 
                            data-post_title="<?php echo esc_html($wpaicg_item['title']) ?>" 
                            data-desc="<?php echo esc_html(@$wpaicg_item['description']) ?>" 
                            data-description="<?php echo esc_html(@$wpaicg_item['description']) ?>" 
                            data-savememorykey="<?php echo isset($wpaicg_item['savememorykey']) && $wpaicg_item['savememorykey'] == "True" ? "True" : "False" ?>" 
                            data-icon="<?php echo esc_html(@$wpaicg_item['icon']) ?>" 
                            data-color="<?php echo esc_html($wpaicg_icon_color) ?>" 
                            data-engine="<?php echo esc_html($wpaicg_engine) ?>" 
                            data-max_tokens="<?php echo esc_html($wpaicg_max_tokens) ?>" 
                            data-temperature="<?php echo esc_html($wpaicg_temperature) ?>" 
                            data-top_p="<?php echo esc_html($wpaicg_top_p) ?>" 
                            data-best_of="<?php echo esc_html($wpaicg_best_of) ?>" 
                            data-frequency_penalty="<?php echo esc_html($wpaicg_frequency_penalty) ?>" 
                            data-presence_penalty="<?php echo esc_html($wpaicg_presence_penalty) ?>" 
                            data-stop="<?php echo esc_html($wpaicg_stop_lists) ?>" 
                            data-categories="<?php echo esc_html(implode(', ', $wpaicg_item_categories_name)) ?>" 
                            data-category="<?php echo esc_html($wpaicg_item['category']) ?>" 
                            data-prompts="<?php echo esc_html(@$wpaicg_item['prompts']); ?>" 
                            data-estimated="<?php echo isset($wpaicg_item['estimated']) ? esc_html($wpaicg_item['estimated']) : ''; ?>" 
                            data-response="<?php echo esc_html(@$wpaicg_item['response']); ?>" 
                            data-editor="<?php echo isset($wpaicg_item['editor']) && $wpaicg_item['editor'] == 'div' ? 'div' : 'textarea' ?>" 
                            data-header="<?php echo isset($wpaicg_item['header']) ? esc_html($wpaicg_item['header']) : ''; ?>" 
                            data-bgcolor="<?php echo isset($wpaicg_item['bgcolor']) ? esc_html($wpaicg_item['bgcolor']) : ''; ?>" 
                            data-dans="<?php echo isset($wpaicg_item['dans']) ? esc_html($wpaicg_item['dans']) : ''; ?>" 
                            data-ddraft="<?php echo isset($wpaicg_item['ddraft']) ? esc_html($wpaicg_item['ddraft']) : ''; ?>" 
                            data-dclear="<?php echo isset($wpaicg_item['dclear']) ? esc_html($wpaicg_item['dclear']) : ''; ?>" 
                            data-dnotice="<?php echo isset($wpaicg_item['dnotice']) ? esc_html($wpaicg_item['dnotice']) : ''; ?>" 
                            data-generate_text="<?php echo isset($wpaicg_item['generate_text']) && !empty($wpaicg_item['generate_text']) ? esc_html($wpaicg_item['generate_text']) : esc_html__('Generate', 'gpt3-ai-content-generator'); ?>" 
                            data-noanswer_text="<?php echo isset($wpaicg_item['noanswer_text']) && !empty($wpaicg_item['noanswer_text']) ? esc_html($wpaicg_item['noanswer_text']) : esc_html__('Number of Answers', 'gpt3-ai-content-generator'); ?>" 
                            data-draft_text="<?php echo isset($wpaicg_item['draft_text']) && !empty($wpaicg_item['draft_text']) ? esc_html($wpaicg_item['draft_text']) : esc_html__('Save Draft', 'gpt3-ai-content-generator'); ?>" 
                            data-clear_text="<?php echo isset($wpaicg_item['clear_text']) && !empty($wpaicg_item['clear_text']) ? esc_html($wpaicg_item['clear_text']) : esc_html__('Clear', 'gpt3-ai-content-generator'); ?>" 
                            data-stop_text="<?php echo isset($wpaicg_item['stop_text']) && !empty($wpaicg_item['stop_text']) ? esc_html($wpaicg_item['stop_text']) : esc_html__('Stop', 'gpt3-ai-content-generator'); ?>" 
                            data-cnotice_text="<?php echo isset($wpaicg_item['cnotice_text']) && !empty($wpaicg_item['cnotice_text']) ? esc_html($wpaicg_item['cnotice_text']) : esc_html__('Please register to save your result', 'gpt3-ai-content-generator'); ?>" 
                            data-download_text="<?php echo isset($wpaicg_item['download_text']) && !empty($wpaicg_item['download_text']) ? esc_html($wpaicg_item['download_text']) : esc_html__('Download', 'gpt3-ai-content-generator'); ?>" 
                            data-ddownload="<?php echo isset($wpaicg_item['ddownload']) ? esc_html($wpaicg_item['ddownload']) : ''; ?>" class="wpaicg-template-item wpaicg-d-flex wpaicg-align-items-center <?php echo implode(' ', $wpaicg_item_categories) ?><?php echo ' user-' . esc_html($wpaicg_item['author']) ?><?php echo ' wpaicg-template-item-' . $wpaicg_item['type'] . '-' . esc_html($wpaicg_item['id']); ?>">
                            <div class="wpaicg-template-icon" style="background: <?php echo esc_html($wpaicg_icon_color) ?>"><?php echo wp_kses($wpaicg_icon, $allowed_tags) ?></div>
                            <div class="wpaicg-template-content">
                                <strong><?php echo isset($wpaicg_item['title']) && !empty($wpaicg_item['title']) ? esc_html($wpaicg_item['title']) : '' ?></strong>
                                <?php
                                if (isset($wpaicg_item['description']) && !empty($wpaicg_item['description'])) {
                                    echo '<p>' . esc_html($wpaicg_item['description']) . '</p>';
                                }
                                ?>
                            </div>
                            <?php
                            if ($wpaicg_item['type'] == 'custom') :
                            ?>
                                <div class="wpaicg-template-action">
                                    <button class="button button-small wpaicg-template-action-duplicate" data-id="<?php echo esc_html($wpaicg_item['id']) ?>"><?php echo esc_html__('Duplicate', 'gpt3-ai-content-generator') ?></button>
                                    <button class="button button-small wpaicg-template-action-edit" data-id="<?php echo esc_html($wpaicg_item['id']) ?>"><?php echo esc_html__('Edit', 'gpt3-ai-content-generator') ?></button>
                                    <button class="button button-small wpaicg-template-action-delete" data-id="<?php echo esc_html($wpaicg_item['id']) ?>"><?php echo esc_html__('Delete', 'gpt3-ai-content-generator') ?></button>
                                </div>
                            <?php
                            endif;
                            ?>
                        </div>
                <?php
                    endforeach;
                endif;
                ?>
            </div>
            <div class="wpaicg-paginate"></div>
        </div>
    </div>
</div>

<div class="wpaicg-template-modal-content" style="display: none">
    <form method="post" action="">
        <div class="wpaicg-grid-three">
            <div class="wpaicg-grid-2">
                <input type="hidden" class="wpaicg-template-response-type" value="textarea">
                <textarea style="display: none" class="wpaicg-template-title" rows="8"></textarea>
                <textarea style="display: none" name="title" class="wpaicg-template-title-filled" rows="8"></textarea>
                <div class="wpaicg-form-fields"></div>
                <div class="wpaicg-mb-10">
                    <strong class="wpaicg-template-text-noanswer_text"><?php echo esc_html__('Number of Answers', 'gpt3-ai-content-generator') ?></strong>
                    <select class="wpaicg-template-max-lines">
                        <?php
                        for ($i = 1; $i <= 10; $i++) {
                            echo '<option value="' . esc_html($i) . '">' . esc_html($i) . '</option>';
                        }
                        ?>
                    </select>
                    <button class="button button-primary wpaicg-generate-button wpaicg-template-text-generate_text"><?php echo esc_html__('Generate', 'gpt3-ai-content-generator') ?></button>
                    &nbsp;<button type="button" class="button button-primary wpaicg-template-stop-generate wpaicg-template-text-stop_text" style="display: none"><?php echo esc_html__('Stop', 'gpt3-ai-content-generator') ?></button>
                </div>
                <div class="mb-5">
                    <div class="wpaicg-template-response-editor">
                        <textarea class="wpaicg-template-result" rows="12"></textarea>
                    </div>
                    <div class="wpaicg-template-response-element"></div>
                </div>
                <div class="wpaicg-template-save-result" style="display: none">
                    <button type="button" class="button button-primary wpaicg-template-save-draft wpaicg-template-text-draft_text"><?php echo esc_html__('Save Draft', 'gpt3-ai-content-generator') ?></button>
                    <button type="button" class="button wpaicg-template-clear wpaicg-template-text-clear_text"><?php echo esc_html__('Clear', 'gpt3-ai-content-generator') ?></button>
                    <button type="button" class="button wpaicg-template-download wpaicg-template-text-download_text"><?php echo esc_html__('Download', 'gpt3-ai-content-generator') ?></button>
                </div>
            </div>
            <div class="wpaicg-grid-1">
                <div class="wpaicg-mb-10 wpaicg-template-settings">
                    <button type="button" style="width: 100%" class="button button-primary wpaicg-template-action-customize" data-id="">
                        <?php echo esc_html__('Duplicate This Form', 'gpt3-ai-content-generator') ?></button>
                    <h3><?php echo esc_html__('Settings', 'gpt3-ai-content-generator') ?></h3>
                    <div class="mb-5 wpaicg-template-engine">
                        <strong><?php echo esc_html__('Engine', 'gpt3-ai-content-generator') ?>: </strong>
                        <?php if ($wpaicg_provider == 'OpenAI') : ?>
                            <!-- Display dropdown for OpenAI -->
                            <select name="engine" class="wpaicg-w-100 wpaicg-create-template-engine" required>
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
                        <?php else : ?>
                            <!-- Display readonly text field for AzureAI -->
                            <input type="text" name="engine" class="regular-text wpaicg_custom_template_model" readonly value="<?php echo esc_html($azure_deployment_name); ?>" />
                        <?php endif; ?>

                    </div>
                    <div class="mb-5 wpaicg-template-max_tokens"><strong><?php echo esc_html__('Max Tokens', 'gpt3-ai-content-generator') ?>: </strong><input class="wpaicg-field-max-tokens" name="max_tokens" type="text" min="1" max="2048"></div>
                    <div class="mb-5 wpaicg-template-temperature"><strong><?php echo esc_html__('Temperature', 'gpt3-ai-content-generator') ?>: </strong><input name="temperature" type="text" min="0" max="1" step="any"></div>
                    <div class="mb-5 wpaicg-template-top_p"><strong><?php echo esc_html__('Top P', 'gpt3-ai-content-generator') ?>: </strong><input name="top_p" type="text" min="0" max="1"></div>
                    <div class="mb-5 wpaicg-template-best_of"><strong><?php echo esc_html__('Best Of', 'gpt3-ai-content-generator') ?>: </strong><input name="best_of" type="text" min="1" max="20"></div>
                    <div class="mb-5 wpaicg-template-frequency_penalty"><strong><?php echo esc_html__('Frequency Penalty', 'gpt3-ai-content-generator') ?>: </strong><input name="frequency_penalty" type="text" min="0" max="2" step="any"></div>
                    <div class="mb-5 wpaicg-template-presence_penalty"><strong><?php echo esc_html__('Presence Penalty', 'gpt3-ai-content-generator') ?>: </strong><input name="presence_penalty" type="text" min="0" max="2" step="any"></div>
                    <div class="mb-5 wpaicg-template-stop"><strong><?php echo esc_html__('Stop', 'gpt3-ai-content-generator') ?>:<small><?php echo esc_html__('separate by commas', 'gpt3-ai-content-generator') ?></small></strong><input name="stop" type="text"></div>
                    <div class="mb-5 wpaicg-template-estimated"><strong><?php echo esc_html__('Estimated', 'gpt3-ai-content-generator') ?>: </strong><span></span></div>
                    <div class="mb-5 wpaicg-template-post_title"><input type="hidden" name="post_title"></div>
                    <div class="mb-5 wpaicg-template-sample"><?php echo esc_html__('Sample Response', 'gpt3-ai-content-generator') ?><div class="wpaicg-template-response"></div>
                    </div>
                    <div style="padding: 5px;background: #ffc74a;border-radius: 4px;color: #000;" class="wpaicg-template-shortcode"></div>
                </div>
            </div>
        </div>
    </form>
</div>
<div class="wpaicg-openai-settings-modal-content" style="display: none">
    <form class="wpaicg-openai-settings-modal-content-form" method="post" action="">
        <div style="padding: 0px 20px; margin-top: 10px;" class="wpaicg-mb-12">
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
            <div class="wpcgai_form_row">
                <label class="wpcgai_label"><?php echo esc_html__('Api Key', 'gpt3-ai-content-generator') ?>:</label>
                <input type="text" class="regular-text" id="label_api_key" name="wpaicg_settings[api_key]" value="<?php
                                                                                                                    echo  esc_html($user_openai_api_key);
                                                                                                                    ?>">
                <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/ai-engine/openai/api-key#how-to-generate-an-openai-api-key" target="_blank">?</a>
                <a class="wpcgai_help_link" href="https://beta.openai.com/account/api-keys" target="_blank"><?php echo esc_html__('Get Your Api Key', 'gpt3-ai-content-generator') ?></a>
            </div>
            <div style="margin-top: 10px; display: flex; justify-content: right;"><input type="submit" value="<?php echo esc_html__('Save', 'gpt3-ai-content-generator') ?>" name="wpaicg_submit" class="button button-primary button-large"></div>
            <div style="padding: 5px;background: #ffc74a;border-radius: 4px;color: #000; margin-top: 10px;" class="wpaicg-template-shortcode"></div>
        </div>
    </form>
</div>

<script>
    jQuery(document).ready(function($) {
        let prompt_id;
        let prompt_name;
        let prompt_response = '';
        let wpaicg_limited_token = false;
        let wp_nonce = '<?php echo esc_html(wp_create_nonce('wpaicg-formlog')) ?>'
        /*Modal tab*/
        $(document).on('click', '.wpaicg-modal-tabs li', function(e) {
            var tab = $(e.currentTarget);
            var target = tab.attr('data-target');
            var modal = tab.closest('.wpaicg_modal_content');
            modal.find('.wpaicg-modal-tabs li').removeClass('wpaicg-active');
            tab.addClass('wpaicg-active');
            modal.find('.wpaicg-modal-tab').hide();
            modal.find('.wpaicg-modal-tab-' + target).show();
        })

        /* Create Prompt Template */
        var wpaicgCreatePrompt = $('.wpaicg-template-form-prompt-default');

        /*Create Template*/
        var wpaicgTemplateContent = $('.wpaicg-create-template-content');
        var wpaicgTemplageFieldDefault = $('.wpaicg-template-form-field-default');
        var wpaicgCreateField = $('.wpaicg-template-form-field-default');
        var wpaicgFieldInputs = ['label', 'id', 'type', 'min', 'max', 'options', 'rows', 'cols', 'description'];
        $(document).on('click', '.wpaicg-template-icons span', function(e) {
            var icon = $(e.currentTarget);
            icon.parent().find('span').removeClass('icon_selected');
            icon.addClass('icon_selected');
            icon.parent().parent().find('.wpaicg-create-template-icon').val(icon.attr('data-key'));
        });
        $(document).on('click', '.wpaicg-field-delete', function(e) {
            $(e.currentTarget).parent().remove();
            wpaicgSortField();
        });

        $(document).on('click', '.wpaicg-prompt-delete', function(e) {
            $(e.currentTarget).parent().remove();
            wpaicgSortPrompt();
        });

        function wpaicgSortField() {
            $('.wpaicg-create-template-form .wpaicg-template-form-field').each(function(idx, item) {
                $.each(wpaicgFieldInputs, function(idxy, field) {
                    $(item).find('.wpaicg-create-template-field-' + field).attr('name', 'fields[' + idx + '][' + field + ']');
                });
            })
        }

        function isUnicode(str) {
            const unicodeRegex = /\\u[\dA-Fa-f]{4}/g;
            return unicodeRegex.test(str);
        }

        function decodeUnicode(str) {
            var result = str.replace(/\\u[\da-fA-F]{4}/g,
                function(match) {
                    return String.fromCharCode(parseInt(match.replace(/\\u/g, ''), 16));
                });

            return result;
        }

        function encodeUnicode(str) {
            var res = [];
            for (var i = 0; i < str.length; i++) {
                res[i] = ("00" + str.charCodeAt(i).toString(16)).slice(-4);
            }
            return "\\u" + res.join("\\u");
        }

        $(document).on('click', '.wpaicg-create-form-field', function(e) {
            $('.wpaicg-create-template-form .wpaicg-template-fields').append(wpaicgCreateField.html());
            wpaicgSortField();
        });

        function wpaicgSortPrompt() {
            $('.wpaicg-create-template-form .wpaicg-template-form-prompt').each(function(idx, item) {
                $(item).find('.wpaicg-create-template-prompt-content').attr('name', 'prompts[' + idx + ']');
                $(item).find('.wpaicg-create-template-prompt-title-label').text("Prompt" + (idx + 1));
            })
        }

        // Add prompt in Edit your Template page
        $(document).on('click', '.wpaicg-create-form-prompt', function(e) {
            $('.wpaicg-create-template-form .wpaicg-template-prompts').append(wpaicgCreatePrompt.html());
            wpaicgSortPrompt();
        });

        $(document).on('change', '.wpaicg-create-template-engine', function(e) {
            var selectedEngine = $(this).val();
            var maxTokensInput = $('.wpaicg-field-max-tokens');
            var maxtokens = 0;
            if (selectedEngine === 'chatgpt-4o-latest') {
                maxTokensInput.val(4000);
                maxtokens = 4000;
            } else {
                maxTokensInput.val(2000);
                maxtokens = 2000;
            }
            var wpaicg_estimated_cost = maxtokens !== '' ? parseFloat(maxtokens) * 0.002 / 1000 : 0;
            wpaicg_estimated_cost = '$' + parseFloat(wpaicg_estimated_cost.toFixed(5));
            $('.wpaicg-template-form .wpaicg-template-estimated span').html(wpaicg_estimated_cost);
        });

        $(document).on('change', '.wpaicg-create-template-field-type', function(e) {
            var type = $(e.currentTarget).val();
            var parentEl = $(e.currentTarget).closest('.wpaicg-template-form-field');
            if (type === 'select' || type === 'checkbox' || type === 'radio') {
                parentEl.find('.wpaicg-create-template-field-options-main').show();
                parentEl.find('.wpaicg-create-template-field-min-main').hide();
                parentEl.find('.wpaicg-create-template-field-max-main').hide();
                parentEl.find('.wpaicg-create-template-field-rows-main').hide();
                parentEl.find('.wpaicg-create-template-field-cols-main').hide();
            } else if (type === 'textarea') {
                parentEl.find('.wpaicg-create-template-field-rows-main').show();
                parentEl.find('.wpaicg-create-template-field-cols-main').show();
                parentEl.find('.wpaicg-create-template-field-options-main').hide();
                parentEl.find('.wpaicg-create-template-field-min-main').show();
                parentEl.find('.wpaicg-create-template-field-max-main').show();
            } else if (type === 'pdf') {
                parentEl.find('.wpaicg-create-template-field-min-main').hide();
                parentEl.find('.wpaicg-create-template-field-max-main').hide();
                parentEl.find('.wpaicg-create-template-field-rows-main').hide();
                parentEl.find('.wpaicg-create-template-field-cols-main').hide();
            } else {
                parentEl.find('.wpaicg-create-template-field-rows-main').hide();
                parentEl.find('.wpaicg-create-template-field-cols-main').hide();
                parentEl.find('.wpaicg-create-template-field-options-main').hide();
                parentEl.find('.wpaicg-create-template-field-min-main').show();
                parentEl.find('.wpaicg-create-template-field-max-main').show();
            }
        })
        $('.wpaicg-create-template').click(function() {
            $('.wpaicg_modal_content').empty();
            $('.wpaicg_modal_title').html('<?php echo esc_html__('Design Your Form', 'gpt3-ai-content-generator') ?>');
            $('.wpaicg_modal_content').html('<form action="" method="post" class="wpaicg-create-template-form">' + wpaicgTemplateContent.html() + '</form>');
            $('.wpaicg-create-template-form .wpaicg-create-template-color').wpColorPicker();
            $('.wpaicg-create-template-form .wpaicg-create-template-bgcolor').wpColorPicker();
            $('.wpaicg-create-template-form .wpaicg-create-template-category').val('generation');
            $('.wpaicg-create-template-form .wpaicg-template-icons span[data-key=robot]').addClass('icon_selected');
            $('.wpaicg-overlay').show();
            //$('.wpaicg_modal').css('height','60%');
            $('.wpaicg_modal').show();
        })
        $(document).on('click', '.wpaicg-template-item .wpaicg-template-action-delete', function(e) {
            var id = $(e.currentTarget).attr('data-id');
            var conf = confirm('<?php echo esc_html__('Are you sure?', 'gpt3-ai-content-generator') ?>');
            if (conf) {
                $('.wpaicg-template-item-custom-' + id).remove();
                $.post('<?php echo admin_url('admin-ajax.php') ?>', {
                    action: 'wpaicg_template_delete',
                    id: id,
                    'nonce': '<?php echo wp_create_nonce('wpaicg-ajax-nonce') ?>'
                });
            }
        });

        $(document).on('change', '.wpaicg-create-template-savememorykey', function() {
            if ($(this).is(':checked')) {
                $(this).val('True');
            } else {
                $(this).val('False');
            }
        });

        // open edit panel
        $(document).on('click', '.wpaicg-template-item .wpaicg-template-action-edit', function(e) {
            var id = $(e.currentTarget).attr('data-id');
            var item = $('.wpaicg-template-item-custom-' + id);
            $('.wpaicg_modal_content').empty();
            $('.wpaicg_modal_title').html('<?php echo esc_html__('Edit your Template', 'gpt3-ai-content-generator') ?>');
            $('.wpaicg_modal_content').html('<form action="" method="post" class="wpaicg-create-template-form">' + wpaicgTemplateContent.html() + '</form>');
            var form = $('.wpaicg-create-template-form');
            var wpaicg_template_keys = ['engine', 'editor', 'title', 'description', 'max_tokens', 'savememorykey', 'temperature', 'top_p', 'best_of', 'frequency_penalty', 'presence_penalty', 'stop', 'prompts', 'response', 'category', 'icon', 'color', 'bgcolor', 'header', 'dans', 'ddraft', 'dclear', 'dnotice', 'generate_text', 'noanswer_text', 'draft_text', 'clear_text', 'stop_text', 'cnotice_text', 'download_text', 'ddownload'];
            for (var i = 0; i < wpaicg_template_keys.length; i++) {
                var wpaicg_template_key = wpaicg_template_keys[i];
                var wpaicg_template_key_value = item.attr('data-' + wpaicg_template_key);
                form.find('.wpaicg-create-template-' + wpaicg_template_key).val(wpaicg_template_key_value);
                if (wpaicg_template_key === 'icon') {
                    if (wpaicg_template_key_value === '') {
                        wpaicg_template_key_value = 'robot';
                    }
                    $('.wpaicg-create-template-form .wpaicg-template-icons span[data-key=' + wpaicg_template_key_value + ']').addClass('icon_selected');
                }
                if (wpaicg_template_key === 'savememorykey') {
                    if (wpaicg_template_key_value === 'True') {
                        form.find('.wpaicg-create-template-savememorykey').prop('checked', true);
                        form.find('.wpaicg-create-template-savememorykey').val("True");
                    } else {
                        form.find('.wpaicg-create-template-savememorykey').prop('checked', false);
                        form.find('.wpaicg-create-template-savememorykey').val("False");
                    }
                }
            }

            var wpaicg_form_prompts = item.attr('data-prompts');
            if (wpaicg_form_prompts !== '') {

                // wpaicg_form_prompts = wpaicg_form_prompts.replace(/\\/g, '');
                wpaicg_form_prompts = JSON.parse(wpaicg_form_prompts);
                $.each(wpaicg_form_prompts, function(idx, prompt) {
                    $('.wpaicg-create-template-form .wpaicg-template-prompts').append('<div id="wpaicg-template-form-prompt-' + idx + '">' + wpaicgCreatePrompt.html() + '</div>');
                    var prompt_item = $('#wpaicg-template-form-prompt-' + idx);
                    var prompt_value = '';
                    if (isUnicode(prompt)) {
                        prompt_value = decodeUnicode(prompt).replace(/\\u00a/g, '\n');
                    } else {
                        prompt_value = prompt.replaceAll('[QUOTE]', '"');
                    }
                    prompt_item.find('.wpaicg-create-template-prompt-content').val(prompt_value);
                    wpaicgSortPrompt();
                })
            }

            var wpaicg_form_fields = item.attr('data-fields');
            if (wpaicg_form_fields !== '') {
                wpaicg_form_fields = wpaicg_form_fields.replace(/\\/g, '');
                wpaicg_form_fields = JSON.parse(wpaicg_form_fields);
                $.each(wpaicg_form_fields, function(idx, field) {
                    $('.wpaicg-create-template-form .wpaicg-template-fields').append('<div id="wpaicg-template-form-field-' + idx + '">' + wpaicgCreateField.html() + '</div>');
                    var field_item = $('#wpaicg-template-form-field-' + idx);
                    $.each(wpaicgFieldInputs, function(idxy, field_key) {
                        if (field[field_key] !== undefined) {
                            var field_value = field[field_key];
                            field_value = field_value.replaceAll('[QUOTE]', '"');
                            if (field_key === 'description') {
                                field_value = field_value.replaceAll('shinydev19680903', '\n');

                            }
                            field_item.find('.wpaicg-create-template-field-' + field_key).val(field_value);
                            if (field_key === 'type' && (field_value === 'select' || field_value === 'checkbox' || field_value === 'radio')) {
                                field_item.find('.wpaicg-create-template-field-options-main').show();
                                field_item.find('.wpaicg-create-template-field-min-main').hide();
                                field_item.find('.wpaicg-create-template-field-max-main').hide();
                            } else if (field_key === 'type' && field_value === 'pdf') {
                                field_item.find('.wpaicg-create-template-field-min-main').hide();
                                field_item.find('.wpaicg-create-template-field-max-main').hide();
                            } else if (field_key === 'type' && field_value === 'textarea') {
                                field_item.find('.wpaicg-create-template-field-rows-main').show();
                                field_item.find('.wpaicg-create-template-field-cols-main').show();
                            }
                        }
                    });
                    wpaicgSortField();
                })
            }
            form.find('.wpaicg-create-template-id').val(id);
            $('.wpaicg-create-template-form .wpaicg-create-template-color').wpColorPicker();
            $('.wpaicg-create-template-form .wpaicg-create-template-bgcolor').wpColorPicker();
            //$('.wpaicg_modal').css('height','60%');
            $('.wpaicg-overlay').show();
            $('.wpaicg_modal').show();
        });

        $(document).on('click', '.wpaicg-template-action-customize', function(e) {
            var id = $(e.currentTarget).attr('data-id');
            var item = $('.wpaicg-template-item-json-' + id);
            $('.wpaicg_modal_content').empty();
            $('.wpaicg_modal_title').html('<?php echo esc_html__('Customize your Template', 'gpt3-ai-content-generator') ?>');
            $('.wpaicg_modal_content').html('<form action="" method="post" class="wpaicg-create-template-form">' + wpaicgTemplateContent.html() + '</form>');
            var form = $('.wpaicg-create-template-form');
            var wpaicg_template_keys = ['engine', 'editor', 'title', 'description', 'max_tokens', 'temperature', 'top_p', 'best_of', 'frequency_penalty', 'presence_penalty', 'stop', 'prompts', 'response', 'category', 'icon', 'color', 'bgcolor', 'header', 'dans', 'ddraft', 'dclear', 'dnotice', 'generate_text', 'noanswer_text', 'draft_text', 'clear_text', 'stop_text', 'cnotice_text', 'download_text', 'ddownload'];
            for (var i = 0; i < wpaicg_template_keys.length; i++) {
                var wpaicg_template_key = wpaicg_template_keys[i];
                var wpaicg_template_key_value = item.attr('data-' + wpaicg_template_key);
                if (wpaicg_template_key === 'category' && wpaicg_template_key_value !== '') {
                    if (wpaicg_template_key_value.indexOf(',') > -1) {
                        wpaicg_template_key_value = wpaicg_template_key_value.split(',')[0];
                    }
                }
                form.find('.wpaicg-create-template-' + wpaicg_template_key).val(wpaicg_template_key_value);
                if (wpaicg_template_key === 'icon') {
                    if (wpaicg_template_key_value === '') {
                        wpaicg_template_key_value = 'robot';
                    }
                    $('.wpaicg-create-template-form .wpaicg-template-icons span[data-key=' + wpaicg_template_key_value + ']').addClass('icon_selected');
                }
            }
            var wpaicg_form_fields = item.attr('data-fields');
            if (wpaicg_form_fields !== '') {
                wpaicg_form_fields = wpaicg_form_fields.replace(/\\/g, '');
                wpaicg_form_fields = JSON.parse(wpaicg_form_fields);
                $.each(wpaicg_form_fields, function(idx, field) {
                    $('.wpaicg-create-template-form .wpaicg-template-fields').append('<div id="wpaicg-template-form-field-' + idx + '">' + wpaicgCreateField.html() + '</div>');
                    var field_item = $('#wpaicg-template-form-field-' + idx);
                    $.each(wpaicgFieldInputs, function(idxy, field_key) {
                        if (field[field_key] !== undefined) {
                            var field_value = field[field_key];

                            if (field_key === 'options') {
                                if (typeof field_value === 'object') {
                                    field_value = field_value.join('|');
                                }
                                field_item.find('.wpaicg-create-template-field-' + field_key).val(field_value);
                            } else {
                                field_item.find('.wpaicg-create-template-field-' + field_key).val(field_value);
                            }
                            if (field_key === 'type' && (field_value === 'select' || field_value === 'checkbox' || field_value === 'radio')) {
                                field_item.find('.wpaicg-create-template-field-options-main').show();
                                field_item.find('.wpaicg-create-template-field-min-main').hide();
                                field_item.find('.wpaicg-create-template-field-max-main').hide();
                            } else if (field_key === 'type' && field_value === 'textarea') {
                                field_item.find('.wpaicg-create-template-field-rows-main').show();
                                field_item.find('.wpaicg-create-template-field-cols-main').show();
                            }
                        }
                    });
                    wpaicgSortField();
                })
            }
            $('.wpaicg-create-template-form .wpaicg-create-template-color').wpColorPicker();
            $('.wpaicg-create-template-form .wpaicg-create-template-bgcolor').wpColorPicker();
            //$('.wpaicg_modal').css('height','60%');
            $('.wpaicg-overlay').show();
            $('.wpaicg_modal').show();
        });

        function updateFormData(data, key, value) {
            let params = new URLSearchParams(data);
            params.set(key, value);
            return params.toString();
        }

        // submit edit panel
        $(document).on('submit', '.wpaicg-create-template-form', function(e) {
            var form = $(e.currentTarget);
            var btn = form.find('.wpaicg-create-template-save');
            var max_tokens = form.find('.wpaicg-create-template-make-tokens').val();
            var temperature = form.find('.wpaicg-create-template-temperature').val();
            var top_p = form.find('.wpaicg-create-template-top_p').val();
            var best_of = form.find('.wpaicg-create-template-best_of').val();
            var frequency_penalty = form.find('.wpaicg-create-template-frequency_penalty').val();
            var presence_penalty = form.find('.wpaicg-create-template-presence_penalty').val();
            var error_message = false;
            var data = form.serialize();


            if (max_tokens !== '' && (parseFloat(max_tokens) < 1 || parseFloat(max_tokens) > 16000)) {
                error_message = '<?php echo sprintf(esc_html__('Please enter a valid max token value between %d and %d.', 'gpt3-ai-content-generator'), 0, 16000) ?>';
            } else if (temperature !== '' && (parseFloat(temperature) < 0 || parseFloat(temperature) > 1)) {
                error_message = '<?php echo sprintf(esc_html__('Please enter a valid temperature value between %d and %d.', 'gpt3-ai-content-generator'), 0, 1) ?>';
            } else if (top_p !== '' && (parseFloat(top_p) < 0 || parseFloat(top_p) > 1)) {
                error_message = '<?php echo sprintf(esc_html__('Please enter a valid top p value between %d and %d.', 'gpt3-ai-content-generator'), 0, 1) ?>';
            } else if (best_of !== '' && (parseFloat(best_of) < 1 || parseFloat(best_of) > 20)) {
                error_message = '<?php echo sprintf(esc_html__('Please enter a valid best of value between %d and %d.', 'gpt3-ai-content-generator'), 1, 20) ?>';
            } else if (frequency_penalty !== '' && (parseFloat(frequency_penalty) < 0 || parseFloat(frequency_penalty) > 2)) {
                error_message = '<?php echo sprintf(esc_html__('Please enter a valid frequency penalty value between %d and %d.', 'gpt3-ai-content-generator'), 0, 2) ?>';
            } else if (presence_penalty !== '' && (parseFloat(presence_penalty) < 0 || parseFloat(presence_penalty) > 2)) {
                error_message = '<?php echo sprintf(esc_html__('Please enter a valid presence penalty value between %d and %d.', 'gpt3-ai-content-generator'), 0, 2) ?>';
            }
            if (error_message) {
                alert(error_message);
            } else {
                if (form.find('.wpaicg-template-form-field').length) {
                    var wpaicgFieldID = [];
                    form.find('.wpaicg-template-form-field').each(function(idx, item) {
                        var field_id = $(item).find('.wpaicg-create-template-field-id').val();

                        $(item).find('.wpaicg-create-template-field-description').each(function(idy, descrition_form) {

                            let form_name = $(descrition_form).attr('name');

                            let description = $(descrition_form).val();
                            description = description.replaceAll('\n', 'shinydev19680903');
                            data = updateFormData(data, form_name, description);
                        })

                        if (field_id !== '') {
                            if ($.inArray(field_id, wpaicgFieldID) > -1) {
                                error_message = '<?php echo esc_html__('Please insert unique ID', 'gpt3-ai-content-generator') ?>';
                            } else {
                                wpaicgFieldID.push(field_id)
                            }
                        }
                    })
                }
                if (form.find('.wpaicg-create-template-prompt-content').length) {
                    form.find('.wpaicg-create-template-prompt-content').each(function(idx, item) {
                        let form_name = $(item).attr('name');

                        let prompt = $(item).val();
                        prompt = encodeUnicode(prompt);
                        data = updateFormData(data, form_name, prompt);
                    })
                }
                if (form.find('.wpaicg-create-template-savememorykey').val() === 'True') {
                    data = updateFormData(data, 'savememorykey', 'True');
                } else {
                    data = updateFormData(data, 'savememorykey', 'False');
                }

                if (error_message) {
                    alert(error_message)
                } else {
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php') ?>',
                        data: data,
                        dataType: 'JSON',
                        type: 'POST',
                        beforeSend: function() {
                            wpaicgLoading(btn)
                        },
                        success: function(res) {
                            wpaicgRmLoading(btn);
                            if (res.status === 'success') {
                                window.location.href = '<?php echo admin_url('admin.php?page=wpaicg_forms&update_template=') ?>' + res.id;
                            } else {
                                alert(res.msg)
                            }
                        },
                        error: function(xhr, status, error) {
                            wpaicgRmLoading(btn);
                            alert('<?php echo esc_html__('Something went wrong', 'gpt3-ai-content-generator') ?>');
                        }
                    })
                }
            }
            return false;
        })
        /*End create*/
        var wpaicgNumberParse = 3;
        if ($(window).width() < 900) {
            wpaicgNumberParse = 2;
        }
        if ($(window).width() < 480) {
            wpaicgNumberParse = 1;
        }
        var wpaicg_per_page = <?php echo esc_html($wpaicg_per_page); ?>;
        var wpaicg_count_templates = <?php echo esc_html(count($wpaicg_items)) ?>;
        $('.wpaicg-list input').on('change', function() {
            wpaicgTemplatesFilter();
        });
        var wpaicgTemplateItem = $('.wpaicg-template-item');
        var wpaicgTemplateSearch = $('.wpaicg-template-search');
        var wpaicgTemplateItems = $('.wpaicg-template-items');
        var wpaicgTemplateSettings = ['engine', 'max_tokens', 'temperature', 'top_p', 'best_of', 'frequency_penalty', 'presence_penalty', 'stop', 'post_title', 'generate_text', 'noanswer_text', 'draft_text', 'clear_text', 'stop_text', 'cnotice_text', 'download_text'];
        var wpaicgTemplateDefaultContent = $('.wpaicg-template-modal-content');
        var wpaicgOpenaiSettingsContent = $('.wpaicg-openai-settings-modal-content');
        var wpaicgTemplateEditor = false;
        var eventReader = false;
        wpaicgTemplateSearch.on('input', function() {
            wpaicgTemplatesFilter();
        });

        function wpaicgTemplatesFilter() {
            var categories = [];
            var users = [];
            var filterClasses = [];
            $('.wpaicg-categories input').each(function(idx, item) {
                if ($(item).prop('checked')) {
                    categories.push($(item).val());
                    filterClasses.push($(item).val());
                }
            });
            $('.wpaicg-authors input').each(function(idx, item) {
                if ($(item).prop('checked')) {
                    users.push('user-' + $(item).val());
                    filterClasses.push('user-' + $(item).val());
                }
            });
            var search = wpaicgTemplateSearch.val();
            wpaicgTemplateItem.each(function(idx, item) {
                var show = false;
                var item_title = $(item).attr('data-title');
                var item_desc = $(item).attr('data-desc');
                if (categories.length) {
                    for (var i = 0; i < categories.length; i++) {
                        if ($(item).hasClass(categories[i])) {
                            show = true;
                            break;
                        } else {
                            show = false;
                        }
                    }
                    if (show && users.length) {
                        for (var i = 0; i < users.length; i++) {
                            if ($(item).hasClass(users[i])) {
                                show = true;
                                break;
                            } else {
                                show = false;
                            }
                        }
                    }
                }
                if (users.length) {
                    for (var i = 0; i < users.length; i++) {
                        if ($(item).hasClass(users[i])) {
                            show = true;
                            break;
                        } else {
                            show = false;
                        }
                    }
                    if (show && categories.length) {
                        for (var i = 0; i < categories.length; i++) {
                            if ($(item).hasClass(categories[i])) {
                                show = true;
                                break;
                            } else {
                                show = false;
                            }
                        }
                    }
                }
                if (!users.length && !categories.length) {
                    show = true;
                }
                if (search !== '' && show) {
                    search = search.toLowerCase();
                    item_title = item_title.toLowerCase();
                    item_desc = item_desc.toLowerCase();
                    if (item_title.indexOf(search) === -1 && item_desc.indexOf(search) === -1) {
                        show = false;
                    }
                }
                if (show) {
                    $(item).show();
                } else {
                    $(item).hide();
                }
            });
            wpaicgTemplatePagination();
        }
        wpaicgTemplatePagination();

        function wpaicgTemplatePagination() {
            wpaicgTemplateItem.removeClass('disappear-item');
            var number_rows = 0;
            wpaicgTemplateItem.each(function(idx, item) {
                if ($(item).is(':visible')) {
                    number_rows++;
                }
            });
            $('.wpaicg-paginate').empty();
            if (number_rows > wpaicg_per_page) {
                var totalPage = Math.ceil(number_rows / wpaicg_per_page);
                for (var i = 1; i <= totalPage; i++) {
                    var classSpan = 'page-numbers';
                    if (i === 1) {
                        classSpan = 'page-numbers current';
                    }
                    $('.wpaicg-paginate').append('<span class="' + classSpan + '" data-page="' + i + '">' + i + '</span>');
                }
            }
            var rowDisplay = 0;
            wpaicgTemplateItem.each(function(idx, item) {
                if ($(item).is(':visible')) {
                    rowDisplay += 1;
                }
            });
            if (rowDisplay > wpaicg_per_page) {
                wpaicgTemplateItems.css('height', ((Math.ceil(wpaicg_per_page / wpaicgNumberParse) * 120) - 20) + 'px');
            } else {
                wpaicgTemplateItems.css('height', ((Math.ceil(rowDisplay / wpaicgNumberParse) * 120) - 20) + 'px');
            }
        }

        $(document).on('click', '.wpaicg-paginate span:not(.current)', function(e) {
            var btn = $(e.currentTarget);
            var page = parseInt(btn.attr('data-page'));
            $('.wpaicg-paginate span').removeClass('current');
            btn.addClass('current');
            var prevpage = page - 1;
            var startRow = prevpage * wpaicg_per_page;
            var endRow = startRow + wpaicg_per_page;
            var keyRow = 0;
            var rowDisplay = 0;
            wpaicgTemplateItem.each(function(idx, item) {
                if ($(item).is(':visible')) {
                    keyRow += 1;
                    if (keyRow > startRow && keyRow <= endRow) {
                        rowDisplay += 1;
                        $(item).removeClass('disappear-item');
                    } else {
                        $(item).addClass('disappear-item');
                    }
                }
            });
            wpaicgTemplateItems.css('height', ((Math.ceil(rowDisplay / wpaicgNumberParse) * 120) - 20) + 'px');
        });
        $('.wpaicg_modal_close').click(function() {
            $('.wpaicg_modal_close').closest('.wpaicg_modal').hide();
            $('.wpaicg_modal_close').closest('.wpaicg_modal').removeClass('wpaicg-small-modal');
            $('.wpaicg-overlay').hide();
            if (eventReader) {
                eventReader.cancel();
                // eventReader.close();
            }
        });
        var wpaicgEditorNumber;
        $(document).on('click', '.wpaicg-template-form .wpaicg-template-save-draft', function(e) {
            var response_type = $('.wpaicg-template-form .wpaicg-template-response-type').val();
            var post_content = '';
            if (response_type === 'textarea') {
                var basicEditor = true;
                var btn = $(e.currentTarget);
                var editor = tinyMCE.get('editor-' + wpaicgEditorNumber);
                if ($('#wp-editor-' + wpaicgEditorNumber + '-wrap').hasClass('tmce-active') && editor) {
                    basicEditor = false;
                }
                if (basicEditor) {
                    post_content = $('#editor-' + wpaicgEditorNumber).val();
                } else {
                    post_content = editor.getContent();
                }
            } else {
                post_content = $('.wpaicg-template-response-element').html();
            }
            var post_title = $('.wpaicg-template-form .wpaicg-template-post_title input').val();
            if (post_content !== '') {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php') ?>',
                    data: {
                        title: post_title,
                        content: post_content,
                        action: 'wpaicg_save_draft_post_extra',
                        save_source: 'promptbase',
                        'nonce': '<?php echo wp_create_nonce('wpaicg-ajax-nonce') ?>'
                    },
                    dataType: 'json',
                    type: 'POST',
                    beforeSend: function() {
                        wpaicgLoading(btn);
                    },
                    success: function(res) {
                        wpaicgRmLoading(btn);
                        if (res.status === 'success') {
                            window.location.href = '<?php echo admin_url('post.php') ?>?post=' + res.id + '&action=edit';
                        } else {
                            alert(res.msg);
                        }
                    },
                    error: function() {
                        wpaicgRmLoading(btn);
                        alert('<?php echo esc_html__('Something went wrong', 'gpt3-ai-content-generator') ?>');
                    }
                });
            } else {
                alert('<?php echo esc_html__('Please wait content generated', 'gpt3-ai-content-generator') ?>');
            }

        });
        $(document).on('click', '.wpaicg-template-item .wpaicg-template-action-duplicate', function(e) {
            var id = $(e.currentTarget).attr('data-id');
            var btn = $(e.currentTarget);
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php') ?>',
                data: {
                    action: 'wpaicg_form_duplicate',
                    id: id,
                    nonce: '<?php echo wp_create_nonce('wpaicg-ajax-nonce') ?>'
                },
                dataType: 'JSON',
                type: 'POST',
                beforeSend: function() {
                    wpaicgLoading(btn);
                },
                success: function(res) {
                    window.location.reload();
                }
            });
        });
        $(document).on('click', '.wpaicg-template-form .wpaicg-template-clear', function(e) {
            var response_type = $('.wpaicg-template-form .wpaicg-template-response-type').val();
            if (response_type === 'textarea') {
                var basicEditor = true;
                var editor = tinyMCE.get('editor-' + wpaicgEditorNumber);
                if ($('#wp-editor-' + wpaicgEditorNumber + '-wrap').hasClass('tmce-active') && editor) {
                    basicEditor = false;
                }
                if (basicEditor) {
                    $('#editor-' + wpaicgEditorNumber).val('');
                } else {
                    editor.setContent('');
                }
            } else {
                $('.wpaicg-template-response-element').empty();
            }
            $('.wpaicg-template-form .wpaicg-template-save-result').hide();
        });
        $(document).on('click', '.wpaicg-template-download', function(e) {
            var currentContent = '';
            var response_type = $('.wpaicg-template-form .wpaicg-template-response-type').val();
            if (response_type === 'textarea') {
                var basicEditor = true;
                var editor = tinyMCE.get('editor-' + wpaicgEditorNumber);
                if ($('#wp-editor-' + wpaicgEditorNumber + '-wrap').hasClass('tmce-active') && editor) {
                    basicEditor = false;
                }
                if (basicEditor) {
                    currentContent = $('#editor-' + wpaicgEditorNumber).val();
                } else {
                    currentContent = editor.getContent();
                    currentContent = currentContent.replace(/<\/?p(>|$)/g, "");
                }
            } else {
                currentContent = $('.wpaicg-template-response-element').html();
            }
            var element = document.createElement('a');
            currentContent = currentContent.replace(/<br>/g, "\n");
            currentContent = currentContent.replace(/<br \/>/g, "\n");
            element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(currentContent));
            element.setAttribute('download', 'ai-response.txt');

            element.style.display = 'none';
            document.body.appendChild(element);

            element.click();

            document.body.removeChild(element);
        });
        $(document).on('input', '.wpaicg-template-form .wpaicg-template-max_tokens input', function(e) {
            var maxtokens = $(e.currentTarget).val();
            var wpaicg_estimated_cost = maxtokens !== '' ? parseFloat(maxtokens) * 0.002 / 1000 : 0;
            wpaicg_estimated_cost = '$' + parseFloat(wpaicg_estimated_cost.toFixed(5));
            $('.wpaicg-template-form .wpaicg-template-estimated span').html(wpaicg_estimated_cost);
        });
        $(document).on('click', '#wpaicg_ai_model', function(e) {
            $wpaicg_ai_model = e.target.value
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
                    alert(error);
                    console.error(error);
                }
            });
        });

        // click playground
        $(document).on('click', '.wpaicg-template-item .wpaicg-template-content,.wpaicg-template-item .wpaicg-template-icon', function(e) {
            // Click event of item in AI form
            var item = $(e.currentTarget).parent();
            var title = item.attr('data-title');
            var id = item.attr('data-id');
            var type = item.attr('data-type');
            var categories = item.attr('data-categories');
            var savememorykey = item.attr('data-savememorykey');

            if (id !== "118") {
                prompt_name = title;
                prompt_id = id;
                $('.wpaicg_modal_content').empty();
                if (type === 'json') {
                    $('.wpaicg-template-action-customize').attr('data-id', id);
                } else {
                    $('.wpaicg-template-action-customize').hide();
                }
                var modal_head = '<div class="wpaicg-d-flex wpaicg-align-items-center wpaicg-modal-template-head"><div style="margin-left: 10px;">';
                modal_head += '<strong>' + title + '</strong>';
                // save memory key flag
                modal_head += '<strong class="wpaicg-template-savememorykey" style="display: none;">' + savememorykey + '</strong>';

                if (categories !== '') {
                    modal_head += '<div><small>' + categories + '</small></div>';
                }
                modal_head += '</div></div>';
                $('.wpaicg_modal_title').html(modal_head);
                $('.wpaicg-modal-template-head').prepend(item.find('.wpaicg-template-icon').clone());
                var prompts = item.attr('data-prompts');
                var response = item.attr('data-response');
                var response_type = item.attr('data-editor');
                $('.wpaicg-template-response-type').val(response_type);
                wpaicgEditorNumber = Math.ceil(Math.random() * 1000000);
                $('.wpaicg_modal_content').html('<div class="wpaicg-template-form">' + wpaicgTemplateDefaultContent.html() + '</div>');
                $('.wpaicg-template-form').find('.wpaicg-template-title').val(prompts);
                var wpaicgFieldsForm = item.attr('data-fields');
                if (wpaicgFieldsForm !== '') {
                    var wpaicgFormFieldsElement = $('.wpaicg-template-form .wpaicg-form-fields');
                    wpaicgFieldsForm = wpaicgFieldsForm.replace(/\\/g, '');
                    wpaicgFieldsForm = JSON.parse(wpaicgFieldsForm);

                    $.each(wpaicgFieldsForm, function(idx, form_field) {
                        let field_description = ''
                        var form_field_html = ''
                        if (form_field['description']) {
                            let field_description_array = form_field['description'].split('shinydev19680903');

                            for (let i = 0; i < field_description_array.length; i++) {
                                if (i !== field_description_array.length - 1) {
                                    field_description += field_description_array[i] + '<br>';
                                } else {
                                    field_description += field_description_array[i];
                                }
                            }
                            if (field_description !== '') {
                                form_field_html = '<div class="mb-5"><lable><strong>' + form_field['label'] + '</strong></label><br><lable>' + field_description + '</label><br>';
                            } else {
                                form_field_html = '<div class="mb-5"><lable><strong>' + form_field['label'] + '</strong></label><br>';
                            }
                        } else {
                            form_field_html = '<div class="mb-5"><lable><strong>' + form_field['label'] + '</strong></label><br>';
                        }
                        var form_field_type = 'text';
                        if (form_field['type'] !== undefined) {
                            form_field_type = form_field['type'];
                        }
                        if (form_field_type === 'select') {
                            form_field_html += '<select name="' + form_field['id'] + '" data-min="' + form_field['min'] + '" data-max="' + form_field['max'] + '" data-type="' + form_field_type + '" data-label="' + form_field['label'] + '" class="wpaicg-form-field-template">';
                            if (form_field['options'] !== undefined && form_field['options'].length) {
                                var form_field_options = form_field['options'];
                                if (typeof form_field_options === 'string') {
                                    form_field_options = form_field_options.split("|");
                                }
                                $.each(form_field_options, function(idy, form_field_option) {
                                    form_field_html += '<option value="' + form_field_option + '">' + form_field_option + '</option>';
                                })
                            }
                            form_field_html += '</select>';
                        } else if (form_field_type === 'checkbox' || form_field_type === 'radio') {
                            form_field_html += '<div>';
                            if (form_field['options'] !== undefined && form_field['options'].length) {
                                var form_field_options = form_field['options'];
                                if (typeof form_field_options === 'string') {
                                    form_field_options = form_field_options.split("|");
                                }
                                $.each(form_field_options, function(idy, form_field_option) {
                                    form_field_html += '<input name="' + form_field['id'] + (form_field_type === 'checkbox' ? '[]' : '') + '" type="' + form_field_type + '" value="' + form_field_option + '">&nbsp;' + form_field_option + '&nbsp;&nbsp;&nbsp;';
                                })
                            }
                            form_field_html += '</div>';
                        } else if (form_field_type === 'textarea') {
                            var textarea_rows = form_field['rows'] !== undefined ? ' rows="' + form_field['rows'] + '"' : '';
                            var textarea_cols = form_field['cols'] !== undefined ? ' cols="' + form_field['cols'] + '"' : '';
                            form_field_html += '<textarea' + textarea_rows + textarea_cols + ' name="' + form_field['id'] + '" data-type="' + form_field_type + '" data-label="' + form_field['label'] + '" type="' + form_field_type + '" required class="wpaicg-form-field-template" data-min="' + form_field['min'] + '" data-max="' + form_field['max'] + '"></textarea>'
                        } else if (form_field_type === 'pdf') {
                            form_field_html += '<input type="file" accept="application/pdf" class="wpaicg-pdf-file" name="' + form_field['id'] + '" data-type="' + form_field_type + '" data-label="' + form_field['label'] + '" type="' + form_field_type + '" required class="wpaicg-form-field-template">'
                        } else {
                            form_field_html += '<input name="' + form_field['id'] + '" data-type="' + form_field_type + '" data-label="' + form_field['label'] + '" type="' + form_field_type + '" required class="wpaicg-form-field-template" data-min="' + form_field['min'] + '" data-max="' + form_field['max'] + '">'
                        }
                        form_field_html += '</div>';
                        wpaicgFormFieldsElement.append(form_field_html);
                    })
                }
                wpaicgTemplateEditor = $('.wpaicg-template-form').find('.wpaicg-template-result');
                if (id !== undefined) {
                    var embed_message = '<?php echo esc_html__('Embed this form to your website', 'gpt3-ai-content-generator') ?>: [wpaicg_form id=' + id + ' settings=no';
                    if (type === 'custom') {
                        embed_message += ' custom=yes';
                    }
                    embed_message += ']';
                    $('.wpaicg-template-form .wpaicg-template-shortcode').html(embed_message);
                }
                for (var i = 0; i < wpaicgTemplateSettings.length; i++) {
                    var item_name = wpaicgTemplateSettings[i];
                    var item_value = item.attr('data-' + item_name);
                    if (item_name === 'max_tokens') {
                        var wpaicg_estimated_cost = item_value !== undefined ? parseFloat(item_value) * 0.002 / 1000 : 0;
                        wpaicg_estimated_cost = '$' + parseFloat(wpaicg_estimated_cost.toFixed(5));
                        $('.wpaicg-template-form .wpaicg-template-estimated span').html(wpaicg_estimated_cost);
                    }
                    if (item_value !== undefined) {
                        if (
                            item_name === 'generate_text' ||
                            item_name === 'draft_text' ||
                            item_name === 'noanswer_text' ||
                            item_name === 'clear_text' ||
                            item_name === 'stop_text'
                        ) {
                            $('.wpaicg-template-text-' + item_name).html(item_value);
                        } else {
                            if (item_name !== 'engine' && item_name !== 'stop' && item_name !== 'post_title') {
                                item_value = parseFloat(item_value);
                                item_value = item_value.toString().replace(/,/g, '.');
                            } else if (item_name == 'engine') {
                                item_value = <?php echo json_encode($user_model); ?>;
                            }

                            $('.wpaicg-template-form .wpaicg-template-' + item_name).find('[name=' + item_name + ']').val(item_value);
                            $('.wpaicg-template-form .wpaicg-template-' + item_name).show();
                        }
                    } else {
                        $('.wpaicg-template-form .wpaicg-template-' + item_name).hide();
                    }
                }
                $('.wpaicg-template-form .wpaicg-template-response').html(response);
                wpaicgTemplateEditor.attr('id', 'editor-' + wpaicgEditorNumber);
                if (response_type === 'textarea') {
                    wp.editor.initialize('editor-' + wpaicgEditorNumber, {
                        tinymce: {
                            wpautop: true,
                            plugins: 'charmap colorpicker hr lists paste tabfocus textcolor fullscreen wordpress wpautoresize wpeditimage wpemoji wpgallery wplink wptextpattern',
                            toolbar1: 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,wp_more,spellchecker,fullscreen,wp_adv,listbuttons',
                            toolbar2: 'styleselect,strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
                            height: 300
                        },
                        quicktags: {
                            buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,more,close'
                        },
                        mediaButtons: true
                    });
                } else {
                    $('.wpaicg-template-form .wpaicg-template-response-editor').hide();
                }
                $('.wpaicg_modal').css('top', '');
                $('.wpaicg_modal').css('height', '');
                $('.wpaicg-overlay').show();
                $('.wpaicg_modal').show();
            } else {
                prompt_name = title;
                prompt_id = id;
                $('.wpaicg_modal_content').empty();
                if (type === 'json') {
                    $('.wpaicg-template-action-customize').attr('data-id', id);
                } else {
                    $('.wpaicg-template-action-customize').hide();
                }
                var modal_head = '<div class="wpaicg-d-flex wpaicg-align-items-center wpaicg-modal-template-head"><div style="margin-left: 10px;">';
                modal_head += '<strong>' + title + '</strong>';
                if (categories !== '') {
                    modal_head += '<div><small>' + categories + '</small></div>';
                }
                modal_head += '</div></div>';
                $('.wpaicg_modal_title').html(modal_head);
                $('.wpaicg-modal-template-head').prepend(item.find('.wpaicg-template-icon').clone());

                $('.wpaicg_modal_content').html('<div class="wpaicg-template-form">' + wpaicgOpenaiSettingsContent.html() + '</div>');

                if (id !== undefined) {
                    var embed_message = '<?php echo esc_html__('Embed this form to your website', 'gpt3-ai-content-generator') ?>: [wpaicg_form id=' + id + ' settings=no';
                    if (type === 'custom') {
                        embed_message += ' custom=yes';
                    }
                    embed_message += ']';
                    $('.wpaicg-template-form .wpaicg-template-shortcode').html(embed_message);
                }

                $('.wpaicg_modal').css('top', '');
                $('.wpaicg_modal').css('height', '');
                $('.wpaicg-overlay').show();
                $('.wpaicg_modal').show();
            }

        });

        function wpaicgLoading(btn) {
            btn.attr('disabled', 'disabled');
            if (!btn.find('spinner').length) {
                btn.append('<span class="spinner"></span>');
            }
            btn.find('.spinner').css('visibility', 'unset');
        }

        function wpaicgRmLoading(btn) {
            btn.removeAttr('disabled');
            btn.find('.spinner').remove();
        }

        function stopOpenAIGenerator() {
            $('.wpaicg-template-form .wpaicg-template-stop-generate').hide();
            if (!wpaicg_limited_token) {
                $('.wpaicg-template-form .wpaicg-template-save-result').show();
            }
            wpaicgRmLoading($('.wpaicg-template-form .wpaicg-generate-button'));
            eventReader.cancel();
            // eventReader.close();
        }
        $(document).on('click', '.wpaicg-template-form .wpaicg-template-stop-generate', function(e) {
            stopOpenAIGenerator();
        });
        $(document).on('click', '.wpaicg-template-form .wpaicg-api-key-button', function(e) {
            $('.wpaicg-setting-api-key-container').toggle();
        });

        function wpaicgValidEmail(email) {
            return String(email)
                .toLowerCase()
                .match(
                    /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
                );
        }

        function wpaicgValidUrl(string) {
            try {
                new URL(string);
                return true;
            } catch (err) {
                return false;
            }
        }

        function process_message(string) {
            
        }

        function ai_generator(prompts, form, btn, prompt_response, response_type, counter, memorykeyflag) {
            console.log("start AI generator for ", counter);
            let startTime = new Date();

            $('.wpaicg-template-title-filled').val(prompts[counter] + ".\n\n");
            var query = form.serialize();
            var data = new FormData(form[0]); // Use FormData to handle form serialization

            var basicEditor = true;

            if (response_type === 'textarea') {
                var editor = tinyMCE.get('editor-' + wpaicgEditorNumber);
                if ($('#wp-editor-' + wpaicgEditorNumber + '-wrap').hasClass('tmce-active') && editor) {
                    basicEditor = false;
                }
                if (basicEditor) {
                    if (counter === 0) {
                        $('#editor-' + wpaicgEditorNumber).val('');
                    }

                } else {
                    if (counter === 0) {
                        editor.setContent('');
                    }
                }
            }
            wpaicgLoading(btn);
            form.find('.wpaicg-template-stop-generate').show();
            form.find('.wpaicg-template-save-result').hide();
            var wpaicg_limitLines = parseInt(form.find('.wpaicg-template-max-lines').val());
            
            var count_line = 0;
            var currentContent = '';
            var current_prompt_response = '';
            query += '&source_stream=form&nonce=<?php echo wp_create_nonce('wpaicg-ajax-nonce') ?>';
            data.append('source_stream', 'form');
            data.append('nonce', '<?php echo wp_create_nonce('wpaicg-ajax-nonce'); ?>');


            var wpaicg_response_events = 0;
            var wpaicg_newline_before = false;
            wpaicg_limited_token = false;

            // console.log("query", query);
            // console.log("form", form[0]);
            // console.log('<?php echo esc_html(add_query_arg('wpaicg_stream', 'yes', site_url() . '/index.php')); ?>');
            
            fetch('<?php echo esc_html(add_query_arg('wpaicg_stream', 'yes', site_url() . '/index.php')); ?>', {
                method: 'POST',
                body: data
            }).then((response) => {
                // console.log("response", response);
                eventReader = response.body.getReader();
                const decoder = new TextDecoder();

                var buffer = "";

                function read() {
                    eventReader.read().then(({ done, value }) => {
                        try{
                            buffer += decoder.decode(value, { stream: true });
                            let boundary = buffer.indexOf('\n');
                            while (boundary !== -1) {
                                let chunk = buffer.substring(0, boundary).trim();
                                buffer = buffer.substring(boundary + 1);
                                boundary = buffer.indexOf('\n');

                                if (chunk.startsWith('data:')) {
                                    chunk = chunk.substring(5).trim();
                                    if (chunk) {
                                        try {
                                            const resultData = JSON.parse(chunk);
                                            
                                            // Handle the received message here
                                            if (response_type === 'textarea') {
                                                if (basicEditor) {
                                                    currentContent = $('#editor-' + wpaicgEditorNumber).val();
                                                } else {
                                                    currentContent = editor.getContent();
                                                    currentContent = currentContent.replace(/<\/?p(>|$)/g, "");
                                                }
                                            } else {
                                                currentContent = $('.wpaicg-template-response-element').html();
                                            }

                                            // Check if the response contains the finish_reason property and if it's set to "stop"
                                            var hasFinishReason = resultData.choices &&
                                                resultData.choices[0] &&
                                                (resultData.choices[0].finish_reason === "stop" ||
                                                    resultData.choices[0].finish_reason === "length");

                                            if (hasFinishReason) {
                                                // console.log("hasFinishReason", resultData)
                                                count_line += 1;
                                                if (response_type === 'textarea') {
                                                    if (basicEditor) {
                                                        $('#editor-' + wpaicgEditorNumber).val(currentContent + '<br /><br />');
                                                    } else {
                                                        editor.setContent(currentContent + '<br /><br />');
                                                    }
                                                } else {
                                                    $('.wpaicg-template-response-element').append('<br />');
                                                }
                                                wpaicg_response_events = 0;
                                            } else if (chunk === "[LIMITED]") {
                                                // console.log("LIMITED")
                                                wpaicg_limited_token = true;
                                                count_line += 1;
                                                if (response_type === 'textarea') {
                                                    if (basicEditor) {
                                                        $('#editor-' + wpaicgEditorNumber).val(currentContent + '<br /><br />');
                                                    } else {
                                                        editor.setContent(currentContent + '<br /><br />');
                                                    }
                                                } else {
                                                    $('.wpaicg-template-response-element').append('<br />');
                                                }
                                                wpaicg_response_events = 0;
                                            } else {
                                                var result = JSON.parse(chunk);
                                                if (result.error !== undefined) {
                                                    var content_generated = result.error.message;
                                                } else {
                                                    var content_generated = result.choices[0].delta !== undefined ? (result.choices[0].delta.content !== undefined ? result.choices[0].delta.content : '') : result.choices[0].text;
                                                }
                                                
                                                prompt_response += content_generated;
                                                current_prompt_response += content_generated;
                                                if ((content_generated === '\n' || content_generated === ' \n' || content_generated === '.\n' || content_generated === '\n\n' || content_generated === '"\n') && wpaicg_response_events > 0 && currentContent !== '') {
                                                    if (!wpaicg_newline_before) {
                                                        wpaicg_newline_before = true;
                                                        if (response_type === 'textarea') {
                                                            if (basicEditor) {
                                                                $('#editor-' + wpaicgEditorNumber).val(currentContent + '<br /><br />');
                                                            } else {
                                                                editor.setContent(currentContent + '<br /><br />');
                                                            }
                                                        } else {
                                                            $('.wpaicg-template-response-element').append('<br />');
                                                        }
                                                    }
                                                } else if (content_generated.indexOf("\n") > -1 && wpaicg_response_events > 0 && currentContent !== '') {
                                                    if (!wpaicg_newline_before) {
                                                        wpaicg_newline_before = true;
                                                        content_generated = content_generated.replace(/\n/g, '<br>');
                                                        if (response_type === 'textarea') {
                                                            if (basicEditor) {
                                                                $('#editor-' + wpaicgEditorNumber).val(currentContent + content_generated);
                                                            } else {
                                                                editor.setContent(currentContent + content_generated);
                                                            }
                                                        } else {
                                                            $('.wpaicg-template-response-element').append(content_generated);
                                                        }
                                                    }
                                                } else if (content_generated === '\n' && wpaicg_response_events === 0 && currentContent === '') {

                                                } else {
                                                    wpaicg_newline_before = false;
                                                    wpaicg_response_events += 1;
                                                    if (response_type === 'textarea') {
                                                        if (basicEditor) {
                                                            $('#editor-' + wpaicgEditorNumber).val(currentContent + content_generated);
                                                        } else {
                                                            editor.setContent(currentContent + content_generated);
                                                        }
                                                    } else {
                                                        $('.wpaicg-template-response-element').append(content_generated);
                                                    }
                                                }
                                            }
                                            if (count_line === wpaicg_limitLines) {
                                                var answer_id = "{answer" + (counter + 1) + "}";
                                                $.each(prompts, function(index, prompt) {
                                                    prompts[index] = prompt.replace(new RegExp(answer_id, 'g'), current_prompt_response);
                                                    if (memorykeyflag === 'True') {
                                                        if (index === counter && counter < prompts.length - 1) {
                                                            prompts[counter] = prompts[counter].replace(new RegExp("This is previous chat history.\n<<<\n", 'g'), "");
                                                            prompts[counter] = prompts[counter].replace(new RegExp("\n>>>\nThe following is what I want, please just refer above chat history and I don't need repeat answer again.\n", 'g'), "");
                                                            prompts[counter + 1] = "This is previous chat history.\n<<<\n" + prompts[counter] + "\n" + current_prompt_response + "\n>>>\nThe following is what I want, please just refer above chat history and I don't need repeat answer again.\n" + prompts[counter + 1];
                                                        }
                                                    }
                                                });
                                                if (!wpaicg_limited_token) {
                                                    let endTime = new Date();
                                                    let timeDiff = endTime - startTime;
                                                    timeDiff = timeDiff / 1000;
                                                    data += '&action=wpaicg_form_log&prompt_id=' + prompt_id + '&prompt_name=' + prompt_name + '&prompt_response=' + prompt_response + '&duration=' + timeDiff + '&_wpnonce=' + wp_nonce;
                                                    $.ajax({
                                                        url: '<?php echo admin_url('admin-ajax.php') ?>',
                                                        data: data,
                                                        dataType: 'JSON',
                                                        type: 'POST',
                                                        success: function(res) {

                                                        }
                                                    })
                                                }

                                                $('.wpaicg-template-form .wpaicg-template-stop-generate').hide();
                                                stopOpenAIGenerator();
                                                wpaicgRmLoading(btn);
                                                console.log(counter, prompts.length)
                                                if (counter < prompts.length - 1) {
                                                    counter++;
                                                    ai_generator(prompts, form, btn, prompt_response, response_type, counter, memorykeyflag)
                                                    return;
                                                } else {
                                                    return;
                                                }
                                            }
                                        } catch (e) {
                                            console.error('Failed to parse JSON:', e);
                                        }
                                    }
                                }
                            }
                            read(); // Continue reading
                        } catch (err) {
                            console.log(err);
                        }
                    });
                }

                read();
            }).catch(error => {
                console.error('Fetch error:', error);
            });
        }

        // ai generation part
        $(document).on('submit', '.wpaicg-template-form form', function(e) {
            var form = $(e.currentTarget);
            var btn = form.find('.wpaicg-generate-button');
            var template_title = form.find('.wpaicg-template-title').val();
            var response_type = form.find('.wpaicg-template-response-type').val();

            var savememorykey = form.parent().parent().parent().find('.wpaicg-template-savememorykey').text();

            if (template_title !== '') {
                var engine = form.find('.wpaicg-create-template-engine').val();
                var max_tokens = form.find('.wpaicg-template-max_tokens input').val();
                var temperature = form.find('.wpaicg-template-temperature input').val();
                var top_p = form.find('.wpaicg-template-top_p input').val();
                var best_of = form.find('.wpaicg-template-best_of input').val();
                var frequency_penalty = form.find('.wpaicg-template-frequency_penalty input').val();
                var presence_penalty = form.find('.wpaicg-template-presence_penalty input').val();
                var error_message = false;
                if (max_tokens === '') {
                    error_message = '<?php echo esc_html__('Please enter max tokens', 'gpt3-ai-content-generator') ?>';
                } else if (engine === 'chatgpt-4o-latest' && (parseFloat(max_tokens) < 1 || parseFloat(max_tokens) > 16000)) {
                    error_message = '<?php echo sprintf(esc_html__('Please enter a valid max token value between %d and %d.', 'gpt3-ai-content-generator'), 4000, 16000) ?>';
                } else if (engine !== 'chatgpt-4o-latest' && (parseFloat(max_tokens) < 1 || parseFloat(max_tokens) > 4000)) {
                    error_message = '<?php echo sprintf(esc_html__('Please enter a valid max token value between %d and %d.', 'gpt3-ai-content-generator'), 1, 4000) ?>';
                } else if (temperature === '') {
                    error_message = '<?php echo esc_html__('Please enter temperature', 'gpt3-ai-content-generator') ?>';
                } else if (parseFloat(temperature) < 0 || parseFloat(temperature) > 1) {
                    error_message = '<?php echo sprintf(esc_html__('Please enter a valid temperature value between %d and %d.', 'gpt3-ai-content-generator'), 0, 1) ?>';
                } else if (top_p === '') {
                    error_message = '<?php echo esc_html__('Please enter Top P', 'gpt3-ai-content-generator') ?>';
                } else if (parseFloat(top_p) < 0 || parseFloat(top_p) > 1) {
                    error_message = '<?php echo sprintf(esc_html__('Please enter a valid top p value between %d and %d.', 'gpt3-ai-content-generator'), 0, 1) ?>';
                } else if (best_of === '') {
                    error_message = '<?php echo esc_html__('Please enter best of', 'gpt3-ai-content-generator') ?>';
                } else if (parseFloat(best_of) < 1 || parseFloat(best_of) > 20) {
                    error_message = '<?php echo sprintf(esc_html__('Please enter a valid best of value between %d and %d.', 'gpt3-ai-content-generator'), 1, 20) ?>';
                } else if (frequency_penalty === '') {
                    error_message = '<?php echo esc_html__('Please enter frequency penalty', 'gpt3-ai-content-generator') ?>';
                } else if (parseFloat(frequency_penalty) < 0 || parseFloat(frequency_penalty) > 2) {
                    error_message = '<?php echo sprintf(esc_html__('Please enter a valid frequency penalty value between %d and %d.', 'gpt3-ai-content-generator'), 0, 2) ?>';
                } else if (presence_penalty === '') {
                    error_message = '<?php echo esc_html__('Please enter presence penalty', 'gpt3-ai-content-generator') ?>';
                } else if (parseFloat(presence_penalty) < 0 || parseFloat(presence_penalty) > 2) {
                    error_message = '<?php echo sprintf(esc_html__('Please enter a valid presence penalty value between %d and %d.', 'gpt3-ai-content-generator'), 0, 2) ?>';
                }
                if (error_message) {
                    alert(error_message);
                } else {
                    if ($('.wpaicg-template-form .wpaicg-form-field-template').length) {
                        $('.wpaicg-template-form .wpaicg-form-field-template').each(function(idf, item) {
                            var field_type = $(item).attr('data-type');
                            var field_name = $(item).attr('name');
                            var field_label = $(item).attr('data-label');
                            var field_value = $(item).val();
                            var field_min = $(item).attr('data-min');
                            var field_max = $(item).attr('data-max');
                            if (field_type === 'text' || field_type === 'textarea' || field_type === 'email' || field_type === 'url') {
                                if (field_min !== '' && field_value.length < parseInt(field_min)) {
                                    error_message = '<?php echo esc_html__('{label} minimum {min} characters', 'gpt3-ai-content-generator') ?>'.replace(/{label}/g, field_label).replace(/{min}/g, field_min);
                                } else if (field_max !== '' && field_value.length > parseInt(field_max)) {
                                    error_message = '<?php echo esc_html__('{label} maximum {max} characters', 'gpt3-ai-content-generator') ?>'.replace(/{label}/g, field_label).replace(/{max}/g, field_max);
                                } else if (field_type === 'email' && !wpaicgValidEmail(field_value)) {
                                    error_message = '<?php echo esc_html__('{label} must be email address', 'gpt3-ai-content-generator') ?>'.replace(/{label}/g, field_label);
                                } else if (field_type === 'url' && !wpaicgValidUrl(field_value)) {
                                    error_message = '<?php echo esc_html__('{label} must be url', 'gpt3-ai-content-generator') ?>'.replace(/{label}/g, field_label);
                                }
                            } else if (field_type === 'number') {
                                if (field_min !== '' && parseFloat(field_value) < parseInt(field_min)) {
                                    error_message = '<?php echo esc_html__('{label} minimum {min}', 'gpt3-ai-content-generator') ?>'.replace(/{label}/g, field_label).replace(/{min}/g, field_min);
                                } else if (field_max !== '' && parseFloat(field_value) > parseInt(field_max)) {
                                    error_message = '<?php echo esc_html__('{label} maximum {max}', 'gpt3-ai-content-generator') ?>'.replace(/{label}/g, field_label).replace(/{max}/g, field_max);
                                }
                            }
                        })
                    }
                    if (error_message) {
                        alert(error_message);
                    } else {
                        prompt_response = '';

                        if ($('.wpaicg-template-form .wpaicg-form-field-template').length) {
                            $('.wpaicg-template-form .wpaicg-form-field-template').each(function(idf, item) {
                                var field_name = $(item).attr('name');
                                var field_value = $(item).val();
                                var sRegExInput = new RegExp('{' + field_name + '}', 'g');
                                template_title = template_title.replace(sRegExInput, field_value);
                            })
                        }

                        let counter = 0;
                        let prompts = [];
                        if (template_title.startsWith('[')) {
                            prompts = JSON.parse(template_title);

                            for (let i = 0; i < prompts.length; i++) {
                                if (isUnicode(prompts[i])) {
                                    prompts[i] = decodeUnicode(prompts[i]).replace(/\\u00a/g, '\n');
                                    if ($('.wpaicg-template-form .wpaicg-form-field-template').length) {
                                        $('.wpaicg-template-form .wpaicg-form-field-template').each(function(idf, item) {
                                            var field_name = $(item).attr('name');
                                            var field_value = $(item).val();
                                            var sRegExInput = new RegExp('{' + field_name + '}', 'g');
                                            prompts[i] = prompts[i].replace(sRegExInput, field_value);
                                        })
                                    }

                                }
                            }
                        } else {
                            prompts.push(template_title);
                        }
                        ai_generator(prompts, form, btn, prompt_response, response_type, counter, savememorykey);
                    }
                }
            } else {
                alert('<?php echo esc_html__('Please enter prompt', 'gpt3-ai-content-generator') ?>');
            }
            return false;
        });

        // ========================= Embedding ============================

        $(document).on('change', '.wpaicg-pdf-file', function(e) {
            wpaicgPDFChange(e.currentTarget);
        });

        function wpaicgPDFEmbedding(
            start,
            namespace,
            filename,
            nonce,
            contents,
            type,
            bot_id,
            callback
        ) {
            var result = "";
            var next = start + 1;
            var pageContent = contents[start];
            var embeddingData = new FormData();
            var embeddingRequest = new XMLHttpRequest();
            embeddingData.append("nonce", nonce);
            embeddingData.append("action", "wpaicg_pdf_embedding");
            embeddingData.append("content", pageContent);
            embeddingData.append("page", next);
            embeddingData.append("namespace", namespace);
            embeddingData.append("type", type);
            embeddingData.append("bot_id", bot_id);
            embeddingData.append("filename", filename);
            embeddingRequest.open("POST", wpaicgParams.ajax_url, true);
            embeddingRequest.send(embeddingData);
            embeddingRequest.onreadystatechange = function(oEvent) {
                if (embeddingRequest.readyState === 4) {
                    if (embeddingRequest.status === 200) {
                        result = this.responseText;
                        result = JSON.parse(result);
                        if (result.status === "success") {
                            if (next === contents.length) {
                                callback(result);
                            } else {
                                wpaicgPDFEmbedding(
                                    next,
                                    namespace,
                                    filename,
                                    nonce,
                                    contents,
                                    type,
                                    bot_id,
                                    callback
                                );
                            }
                        } else {
                            alert(result.msg);
                        }
                    }
                }
            };
        }
        async function wpaicgPDFChange(input) {

            var type = input.getAttribute("data-type");
            // var chat, class_ai_item;
            // if (type === "widget") {
            //     chat = input.closest(".wpaicg-chatbox");
            //     class_ai_item = "wpaicg-chat-ai-message";
            // } else {
            //     chat = input.closest(".wpaicg-chat-shortcode");
            //     class_ai_item = "wpaicg-ai-message";
            // }
            // let wpaicg_ai_bg = chat.getAttribute("data-ai-bg-color");
            // let wpaicg_font_color = chat.getAttribute("data-color");
            // let wpaicg_font_size = chat.getAttribute("data-fontsize");
            // let wpaicg_nonce = chat.getAttribute("data-nonce");
            // let wpaicg_ai_name = chat.getAttribute("data-ai-name") + ":";
            // let wpaicg_use_avatar = parseInt(chat.getAttribute("data-use-avatar"));
            // let wpaicg_ai_avatar = chat.getAttribute("data-ai-avatar");
            // if (wpaicg_use_avatar) {
            //     wpaicg_ai_name =
            //         '<img src="' + wpaicg_ai_avatar + '" height="40" width="40">';
            // }
            // var pdfLoading = input.parentElement.querySelector(".wpaicg-pdf-loading");
            // var pdfIcon = input.parentElement.querySelector(".wpaicg-pdf-icon");
            // var pdfRemove = input.parentElement.querySelector(".wpaicg-pdf-remove");
            var limitPage = parseInt(input.getAttribute("data-limit"));

            if (input.files.length) {
                // pdfRemove.style.display = "none";
                // pdfLoading.style.display = "block";
                var _OBJECT_URL = URL.createObjectURL(input.files[0]);
                var loadingTask = pdfjsLib.getDocument({
                    url: _OBJECT_URL
                });
                var pageContents = [];
                var pdfTextContent = "";
                var pageNumbers = 0;

                var filename = input.files[0].name;
                await loadingTask.promise.then(async function(pdf) {
                    pageNumbers = pdf.numPages;
                    for (var i = 1; i <= pageNumbers; i++) {
                        var page = await pdf.getPage(i);
                        var textContent = await page.getTextContent();
                        pageContents.push(textContent.items.map((u) => u.str).join("\n"));
                        pdfTextContent += textContent.items.map((u) => u.str).join("\n");
                    }
                });
                if (pageContents.length) {
                    if (pageNumbers > limitPage) {
                        // pdfIcon.style.display = "block";
                        // pdfLoading.style.display = "none";
                        // pdfRemove.style.display = "none";
                        input.value = "";
                        alert(
                            "Your PDF exceeds the page limit of " +
                            limitPage +
                            ". Please upload a smaller one."
                        );
                    } else {
                        var namespace = "gptpdf_" + Math.ceil(Math.random() * 100000);
                        var type = chat.getAttribute("data-type");
                        var bot_id = parseInt(chat.getAttribute("data-bot-id"));
                        wpaicgPDFEmbedding(
                            0,
                            namespace,
                            filename,
                            wpaicg_nonce,
                            pageContents,
                            type,
                            bot_id,
                            // function(result) {
                            //     if (result.status === "success") {
                            //         var firstWords = wpaicggetWords(pdfTextContent, 1000);
                            //         var questionData = new FormData();
                            //         var questionRequest = new XMLHttpRequest();
                            //         questionData.append("type", type);
                            //         questionData.append("bot_id", bot_id);
                            //         questionData.append("nonce", wpaicg_nonce);
                            //         questionData.append("action", "wpaicg_example_questions");
                            //         questionData.append("content", firstWords);
                            //         questionRequest.open("POST", wpaicgParams.ajax_url);
                            //         questionRequest.send(questionData);
                            //         questionRequest.onload = function(oEvent) {
                            //             result = this.responseText;
                            //             if (result !== "") {
                            //                 result = result.replace(/\n/g, "<br>");
                            //                 result = JSON.parse(result);
                            //                 if (result.status === "success") {
                            //                     var wpaicg_randomnum = Math.floor(
                            //                         Math.random() * 100000 + 1
                            //                     );
                            //                     result.data = result.data.replace(/\n/g, "<br>");
                            //                     var wpaicg_message =
                            //                         '<li class="' +
                            //                         class_ai_item +
                            //                         '" style="background-color:' +
                            //                         wpaicg_ai_bg +
                            //                         ";font-size: " +
                            //                         wpaicg_font_size +
                            //                         "px;color: " +
                            //                         wpaicg_font_color +
                            //                         '"><p style="width:100%"><strong class="wpaicg-chat-avatar">' +
                            //                         wpaicg_ai_name +
                            //                         '</strong><span class="wpaicg-chat-message" id="wpaicg-chat-message-' +
                            //                         wpaicg_randomnum +
                            //                         '">' +
                            //                         result.data +
                            //                         "</span></p></li>";
                            //                     if (type === "widget") {
                            //                         chat.querySelector(
                            //                             ".wpaicg-chatbox-messages"
                            //                         ).innerHTML += wpaicg_message;
                            //                     } else {
                            //                         chat.querySelector(
                            //                             ".wpaicg-chat-shortcode-messages"
                            //                         ).innerHTML += wpaicg_message;
                            //                     }
                            //                     chat.setAttribute("data-pdf", namespace);
                            //                     pdfIcon.style.display = "none";
                            //                     pdfLoading.style.display = "none";
                            //                     pdfRemove.style.display = "flex";
                            //                 } else {
                            //                     pdfRemove.style.display = "none";
                            //                     pdfIcon.style.display = "block";
                            //                     pdfLoading.style.display = "none";
                            //                     alert(result.msg);
                            //                 }
                            //             }
                            //         };
                            //     } else {
                            //         pdfIcon.style.display = "block";
                            //         pdfLoading.style.display = "none";
                            //         pdfRemove.style.display = "none";
                            //         alert(result.msg);
                            //     }
                            // }
                        );
                    }
                } else {
                    alert("Your pdf is empty.");
                    pdfIcon.style.display = "block";
                    pdfLoading.style.display = "none";
                    input.value = "";
                }
            }
        }

        function wpaicggetWords(str, limit) {
            return str.split(/\s+/).slice(0, limit).join(" ");
        }

        // $(document).on('submit', '.wpaicg-template-form form', function(e) {
        //     var form = $(e.currentTarget);
        //     var btn = form.find('.wpaicg-generate-button');
        //     var template_title = form.find('.wpaicg-template-title').val();
        //     var response_type = form.find('.wpaicg-template-response-type').val();
        //     if (template_title !== '') {
        //         var max_tokens = form.find('.wpaicg-template-max_tokens input').val();
        //         var temperature = form.find('.wpaicg-template-temperature input').val();
        //         var top_p = form.find('.wpaicg-template-top_p input').val();
        //         var best_of = form.find('.wpaicg-template-best_of input').val();
        //         var frequency_penalty = form.find('.wpaicg-template-frequency_penalty input').val();
        //         var presence_penalty = form.find('.wpaicg-template-presence_penalty input').val();
        //         var error_message = false;
        //         if (max_tokens === '') {
        //             error_message = '<?php echo esc_html__('Please enter max tokens', 'gpt3-ai-content-generator') ?>';
        //         } else if (parseFloat(max_tokens) < 1 || parseFloat(max_tokens) > 4000) {
        //             error_message = '<?php echo sprintf(esc_html__('Please enter a valid max token value between %d and %d.', 'gpt3-ai-content-generator'), 1, 4000) ?>';
        //         } else if (temperature === '') {
        //             error_message = '<?php echo esc_html__('Please enter temperature', 'gpt3-ai-content-generator') ?>';
        //         } else if (parseFloat(temperature) < 0 || parseFloat(temperature) > 1) {
        //             error_message = '<?php echo sprintf(esc_html__('Please enter a valid temperature value between %d and %d.', 'gpt3-ai-content-generator'), 0, 1) ?>';
        //         } else if (top_p === '') {
        //             error_message = '<?php echo esc_html__('Please enter Top P', 'gpt3-ai-content-generator') ?>';
        //         } else if (parseFloat(top_p) < 0 || parseFloat(top_p) > 1) {
        //             error_message = '<?php echo sprintf(esc_html__('Please enter a valid top p value between %d and %d.', 'gpt3-ai-content-generator'), 0, 1) ?>';
        //         } else if (best_of === '') {
        //             error_message = '<?php echo esc_html__('Please enter best of', 'gpt3-ai-content-generator') ?>';
        //         } else if (parseFloat(best_of) < 1 || parseFloat(best_of) > 20) {
        //             error_message = '<?php echo sprintf(esc_html__('Please enter a valid best of value between %d and %d.', 'gpt3-ai-content-generator'), 1, 20) ?>';
        //         } else if (frequency_penalty === '') {
        //             error_message = '<?php echo esc_html__('Please enter frequency penalty', 'gpt3-ai-content-generator') ?>';
        //         } else if (parseFloat(frequency_penalty) < 0 || parseFloat(frequency_penalty) > 2) {
        //             error_message = '<?php echo sprintf(esc_html__('Please enter a valid frequency penalty value between %d and %d.', 'gpt3-ai-content-generator'), 0, 2) ?>';
        //         } else if (presence_penalty === '') {
        //             error_message = '<?php echo esc_html__('Please enter presence penalty', 'gpt3-ai-content-generator') ?>';
        //         } else if (parseFloat(presence_penalty) < 0 || parseFloat(presence_penalty) > 2) {
        //             error_message = '<?php echo sprintf(esc_html__('Please enter a valid presence penalty value between %d and %d.', 'gpt3-ai-content-generator'), 0, 2) ?>';
        //         }
        //         if (error_message) {
        //             alert(error_message);
        //         } else {
        //             if ($('.wpaicg-template-form .wpaicg-form-field-template').length) {
        //                 $('.wpaicg-template-form .wpaicg-form-field-template').each(function(idf, item) {
        //                     var field_type = $(item).attr('data-type');
        //                     var field_name = $(item).attr('name');
        //                     var field_label = $(item).attr('data-label');
        //                     var field_value = $(item).val();
        //                     var field_min = $(item).attr('data-min');
        //                     var field_max = $(item).attr('data-max');
        //                     if (field_type === 'text' || field_type === 'textarea' || field_type === 'email' || field_type === 'url') {
        //                         if (field_min !== '' && field_value.length < parseInt(field_min)) {
        //                             error_message = '<?php echo esc_html__('{label} minimum {min} characters', 'gpt3-ai-content-generator') ?>'.replace(/{label}/g, field_label).replace(/{min}/g, field_min);
        //                         } else if (field_max !== '' && field_value.length > parseInt(field_max)) {
        //                             error_message = '<?php echo esc_html__('{label} maximum {max} characters', 'gpt3-ai-content-generator') ?>'.replace(/{label}/g, field_label).replace(/{max}/g, field_max);
        //                         } else if (field_type === 'email' && !wpaicgValidEmail(field_value)) {
        //                             error_message = '<?php echo esc_html__('{label} must be email address', 'gpt3-ai-content-generator') ?>'.replace(/{label}/g, field_label);
        //                         } else if (field_type === 'url' && !wpaicgValidUrl(field_value)) {
        //                             error_message = '<?php echo esc_html__('{label} must be url', 'gpt3-ai-content-generator') ?>'.replace(/{label}/g, field_label);
        //                         }
        //                     } else if (field_type === 'number') {
        //                         if (field_min !== '' && parseFloat(field_value) < parseInt(field_min)) {
        //                             error_message = '<?php echo esc_html__('{label} minimum {min}', 'gpt3-ai-content-generator') ?>'.replace(/{label}/g, field_label).replace(/{min}/g, field_min);
        //                         } else if (field_max !== '' && parseFloat(field_value) > parseInt(field_max)) {
        //                             error_message = '<?php echo esc_html__('{label} maximum {max}', 'gpt3-ai-content-generator') ?>'.replace(/{label}/g, field_label).replace(/{max}/g, field_max);
        //                         }
        //                     }
        //                 })
        //             }
        //             if (error_message) {
        //                 alert(error_message);
        //             } else {
        //                 prompt_response = '';
        //                 let startTime = new Date();
        //                 if ($('.wpaicg-template-form .wpaicg-form-field-template').length) {
        //                     $('.wpaicg-template-form .wpaicg-form-field-template').each(function(idf, item) {
        //                         var field_name = $(item).attr('name');
        //                         var field_value = $(item).val();
        //                         var sRegExInput = new RegExp('{' + field_name + '}', 'g');
        //                         template_title = template_title.replace(sRegExInput, field_value);
        //                     })
        //                 }
        //                 $('.wpaicg-template-title-filled').val(template_title + ".\n\n");
        //                 var data = form.serialize();
        //                 var basicEditor = true;
        //                 if (response_type === 'textarea') {
        //                     var editor = tinyMCE.get('editor-' + wpaicgEditorNumber);
        //                     if ($('#wp-editor-' + wpaicgEditorNumber + '-wrap').hasClass('tmce-active') && editor) {
        //                         basicEditor = false;
        //                     }
        //                     if (basicEditor) {
        //                         $('#editor-' + wpaicgEditorNumber).val('');
        //                     } else {
        //                         editor.setContent('');
        //                     }
        //                 }
        //                 wpaicgLoading(btn);
        //                 form.find('.wpaicg-template-stop-generate').show();
        //                 form.find('.wpaicg-template-save-result').hide();
        //                 var wpaicg_limitLines = parseInt(form.find('.wpaicg-template-max-lines').val());
        //                 var count_line = 0;
        //                 var currentContent = '';
        //                 data += '&source_stream=form&nonce=<?php echo wp_create_nonce('wpaicg-ajax-nonce') ?>';
        //                 let prompts = JSON.parse(template_title);
        //                 eventReader = new EventSource('<?php echo esc_html(add_query_arg('wpaicg_stream', 'yes', site_url() . '/index.php')); ?>&' + data);
        //                 var wpaicg_response_events = 0;
        //                 var wpaicg_newline_before = false;
        //                 wpaicg_limited_token = false;
        //                 eventReader.onmessage = function(e) {
        //                     if (response_type === 'textarea') {
        //                         if (basicEditor) {
        //                             currentContent = $('#editor-' + wpaicgEditorNumber).val();
        //                         } else {
        //                             currentContent = editor.getContent();
        //                             currentContent = currentContent.replace(/<\/?p(>|$)/g, "");
        //                         }
        //                     } else {
        //                         currentContent = $('.wpaicg-template-response-element').html();
        //                     }
        //                     var resultData = JSON.parse(e.data);

        //                     // Check if the response contains the finish_reason property and if it's set to "stop"
        //                     var hasFinishReason = resultData.choices &&
        //                         resultData.choices[0] &&
        //                         (resultData.choices[0].finish_reason === "stop" ||
        //                             resultData.choices[0].finish_reason === "length");

        //                     if (hasFinishReason) {
        //                         count_line += 1;
        //                         if (response_type === 'textarea') {
        //                             if (basicEditor) {
        //                                 $('#editor-' + wpaicgEditorNumber).val(currentContent + '<br /><br />');
        //                             } else {
        //                                 editor.setContent(currentContent + '<br /><br />');
        //                             }
        //                         } else {
        //                             $('.wpaicg-template-response-element').append('<br />');
        //                         }
        //                         wpaicg_response_events = 0;
        //                     } else if (e.data === "[LIMITED]") {
        //                         wpaicg_limited_token = true;
        //                         count_line += 1;
        //                         if (response_type === 'textarea') {
        //                             if (basicEditor) {
        //                                 $('#editor-' + wpaicgEditorNumber).val(currentContent + '<br /><br />');
        //                             } else {
        //                                 editor.setContent(currentContent + '<br /><br />');
        //                             }
        //                         } else {
        //                             $('.wpaicg-template-response-element').append('<br />');
        //                         }
        //                         wpaicg_response_events = 0;
        //                     } else {
        //                         var result = JSON.parse(e.data);
        //                         if (result.error !== undefined) {
        //                             var content_generated = result.error.message;
        //                         } else {
        //                             var content_generated = result.choices[0].delta !== undefined ? (result.choices[0].delta.content !== undefined ? result.choices[0].delta.content : '') : result.choices[0].text;
        //                         }
        //                         prompt_response += content_generated;
        //                         if ((content_generated === '\n' || content_generated === ' \n' || content_generated === '.\n' || content_generated === '\n\n' || content_generated === '"\n') && wpaicg_response_events > 0 && currentContent !== '') {
        //                             if (!wpaicg_newline_before) {
        //                                 wpaicg_newline_before = true;
        //                                 if (response_type === 'textarea') {
        //                                     if (basicEditor) {
        //                                         $('#editor-' + wpaicgEditorNumber).val(currentContent + '<br /><br />');
        //                                     } else {
        //                                         editor.setContent(currentContent + '<br /><br />');
        //                                     }
        //                                 } else {
        //                                     $('.wpaicg-template-response-element').append('<br />');
        //                                 }
        //                             }
        //                         } else if (content_generated.indexOf("\n") > -1 && wpaicg_response_events > 0 && currentContent !== '') {
        //                             if (!wpaicg_newline_before) {
        //                                 wpaicg_newline_before = true;
        //                                 content_generated = content_generated.replace(/\n/g, '<br>');
        //                                 if (response_type === 'textarea') {
        //                                     if (basicEditor) {
        //                                         $('#editor-' + wpaicgEditorNumber).val(currentContent + content_generated);
        //                                     } else {
        //                                         editor.setContent(currentContent + content_generated);
        //                                     }
        //                                 } else {
        //                                     $('.wpaicg-template-response-element').append(content_generated);
        //                                 }
        //                             }
        //                         } else if (content_generated === '\n' && wpaicg_response_events === 0 && currentContent === '') {

        //                         } else {
        //                             wpaicg_newline_before = false;
        //                             wpaicg_response_events += 1;
        //                             if (response_type === 'textarea') {
        //                                 if (basicEditor) {
        //                                     $('#editor-' + wpaicgEditorNumber).val(currentContent + content_generated);
        //                                 } else {
        //                                     editor.setContent(currentContent + content_generated);
        //                                 }
        //                             } else {
        //                                 $('.wpaicg-template-response-element').append(content_generated);
        //                             }
        //                         }
        //                     }
        //                     if (count_line === wpaicg_limitLines) {
        //                         if (!wpaicg_limited_token) {
        //                             let endTime = new Date();
        //                             let timeDiff = endTime - startTime;
        //                             timeDiff = timeDiff / 1000;
        //                             data += '&action=wpaicg_form_log&prompt_id=' + prompt_id + '&prompt_name=' + prompt_name + '&prompt_response=' + prompt_response + '&duration=' + timeDiff + '&_wpnonce=' + wp_nonce;
        //                             $.ajax({
        //                                 url: '<?php echo admin_url('admin-ajax.php') ?>',
        //                                 data: data,
        //                                 dataType: 'JSON',
        //                                 type: 'POST',
        //                                 success: function(res) {

        //                                 }
        //                             })
        //                         }
        //                         $('.wpaicg-template-form .wpaicg-template-stop-generate').hide();
        //                         stopOpenAIGenerator();
        //                         wpaicgRmLoading(btn);
        //                     }
        //                 }
        //             }
        //         }
        //     } else {
        //         alert('<?php echo esc_html__('Please enter prompt', 'gpt3-ai-content-generator') ?>');
        //     }
        //     return false;
        // })
    })
</script>