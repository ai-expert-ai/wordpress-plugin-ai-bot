<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
$languages = [
    'en'  => 'English',
    'af'  => 'Afrikaans',
    'ar'  => 'Arabic',
    'an'  => 'Armenian',
    'bs'  => 'Bosnian',
    'bg'  => 'Bulgarian',
    'zh'  => 'Chinese (Simplified)',
    'zt'  => 'Chinese (Traditional)',
    'hr'  => 'Croatian',
    'cs'  => 'Czech',
    'da'  => 'Danish',
    'nl'  => 'Dutch',
    'et'  => 'Estonian',
    'fil' => 'Filipino',
    'fi'  => 'Finnish',
    'fr'  => 'French',
    'de'  => 'German',
    'el'  => 'Greek',
    'he'  => 'Hebrew',
    'hi'  => 'Hindi',
    'hu'  => 'Hungarian',
    'id'  => 'Indonesian',
    'it'  => 'Italian',
    'ja'  => 'Japanese',
    'ko'  => 'Korean',
    'lv'  => 'Latvian',
    'lt'  => 'Lithuanian',
    'ms'  => 'Malay',
    'no'  => 'Norwegian',
    'fa'  => 'Persian',
    'pl'  => 'Polish',
    'pt'  => 'Portuguese',
    'ro'  => 'Romanian',
    'ru'  => 'Russian',
    'sr'  => 'Serbian',
    'sk'  => 'Slovak',
    'sl'  => 'Slovenian',
    'es'  => 'Spanish',
    'sv'  => 'Swedish',
    'th'  => 'Thai',
    'tr'  => 'Turkish',
    'uk'  => 'Ukrainian',
    'vi'  => 'Vietnamese',
];
$currentLanguage = esc_html( $existingValue['wpai_language'] );
?>
<div id="tabs-2">
    <div class="wpcgai_form_row">
        <p><?php 
echo  esc_html__( 'This tab allows you to set and save default values for both Express Mode and Auto Content Writer. Changes made here will be applied to both modules.', 'gpt3-ai-content-generator' ) ;
?></p>
        <p><b><?php 
echo  esc_html__( 'Language, Style and Tone', 'gpt3-ai-content-generator' ) ;
?></b></p>
        <label class="wpcgai_label">Language:</label>
        <select class="regular-text" id="label_wpai_language" name="wpaicg_settings[wpai_language]">
            <?php 
foreach ( $languages as $code => $displayName ) {
    ?>
                <option value="<?php 
    echo  esc_attr( $code ) ;
    ?>" <?php 
    echo  ( esc_attr( $code ) === $currentLanguage ? 'selected' : '' ) ;
    ?>><?php 
    echo  esc_html( $displayName ) ;
    ?></option>
            <?php 
}
?>
        </select>

        <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/content-writer/express-mode/language-style-tone#language" target="_blank">?</a>
    </div>


