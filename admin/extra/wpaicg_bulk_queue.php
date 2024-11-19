<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;
if(isset($_GET['sub_action']) && sanitize_text_field($_GET['sub_action']) == 'delete' && isset($_GET['id']) && !empty($_GET['id'])){
    $wpaicg_delete_id = sanitize_text_field($_GET['id']);
    if(!wp_verify_nonce($_GET['_wpnonce'], 'wpaicg_delete_'.$wpaicg_delete_id)){
        die(esc_html__('Nonce verification failed','gpt3-ai-content-generator'));
    }
    $wpdb->delete($wpdb->posts,array('ID' => $wpaicg_delete_id));
    $wpdb->delete($wpdb->posts,array('post_type' => 'wpaicg_bulk', 'post_parent' => $wpaicg_delete_id));
    /*Search Twitter Bot*/
    $wpaicg_twitter_track = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value=%d", 'wpaicg_twitter_track', $wpaicg_delete_id));
    if($wpaicg_twitter_track){
        delete_post_meta($wpaicg_twitter_track->post_id,'wpaicg_tweeted');
    }
    echo '<script>window.location.href = "'.admin_url('admin.php?page=wpaicg_bulk_content&wpaicg_action=tracking').'";</script>';
    exit;
}
if (isset($_GET['wsearch']) && !empty($_GET['wsearch']) && !wp_verify_nonce($_GET['wpaicg_nonce'], 'wpaicg_queue_search_nonce')) {
    die(WPAICG_NONCE_ERROR);
}

$search = isset($_GET['wsearch']) && !empty($_GET['wsearch']) ? sanitize_text_field($_GET['wsearch']) : '';
$wpaicg_tracking_page = isset($_GET['wpage']) && !empty($_GET['wpage']) ? sanitize_text_field($_GET['wpage']) : 1;
$wpaicg_tracking_per_page = 10;
$wpaicg_tracking_offset = ( $wpaicg_tracking_page * $wpaicg_tracking_per_page ) - $wpaicg_tracking_per_page;
$wpaicg_sum_length = "SELECT SUM(meta_value) FROM ".$wpdb->postmeta." l LEFT JOIN ".$wpdb->posts." lp ON lp.ID=l.post_id WHERE l.meta_key='_wpaicg_generator_length' AND lp.post_parent=p.ID";
$wpaicg_sum_time = "SELECT SUM(meta_value) FROM ".$wpdb->postmeta." l LEFT JOIN ".$wpdb->posts." lp ON lp.ID=l.post_id WHERE l.meta_key='_wpaicg_generator_run' AND lp.post_parent=p.ID";
$wpaicg_sum_tokens = "SELECT SUM(meta_value) FROM ".$wpdb->postmeta." l LEFT JOIN ".$wpdb->posts." lp ON lp.ID=l.post_id WHERE l.meta_key='_wpaicg_generator_token' AND lp.post_parent=p.ID";

$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

$source = isset($_GET['source']) ? sanitize_text_field($_GET['source']) : '';

$where = " (p.post_type='wpaicg_tracking' OR p.post_type='wpaicg_twitter') AND p.post_status IN ('publish','pending','draft','trash')";

if(!empty($search)){
    $where .= $wpdb->prepare(" AND p.post_title LIKE %s",'%'.$wpdb->esc_like($search).'%');
}

if(!empty($status)){
    $where .= $wpdb->prepare(" AND p.post_status = %s", $status);
}

if(!empty($source)){
    if($source === "twitter"){
        $where .= " AND p.post_type='wpaicg_twitter'";
    } elseif($source === "csv") {
        $where .= " AND p.post_mime_type='csv'";
    } elseif($source === "editor") {
        $where .= " AND (p.post_mime_type='' OR p.post_mime_type='editor')";
    } elseif($source === "rss") {
        $where .= " AND p.post_mime_type='rss'";
    } elseif($source === "sheets") {
        $where .= " AND p.post_mime_type='sheets'";
    } elseif($source === "multi") {
        $where .= " AND p.post_mime_type='multi'";
    }
}


