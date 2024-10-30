<?php
/*
 Plugin Name: CommentTweets
 Plugin URI:  http://omblogs.dk/myplugins/CommentTweets
 Description:  When a reply is made to a comment the user has left on the blog, a twitter notification (@twittername) will be sent to the user from your Twitteraccount to notify him/her of the reply. This will allow the users to follow up on the comment and expand the conversation if desired.
 Version: 0.6
 Author: Therese Hansen
 AuthorURI: http://omblogs.dk
 */

//------------------start actions
add_action('comment_post', 'cn_wrap_main_twitter_notification');
add_action('edit_comment', 'cn_wrap_main_twitter_notification');
add_action('admin_menu', 'cn_addTwitterNotifierAdminPages');
add_action('transition_comment_status','cn_wrap_main_twitter_notification_2');
//------------------end actions
//---------------------------- communication with twitter start
function cn_send_twitter_notification ($twittermessage){

	$host = 'www.twitter.com';
	$port = 80;
	$err_num = 10;
	$err_msg = 10;
	$agent = 'Wordpress';
	$twit = $twittermessage.'status=';
	$fp = fsockopen($host, $port, $err_num, $err_msg, 10);
	$twitterURI = "/statuses/update.xml";

	//check if user login details have been entered on options page
	$thisLoginDetails = get_option('twitterlogin_base64');
	if($thisLoginDetails != '')
	{
		if (!$fp) {
			echo "fejl i adgangen til twitter";
		} else {
			fputs($fp, "POST /statuses/update.xml HTTP/1.1\r\n");
			fputs($fp, "Authorization: Basic ".$thisLoginDetails."\r\n");
			fputs($fp, "User-Agent: ".$agent."\n");
			fputs($fp, "Host: ".$host."\n");
			fputs($fp, "Content-type: application/x-www-form-urlencoded \n");
			fputs($fp, "Content-length: ".strlen($twit)."\n");
			fputs($fp, "Connection: close\n\n");
			fputs($fp, "status=".$twit);
			for ($i = 1; $i < 30; $i++){$response = fgets($fp, 256);$reply = $reply.$response;}
			fclose($fp);
		}
		return $reply;
	} else {
		//do nothing if the plugin is not configured correctly
		return '';
	}
}


function cn_get_twitter_account_from_email($parent_email){
	$host = 'www.twitter.com';
	$port = 80;
	$err_num = 10;
	$err_msg = 10;
	$agent = 'Wordpress';

	$fp = fsockopen($host, $port, $err_num, $err_msg, 10);
	$twitterURI = '/users/show.xml?email='.$parent_email;

	if (!$fp) {
			
	} else {

		fputs($fp, "GET $twitterURI HTTP/1.1\r\n");
		fputs($fp, "Host: $host\n");
		fputs($fp, "Connection: close\n\n");
		for ($i = 1; $i < 800; $i++){$response = fgets($fp, 256);$reply = $reply.$response;}
		fclose($fp);
		//remove the first part of reply to leave the XML - I should probably have used a regular expression
		$pieces = explode("<", $reply);
		$impl="";
		for ($i=1; $i<=200; $i++)
		{
			if ($pieces[$i]!=""){
				$tag = '<'.$pieces[$i];
				$impl = $impl.$tag;
			}
		}
		//reply is now the desired XML-document
		$reply=$impl;
		//get username from XML
		$doc = new DOMDocument();
		$doc->loadXML($reply);

		$dataset = $doc->getElementsByTagName('screen_name');

		if ($dataset->length==0){}
		for ($i = 0; $i < $dataset->length; $i++) {
			$item = $dataset->item(0);
			$username =$item->nodeValue;
		}
		return $username;
	}
}
//--------------------communication with twitter end

//-----------------------------Check if you should send message and send if you should (start)
function cn_get_short_url($longurl){
	$gettinyurl = "http://tinyurl.com/api-create.php?url=$longurl";
	$tinyurl = file_get_contents($gettinyurl);
	return $tinyurl;
}

function cn_build_twitter_message($post_url,$twitteraccountname,$authorname){
	$twitter_message = '@'. $twitteraccountname . ' '. $authorname . ' has responded to your comment on ' . $post_url . '.';
	return $twitter_message;
}

function cn_wrap_main_twitter_notification($comment_id){
	$status_string=wp_get_comment_status($comment_id);
		cn_main_twitter_notification($comment_id,$status_string);
}

function cn_wrap_main_twitter_notification_2($new_status){
		if ($new_status==1||$new_status=='approve'||$new_status=='approved'){
			cn_main_twitter_notification($comment_id,"approved");
	}
}

function cn_main_twitter_notification($comment_id, $comment_status) {

	//global $user_ID, $userdata, $twitteraccountname;
	global $twitteraccountname;
	if ($comment_status==""){$comment_status=wp_get_comment_status($comment_id);}
	if ($comment_status=="approved"||$comment_status==1||$comment_status=="approve"){
		//First get the post that the comment is a response to
		$comment = get_comment($comment_id);
		$post_id = $comment->comment_post_ID;
		$link = get_permalink($post_id);
		$post = get_post($post_id);
		$title = $post->post_title;
		$post_url = cn_get_short_url($link);
		if ($post_url==""){$post_url=$link;}
		$authorname = $comment->comment_author;
		$authoremail =$comment->comment_author_email;

		//Get all the approved comments before the current one and send a tweet. Remove duplicates as an optimization.
		$comment_array = get_approved_comments($post_id);
		$email_array = array();
		foreach ($comment_array as $dupcomment){
			$comment_connected_email=$dupcomment->comment_author_email;
			if (!(in_array($comment_connected_email,$email_array))){array_push($email_array,$comment_connected_email);}
		}
		foreach($email_array as $email){
			if (!($authoremail==($email))){ //don't send notifications to the commentauthor
				$parent_email=$email;
				//Do nothing if email is empty
				if(empty($parent_email) || !is_email($parent_email)){
				} else {
					//Build message
					$twitteraccountname = cn_get_twitter_account_from_email($parent_email);
					if (!($twitteraccountname=="") && !($post_url=="") && !($authorname=="")){$twittermessage = cn_build_twitter_message($post_url,$twitteraccountname,$authorname);
					$succes = cn_send_twitter_notification($twittermessage);
					//what would be the appropriate action if sending fail?
					}
				}
			} 
		}

	}
}
//---------------------------Check if you should send message and send if you should end

//----------------------------admin page start
// ADMIN PANEL - under Manage menu - 
function cn_addTwitterNotifierAdminPages() {
	if (function_exists('add_options_page')) {
		add_options_page('CommentTweets', 'CommentTweets', 8, __FILE__, 'cn_addTwitNotifieradminpage');
	}
}

function cn_addTwitNotifieradminpage(){
	include(dirname(__FILE__).'/cn_twitter_notifier_manage.php');
}

//-----------------------------admin page end

?>