<?php 
$writing_styles = array(
    'infor'  => 'Informative',
    'acade'  => 'Academic',
    'analy'  => 'Analytical',
    'anect'  => 'Anecdotal',
    'argum'  => 'Argumentative',
    'artic'  => 'Articulate',
    'biogr'  => 'Biographical',
    'blog'   => 'Blog',
    'casua'  => 'Casual',
    'collo'  => 'Colloquial',
    'compa'  => 'Comparative',
    'conci'  => 'Concise',
    'creat'  => 'Creative',
    'criti'  => 'Critical',
    'descr'  => 'Descriptive',
    'detai'  => 'Detailed',
    'dialo'  => 'Dialogue',
    'direct' => 'Direct',
    'drama'  => 'Dramatic',
    'evalu'  => 'Evaluative',
    'emoti'  => 'Emotional',
    'expos'  => 'Expository',
    'ficti'  => 'Fiction',
    'histo'  => 'Historical',
    'journ'  => 'Journalistic',
    'lette'  => 'Letter',
    'lyric'  => 'Lyrical',
    'metaph' => 'Metaphorical',
    'monol'  => 'Monologue',
    'narra'  => 'Narrative',
    'news'   => 'News',
    'objec'  => 'Objective',
    'pasto'  => 'Pastoral',
    'perso'  => 'Personal',
    'persu'  => 'Persuasive',
    'poeti'  => 'Poetic',
    'refle'  => 'Reflective',
    'rheto'  => 'Rhetorical',
    'satir'  => 'Satirical',
    'senso'  => 'Sensory',
    'simpl'  => 'Simple',
    'techn'  => 'Technical',
    'theore' => 'Theoretical',
    'vivid'  => 'Vivid',
    'busin'  => 'Business',
    'repor'  => 'Report',
    'resea'  => 'Research',
);
// Get the existing value from settings
$currentStyle = esc_html( $existingValue['wpai_writing_style'] );
?>
    <div class="wpcgai_form_row">
        <label class="wpcgai_label">Writing Style:</label>
        <select class="regular-text" id="label_wpai_writing_style" name="wpaicg_settings[wpai_writing_style]">
            <?php 
foreach ( $writing_styles as $code => $displayName ) {
    ?>
                <option value="<?php 
    echo  esc_attr( $code ) ;
    ?>" <?php 
    echo  ( esc_attr( $code ) === $currentStyle ? 'selected' : '' ) ;
    ?>><?php 
    echo  esc_html( $displayName ) ;
    ?></option>
                <?php 
}
?>
        </select>
        <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/content-writer/express-mode/language-style-tone#writing-style" target="_blank">?</a>
    </div>
<?php 
$writing_tones = array(
    'formal'        => 'Formal',
    'asser'         => 'Assertive',
    'authoritative' => 'Authoritative',
    'cheer'         => 'Cheerful',
    'confident'     => 'Confident',
    'conve'         => 'Conversational',
    'factual'       => 'Factual',
    'friendly'      => 'Friendly',
    'humor'         => 'Humorous',
    'informal'      => 'Informal',
    'inspi'         => 'Inspirational',
    'neutr'         => 'Neutral',
    'nostalgic'     => 'Nostalgic',
    'polite'        => 'Polite',
    'profe'         => 'Professional',
    'romantic'      => 'Romantic',
    'sarca'         => 'Sarcastic',
    'scien'         => 'Scientific',
    'sensit'        => 'Sensitive',
    'serious'       => 'Serious',
    'sincere'       => 'Sincere',
    'skept'         => 'Skeptical',
    'suspenseful'   => 'Suspenseful',
    'sympathetic'   => 'Sympathetic',
    'curio'         => 'Curious',
    'disap'         => 'Disappointed',
    'encou'         => 'Encouraging',
    'optim'         => 'Optimistic',
    'surpr'         => 'Surprised',
    'worry'         => 'Worried',
);
$currentTone = esc_html( $existingValue['wpai_writing_tone'] );
?>
    <div class="wpcgai_form_row">
        <label class="wpcgai_label"><?php 
echo  esc_html__( 'Writing Tone', 'gpt3-ai-content-generator' ) ;
?>:</label>
        <select class="regular-text" id="label_wpai_writing_tone" name="wpaicg_settings[wpai_writing_tone]">
            <?php 
foreach ( $writing_tones as $code => $displayName ) {
    ?>
                <option value="<?php 
    echo  esc_attr( $code ) ;
    ?>" <?php 
    echo  ( $code === $currentTone ? 'selected' : '' ) ;
    ?>>
                    <?php 
    echo  esc_html__( $displayName, 'gpt3-ai-content-generator' ) ;
    ?>
                </option>
            <?php 
}
?>
        </select>
        <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/content-writer/express-mode/language-style-tone#writing-tone" target="_blank">?</a>
    </div>
    <hr>
    <p><b><?php 
echo  esc_html__( 'Headings', 'gpt3-ai-content-generator' ) ;
?></b></p>
    <div class="wpcgai_form_row">
        <label class="wpcgai_label"><?php 