$wpaicg_trackings_sql = $wpdb->prepare("SELECT p.*,(".$wpaicg_sum_length.") as word_count,(".$wpaicg_sum_time.") as time_run,(".$wpaicg_sum_tokens.") as total_tokens FROM ".$wpdb->posts." p WHERE ".$where." ORDER BY p.post_date DESC LIMIT %d, %d", $wpaicg_tracking_offset, $wpaicg_tracking_per_page);
$wpaicg_trackings_total_sql = "SELECT COUNT(*) FROM ".$wpdb->posts." p WHERE ".$where;

$wpaicg_trackings = $wpdb->get_results($wpaicg_trackings_sql);
$wpaicg_trackings_total = $wpdb->get_var( $wpaicg_trackings_total_sql );
?>
<form action="" method="get">
    <input type="hidden" name="page" value="wpaicg_bulk_content">
    <input type="hidden" name="wpaicg_action" value="tracking">
    <?php wp_nonce_field('wpaicg_queue_search_nonce', 'wpaicg_nonce'); ?>
    <div class="wpaicg-d-flex mb-5">
        <input style="width: 100%" value="<?php echo esc_html($search)?>" class="regular-text" name="wsearch" type="text" placeholder="<?php echo esc_html__('Type for search','gpt3-ai-content-generator')?>">
        <!-- Status filter -->
        <select name="status" class="wpaicg-status-filter">
            <option value="" <?php echo (empty($status) ? 'selected' : ''); ?>><?php echo esc_html__('Filter by Status','gpt3-ai-content-generator')?></option>
            <option value="publish" <?php echo ($status === 'publish' ? 'selected' : ''); ?>><?php echo esc_html__('Completed','gpt3-ai-content-generator')?></option>
            <option value="pending" <?php echo ($status === 'pending' ? 'selected' : ''); ?>><?php echo esc_html__('Pending','gpt3-ai-content-generator')?></option>
        </select>
        <!-- Source filter -->
        <select name="source" class="wpaicg-source-filter">
            <option value="" <?php echo (empty($source) ? 'selected' : ''); ?>><?php echo esc_html__('Filter by Source','gpt3-ai-content-generator')?></option>
            
            <!-- Always available options (non-Pro) -->
            <option value="editor" <?php echo ($source === 'editor' ? 'selected' : ''); ?>><?php echo esc_html__('Bulk Editor','gpt3-ai-content-generator')?></option>
            <option value="csv" <?php echo ($source === 'csv' ? 'selected' : ''); ?>><?php echo esc_html__('CSV','gpt3-ai-content-generator')?></option>
            <option value="multi" <?php echo ($source === 'multi' ? 'selected' : ''); ?>><?php echo esc_html__('Copy-Paste','gpt3-ai-content-generator')?></option>

            <!-- Pro-only options -->
            <?php if(\WPAICG\wpaicg_util_core()->wpaicg_is_pro()): ?>
                <option value="twitter" <?php echo ($source === 'twitter' ? 'selected' : ''); ?>><?php echo esc_html__('Twitter','gpt3-ai-content-generator')?></option>
                <option value="rss" <?php echo ($source === 'rss' ? 'selected' : ''); ?>><?php echo esc_html__('RSS','gpt3-ai-content-generator')?></option>
                <option value="sheets" <?php echo ($source === 'sheets' ? 'selected' : ''); ?>><?php echo esc_html__('Google Sheets','gpt3-ai-content-generator')?></option>
            <?php endif; ?>
        </select>

        <button class="button button-primary"><?php echo esc_html__('Search','gpt3-ai-content-generator')?></button>
    </div>
</form>
<?php

