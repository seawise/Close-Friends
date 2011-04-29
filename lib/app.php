<?
include("facebook/facebook.php");
error_reporting (E_ALL ^ E_NOTICE);
set_time_limit(0);

function pr ($string) {
	print("<pre>");
	print_r($string);
	print("</pre>");
}

function redirectFb($url, $type="iframe") {
	if ($type=="fbml") {
		echo "<fb:redirect url=\"" . $url . "\"/>";
	}
	else if ($type=="iframe") {
		echo "<script>window.top.location='" . $url . "';</script>";
	}
	else if ($type=="offsite") {
		header ("Location: " . $url);
	}
	exit;
}

$fbApp = array(
	"id" => "115294428552261",
	"key" => "cef30f5509d0a41304df4cfdcc7d8605",
	"secret" => "a4c01234443ba7692ab67b8a7029f21d",
	"canvas" => "http://apps.facebook.com/friendsplayground/",
	"source" => "http://173.203.126.224/closefriends/",
	"tab" => "http://www.facebook.com/pages/Playground/158010977543098?sk=app_115294428552261",
	"name" => "Close Friends",
	"perms" => "read_stream,user_likes,friends_likes,user_interests,friends_interests,user_location,friends_location,user_hometown,friends_hometown,user_birthday,friends_birthday,user_events,friends_events,user_photo_video_tags,friends_photo_video_tags,user_relationships,friends_relationships,user_education_history,friends_education_history"
);

$facebook = new Facebook(array(
	"appId" => $fbApp["id"],
	"secret" => $fbApp["secret"],
	"cookie" => false
));

$fbSession = $facebook->getSession();
$fbRequest = $facebook->getSignedRequest();

