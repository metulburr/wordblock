<?php
/**
 * Created by PhpStorm.
 * User: Mark Janssen
 * Date: 10/24/2016
 * Time: 8:22 AM
 */

function wordblock_info()
{
    global $mybb;
    $updatelink = "index.php?module=config-plugins&action=updatewordblock&my_post_key=" . $mybb->post_code;
    return array(
        "name" => "Word Block",
        "description" => "Block specific words from being posted on your forum.  <a href='" . $updatelink . "'>Click here</a> to run the update script.",
        "author" => "Mark Janssen",
        "version" => "2.0",
        "codename" => "wordblock",
        "compatibility" => "18**"
    );
}

function wordblock_install()
{
    global $db;
    $db->write_query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "wordblock (
    wid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    word TEXT,
    case_sensitive INT(1) DEFAULT 0,
    uses INT UNSIGNED DEFAULT 0,
    lastattempt BIGINT(20) UNSIGNED DEFAULT 0
    ) ENGINE = Innodb " . $db->build_create_table_collation());
}

function wordblock_is_installed()
{
    global $db;
    return $db->table_exists("wordblock");
}

function wordblock_activate()
{
}

function wordblock_deactivate()
{
}

function wordblock_uninstall()
{
    global $db;
    if($db->table_exists("wordblock"))
    {
        $db->drop_table("wordblock");
    }
}

// Hooks
$plugins->add_hook("datahandler_post_validate_post", "wordblock_datahandler_post_validate_post");
$plugins->add_hook("newthread_do_newthread_start", "wordblock_newthread_do_newthread_start");
$plugins->add_hook("admin_config_menu", "wordblock_admin_config_menu");
$plugins->add_hook("admin_config_action_handler", "wordblock_admin_config_action_handler");
$plugins->add_hook("admin_config_plugins_begin", "wordblock_admin_config_plugins_update");

function wordblock_datahandler_post_validate_post($this)
{
    global $post, $db;
    $query = $db->simple_select("wordblock", "*");
    while($word = $db->fetch_array($query))
    {
        if($word['case_sensitive'] == 1)
        {
            if(strpos($post['message'], $word['word']) !== false || strpos($post['subject'], $word['word']) !== false)
            {
                // Update the last use column
                $update_array = array(
                    "lastattempt" => time(),
                    "uses" => $word['uses'] += 1
                );
                $wid = $word['wid'];
                $db->update_query("wordblock", $update_array, "wid=$wid");
                error("Your post contains one or more words that are not allowed.");
            }
        }
        else
        {
            // Not case sensitive
            if(stripos($post['message'], $word['word']) !== false || stripos($post['subject'], $word['word']) !== false)
            {
                // Update the last use column
                $update_array = array(
                    "lastattempt" => time(),
                    "uses" => $word['uses'] += 1
                );
                $wid = $word['wid'];
                $db->update_query("wordblock", $update_array, "wid=$wid");
                error("Your post contains one or more words that are not allowed.");
            }
        }
    }
    return;
}

function wordblock_newthread_do_newthread_start()
{
    global $mybb, $db;
    $query = $db->simple_select("wordblock", "*");
    $message = $mybb->get_input("message");
    $subject = $mybb->get_input("subject");
    while($word = $db->fetch_array($query))
    {
        if($word['case_sensitive'] == 1)
        {
            if (strpos($message, $word['word']) !== false || strpos($subject, $word['word']) !== false)
            {
                // Update the last use column
                $update_array = array(
                    "lastattempt" => time(),
                    "uses" => $word['uses'] += 1
                );
                $wid = $word['wid'];
                $db->update_query("wordblock", $update_array, "wid=$wid");
                error("Your post contains one or more words that are not allowed.");
            }
        }
        else
        {
            // Not case sensitive
            if (stripos($message, $word['word']) !== false || stripos($subject, $word['word']) !== false)
            {
                // Update the last use column
                $update_array = array(
                    "lastattempt" => time(),
                    "uses" => $word['uses'] += 1
                );
                $wid = $word['wid'];
                $db->update_query("wordblock", $update_array, "wid=$wid");
                error("Your post contains one or more words that are not allowed.");
            }
        }
    }
    return;
}

function wordblock_admin_config_menu(&$sub_menu)
{
    $key = count($sub_menu) *10 + 20;
    $sub_menu[$key] = array(
        "id" => "wordblock",
        "title" => "Manage Blocked Words",
        "link" => "index.php?module=config-wordblock"
    );
}

function wordblock_admin_config_action_handler(&$actions)
{
    $actions['wordblock'] = array(
        "active" => "wordblock",
        "file" => "admin_wordblock.php"
    );
}

function wordblock_admin_config_plugins_update()
{
    global $mybb, $db;
    if($mybb->input['action'] == "updatewordblock")
    {
        verify_post_check($mybb->input['my_post_key']);
        if(!$db->field_exists("uses", "wordblock"))
        {
            $db->write_query("ALTER TABLE " . TABLE_PREFIX . "wordblock
            ADD uses INT UNSIGNED NOT NULL DEFAULT 0");
        }
        if(!$db->field_exists("case_sensitive", "wordblock"))
        {
            $db->write_query("ALTER TABLE " . TABLE_PREFIX . "wordblock
            ADD case_sensitive INT(1) DEFAULT 0");
        }
        flash_message("Word Block has been updated.", "success");
        admin_redirect("index.php?module=config-wordblock");
    }
}