if (!empty($source)) {
    // Using a switch case to map the source values to their display names
    switch($source) {
        case "twitter":
            $source_display = esc_html__('Twitter', 'gpt3-ai-content-generator');
            break;
        case "csv":
            $source_display = esc_html__('CSV', 'gpt3-ai-content-generator');
            break;
        case "editor":
            $source_display = esc_html__('Bulk Editor', 'gpt3-ai-content-generator');
            break;
        case "rss":
            $source_display = esc_html__('RSS', 'gpt3-ai-content-generator');
            break;
        case "sheets":
            $source_display = esc_html__('Google Sheets', 'gpt3-ai-content-generator');
            break;
        case "multi":
            $source_display = esc_html__('Copy-Paste', 'gpt3-ai-content-generator');
            break;
        default:
            $source_display = esc_html__('Unknown Source', 'gpt3-ai-content-generator');
    }
}


/* Filtering added by Hung Le */

// New SQL statements to count filtered results
$wpaicg_count_sql = "SELECT COUNT(*) FROM ".$wpdb->posts." p WHERE ".$where;
$wpaicg_count_pending_sql = $wpaicg_count_sql . " AND p.post_status = 'pending'";
$wpaicg_count_completed_sql = $wpaicg_count_sql . " AND p.post_status = 'publish'";

// Get counts
$wpaicg_count = $wpdb->get_var($wpaicg_count_sql);
$wpaicg_count_pending = $wpdb->get_var($wpaicg_count_pending_sql);
$wpaicg_count_completed = $wpdb->get_var($wpaicg_count_completed_sql);

$wpaicg_count_source_sql = $wpaicg_count_sql;
if (!empty($source)) {
    switch($source) {
        case "twitter":
            $wpaicg_count_source_sql .= " AND p.post_type='wpaicg_twitter'";
            break;
        case "csv":
            $wpaicg_count_source_sql .= " AND p.post_mime_type='csv'";
            break;
        case "editor":
            $wpaicg_count_source_sql .= " AND p.post_mime_type='editor'";
            break;
        case "rss":
            $wpaicg_count_source_sql .= " AND p.post_mime_type='rss'";
            break;
        case "sheets":
            $wpaicg_count_source_sql .= " AND p.post_mime_type='sheets'";
            break;
        case "multi":
            $wpaicg_count_source_sql .= " AND p.post_mime_type='multi'";
            break;
        default:
            break;
    }

    $wpaicg_count_pending_source_sql = $wpaicg_count_source_sql . " AND p.post_status = 'pending'";
    $wpaicg_count_completed_source_sql = $wpaicg_count_source_sql . " AND p.post_status = 'publish'";

    // Fetch the counts from the database
    $wpaicg_count_source = $wpdb->get_var($wpaicg_count_source_sql);
    $wpaicg_count_pending_source = $wpdb->get_var($wpaicg_count_pending_source_sql);
    $wpaicg_count_completed_source = $wpdb->get_var($wpaicg_count_completed_source_sql);
}