if (!empty($fbRequest["page"])) {
	// tab level
}
else {
	// canvas level
	if ($fbSession) {
		if ($_REQUEST["ajax"]) {
						
			$startTime = microtime(true);
			
			$fbUID = $facebook->getUser();
			
			$criteria = array();
			$modifiers = array();
			$friends = array();
			$criteriaPoints = array();
			
			try {
				$data = $facebook->api(array(
					"method" => "fql.multiquery",
					"queries" => '{
						"friends": "SELECT uid2 FROM friend WHERE uid1=' . $fbUID .  '",
						"feed": "SELECT actor_id FROM stream WHERE source_id=' . $fbUID .  ' AND actor_id!=' . $fbUID .  ' LIMIT 100",
						"fan": "SELECT page_id FROM page_fan WHERE uid=' . $fbUID .  '",
						"mutualfans": "SELECT uid FROM page_fan WHERE page_id IN (SELECT page_id FROM #fan) AND uid IN (SELECT uid2 FROM #friends)",
						"likes": "SELECT object_id, post_id FROM like WHERE user_id=' . $fbUID .  '",
						"mutuallikes": "SELECT user_id FROM like WHERE object_id IN (SELECT object_id FROM #likes) AND user_id IN (SELECT uid2 FROM #friends)",
						"mutualfriends": "SELECT uid1,uid2 FROM friend WHERE uid1 IN (SELECT uid2 FROM #friends) and uid2 IN (SELECT uid2 from #friends)",
						"photos": "SELECT pid FROM photo_tag WHERE subject=' . $fbUID .  '",
						"mutualphotos": "SELECT subject FROM photo_tag WHERE pid IN (SELECT pid FROM #photos) AND subject IN (SELECT uid2 FROM #friends)",
						"profiles": "SELECT uid, first_name, last_name, pic_square, birthday_date, sex, current_location, hometown_location, education_history FROM user WHERE uid=' . $fbUID . ' OR uid IN (SELECT uid2 FROM #friends)"
					}'
				));
			}
			catch (FacebookApiException $e) {
				die ("Facebook API unreachable: " . $e->__toString());
			}
			
			// setting user
			$fbUser = $data[9]["fql_result_set"][0];
			$fbUserBirthyear = substr($fbUser["birthday_date"], 6, 4);
			unset($data[9]["fql_result_set"][0]);
			
			// settings friends
			foreach ((array)$data[9]["fql_result_set"] as $key => $item) {
				$friends[$item["uid"]] = $item;
			}

			foreach ((array)$data[9]["fql_result_set"] as $key => $item) {
				
				// MODIFIER 1
				// close friend is likely from current living location or hometown
				if ($item["current_location"]["id"] == $fbUser["current_location"]["id"] || $item["hometown_location"]["id"] == $fbUser["hometown_location"]["id"]) {
					$modifiers["location"][] = $item["uid"];
				}
				
				// MODIFIER 2
				// close friend is likely the same gender as user
				if ($item["sex"] == $fbUser["sex"]) {
					$modifiers["sex"][] = $item["uid"];
				}
				
				// MODIFIER 3
				// close age/same generation - close friend is likely same age or generation as user
				$friendBirthyear = substr($item["birthday_date"], 6, 4);
				
				if (!empty($fbUserBirthyear) && !empty($friendBirthyear)) {
					if ($friendBirthyear >= $fbUserBirthyear - 5 && $friendBirthyear <= $fbUserBirthyear + 5) {
						$modifiers["age"][] = $item["uid"];
					}
				}
				
				// MODIFIER 4
				// close age/same generation - close friend is likely same age or generation as user
				if (!empty($fbUser["education_history"]) && !empty($item["education_history"])) {
					foreach ($fbUser["education_history"] as $fbUserColledge) {
						foreach ($item["education_history"] as $friendColledge) {
							if ($fbUserColledge["school_type"] == "College" && $friendColledge["school_type"] == "College" && $fbUserColledge["name"] == $friendColledge["name"]) {
								$modifiers["college"][] = $item["uid"];
								break 2;
							}
						}
					}
				}
			}
			
			// CRITERIA 1
			// greatest number of common likes, page fans, interests and activities
			$criteria["likes"] = array();
			foreach ((array)$data[7]["fql_result_set"] as $key => $item) {
				$criteria["likes"][$item["user_id"]]++;
			}
			foreach ((array)$data[5]["fql_result_set"] as $key => $item) {
				$criteria["likes"][$item["uid"]]++;
			}
			arsort($criteria["likes"]);
			
			// CRITERIA 2
			// greatest number of mutual friends
			$criteria["friends"] = array();
			foreach ((array)$data[6]["fql_result_set"] as $key => $item) {
				$criteria["friends"][$item["uid1"]]++;
			}
			arsort($criteria["friends"]);
			
			// CRITERIA 3
			// greatest number of marked toghether on photos
			$criteria["photos"] = array();
			foreach ((array)$data[8]["fql_result_set"] as $key => $item) {
				$criteria["photos"][$item["subject"]]++;
			}
			arsort($criteria["photos"]);
			
			// CRITERIA 4a
			// greatest number of communication (friends poster to user wall)
			$criteria["communication"] = array();
			foreach ((array)$data[1]["fql_result_set"] as $key => $item) {
				$criteria["communication"][$item["actor_id"]]++;
			}
			
			// CRITERIA 4b
			// greatest number of communication (posted to friends walls)
			$queries = array();
			$i = 0;
			$j = 0;
			foreach ((array)$friends as $key => $item) {
				$queries[$j][$item["uid"]] = "SELECT actor_id FROM stream WHERE source_id=" . $item["uid"] . " AND actor_id=" . $fbUID . " LIMIT 50";
				if (($i+1)/20 == round(($i+1)/20)) {
					$j++;
				}
				$i++;
			}
			
			try {
				$posts = array();
				foreach ($queries as $key => $query) {
					$item = $facebook->api(array(
						"method" => "fql.multiquery",
						"queries" => json_encode($query)
					));
					$posts = array_merge($posts, $item);
				}
			}
			catch (FacebookApiException $e) {
				die ("Unable to fetch Facebook API: " . $e->__toString());
			}
			
			$criteria["communication"] = array();
			foreach ((array)$posts as $key => $item) {
				foreach ((array)$item["fql_result_set"] as $resultKey => $resultItem) {
					$criteria["communication"][$item["name"]]++;
				}
			}
			arsort($criteria["communication"]);
			
			$criteriaPoints = array();
			
			foreach ($criteria as $criteriaKey => $criteriaItem) {
				$ratio = 100 / reset($criteriaItem);
				foreach ($criteriaItem as $friendFbuid => $value) {
					$modifierMultiplier = 1;
					foreach ((array)$modifiers as $modifierKey => $modifierItem) {
						if (in_array($friendFbuid, $modifierItem)) {
							// applying multiplier
							$modifierMultiplier *= 1.2;
						}
					}
					$criteriaPoints[$friendFbuid] += round($value * $ratio * $modifierMultiplier);
				}
			}
			
			arsort($criteriaPoints);
			
			?>
			<? $i=1; foreach ((array)$criteriaPoints as $key => $item): ?>
				<div class="friend">
					<div class="rank<? if ($i==1): ?> first<? endif; ?>"><?=$i; ?>.</div>
					<div class="picture"><a href="http://www.facebook.com/profile.php?id=<?=$friends[$key]["uid"]; ?>"><img src="<?=$friends[$key]["pic_square"] ?>" width="40" height="40" alt="" /></a></div>
					<div class="name"><?=($friends[$key]["first_name"] . " " . $friends[$key]["last_name"]); ?> <span class="points"><?=$item; ?> points</span></div>
				</div>
				<div class="cleaner"></div>
				<? if ($i>=10) break; ?>
			<? $i++; endforeach; ?>
			<script type="text/javascript">
				function share() {
					var publish = {
						method: 'feed',
						message: 'Check this out! It\'s really funny.',
						name: '<?=$fbApp["name"]; ?>',
						caption: 'Who are your closest friends?',
						description: (
							'My top 10 closest friends are:<center></center>' +
							<? $i=1; foreach ((array)$criteriaPoints as $key => $item): ?>
							'<?=$i; ?>. <?=($friends[$key]["first_name"] . " " . $friends[$key]["last_name"]); ?><center></center>' + 
							<? if ($i>=10) break; ?>
							<? $i++; endforeach; ?>
							''
						),
						link: '<?=$fbApp["tab"]; ?>',
						picture: '<?=$fbApp["source"]; ?>images/picture.png',
						actions: [
							{ name: 'Find yours!', link: '<?=$fbApp["tab"]; ?>' }
						]
					};
					FB.ui(publish, function(response) {});
				}
			</script>
			<?
			
			// ajax outputting
			die;
		}
	}
	else {
		redirectFb($facebook->getLoginUrl(array(
			"canvas" => 1,
			"fbconnect" => 0,
			"req_perms" => $fbApp["perms"],
		)), "iframe");
	}
}
?>