echo  esc_html__( 'Number of Headings', 'gpt3-ai-content-generator' ) ;
?>:</label>
        <input type="number" min="1" max="15" class="regular-text" id="label_wpai_number_of_heading"  name="wpaicg_settings[wpai_number_of_heading]" value="<?php 
echo  esc_html( $existingValue['wpai_number_of_heading'] ) ;
?>" placeholder="e.g. 5" >
        <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/content-writer/express-mode/headings#number-of-headings" target="_blank">?</a>
    </div>

<?php 
$heading_tags = array(
    'h1',
    'h2',
    'h3',
    'h4',
    'h5',
    'h6'
);
$currentTag = esc_html( $existingValue['wpai_heading_tag'] );
?>
    <div class="wpcgai_form_row">
        <label class="wpcgai_label"><?php 
echo  esc_html__( 'Heading Tag', 'gpt3-ai-content-generator' ) ;
?>:</label>
        <select class="regular-text" id="label_wpai_heading_tag" name="wpaicg_settings[wpai_heading_tag]">
            <?php 
foreach ( $heading_tags as $tag ) {
    ?>
                <option value="<?php 
    echo  esc_attr( $tag ) ;
    ?>" <?php 
    echo  ( $tag === $currentTag ? 'selected' : '' ) ;
    ?>>
                    <?php 
    echo  $tag ;
    ?>
                </option>
            <?php 
}
?>
        </select>
        <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/content-writer/express-mode/headings#heading-tag" target="_blank">?</a>
    </div>

    <div class="wpcgai_form_row">
        <label class="wpcgai_label"><?php 
echo  esc_html__( 'Outline Editor', 'gpt3-ai-content-generator' ) ;
?>:</label>
        <input type="checkbox" id="label_wpai_modify_headings" name="wpaicg_settings[wpai_modify_headings]"
               value="<?php 
echo  esc_html( $existingValue['wpai_modify_headings'] ) ;
?>"
            <?php 
echo  ( esc_html( $existingValue['wpai_modify_headings'] ) == 1 ? " checked" : "" ) ;
?>
        />
        <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/content-writer/express-mode/headings#outline-editor" target="_blank">?</a>
    </div>
    <hr>
    <p><b><?php 
echo  esc_html__( 'Additional Content', 'gpt3-ai-content-generator' ) ;
?></b></p>
    <div class="wpcgai_form_row">
        <label class="wpcgai_label"><?php 
echo  esc_html__( 'Add Tagline?', 'gpt3-ai-content-generator' ) ;
?>:</label>
        <input type="checkbox" id="label_wpai_add_tagline" name="wpaicg_settings[wpai_add_tagline]"
               value="<?php 
echo  esc_html( $existingValue['wpai_add_tagline'] ) ;
?>"
            <?php 
echo  ( esc_html( $existingValue['wpai_add_tagline'] ) == 1 ? " checked" : "" ) ;
?>
        />
        <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/content-writer/express-mode/additional-content#enable-or-disable-tagline" target="_blank">?</a>
    </div>
    <div class="wpcgai_form_row">
        <label class="wpcgai_label"><?php 
echo  esc_html__( 'Add Q&A?', 'gpt3-ai-content-generator' ) ;
?>:</label>
        <?php 
?>
            <input type="checkbox" value="0" disabled><?php 
echo  esc_html__( 'Available in Pro', 'gpt3-ai-content-generator' ) ;
?>
            <?php 
?>
        <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/content-writer/express-mode/qa#enable-or-disable-qa" target="_blank">?</a>
    </div>
    <div class="wpcgai_form_row">
        <label class="wpcgai_label"><?php 
echo  esc_html__( 'Make Keywords Bold?', 'gpt3-ai-content-generator' ) ;
?>:</label>
        <?php 
?>
            <input type="checkbox" value="0" disabled class="pro_chk"><?php 