// Set display text
if (!empty($status)) {
    if ($status === 'pending') {
        if (!empty($source)) {
            $wpaicg_count_display = sprintf(
                /* translators: 1: Source type (e.g., CSV, Bulk Editor), 2: The count of pending batches for that source */
                esc_html__('Number of Pending Batches for %1$s: %2$s', 'gpt3-ai-content-generator'), 
                ucfirst($source),
                $wpaicg_count_pending_source
            );
        } else {
            $wpaicg_count_display = sprintf(
                /* translators: 1: The count of pending batches */
                esc_html__('Number of Pending Batches: %s', 'gpt3-ai-content-generator'), 
                $wpaicg_count_pending
            );
        }
    } elseif ($status === 'publish') {
        if (!empty($source)) {
            $wpaicg_count_display = sprintf(
                /* translators: 1: Source type (e.g., CSV, Bulk Editor), 2: The count of completed batches for that source */
                esc_html__('Number of Completed Batches for %1$s: %2$s', 'gpt3-ai-content-generator'), 
                ucfirst($source),
                $wpaicg_count_completed_source
            );
        } else {
            $wpaicg_count_display = sprintf(
                /* translators: 1: The count of completed batches */
                esc_html__('Number of Completed Batches: %s', 'gpt3-ai-content-generator'), 
                $wpaicg_count_completed
            );
        }
    }
} else {
    if (!empty($source)) {
        $wpaicg_count_display = sprintf(
            /* translators: 1: Source type (e.g., CSV, Bulk Editor), 2: Total count of batches for that source, 3: Completed batches for that source, 4: Pending batches for that source */
            esc_html__('Number of Batches for %1$s: %2$s, Completed: %3$s, Pending: %4$s', 'gpt3-ai-content-generator'), 
            ucfirst($source),
            $wpaicg_count_source, 
            "<span style='color:green;'>".$wpaicg_count_completed_source."</span>", 
            "<span style='color:red;'>".$wpaicg_count_pending_source."</span>"
        );
    } else {
        $wpaicg_count_display = sprintf(
            /* translators: 1: Total count of batches, 2: Completed batches, 3: Pending batches */
            esc_html__('Number of Batches: %1$s, Completed: %2$s, Pending: %3$s', 'gpt3-ai-content-generator'), 
            $wpaicg_count, 
            "<span style='color:green;'>".$wpaicg_count_completed."</span>", 
            "<span style='color:red;'>".$wpaicg_count_pending."</span>"
        );
    }
}
?>
<p><?php echo $wpaicg_count_display ?></p>


<!-- End of Filtering -->

