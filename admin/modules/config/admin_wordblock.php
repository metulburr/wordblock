<?php
/**
 * Created by PhpStorm.
 * User: Mark Janssen
 * Date: 10/24/2016
 * Time: 9:25 AM
 */

if(!defined("IN_MYBB"))
{
    die("Direct access not allowed.");
}

$page->output_header("Word Block Manager");
$page->add_breadcrumb_item("Word Block Manager", "index.php?module=config-wordblock");

$wordblockurl = "index.php?module=config-wordblock";

// Create the tabs
$sub_tabs = array(
    "browse" => array(
        "title" => "Browse",
        "link" => $wordblockurl . "&action=browse"
    ),
    "create" => array(
        "title" => "Add Word",
        "link" => $wordblockurl . "&action=add"
    )
);

$page->output_nav_tabs($sub_tabs);

if($mybb->input['action'])
{
    $action = $mybb->get_input("action");
}
else
{
    $action = "browse";
}

if($action == "add")
{
    if($mybb->request_method == "post")
    {
        $new_word = array(
            "word" => $db->escape_string($mybb->get_input("word")),
            "lastattempt" => 0,
            "case_sensitive" => $mybb->get_input("case_sensitive"),
            "uses" => 0
        );
        $db->insert_query("wordblock", $new_word);
        flash_message("The word has been added.", "success");
        admin_redirect($wordblockurl);
    }
    else
    {
        // Show the form
        $form = new form($wordblockurl . "&action=add", "post");
        $form_container = new FormContainer("Add Word");
        $form_container->output_row("Word", "The word to block", $form->generate_text_box("word"));
        $form_container->output_row("Case Sensitive", "", $form->generate_yes_no_radio("case_sensitive", 0));
        $form_container->end();
        $form->output_submit_wrapper(array($form->generate_submit_button("Add Word")));
        $form->end();
    }
}

if($action == "edit" && $mybb->input['wid'])
{
    $wid = $mybb->get_input("wid", MyBB::INPUT_INT);
    $query = $db->simple_select("wordblock", "*", "wid=$wid");
    $word = $db->fetch_array($query);
    if($mybb->request_method == "post")
    {
        $update_word = array(
            "word" => $db->escape_string($mybb->get_input("word")),
            "lastattempt" => 0,
            "uses" => 0,
            "case_sensitive" => $mybb->get_input("case_sensitive")
        );

        $db->update_query("wordblock", $update_word, "wid=$wid");
        flash_message("The word has been updated successfully.", "success");
        admin_redirect($wordblockurl);
    }
    else
    {
        $form = new form($wordblockurl . "&action=edit&wid=$wid", "post");
        $form_container = new FormContainer("Edit Word");
        $form_container->output_row("Word", "The word to block", $form->generate_text_box("word", $word['word']));
        $form_container->output_row("Case Sensitive", "", $form->generate_yes_no_radio("case_sensitive", $word['case_sensitive']));
        $form_container->end();
        $form->output_submit_wrapper(array($form->generate_submit_button("Update Word")));
        $form->end();
    }
}

if($action == "delete" && $mybb->input['wid'] && verify_post_check($mybb->input['my_post_key']))
{
    $db->delete_query("wordblock", "wid=" . $mybb->get_input("wid", MyBB::INPUT_INT));
    flash_message("The word has been deleted.", "success");
    admin_redirect($wordblockurl);
}

if($action == "browse")
{
    $table = new TABLE;
    $table->construct_header("ID");
    $table->construct_header("Word");
    $table->construct_header("Case Sensitive");
    $table->construct_header("Times Triggered");
    $table->construct_header("Last Use");
    $table->construct_header("Edit Link");
    $table->construct_header("Delete Link");
    $table->construct_row();
    $query = $db->simple_select("wordblock", "*");
    while($word = $db->fetch_array($query))
    {
        $table->construct_cell($word['wid']);
        $table->construct_cell(htmlspecialchars_uni($word['word']));
        if($word['case_sensitive'] == 1)
        {
            $table->construct_cell("Yes");
        }
        else
        {
            $table->construct_cell("No");
        }
        $table->construct_cell(number_format($word['uses']));
        if($word['lastattempt'] == 0)
        {
            $lasttime = "Never";
        }
        else
        {
            $lasttime = my_date($mybb->settings['dateformat'] . " " . $mybb->settings['timeformat'], $word['lastattempt']);
        }
        $table->construct_cell($lasttime);
        $editlink = $wordblockurl . "&action=edit&wid=" . $word['wid'];
        $table->construct_cell("<a href='" . $editlink . "'>Edit</a>");
        $deletelink = $wordblockurl . "&action=delete&wid=" . $word['wid'] . "&my_post_key=" . $mybb->post_code;
        $table->construct_cell("<a href='" . $deletelink . "'>Delete</a>");
        $table->construct_row();
    }
    $table->output("Blocked Words");
}
