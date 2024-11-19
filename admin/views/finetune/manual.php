<?php
if (!defined('ABSPATH')) {
    exit;
}

?>
<style>
    #wpaicg_form_data{
        max-width: 900px;
    }
    .wpaicg_list_data{
        padding: 10px;
        background: #e1e1e1;
        border: 1px solid #ccc;
        border-radius: 5px;
    }
    .wpaicg_data_item:after{
        clear: both;
        display: block;
        content: '';
    }
    .wpaicg_data_item input{
        flex: 1;
    }
    .wpaicg_data_item > div{
        float: left;
        width: calc(50% - 2px);
        margin-right: 2px;
        margin-bottom: 5px;
        display: flex;
    }
    .gpt-turbo .wpaicg_data_item > div{
        width: calc(33% - 2px);
    }
    .wpaicg_add_data{
        width: 100%;
        margin-top: 10px!important;
    }
    .wpaicg-convert-progress{
        height: 15px;
        background: #727272;
        border-radius: 5px;
        color: #fff;
        padding: 2px 12px;
        position: relative;
        font-size: 12px;
        text-align: center;
        margin-bottom: 10px;
        display: none;
        margin-top: 10px;
    }
    .wpaicg-convert-progress.wpaicg_error span{
        background: #bb0505;
    }
    .wpaicg-convert-progress span{
        display: block;
        position: absolute;
        height: 100%;
        border-radius: 5px;
        background: #2271b1;
        top: 0;
        left: 0;
        transition: width .6s ease;
    }
    .wpaicg-convert-progress small{
        position: relative;
        font-size: 12px;
    }
    #wpaicg_form_data span.button-link-delete {
        display:none;
    }
</style>
<p>Need help? <a href="https://docs.aipower.org/docs/fine-tuning" target="_blank">Read the documentation</a>.</p>
<form id="wpaicg_form_data" action="" method="post">

    <table class="form-table">
        <tbody>
        <tr>
            <th scope="row"><?php echo esc_html__('Purpose', 'gpt3-ai-content-generator') ?></th>
            <td>
                <select name="purpose">
                    <option value="fine-tune"><?php echo esc_html__('Fine-Tune', 'gpt3-ai-content-generator') ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php echo esc_html__('Model Base', 'gpt3-ai-content-generator') ?></th>
            <td>
                <select name="model">
                    <option value="gpt-3.5-turbo">gpt-3.5-turbo</option>
                    <option value="babbage-002">babbage-002</option>
                    <option value="davinci-002">davinci-002</option>
                </select>
            </td>
        </tr>
        <tr style="display:none;">
            <th scope="row"><?php echo esc_html__('Custom Name (Optional)', 'gpt3-ai-content-generator') ?></th>
            <td>
                <input type="text" name="custom">
            </td>
        </tr>
        </tbody>
    </table>

    <div class="wpaicg_list_data normal" style="display:none;">
        <div class="wpaicg_data_item">
            <div class="text-center"><strong><?php echo esc_html__('Prompt', 'gpt3-ai-content-generator') ?></strong></div>
            <div class="text-center"><strong><?php echo esc_html__('Completion', 'gpt3-ai-content-generator') ?></strong></div>
        </div>
        <div class="wpaicg_data_list">
            <div class="wpaicg_data_item wpaicg_data">
                <div>
                    <input type="text" name="data[0][prompt]" class="regular-text wpaicg_data_prompt" placeholder="<?php echo esc_html__('Prompt', 'gpt3-ai-content-generator') ?>">
                </div>
                <div>
                    <input type="text" name="data[0][completion]" class="regular-text wpaicg_data_completion" placeholder="<?php echo esc_html__('Completion', 'gpt3-ai-content-generator') ?>">
                    <span class="button button-link-delete">&times;</span>
                </div>
            </div>
        </div>
        <button class="button button-primary wpaicg_add_data" type="button"><?php echo esc_html__('Add More', 'gpt3-ai-content-generator') ?></button>
    </div>

    <div class="wpaicg_list_data gpt-turbo">
        <div class="wpaicg_data_item">
            <div class="text-center"><strong><?php echo esc_html__('System', 'gpt3-ai-content-generator') ?></strong></div>
            <div class="text-center"><strong><?php echo esc_html__('User', 'gpt3-ai-content-generator') ?></strong></div>
            <div class="text-center"><strong><?php echo esc_html__('Assistant', 'gpt3-ai-content-generator') ?></strong></div>
        </div>
        <div class="wpaicg_data_list">
            <div class="wpaicg_data_item wpaicg_data">
                <div>
                    <input type="text" name="data[0][system]" class="regular-text wpaicg_data_system" placeholder="<?php echo esc_html__('Lisa is a chatbot that is also sarcastic.', 'gpt3-ai-content-generator') ?>">
                </div>
                <div>
                    <input type="text" name="data[0][user]" class="regular-text wpaicg_data_user" placeholder="<?php echo esc_html__('What is the capital of France?', 'gpt3-ai-content-generator') ?>">
                </div>
                <div>
                    <input type="text" name="data[0][assistant]" class="regular-text wpaicg_data_assistant" placeholder="<?php echo esc_html__('Paris, as if everyone doesnt know that already.', 'gpt3-ai-content-generator') ?>">
                    <span class="button button-link-delete">&times;</span>
                </div>
            </div>
        </div>
        <button class="button button-primary wpaicg_add_data" type="button"><?php echo esc_html__('Add More', 'gpt3-ai-content-generator') ?></button>
    </div>

    <div class="wpaicg-convert-progress wpaicg-convert-bar">
        <span></span>
        <small>0%</small>
    </div>
    <div class="wpaicg-upload-message"></div>
    <button class="button-primary button wpaicg_submit" style="width: 100%;margin-top: 15px;"><?php echo esc_html__('Upload', 'gpt3-ai-content-generator') ?></button>
