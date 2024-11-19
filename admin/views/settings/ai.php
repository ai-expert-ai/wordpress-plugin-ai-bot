<?php
if (!defined('ABSPATH')) exit;
?>
<div id="tabs-1">
    <?php
    $wpaicg_ai_model = get_option('wpaicg_ai_model', 'gpt-4-turbo', 'chatgpt-4o-latest');
    $wpaicg_provider = get_option('wpaicg_provider', 'OpenAI');  // Default to OpenAI
    ?>
    <!-- Provider Dropdown -->
    <div class="wpcgai_form_row">
        <label class="wpcgai_label" for="wpaicg_provider"><?php echo esc_html__('Provider', 'gpt3-ai-content-generator'); ?>:</label>
        <select class="regular-text" id="wpaicg_provider" name="wpaicg_provider">
            <option value="OpenAI" <?php selected($wpaicg_provider, 'OpenAI'); ?>>OpenAI</option>
            <option value="Azure" <?php selected($wpaicg_provider, 'Azure'); ?>>Azure</option>
            <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/category/ai-engines" target="_blank">?</a>
        </select>
    </div>

    <!-- Azure Settings Fields -->
    <div class="wpaicg_azure_settings" style="<?php echo ($wpaicg_provider === 'Azure') ? '' : 'display:none'; ?>">
        <?php
        // Get Azure settings from wp_options
        $wpaicg_azure_api_key = get_option('wpaicg_azure_api_key', '');
        $wpaicg_azure_endpoint = get_option('wpaicg_azure_endpoint', '');
        $wpaicg_azure_deployment = get_option('wpaicg_azure_deployment', '');
        $wpaicg_azure_embeddings = get_option('wpaicg_azure_embeddings', '');
        ?>

        <div class="wpcgai_form_row">
            <label class="wpcgai_label" for="wpaicg_azure_api_key"><?php echo esc_html__('Azure API Key', 'gpt3-ai-content-generator'); ?>:</label>
            <input type="text" class="regular-text" id="wpaicg_azure_api_key" name="wpaicg_azure_api_key" value="<?php echo esc_attr($wpaicg_azure_api_key); ?>">
            <a class="wpcgai_help_link" href="https://azure.microsoft.com/en-us/products/ai-services/openai-service" target="_blank"><?php echo esc_html__('Get Your Api Key', 'gpt3-ai-content-generator') ?></a>
        </div>
        <div class="wpcgai_form_row">
            <label class="wpcgai_label" for="wpaicg_azure_endpoint"><?php echo esc_html__('Azure Endpoint', 'gpt3-ai-content-generator'); ?>:</label>
            <input type="text" class="regular-text" id="wpaicg_azure_endpoint" name="wpaicg_azure_endpoint" value="<?php echo esc_attr($wpaicg_azure_endpoint); ?>">
            <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/ai-engine/azure-openai" target="_blank">?</a>
        </div>
        <div class="wpcgai_form_row">
            <label class="wpcgai_label" for="wpaicg_azure_deployment"><?php echo esc_html__('Azure Deployment', 'gpt3-ai-content-generator'); ?>:</label>
            <input type="text" class="regular-text" id="wpaicg_azure_deployment" name="wpaicg_azure_deployment" value="<?php echo esc_attr($wpaicg_azure_deployment); ?>">
            <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/ai-engine/azure-openai" target="_blank">?</a>
        </div>
        <div class="wpcgai_form_row">
            <label class="wpcgai_label" for="wpaicg_azure_embeddings"><?php echo esc_html__('Azure Embeddings', 'gpt3-ai-content-generator'); ?>:</label>
            <input type="text" class="regular-text" id="wpaicg_azure_embeddings" name="wpaicg_azure_embeddings" value="<?php echo esc_attr($wpaicg_azure_embeddings); ?>">
            <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/ai-engine/azure-openai" target="_blank">?</a>
        </div>
    </div>
    <div class="wpcgai_form_row">
        <label class="wpcgai_label" for="wpaicg_ai_model"><?php echo esc_html__('Model', 'gpt3-ai-content-generator') ?>:</label>
        <select class="regular-text" id="wpaicg_ai_model" name="wpaicg_ai_model">
            <?php
            $gpt4_models = ['gpt-4', 'gpt-4-turbo', 'chatgpt-4o-latest'];
            $gpt35_models = ['gpt-3.5-turbo', 'gpt-3.5-turbo-16k', 'gpt-3.5-turbo-instruct'];
            $gpt3_models = ['text-curie-001', 'text-babbage-001', 'text-ada-001'];
            $legacy_models = ['text-davinci-003'];
            $custom_models = get_option('wpaicg_custom_models', []);
            $current_model = $wpaicg_ai_model; // This should be the model currently selected
            ?>
            <optgroup label="GPT-4">
                <?php foreach ($gpt4_models as $model) : ?>
                    <option value="<?php echo esc_attr($model); ?>" <?php selected($model, $current_model); ?>><?php echo esc_html($model); ?></option>
                <?php endforeach; ?>
            </optgroup>
            <!-- <optgroup label="GPT-3.5">
                <?php foreach ($gpt35_models as $model) : ?>
                    <option value="<?php echo esc_attr($model); ?>" <?php selected($model, $current_model); ?>><?php echo esc_html($model); ?></option>
                <?php endforeach; ?>
            </optgroup>
            <optgroup label="GPT-3">
                <?php foreach ($gpt3_models as $model) : ?>
                    <option value="<?php echo esc_attr($model); ?>" <?php selected($model, $current_model); ?>><?php echo esc_html($model); ?></option>
                <?php endforeach; ?>
            </optgroup>
            <optgroup label="Legacy Models">
                <?php foreach ($legacy_models as $model) : ?>
                    <option value="<?php echo esc_attr($model); ?>" <?php selected($model, $current_model); ?>><?php echo esc_html($model); ?></option>
                <?php endforeach; ?>
            </optgroup>
            <optgroup label="Custom Models">
                <?php foreach ($custom_models as $model) : ?>
                    <option value="<?php echo esc_attr($model); ?>" <?php selected($model, $current_model); ?>><?php echo esc_html($model); ?></option>
                <?php endforeach; ?>
            </optgroup> -->
        </select>
        <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/ai-engine/openai/gpt-models#model-configuration" target="_blank">?</a>
        <a class="wpaicg_sync_finetune" href="javascript:void(0)"><?php echo esc_html__('Sync', 'gpt3-ai-content-generator') ?></a>
    </div>

    <?php
    $wpaicg_sleep_time = get_option('wpaicg_sleep_time', 1);
    ?>
    <div class="wpcgai_form_row wpaicg_beta_notice" style="<?php echo $wpaicg_ai_model == 'gpt-4-32k' || $wpaicg_ai_model == 'gpt-4' ? '' : 'display:none' ?>">
        <p><?php echo sprintf(esc_html__('Please note that GPT-4 is currently in limited beta, which means that access to the GPT-4 API from OpenAI is available only through a waiting list and is not open to everyone yet. You can sign up for the waiting list at %shere%s.', 'gpt3-ai-content-generator'), '<a href="https://openai.com/waitlist/gpt-4-api" target="_blank">', '</a>') ?></p>
    </div>
    <div class="wpcgai_form_row wpaicg_sleep_time" style="<?php echo $wpaicg_ai_model == 'gpt-3.5-turbo' || $wpaicg_ai_model == 'gpt-3.5-turbo-16k' || $wpaicg_ai_model == 'gpt-4-32k' || $wpaicg_ai_model == 'gpt-4' ? '' : 'display:none' ?>">
        <label class="wpcgai_label"><?php echo esc_html__('Rate Limit Buffer (in Seconds)', 'gpt3-ai-content-generator') ?>:</label>
        <select class="regular-text" name="wpaicg_sleep_time">
            <?php
            for ($i = 1; $i <= 10; $i++) {
                echo '<option' . ($wpaicg_sleep_time == $i ? ' selected' : '') . ' value="' . esc_html($i) . '">' . esc_html($i) . '</option>';
            }
            ?>
        </select>
        <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/ai-engine/openai/gpt-models#rate-limit-buffer" target="_blank">?</a>
    </div>

    <div class="wpcgai_form_row">
        <label class="wpcgai_label"><?php echo esc_html__('Temperature', 'gpt3-ai-content-generator') ?>:</label>
        <input type="text" class="regular-text" id="label_temperature" name="wpaicg_settings[temperature]" value="<?php
                                                                                                                    echo  esc_html($existingValue['temperature']);
                                                                                                                    ?>">
        <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/ai-engine/openai/temperature" target="_blank">?</a>
    </div>

    <div class="wpcgai_form_row">
        <label class="wpcgai_label"><?php echo esc_html__('Max Tokens', 'gpt3-ai-content-generator') ?>:</label>
        <input type="text" class="regular-text" id="label_max_tokens" name="wpaicg_settings[max_tokens]" value="<?php
                                                                                                                echo  esc_html($existingValue['max_tokens']);
                                                                                                                ?>">
        <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/ai-engine/openai/max-tokens#adjusting-the-max-tokens-setting" target="_blank">?</a>
    </div>
    <div class="wpcgai_form_row">
        <label class="wpcgai_label"><?php echo esc_html__('Api Key', 'gpt3-ai-content-generator') ?>:</label>
        <input type="text" class="regular-text" id="label_api_key" name="wpaicg_settings[api_key]" value="<?php
                                                                                                            echo  esc_html($existingValue['api_key']);
                                                                                                            ?>">
        <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/ai-engine/openai/api-key#how-to-generate-an-openai-api-key" target="_blank">?</a>
        <a class="wpcgai_help_link" href="https://beta.openai.com/account/api-keys" target="_blank"><?php echo esc_html__('Get Your Api Key', 'gpt3-ai-content-generator') ?></a>
    </div>
    <div class="wpaicg_more_ai_settings" style="display: none">
        <div class="wpcgai_form_row">
            <label class="wpcgai_label"><?php echo esc_html__('Top P', 'gpt3-ai-content-generator') ?>:</label>
            <input type="text" class="regular-text" id="label_top_p" name="wpaicg_settings[top_p]" value="<?php
                                                                                                            echo  esc_html($existingValue['top_p']);
                                                                                                            ?>">
            <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/ai-engine/openai/top-p#adjusting-the-top_p-setting" target="_blank">?</a>
        </div>
        <div class="wpcgai_form_row">
            <label class="wpcgai_label"><?php echo esc_html__('Best Of', 'gpt3-ai-content-generator') ?>:</label>
            <input type="text" class="regular-text" id="label_best_of" name="wpaicg_settings[best_of]" value="<?php
                                                                                                                echo  esc_html($existingValue['best_of']);
                                                                                                                ?>">
            <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/ai-engine/openai/best-of#adjusting-the-best-of-setting" target="_blank">?</a>
        </div>
        <div class="wpcgai_form_row">
            <label class="wpcgai_label"><?php echo esc_html__('Frequency Penalty', 'gpt3-ai-content-generator') ?>:</label>
            <input type="text" class="regular-text" id="label_frequency_penalty" name="wpaicg_settings[frequency_penalty]" value="<?php
                                                                                                                                    echo  esc_html($existingValue['frequency_penalty']);
                                                                                                                                    ?>">
            <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/ai-engine/openai/frequency-penalty#adjusting-the-frequency-penalty" target="_blank">?</a>
        </div>
        <div class="wpcgai_form_row">
            <label class="wpcgai_label"><?php echo esc_html__('Presence Penalty', 'gpt3-ai-content-generator') ?>:</label>
            <input type="text" class="regular-text" id="label_presence_penalty" name="wpaicg_settings[presence_penalty]" value="<?php
                                                                                                                                echo  esc_html($existingValue['presence_penalty']);
                                                                                                                                ?>">
            <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/ai-engine/openai/presence-penalty#adjusting-the-presence-penalty" target="_blank">?</a>
        </div>
    </div>
    <div class="mb-5">
        <a href="javascript:void(0)" class="wpaicg_show_ai_settings">[<?php echo esc_html__('+ Advance Settings', 'gpt3-ai-content-generator') ?>]</a>
    </div>