<table class="wp-list-table widefat fixed striped table-view-list comments">
    <thead>
    <tr>
        <th><?php echo esc_html__('ID','gpt3-ai-content-generator')?></th>
        <th><?php echo esc_html__('Batch','gpt3-ai-content-generator')?></th>
        <th><?php echo esc_html__('Status','gpt3-ai-content-generator')?></th>
        <th><?php echo esc_html__('Source','gpt3-ai-content-generator')?></th>
        <th><?php echo esc_html__('Duration','gpt3-ai-content-generator')?></th>
        <th><?php echo esc_html__('Token','gpt3-ai-content-generator')?></th>
        <th><?php echo esc_html__('Word Count','gpt3-ai-content-generator')?></th>
        <th><?php echo esc_html__('Action','gpt3-ai-content-generator')?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    if($wpaicg_trackings && is_array($wpaicg_trackings) && count($wpaicg_trackings)):
        foreach($wpaicg_trackings as $wpaicg_tracking):
            ?>
            <tr>
                <td><?php echo esc_html($wpaicg_tracking->ID)?></td>
                <td>
                    <?php
                    if($wpaicg_tracking->post_type == 'wpaicg_twitter'):
                        echo esc_html($wpaicg_tracking->post_title);
                    else:
                    ?>
                    <?php
                    $batch_name = $wpaicg_tracking->post_title;
                    $batch_name_trimmed = (mb_strlen($batch_name) > 100) ? mb_substr($batch_name, 0, 100) . '...' : $batch_name;
                    ?>
                    <a href="<?php echo admin_url('admin.php?page=wpaicg_bulk_content&wpaicg_track='.$wpaicg_tracking->ID)?>">
                        <?php echo esc_html($batch_name_trimmed)?>
                    </a>
                    <?php
                    endif;
                    ?>
                </td>
                <td>
                    <?php
                    if($wpaicg_tracking->post_status == 'pending'){
                        echo esc_html__('Pending','gpt3-ai-content-generator');
                    }
                    if($wpaicg_tracking->post_status == 'publish'){
                        echo '<span style="color: #10922c">'.esc_html__('Completed','gpt3-ai-content-generator').'</span>';
                    }
                    if($wpaicg_tracking->post_status == 'draft'){
                        echo '<span style="color: #bb0505">'.esc_html__('Error','gpt3-ai-content-generator').'</span>';
                        if($wpaicg_tracking->post_type == 'wpaicg_twitter'){
                            echo '<p><small>'.esc_html($wpaicg_tracking->post_content).'</small></p>';
                        }
                    }
                    if($wpaicg_tracking->post_status == 'trash'){
                        echo '<span style="color: #bb0505">'.esc_html__('Cancelled','gpt3-ai-content-generator').'</span>';
                    }
                    ?>
                </td>
                <td>
                    <?php
                    if($wpaicg_tracking->post_type == 'wpaicg_twitter'){
                        echo esc_html__('Twitter','gpt3-ai-content-generator');
                    }
                    elseif(empty($wpaicg_tracking->post_mime_type) || $wpaicg_tracking->post_mime_type == 'editor'){
                        echo esc_html__('Bulk Editor','gpt3-ai-content-generator');
                    }
                    elseif($wpaicg_tracking->post_mime_type == 'csv'){
                        echo esc_html__('CSV','gpt3-ai-content-generator');
                    }
                    elseif($wpaicg_tracking->post_mime_type == 'rss'){
                        echo esc_html__('RSS','gpt3-ai-content-generator');
                    }
                    elseif($wpaicg_tracking->post_mime_type == 'sheets'){
                        echo esc_html__('Google Sheets','gpt3-ai-content-generator');
                    }
                    elseif($wpaicg_tracking->post_mime_type == 'multi'){
                        echo esc_html__('Copy-Paste','gpt3-ai-content-generator');
                    }
                    ?>
                </td>
                <td>
                    <?php
                    if($wpaicg_tracking->post_type == 'wpaicg_twitter'){
                        $wpaicg_twitter_duration = get_post_meta($wpaicg_tracking->ID,'wpaicg_twitter_duration',true);
                        echo !empty($wpaicg_twitter_duration) ? esc_html($this->wpaicg_seconds_to_time((int)$wpaicg_twitter_duration)) : '';
                    }
                    else {
                        echo !empty($wpaicg_tracking->time_run) ? esc_html($this->wpaicg_seconds_to_time((int)$wpaicg_tracking->time_run)) : '';
                    }
                    ?>
                </td>
                <td>
                    <?php
                    if($wpaicg_tracking->post_type == 'wpaicg_twitter'){
                        $wpaicg_twitter_tokens = get_post_meta($wpaicg_tracking->ID,'wpaicg_twitter_tokens',true);
                        echo esc_html($wpaicg_twitter_tokens);
                    }
                    else {
                        echo esc_html($wpaicg_tracking->total_tokens);
                    }
                    ?>
                </td>
                <td>
                    <?php
                    if($wpaicg_tracking->post_type == 'wpaicg_twitter'){
                        $wpaicg_twitter_length = get_post_meta($wpaicg_tracking->ID,'wpaicg_twitter_length',true);
                        echo esc_html($wpaicg_twitter_length);
                    }
                    else {
                        echo esc_html($wpaicg_tracking->word_count);
                    }
                    ?>
                </td>
                <td><a onclick="return confirm('<?php echo esc_html__('Are you sure?','gpt3-ai-content-generator')?>')" class="button button-link-delete button-small" href="<?php echo wp_nonce_url(admin_url('admin.php?page=wpaicg_bulk_content&wpaicg_action=tracking&sub_action=delete&id='.$wpaicg_tracking->ID), 'wpaicg_delete_'.$wpaicg_tracking->ID)?>"><?php echo esc_html__('Delete','gpt3-ai-content-generator')?></a></td>
            </tr>
        <?php
        endforeach;
    endif;
    ?>
    </tbody>
</table>
<div class="wpaicg-paginate">
    <?php
    echo paginate_links( array(
        'base'         => admin_url('admin.php?page=wpaicg_bulk_content&wpaicg_action=tracking&wpage=%#%'),
        'total'        => ceil($wpaicg_trackings_total / $wpaicg_tracking_per_page),
        'current'      => $wpaicg_tracking_page,
        'format'       => '?wpaged=%#%',
        'show_all'     => false,
        'prev_next'    => false,
        'add_args'     => false,
    ));
    ?>
</div>