echo  esc_html__( 'Available in Pro', 'gpt3-ai-content-generator' ) ;
?>
            <?php 
?>
        <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/content-writer/express-mode/keywords#add-keywords" target="_blank">?</a>
    </div>

    <div class="wpcgai_form_row">
        <label class="wpcgai_label"><?php 
echo  esc_html__( 'Call-to-Action Position', 'gpt3-ai-content-generator' ) ;
?>:</label>
        <select class="regular-text" id="label_wpai_cta_pos" name="wpaicg_settings[wpai_cta_pos]" >
            <option value="beg" <?php 
echo  ( esc_html( $existingValue['wpai_cta_pos'] ) == 'beg' ? 'selected' : '' ) ;
?>><?php 
echo  esc_html__( 'Beginning', 'gpt3-ai-content-generator' ) ;
?></option>
            <option value="end" <?php 
echo  ( esc_html( $existingValue['wpai_cta_pos'] ) == 'end' ? 'selected' : '' ) ;
?>><?php 
echo  esc_html__( 'End', 'gpt3-ai-content-generator' ) ;
?></option>
        </select>
        <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/content-writer/express-mode/links#adding-call-to-action" target="_blank">?</a>
    </div>
    <hr>
    <p><strong><?php 
echo  esc_html__( 'Table of Contents', 'gpt3-ai-content-generator' ) ;
?></strong></p>
    <div class="wpcgai_form_row">
        <label class="wpcgai_label"><?php 
echo  esc_html__( 'Add ToC?', 'gpt3-ai-content-generator' ) ;
?>:</label>
        <?php 
$wpaicg_toc = get_option( 'wpaicg_toc', false );
?>
        <input<?php 
echo  ( $wpaicg_toc ? ' checked' : '' ) ;
?> type="checkbox" value="1" name="wpaicg_toc">
        <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/content-writer/express-mode/table-of-contents#enable-or-disable-toc" target="_blank">?</a>
    </div>

    <div class="wpcgai_form_row">
        <label class="wpcgai_label"><?php 
echo  esc_html__( 'ToC Title', 'gpt3-ai-content-generator' ) ;
?>:</label>
        <?php 
$wpaicg_toc_title = get_option( 'wpaicg_toc_title', 'Table of Contents' );
?>
        <input type="text" class="regular-text" value="<?php 
echo  esc_html( $wpaicg_toc_title ) ;
?>" name="wpaicg_toc_title">
        <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/content-writer/express-mode/table-of-contents#customize-toc-title" target="_blank">?</a>
    </div>
    <div class="wpcgai_form_row">
        <label class="wpcgai_label"><?php 
echo  esc_html__( 'ToC Title Tag', 'gpt3-ai-content-generator' ) ;
?>:</label>
        <?php 
$heading_tags = array(
    'h1',
    'h2',
    'h3',
    'h4',
    'h5',
    'h6'
);
$wpaicg_toc_title_tag = esc_html( get_option( 'wpaicg_toc_title_tag', 'h2' ) );
?>

        <select class="regular-text" name="wpaicg_toc_title_tag">
            <?php 
foreach ( $heading_tags as $tag ) {
    ?>
                <option value="<?php 
    echo  esc_attr( $tag ) ;
    ?>" <?php 
    echo  ( $tag === $wpaicg_toc_title_tag ? 'selected' : '' ) ;
    ?>>
                    <?php 
    echo  $tag ;
    ?>
                </option>
            <?php 
}
?>
        </select>
        <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/content-writer/express-mode/table-of-contents#toc-title-tag" target="_blank">?</a>
    </div>
    <hr>
    <p><b><?php 
echo  esc_html__( 'Introduction', 'gpt3-ai-content-generator' ) ;
?></b></p>
    <div class="wpcgai_form_row">
        <label class="wpcgai_label"><?php 
