<?php

namespace WPAICG;

if (!defined('ABSPATH')) exit;
if (!class_exists('\\WPAICG\\WPAICG_Promptbase')) {
    class WPAICG_Promptbase
    {
        private static $instance = null;
        public $wpaicg_engine = 'gpt-3.5-turbo';
        public $wpaicg_max_tokens = 2000;
        public $wpaicg_temperature = 0;
        public $wpaicg_top_p = 1;
        public $wpaicg_best_of = 1;
        public $wpaicg_frequency_penalty = 0;
        public $wpaicg_presence_penalty = 0;
        public $wpaicg_stop = [];

        public static function get_instance()
        {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function create_table_logs()
        {
            global $wpdb;
            if (is_admin()) {
                $wpaicgLogTable = $wpdb->prefix . 'wpaicg_promptbase_logs';
                if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpaicgLogTable)) != $wpaicgLogTable) {
                    $charset_collate = $wpdb->get_charset_collate();
                    $sql = "CREATE TABLE " . $wpaicgLogTable . " (
    `id` mediumint(11) NOT NULL AUTO_INCREMENT,
    `prompt` TEXT NOT NULL,
    `source` INT NOT NULL DEFAULT '0',
    `data` LONGTEXT NOT NULL,
    `prompt_id` VARCHAR(255) DEFAULT NULL,
    `name` VARCHAR(255) DEFAULT NULL,
    `model` VARCHAR(255) DEFAULT NULL,
    `duration` VARCHAR(255) DEFAULT NULL,
    `tokens` VARCHAR(255) DEFAULT NULL,
    `created_at` VARCHAR(255) NOT NULL,
    PRIMARY KEY  (id)
    ) $charset_collate";
                    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                    $wpdb->query($sql);
                }
                $wpaicgTokensTable = $wpdb->prefix . 'wpaicg_prompttokens';
                if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpaicgTokensTable)) != $wpaicgTokensTable) {
                    $charset_collate = $wpdb->get_charset_collate();
                    $sql = "CREATE TABLE " . $wpaicgTokensTable . " (
    `id` mediumint(11) NOT NULL AUTO_INCREMENT,
    `tokens` VARCHAR(255) DEFAULT NULL,
    `user_id` VARCHAR(255) DEFAULT NULL,
    `session_id` VARCHAR(255) DEFAULT NULL,
    `source` VARCHAR(255) DEFAULT NULL,
    `created_at` VARCHAR(255) NOT NULL,
    PRIMARY KEY  (id)
    ) $charset_collate";
                    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                    $wpdb->query($sql);
                }
            }
        }

        public function __construct()
        {
            // add_action( 'admin_menu', array( $this, 'wpaicg_menu' ) );
            add_shortcode('wpaicg_prompt', [$this, 'wpaicg_prompt_shortcode']);
            add_action('wp_ajax_wpaicg_update_prompt', [$this, 'wpaicg_update_prompt']);
            add_action('wp_ajax_wpaicg_prompt_delete', [$this, 'wpaicg_prompt_delete']);
            add_action('wp_ajax_wpaicg_prompt_log', [$this, 'wpaicg_prompt_log']);
            add_action('wp_ajax_wpaicg_prompt_duplicate', [$this, 'wpaicg_prompt_duplicate']);
            add_action('wp_ajax_nopriv_wpaicg_prompt_log', [$this, 'wpaicg_prompt_log']);
            if (!wp_next_scheduled('wpaicg_remove_promptbase_tokens_limited')) {
                wp_schedule_event(time(), 'hourly', 'wpaicg_remove_promptbase_tokens_limited');
            }
            add_action('wpaicg_remove_promptbase_tokens_limited', array($this, 'wpaicg_remove_tokens_limit'));
            $this->create_table_logs();
        }

        public function wpaicg_remove_tokens_limit()
        {
            global $wpdb;
            $wpaicg_settings = get_option('wpaicg_limit_tokens_promptbase', []);
            $widget_reset_limit = isset($wpaicg_settings['reset_limit']) && !empty($wpaicg_settings['reset_limit']) ? $wpaicg_settings['reset_limit'] : 0;
            if ($widget_reset_limit > 0) {
                $widget_time = time() - ($widget_reset_limit * 86400);
                $wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . "wpaicg_prompttokens WHERE created_at < %s", $widget_time));
            }
        }

        public function wpaicg_prompt_log()
        {
            global $wpdb;
            $wpaicg_result = array('status' => 'success');
            $wpaicg_nonce = sanitize_text_field($_REQUEST['_wpnonce']);
            if (!wp_verify_nonce($wpaicg_nonce, 'wpaicg-promptbase')) {
                $wpaicg_result['msg'] = WPAICG_NONCE_ERROR;
                wp_send_json($wpaicg_result);
                exit;
            }
            if (
                isset($_REQUEST['prompt_id'])
                && !empty($_REQUEST['prompt_id'])
                && isset($_REQUEST['prompt_name'])
                && !empty($_REQUEST['prompt_name'])
                && isset($_REQUEST['prompt_response'])
                && !empty($_REQUEST['prompt_response'])
                && isset($_REQUEST['engine'])
                && !empty($_REQUEST['engine'])
                && isset($_REQUEST['title'])
                && !empty($_REQUEST['title'])
            ) {
                $log = array(
                    'prompt' => sanitize_text_field($_REQUEST['title']),
                    'data' => wp_kses_post($_REQUEST['prompt_response']),
                    'prompt_id' => sanitize_text_field($_REQUEST['prompt_id']),
                    'name' => sanitize_text_field($_REQUEST['prompt_name']),
                    'model' => sanitize_text_field($_REQUEST['engine']),
                    'duration' => sanitize_text_field($_REQUEST['duration']),
                    'created_at' => time()
                );
                if (isset($_REQUEST['source_id']) && !empty($_REQUEST['source_id'])) {
                    $log['source'] = sanitize_text_field($_REQUEST['source_id']);
                }
                $wpaicg_generator = WPAICG_Generator::get_instance();
                $log['tokens'] = ceil($wpaicg_generator->wpaicg_count_words($log['data']) * 1000 / 750);
                WPAICG_Account::get_instance()->save_log('promptbase', $log['tokens']);
                $wpdb->insert($wpdb->prefix . 'wpaicg_promptbase_logs', $log);
                $wpaicg_playground = WPAICG_Playground::get_instance();
                $wpaicg_tokens_handling = $wpaicg_playground->wpaicg_token_handling('promptbase');
                if ($wpaicg_tokens_handling['limit']) {
                    if ($wpaicg_tokens_handling['token_id']) {
                        $wpdb->update($wpdb->prefix . $wpaicg_tokens_handling['table'], array(
                            'tokens' => ($log['tokens'] + $wpaicg_tokens_handling['old_tokens'])
                        ), array('id' => $wpaicg_tokens_handling['token_id']));
                    } else {
                        $wpaicg_prompt_token_data = array(
                            'tokens' => $log['tokens'],
                            'created_at' => time()
                        );
                        if (is_user_logged_in()) {
                            $wpaicg_prompt_token_data['user_id'] = get_current_user_id();
                        } else {
                            $wpaicg_prompt_token_data['session_id'] = $wpaicg_tokens_handling['client_id'];
                        }
                        $wpdb->insert($wpdb->prefix . $wpaicg_tokens_handling['table'], $wpaicg_prompt_token_data);
                    }
                }
            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_menu()
        {
            add_submenu_page(
                'wpaicg',
                esc_html__('PromptBase', 'gpt3-ai-content-generator'),
                esc_html__('PromptBase', 'gpt3-ai-content-generator'),
                'wpaicg_promptbase',
                'wpaicg_promptbase',
                array($this, 'wpaicg_promptbase'),
                6
            );
        }

        public function wpaicg_update_prompt()
        {
            $wpaicg_result = array('status' => 'error', 'msg' => esc_html__('Something went wrong', 'gpt3-ai-content-generator'));
            if (!current_user_can('wpaicg_promptbase_promptbase')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if (!wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_promptbase_save')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = WPAICG_NONCE_ERROR;
                wp_send_json($wpaicg_result);
            }
            if (
                isset($_POST['title'])
                && !empty($_POST['title'])
                && isset($_POST['description'])
                && !empty($_POST['description'])
                && isset($_POST['prompt'])
                && !empty($_POST['prompt'])
            ) {
                $title = sanitize_text_field($_POST['title']);
                $description = sanitize_text_field($_POST['description']);
                if (isset($_POST['id']) && !empty($_POST['id'])) {
                    $wpaicg_prompt_id = sanitize_text_field($_POST['id']);
                    wp_update_post(array(
                        'ID' => $wpaicg_prompt_id,
                        'post_title' => $title,
                        'post_content' => $description
                    ));
                } else {
                    $wpaicg_prompt_id = wp_insert_post(array(
                        'post_title' => $title,
                        'post_type' => 'wpaicg_prompt',
                        'post_content' => $description,
                        'post_status' => 'publish'
                    ));
                }
                $prompt_fields = array('prompt', 'response', 'category', 'engine', 'max_tokens', 'temperature', 'top_p', 'best_of', 'frequency_penalty', 'presence_penalty', 'stop', 'color', 'icon', 'editor', 'bgcolor', 'header', 'dans', 'ddraft', 'dclear', 'dnotice', 'generate_text', 'noanswer_text', 'draft_text', 'clear_text', 'stop_text', 'cnotice_text', 'download_text', 'ddownload');
                foreach ($prompt_fields as $prompt_field) {
                    if (isset($_POST[$prompt_field]) && !empty($_POST[$prompt_field])) {
                        $value = wpaicg_util_core()->sanitize_text_or_array_field($_POST[$prompt_field]);
                        $key = sanitize_text_field($prompt_field);
                        update_post_meta($wpaicg_prompt_id, 'wpaicg_prompt_' . $key, $value);
                    } elseif (in_array($prompt_field, array('bgcolor', 'header', 'dans', 'ddraft', 'dclear', 'dnotice', 'ddownload')) && (!isset($_POST[$prompt_field]) || empty($_POST[$prompt_field]))) {
                        delete_post_meta($wpaicg_prompt_id, 'wpaicg_prompt_' . $prompt_field);
                    }
                }
                $wpaicg_result['status'] = 'success';
                $wpaicg_result['id'] = $wpaicg_prompt_id;
            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_prompt_duplicate()
        {
            $wpaicg_result = array('status' => 'success');
            if (!current_user_can('wpaicg_promptbase_promptbase')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if (!wp_verify_nonce($_POST['nonce'], 'wpaicg-ajax-nonce')) {
                $wpaicg_result['msg'] = WPAICG_NONCE_ERROR;
                wp_send_json($wpaicg_result);
            }
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                $promptbase = get_post(sanitize_post($_REQUEST['id']));
                $wpaicg_prompt_id = wp_insert_post(array(
                    'post_title' => $promptbase->post_title,
                    'post_type' => 'wpaicg_prompt',
                    'post_content' => $promptbase->post_content,
                    'post_status' => 'publish'
                ));
                $post_meta = get_post_meta($promptbase->ID);
                if ($post_meta) {

                    foreach ($post_meta as $meta_key => $meta_values) {

                        if ('_wp_old_slug' == $meta_key) { // do nothing for this meta key
                            continue;
                        }

                        foreach ($meta_values as $meta_value) {
                            add_post_meta($wpaicg_prompt_id, $meta_key, $meta_value);
                        }
                    }
                }
            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_prompt_delete()
        {
            $wpaicg_result = array('status' => 'success');
            if (!current_user_can('wpaicg_promptbase_promptbase')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if (!wp_verify_nonce($_POST['nonce'], 'wpaicg-ajax-nonce')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = WPAICG_NONCE_ERROR;
                wp_send_json($wpaicg_result);
            }
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                wp_delete_post(sanitize_text_field($_POST['id']));
            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_promptbase()
        {
            include WPAICG_PLUGIN_DIR . 'admin/extra/wpaicg_promptbase.php';
        }

        public function wpaicg_prompt_shortcode($atts)
        {
            ob_start();
            include WPAICG_PLUGIN_DIR . 'admin/extra/wpaicg_prompt_shortcode.php';
            return ob_get_clean();
        }
    }
    WPAICG_Promptbase::get_instance();
}