</div>
<script>
    jQuery(document).ready(function($) {
        // Function to toggle OpenAI and Azure fields
        function toggleProviderFields() {
            var provider = $('#wpaicg_provider').val();
            if (provider === 'Azure') {
                $('.wpaicg_azure_settings').show();
                $('#label_api_key').closest('.wpcgai_form_row').hide();
                $('#wpaicg_ai_model').closest('.wpcgai_form_row').hide();
                $('.wpaicg_beta_notice').hide();
            } else {
                $('.wpaicg_azure_settings').hide();
                $('#label_api_key').closest('.wpcgai_form_row').show();
                $('#wpaicg_ai_model').closest('.wpcgai_form_row').show();
            }
        }

        // Provider change event to toggle Azure and OpenAI settings visibility
        $('#wpaicg_provider').on('change', toggleProviderFields);

        // Call on page load to set initial state
        toggleProviderFields();

        $('.wpaicg_show_ai_settings').click(function() {
            $(this).toggleClass('wpaig_opened_ai');
            $('.wpaicg_more_ai_settings').slideToggle();
            if ($(this).hasClass('wpaig_opened_ai')) {
                $(this).html('[<?php echo esc_html__('- Hide Advance Settings', 'gpt3-ai-content-generator') ?>]');
            } else {
                $(this).html('[<?php echo esc_html__('+ Advance Settings', 'gpt3-ai-content-generator') ?>]');
            }
        })
        $('#wpaicg_ai_model').on('change', function() {
            if ($(this).val() === 'gpt-3.5-turbo' || $(this).val() === 'gpt-3.5-turbo-16k' || $(this).val() === 'gpt-4' || $(this).val() === 'gpt-4-32k') {
                $('.wpaicg_sleep_time').show();
            } else {
                $('.wpaicg_sleep_time').hide();
            }
            if ($(this).val() === 'gpt-4' || $(this).val() === 'gpt-4-32k') {
                $('.wpaicg_beta_notice').show();
            } else {
                $('.wpaicg_beta_notice').hide();
            }
        })
        // Before saving, validate the Azure fields if Azure is selected as the provider
        $('form').on('submit', function(e) {
            var provider = $('#wpaicg_provider').val();
            if (provider === 'Azure') {
                var apiKey = $('#wpaicg_azure_api_key').val();
                var endpoint = $('#wpaicg_azure_endpoint').val();
                var deployment = $('#wpaicg_azure_deployment').val();

                if (!apiKey || !endpoint || !deployment) {
                    alert('<?php echo esc_js(__('Please fill in all the mandatory fields for Azure.', 'gpt3-ai-content-generator')); ?>');
                    e.preventDefault();
                }
            }
        });
    })
</script>