<?php
    require_once('../../../wp-load.php');
    global $wpdb;
    if ($_REQUEST['id']!=''){
        $output = do_shortcode ('[ardisplay id=\''.$_REQUEST['id'].'\']');
    }elseif ($_REQUEST['cat']!=''){
        $output = do_shortcode ('[ardisplay cat=\''.$_REQUEST['cat'].'\']');
    }
    get_header();
    echo $output;
?>