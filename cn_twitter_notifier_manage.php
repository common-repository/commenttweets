<?php

if(($_POST['twitterlogin'] != '') AND ($_POST['twitterpw'] != '')){
	update_option('twitterlogin', $_POST['twitterlogin']);
	update_option('twitterlogin_base64', base64_encode($_POST['twitterlogin'].':'.$_POST['twitterpw']));
}
?>

<div class="wrap">
	<h2>Comment Notification through Twitter</h2>
<p>
	This plugin needs your twitter login information to work.<br />
	This information is only saved locally and will not be publicly visible.
</p>
<form method="post">
	<div>
		<p>
			<label for="twitterlogin">Your twitter username:</label> 
			<input type="text" name="twitterlogin" id="twitterlogin" value="<?php echo(get_option('twitterlogin')) ?>" />
		</p>
		<p>
			<label for="twitterpw">Your Twitter password:</label> 
			<input type="password" name="twitterpw" id="twitterpw" value="" />
		</p>
			<input type="hidden" name="submit-type" value="login">
		<p>
		<input type="submit" name="submit" value="save login" /> &nbsp; ( <strong>Don't have a Twitter account? <a href="http://www.twitter.com">Get one for free here</a></strong>)
		</p>
	</div>
</form>

</div>