echo  esc_html__( 'Add Introduction?', 'gpt3-ai-content-generator' ) ;
?>:</label>
        <input type="checkbox" id="label_wpai_add_intro" name="wpaicg_settings[wpai_add_intro]"
               value="<?php 
echo  esc_html( $existingValue['wpai_add_intro'] ) ;
?>"
            <?php 
echo  ( esc_html( $existingValue['wpai_add_intro'] ) == 1 ? " checked" : "" ) ;
?>
        />
        <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/content-writer/express-mode/additional-content#enable-or-disable-introduction" target="_blank">?</a>
    </div>
    <div class="wpcgai_form_row">
        <label class="wpcgai_label"><?php 
echo  esc_html__( 'Intro Title Tag', 'gpt3-ai-content-generator' ) ;
?>:</label>
        <?php 
$heading_tags = array(
    'h1',
    'h2',
    'h3',
    'h4',
    'h5',
    'h6'
);
$wpaicg_intro_title_tag = esc_html( get_option( 'wpaicg_intro_title_tag', 'h2' ) );
?>

        <select class="regular-text" name="wpaicg_intro_title_tag">
            <?php 
foreach ( $heading_tags as $tag ) {
    ?>
                <option value="<?php 
    echo  esc_attr( $tag ) ;
    ?>" <?php 
    echo  ( $tag === $wpaicg_intro_title_tag ? 'selected' : '' ) ;
    ?>>
                    <?php 
    echo  $tag ;
    ?>
                </option>
            <?php 
}
?>
        </select>

        <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/content-writer/express-mode/additional-content#setting-the-heading-tag-for-introduction" target="_blank">?</a>
    </div>
    <?php 
$wpaicg_hide_conclusion = get_option( 'wpaicg_hide_conclusion', false );
$wpaicg_hide_introduction = get_option( 'wpaicg_hide_introduction', false );
?>
    <div class="wpcgai_form_row">
        <label class="wpcgai_label"><?php 
echo  esc_html__( 'Hide Introduction Title', 'gpt3-ai-content-generator' ) ;
?>:</label>
        <input type="checkbox" name="wpaicg_hide_introduction" value="1"<?php 
echo  ( $wpaicg_hide_introduction ? " checked" : "" ) ;
?>/>
        <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/content-writer/express-mode/additional-content#show-or-hide-introduction-title" target="_blank">?</a>
    </div>
    <hr>
    <p><strong><?php 
echo  esc_html__( 'Conclusion', 'gpt3-ai-content-generator' ) ;
?></strong></p>
    <div class="wpcgai_form_row">
        <label class="wpcgai_label"><?php 
echo  esc_html__( 'Add Conclusion?', 'gpt3-ai-content-generator' ) ;
?>:</label>
        <input type="checkbox" id="label_wpai_add_conclusion" name="wpaicg_settings[wpai_add_conclusion]"
               value="<?php 
echo  esc_html( $existingValue['wpai_add_conclusion'] ) ;
?>"
            <?php 
echo  ( esc_html( $existingValue['wpai_add_conclusion'] ) == 1 ? " checked" : "" ) ;
?>
        />
        <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/content-writer/express-mode/additional-content#enable-or-disable-conclusion" target="_blank">?</a>
    </div>
    <!-- wpaicg_conclusion_title_tag -->
    <div class="wpcgai_form_row">
        <label class="wpcgai_label"><?php 
echo  esc_html__( 'Conclusion Title Tag', 'gpt3-ai-content-generator' ) ;
?>:</label>
        <?php 
$heading_tags = array(
    'h1',
    'h2',
    'h3',
    'h4',
    'h5',
    'h6'
);
$wpaicg_conclusion_title_tag = esc_html( get_option( 'wpaicg_conclusion_title_tag', 'h2' ) );
?>

        <select class="regular-text" name="wpaicg_conclusion_title_tag">
            <?php 