</form>
<form id="wpaicg_upload_convert" style="display: none" action="" method="post">
    <?php
wp_nonce_field('wpaicg-ajax-nonce', 'nonce');
?>
    <input type="hidden" name="action" value="wpaicg_upload_convert">
    <input type="hidden" id="wpaicg_upload_convert_index" name="index" value="1">
    <input id="wpaicg_upload_convert_line" type="hidden" name="line" value="0">
    <input id="wpaicg_upload_convert_lines" type="hidden" value="0">
    <input type="hidden" name="file" value="">
    <input type="hidden" name="purpose" value="fine-tune">
    <input type="hidden" name="model" value="">
    <input type="hidden" name="custom" value="">
</form>
<script>
    jQuery(document).ready(function ($){

        function wpaicgSortData(){
            if($('select[name="model"]').val() === 'gpt-3.5-turbo'){
                $('.wpaicg_list_data.gpt-turbo .wpaicg_data').each(function (idx, item){
                    $(item).find('.wpaicg_data_system').attr('name','data['+idx+'][system]');
                    $(item).find('.wpaicg_data_user').attr('name','data['+idx+'][user]');
                    $(item).find('.wpaicg_data_assistant').attr('name','data['+idx+'][assistant]');
                })
            } else {
                $('.wpaicg_list_data.normal .wpaicg_data').each(function (idx, item){
                    $(item).find('.wpaicg_data_prompt').attr('name','data['+idx+'][prompt]');
                    $(item).find('.wpaicg_data_completion').attr('name','data['+idx+'][completion]');
                })
            }
        }
        function wpaicgLoading(btn){
            btn.attr('disabled','disabled');
            if(!btn.find('spinner').length){
                btn.append('<span class="spinner"></span>');
            }
            btn.find('.spinner').css('visibility','unset');
        }
        function wpaicgRmLoading(btn){
            btn.removeAttr('disabled');
            btn.find('.spinner').remove();
        }
        var progressBar = $('.wpaicg-convert-bar');
        var wpaicg_add_data = $('.normal .wpaicg_add_data');
        var wpaicg_add_gtp_turbo_data = $('.gpt-turbo .wpaicg_add_data');
        var wpaicg_ajax_url = '<?php echo admin_url('admin-ajax.php') ?>';
        var form = $('#wpaicg_form_data');
        var wpaicg_item = '<div class="wpaicg_data_item wpaicg_data"><div><input type="text" name="data[0][prompt]" class="regular-text wpaicg_data_prompt" placeholder="<?php echo esc_html__('Prompt', 'gpt3-ai-content-generator') ?>"> </div><div><input type="text" name="data[0][completion]" class="regular-text wpaicg_data_completion" placeholder="<?php echo esc_html__('Completion', 'gpt3-ai-content-generator') ?>"><span class="button button-link-delete">×</span></div></div>';
        var wpaicg_gpt_turbo_item = '<div class="wpaicg_data_item wpaicg_data"><div><input type="text" name="data[0][system]" class="regular-text wpaicg_data_system" placeholder="<?php echo esc_html__('Content', 'gpt3-ai-content-generator') ?>"> </div><div><input type="text" name="data[0][user]" class="regular-text wpaicg_data_user" placeholder="<?php echo esc_html__('Content', 'gpt3-ai-content-generator') ?>"> </div><div><input type="text" name="data[0][assistant]" class="regular-text wpaicg_data_assistant" placeholder="<?php echo esc_html__('Content', 'gpt3-ai-content-generator') ?>"><span class="button button-link-delete">×</span></div></div>';
        wpaicg_add_data.click(function (){
            $('.wpaicg_list_data.normal').find('.wpaicg_data_list').append(wpaicg_item);
            if($('.wpaicg_list_data.normal').find('.wpaicg_data_list').find('.wpaicg_data').length > 1){
                $('.wpaicg_list_data.normal').find('span.button-link-delete').show();
            } else {
                $('.wpaicg_list_data.normal').find('span.button-link-delete').hide();
            }
            wpaicgSortData();
        });
        wpaicg_add_gtp_turbo_data.click(function (){
            $('.wpaicg_list_data.gpt-turbo').find('.wpaicg_data_list').append(wpaicg_gpt_turbo_item);
            if($('.wpaicg_list_data.gpt-turbo').find('.wpaicg_data_list').find('.wpaicg_data').length > 1){
                $('.wpaicg_list_data.gpt-turbo').find('span.button-link-delete').show();
            } else {
                $('.wpaicg_list_data.gpt-turbo').find('span.button-link-delete').hide();
            }
            wpaicgSortData();
        });
        $(document).on('click','.wpaicg_data span', function (e){
            if($(this).closest('.wpaicg_data_list').find('.wpaicg_data').length < 3){
                $(this).closest('.wpaicg_data_list').find('span.button-link-delete').hide();
            } else {
                $(this).closest('.wpaicg_data_list').find('span.button-link-delete').show();
            }
            $(e.currentTarget).parent().parent().remove();
            wpaicgSortData();
        });

        function wpaicgFileUpload(data, btn){
            var wpaicg_upload_convert_index = parseInt($('#wpaicg_upload_convert_index').val());
            $.ajax({
                url: wpaicg_ajax_url,
                data: data,
                type: 'POST',
                dataType: 'JSON',
                success: function (res){
                    if(res.status === 'success'){
                        if(res.next === 'DONE'){
                            if($('select[name=model]').val() === 'gpt-3.5-turbo'){
                                $('.wpaicg_list_data.gpt-turbo .wpaicg_data_list').html(wpaicg_gpt_turbo_item);
                            } else {
                                $('.wpaicg_list_data.normal .wpaicg_data_list').html(wpaicg_item);
                            }
                            $('.wpaicg-upload-message').html('<?php echo esc_html__('Upload successful. Now, head over to the Datasets tab and create your fine-tuning using this data.', 'gpt3-ai-content-generator') ?>');
                            progressBar.find('small').html('100%');
                            progressBar.find('span').css('width','100%');
                            wpaicgRmLoading(btn);
                            setTimeout(function (){
                                $('#wpaicg_upload_convert_line').val('0');
                                $('#wpaicg_upload_convert_index').val('1');
                                progressBar.hide();
                                progressBar.removeClass('wpaicg_error')
                                progressBar.find('span').css('width',0);
                                progressBar.find('small').html('0%');
                            },2000);
                        }
                        else{
                            $('#wpaicg_upload_convert_line').val(res.next);
                            $('#wpaicg_upload_convert_index').val(wpaicg_upload_convert_index+1);
                            var data = $('#wpaicg_upload_convert').serialize();
                            wpaicgFileUpload(data,btn);
                        }
                    }
                    else{
                        progressBar.addClass('wpaicg_error');
                        wpaicgRmLoading(btn);
                        alert(res.msg);
                    }
                },
                error: function (){
                    progressBar.addClass('wpaicg_error');
                    wpaicgRmLoading(btn);
                    alert('<?php echo esc_html__('Something went wrong', 'gpt3-ai-content-generator') ?>');
                }
            })
        }

        function wpaicgProcessData(lists,start,file,btn){
            var purpose = $('select[name=purpose]').val();
            var model = $('select[name=model]').val();
            var name = $('input[name=custom]').val();

            var data = {
                action: 'wpaicg_data_insert',
                model: model,
                file: file,
                nonce: '<?php echo wp_create_nonce('wpaicg-ajax-nonce') ?>'
            };

            if(model === 'gpt-3.5-turbo'){
                data['messages'] = lists[start].messages;
            } else {
                data['prompt'] = lists[start].prompt;
                data['completion'] = lists[start].completion;
            }

            $.ajax({
                url: wpaicg_ajax_url,
                data: data,
                dataType: 'JSON',
                type: 'POST',
                success: function (res){
                    if(res.status === 'success'){
                        var percent = Math.ceil((start+1)*90/lists.length);
                        progressBar.find('small').html(percent+'%');
                        progressBar.find('span').css('width',percent+'%');
                        if((start+1) === lists.length){
                            /*Save file done*/
                            $('#wpaicg_upload_convert input[name=model]').val(model);
                            $('#wpaicg_upload_convert input[name=purpose]').val(purpose);
                            $('#wpaicg_upload_convert input[name=custom]').val(name);
                            $('#wpaicg_upload_convert input[name=file]').val(res.file);
                            var data = $('#wpaicg_upload_convert').serialize();
                            wpaicgFileUpload(data, btn);
                        }
                        else{
                            file = res.file;
                            wpaicgProcessData(lists,(start+1),file, btn);
                        }
                    }
                    else{
                        progressBar.addClass('wpaicg_error');
                        wpaicgRmLoading(btn);
                        alert(res.msg);
                    }
                },
                error: function (){
                    progressBar.addClass('wpaicg_error');
                    wpaicgRmLoading(btn);
                    alert('<?php echo esc_html__('Something went wrong', 'gpt3-ai-content-generator') ?>');
                }
            })
        }
        form.on('submit', function (){
            var total = 0;
            var lists = [];
            var btn = form.find('.wpaicg_submit');
            if($('select[name="model"]').val() == 'gpt-3.5-turbo'){
                $('.wpaicg_list_data.gpt-turbo .wpaicg_data').each(function (idx, item){
                    var item_system = $(item).find('.wpaicg_data_system').val();
                    var item_user = $(item).find('.wpaicg_data_user').val();
                    var item_assistant = $(item).find('.wpaicg_data_assistant').val();
                    if(item_system !== '' && item_user !== '' && item_assistant !== ''){
                        total += 1;
                        lists.push({"messages":[{role: "system", content: item_system }, {role: "user", content: item_user }, {role: "assistant", content: item_assistant }]})
                    }
                });

            } else {
                $('.wpaicg_list_data.normal .wpaicg_data').each(function (idx, item){
                    var item_prompt = $(item).find('.wpaicg_data_prompt').val();
                    var item_completion = $(item).find('.wpaicg_data_completion').val();
                    if(item_prompt !== '' && item_completion !== ''){
                        total += 1;
                        lists.push({prompt: item_prompt,completion: item_completion })
                    }
                });
            }

            if(total >= 10){
                $('#wpaicg_upload_convert_line').val('0');
                $('#wpaicg_upload_convert_index').val('1');
                $('.wpaicg-upload-message').empty();
                progressBar.show();
                progressBar.removeClass('wpaicg_error')
                progressBar.find('span').css('width',0);
                progressBar.find('small').html('0%');
                wpaicgLoading(btn)
                wpaicgProcessData(lists,0,'',btn);
            }
            else{
                alert('<?php echo esc_html__('Please insert least 10 rows', 'gpt3-ai-content-generator') ?>');
            }
            return false;
        })

        $(document).on('change','select[name="model"]', function (e){
            if($(this).val() === 'gpt-3.5-turbo'){
                $('.wpaicg_list_data.normal').hide();
                $('.wpaicg_list_data.gpt-turbo').show();
            } else {
                $('.wpaicg_list_data.gpt-turbo').hide();
                $('.wpaicg_list_data.normal').show();
            }
        });

    })
</script>
