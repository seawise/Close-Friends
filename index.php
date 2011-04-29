<? include("lib/app.php"); ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:fb="http://www.facebook.com/2008/fbml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	var auth = {};
	FB.getLoginStatus(function(response) {
		if (response.session) {
			auth = response;
			$.post("<?=$fbApp["source"]; ?>", { signed_request: '<?=!empty($_REQUEST["signed_request"]) ? $_REQUEST["signed_request"] : ""; ?>', ajax: true }, function(data) {
				$("div.friends").html(data).removeClass("loader");
				$("span.share").show();
				$("#wait").hide();
				$("#done").show();
			});
		}
	});
});
</script>
<title>Close friends</title>
<style type="text/css">
* { margin: 0px; padding: 0px; }
html { }
body { font: 14px Arial, Helvetica, sans-serif; color: #333;  }
.wrapper { overflow: hidden; padding: 15px; background: url(http://www.moidesktop.ru/wp-content/uploads/2010/08/aurora_v4.jpg) 55% 0 no-repeat; }
.pad { margin: 0 0 2px 0; padding: 20px; background: #fff; }
	.tab {  }
	.canvas { }
	.faces { padding: 100px 20px 20px 20px; height: 342px; background: url(images/faces.jpg) no-repeat; }
	.facepile {  }
	.dummy { padding: 0px; height: 5px; }
.header { position: relative; padding: 50px 0; z-index: 1; }
	h1 { margin: 0 0 10px 0;  font: italic 48px "Trebuchet MS", Arial, Helvetica, sans-serif; color: #fff; }
		h1 span.subtitle { display: block; padding: 0 0 0 2px; font: italic 17px Arial, Helvetica, sans-serif; }
	.like { padding: 10px 0 0 0; }
	.friends { border-top: 1px solid #ddd; padding: 10px 0 0 0; min-height: 450px; }
		.friend { clear: both; height: 40px; margin: 0 0 5px 0; background: #fff; }
			.friend .rank { float: left; width: 25px; margin: 0 10px 0 0; padding: 7px 0 0 0; font-size: 12px; text-align: right; }
				.friend .rank.first { color: red; font-size: 15px; padding: 5px 0 0 0; }
			.friend .picture { float: left; width: 40px; margin: 0 15px 0 0; }
				.friend .picture img { display: block; }
			.friend .name { float: left; width: 400px; padding: 5px 0 0 0; }
				.friend .name .points { display: block; font: 10px Tahoma, Helvetica, sans-serif; color: #aaa; }
h2 { position: relative; margin: 0 0 10px 0; font: 20px "Trebuchet MS", Arial, Helvetica, sans-serif; }
	h2 span.span-like { position: absolute; top: 3px; padding: 0 0 0 10px; }
	h2 span.share { display: none; position: absolute; top: 4px; font: 11px Verdana, Helvetica, sans-serif; padding: 0 0 0 100px; }
p { margin: 0 0 10px 0; line-height: 1.4em; }
ul { margin: 0 0 10px 20px; line-height: 1.3em; }
	ul li { margin: 0 0 3px 0; }
a { color: blue; outline: none; }
	a img { border: 0px; }
.cleaner { clear: both; height: 0px; overflow: hidden; }
.loader { background: url(images/loader.gif) 50% 49% no-repeat; }
#done { display: none; }
</style>
</head>

<body>
<div id="fb-root"></div>
<script type="text/javascript" src="http://connect.facebook.net/en_US/all.js"></script>
<script type="text/javascript">
FB.init({
	appId  : '<?=$fbApp['id']; ?>',
	status : true, // check login status
	cookie : true, // enable cookies to allow the server to access the session
	xfbml  : true  // parse XFBML
});
window.fbAsyncInit = function() {
	FB.Canvas.setSize();
}
</script>

<div class="wrapper">
	<div class="header">
		<h1>Close friends <span class="subtitle">Who are your closest friends?</span></h1>
	</div>
	<? if (!empty($fbRequest["page"])): ?>
	<div class="tab">
		<div class="pad faces">
			<h2>Wonder who are your<br />closest friends on Facebook?</h2>
			<p><a target="_top" href="<?=$fbApp["canvas"]; ?>">Click here</a> to find it out!</p>
			<div class="like"><fb:like href="http://apps.facebook.com/friendsplayground/" send="false" layout="button_count" width="100" show_faces="false" font="verdana"></fb:like></div>
		</div>
		<div class="pad dummy"></div>
	</div>
	<? else: ?>
	<div class="canvas">
		<div class="pad">
			<h2>Close friends <span class="span-like"><fb:like href="http://apps.facebook.com/friendsplayground/" send="false" layout="button_count" width="100" show_faces="false" font="verdana"></fb:like></span> <span class="share"><a target="_top" href="javascript://" onclick="share();">post to profile</a></span></h2>
			<div class="message">
				<p id="wait">Your top friends are being examined, please wait:</p>
				<p id="done">Your top friends are:</p>
			</div>
			<div class="friends loader"></div>
		</div>
		<div class="pad dummy"></div>
	</div>
	<? endif; ?>
</div>

</body>
</html>