foreach ( $heading_tags as $tag ) {
    ?>
                <option value="<?php 
    echo  esc_attr( $tag ) ;
    ?>" <?php 
    echo  ( $tag === $wpaicg_conclusion_title_tag ? 'selected' : '' ) ;
    ?>>
                    <?php 
    echo  $tag ;
    ?>
                </option>
            <?php 
}
?>
        </select>
        <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/content-writer/express-mode/additional-content#setting-the-heading-tag-for-conclusion" target="_blank">?</a>
    </div>

    <div class="wpcgai_form_row">
        <label class="wpcgai_label"><?php 
echo  esc_html__( 'Hide Conclusion Title', 'gpt3-ai-content-generator' ) ;
?>:</label>
        <input type="checkbox" name="wpaicg_hide_conclusion" value="1"<?php 
echo  ( $wpaicg_hide_conclusion ? " checked" : "" ) ;
?>/>
        <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/content-writer/express-mode/additional-content#show-or-hide-conclusion-title" target="_blank">?</a>
    </div>
    <hr>
    <p><strong><?php 
echo  esc_html__( 'Custom Prompt for Express Mode', 'gpt3-ai-content-generator' ) ;
?></strong></p>
    <div class="wpcgai_form_row">
        <?php 
$wpaicg_content_custom_prompt_enable = get_option( 'wpaicg_content_custom_prompt_enable', false );
$wpaicg_content_custom_prompt = get_option( 'wpaicg_content_custom_prompt', '' );
if ( empty($wpaicg_content_custom_prompt) ) {
    $wpaicg_content_custom_prompt = \WPAICG\WPAICG_Custom_Prompt::get_instance()->wpaicg_default_custom_prompt;
}
?>
        <div class="mb-5">
            <label><input<?php 
echo  ( $wpaicg_content_custom_prompt_enable ? ' checked' : '' ) ;
?> type="checkbox" class="wpaicg_meta_custom_prompt_enable" name="wpaicg_content_custom_prompt_enable">&nbsp;Enable</label>
            <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/content-writer/express-mode/custom-prompt" target="_blank">?</a>
        </div>
        <div class="wpaicg_meta_custom_prompt_box" style="<?php 
echo  ( isset( $wpaicg_content_custom_prompt_enable ) && $wpaicg_content_custom_prompt_enable ? '' : 'display:none' ) ;
?>">
            <label><?php 
echo  esc_html__( 'Custom Prompt', 'gpt3-ai-content-generator' ) ;
?></label>
            <textarea rows="20" class="wpaicg_meta_custom_prompt" name="wpaicg_content_custom_prompt"><?php 
echo  esc_html( str_replace( "\\", '', $wpaicg_content_custom_prompt ) ) ;
?></textarea>
            <?php 

if ( \WPAICG\wpaicg_util_core()->wpaicg_is_pro() ) {
    ?>
                <div>
                    <?php 
    echo  sprintf(
        esc_html__( 'Make sure to include %s in your prompt. You can also incorporate %s and %s to further customize your prompt.', 'gpt3-ai-content-generator' ),
        '<code>[title]</code>',
        '<code>[keywords_to_include]</code>',
        '<code>[keywords_to_avoid]</code>'
    ) ;
    ?>
                </div>
            <?php 
} else {
    ?>
                <div>
                    <?php 
    echo  sprintf( esc_html__( 'Ensure %s is included in your prompt.', 'gpt3-ai-content-generator' ), '<code>[title]</code>' ) ;
    ?>
                </div>
            <?php 
}

?>
            <button style="color: #fff;background: #df0707;border-color: #df0707;" data-prompt="<?php 
echo  esc_html( \WPAICG\WPAICG_Custom_Prompt::get_instance()->wpaicg_default_custom_prompt ) ;
?>" class="button wpaicg_meta_custom_prompt_reset" type="button"><?php 
echo  esc_html__( 'Reset', 'gpt3-ai-content-generator' ) ;
?></button>
            <div class="wpaicg_meta_custom_prompt_auto_error"></div>
        </div>
    </div>
</div>
