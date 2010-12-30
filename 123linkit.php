<?php
/*
Plugin Name: 123Linkit Affiliate Marketing Tool
Plugin URI:  http://www.123linkit.com/general/download
Description: Generate money easily from your blog by transforming brand names and product keywords into affiliate links. There’s no need to apply to affiliate networks or programs - we do it all for you. Just install the plugin, sync your posts and we’ll automatically add relevant, money-making affiliate links to your blog.
Version: 1.11
Author: 123Linkit, LLC.
Author URI: http://www.123linkit.com/
*/

 				@set_time_limit(0);

        function LinkITAlert() {
          if (get_option("LinkITConfig") != "done")
            LinkITSignUpAlert();
        }

        function LinkITSignUpAlert() { 
          echo '<div id="linkitnotification" class="error" style="padding:5px;background-color:#FFDAB4;border-color:#FE7A4D;"><p style="color:red;">Thanks for using 123LinkIt!</p>';
          echo '<p>For new users - to add money-making affiliate links to your posts, click on the <a href="?page=LinkITPluginCentral&step=signup">123LinkIt</a>  tab and register for an account. <br />';
          echo 'For existing users - our Settings tab has changed. <a href="?page=LinkITPluginCentral">Click here</a> to login and then synchronize all your posts at once (even if they\'ve been synced before).</p>';
          echo '<p><b>You must configure your <a href="?page=LinkITPluginCentral&step=options">settings</a> and select your blog category before using the 123Linkit plugin!</b></p><br/>';
          echo '<p><b>Note: You only need to synchronize your posts once.</b> Our plugin will automatically update new and edited posts.</p>';
          echo '</div>';
        } 
        add_action('admin_notices','LinkITAlert');
        function loadjQuery() {
          wp_enqueue_script("jquery");
        }
        add_action('init','loadjQuery');
        require_once("lib/proxy.php");

        add_action('admin_menu', 'LinkITMenu');
        add_action('publish_post', 'LinkITPublish');
        add_filter('the_content', 'TheContent');

        remove_filter('the_content', 'wptexturize');
        remove_filter('the_content', 'convert_smilies');
        remove_filter('the_content', 'convert_chars');
        remove_filter('the_content', 'wpautop');
        remove_filter('the_content', 'prepend_attachment');

        function TheContent($content) {
          $private_key = get_option("LinkITPrivateKey");
          if ($private_key == "") return $content;
          
          global $wpdb;
          $poststable = $wpdb->prefix . "posts";
          //make sure we have a table
          LinkITCreateCacheTable();

          $guid = get_the_guid(0);
          $private_key = get_option("LinkITPrivateKey");
          $params = array("guid" => $guid,
                          "private_key" => $private_key,
                          "blog_url" => get_bloginfo("url"));
					$cached_content = LinkITGetCachedPost($guid);
					if (strlen($cached_content) > strlen($content))
            return $cached_content;
          else {
            $result = LinkITAPIDownload($params);
            $result = json_decode($result['data']);
            $new_content = $result->{"content"};
            
           // if (strlen($new_content) < 5) $new_content = $content;

            if (strlen($new_content) < strlen($content)) $new_content = $content;
            $hash = md5($new_content);

            $new_content = wptexturize($new_content);
            $new_content = convert_smilies($new_content);
            $new_content = convert_chars($new_content);
            $new_content = wpautop($new_content);
            $new_content = prepend_attachment($new_content);
          
            LinkITDeleteCachedPost($guid);
            LinkITAddCachedPost($guid, $new_content, $hash);
            return $new_content;
          }
        }

        function LinkITPublish($post_id) {
          $private_key = get_option("LinkITPrivateKey");
          if ($private_key == "") return 0;
          
          global $wpdb;
          $poststable = $wpdb->prefix . "posts";

          $myrows = $wpdb->get_results("SELECT guid, post_title, post_content, post_type FROM $poststable WHERE id = '$post_id'");
          $s = $myrows[0];
          if ($s->{'post_type'} == 'post') {
            $guid = $s->{'guid'};
            $title = $s->{'post_title'};
            $content = $s->{'post_content'};
            
            $params = array("guid" => $guid,
                            "title" => $title,
                            "content" => $content,
                            "private_key" => $private_key,
                            "blog_url" => get_bloginfo("url"));
            LinkITAPIUpload($params);

            //make sure we have the table

            LinkITCreateCacheTable();
            LinkITDeleteCachedPost($guid);
          }
        }

        function LinkITMenu() {
          // add_management_page('123LinkIt', '123LinkIt', 'manage_options', 'LinkITPluginCentral', 'LinkITPluginCentral');
          add_menu_page('123LinkIt', '123LinkIt', 'manage_options', 'LinkITPluginCentral', 'LinkITPluginCentral', 'http://www.123linkit.com/images/123linkit.favicon.gif', 3);
        }

        function LinkITRenderHeader() {
          echo '<div id="header">';
          echo "	<img src='http://www.123linkit.com/images/plugin/logo.png' />";
          echo "</div>";
        }

        function LinkITRenderLogin($errorMessage) {
          ?>
          <div id="login" style="text-align: center;">
            <form method='post' action='?page=LinkITPluginCentral&step=login'>
              <div class="box" style="width: 500px; min-width: 400px; margin: 0px auto;">
              <span class="caption">Login</span>
              <div class="inner">
                <table style="margin: 0px auto;">
                  <tr><td style="width: 80px;"><label for="email">Email</label></td>
                      <td><input style="width: 200px;" id="email" type='text' name='LinkITEmail'></td></tr>
					<tr><td><label for="pass">Password</label></td>
						  <td><input style="width: 200px;" id="pass" type='password' name='LinkITPassword'></td></tr>
					<tr>
						<td></td>						
						<td style="text-align: right;">
							<a  style="font-size: 10px;" href='http://www.123linkit.com/password_resets/new'>Forgot your password?</a>
						</td>
					</tr>					
					<?php
						if($errorMessage != "") echo "<tr><div class='error'>$errorMessage</div></tr>";
					?>
					<tr>
						<td></td>						
						<td>
							<input type='submit' class="button btn-fix" value='Login'>
							<a class="button btn-fix" href='?page=LinkITPluginCentral&step=signup'>Sign up here</a>
						</td>
					</tr>
				</table>	
			</div>
			</div>
		</form>
	</div>
	<?php
}

function LinkITLogin() {
	$email = $_POST['LinkITEmail'];
	$password = $_POST['LinkITPassword'];
	$result = LinkITAPILogin($email, $password);
	$ar = json_decode($result['data']); //$result['data'];
	return $ar;
}

function LinkITCreateUser() {
	$email = $_POST['LinkITEmail'];
	$password = $_POST['LinkITPassword'];
	$passwordc = $_POST['LinkITPasswordc'];
	$category=$_POST['LinkITBlogCategory'];
	$result = LinkITAPICreateUser($email,$password,$passwordc,$category);
	$ar = json_decode($result['data']);
	return $ar;		
}

function LinkITRenderSignUp() {
	?>
	<div id="login" style="text-align: center;">
		<form method='post' action='?page=LinkITPluginCentral&step=createuser'>
			<div class="box" style="width: 500px; min-width: 400px; margin: 0px auto;">
			<span class="caption">Sign Up</span>
			<div class="inner">
				<table style="margin: 0px auto;">
					<tr><td style="width: 120px;"><label for="email">Email</label></td>
						  <td><input style="width: 200px;" id="email" type='text' name='LinkITEmail'></td></tr>
					<tr><td><label for="password">Password</label></td>
						  <td><input style="width: 200px;" id="password" type='password' name='LinkITPassword'></td></tr>
					<tr><td><label for="passwordc">Password confirmation</label></td>
						  <td><input style="width: 200px;" id="passwordc" type='password' name='LinkITPasswordc'></td></tr>
					<tr><td><label for="blogcategory">Blog category</label></td>
						  <td><select name="LinkITBlogCategory" id="blogcategory" style="width:200px;"><option value="1">Accessories</option><option value="2">Art/Photo/Music</option><option value="3">Automotive</option><option value="4">Beauty</option><option value="5">Books/Media</option><option value="6">Business</option><option value="7">Buying and Selling</option><option value="8">Careers</option><option value="9">Clothing/Apparel</option><option value="10">Computer &amp; Electronics</option><option value="11">Department Stores/Malls</option><option value="12">Education</option><option value="13">Entertainment</option><option value="14">Family</option><option value="15">Financial Services</option><option value="16">Food &amp; Drinks</option><option value="17">Games &amp; Toys</option><option value="18">Gifts &amp; Flowers</option><option value="19">Health and Wellness</option><option value="20">Home &amp; Garden</option><option value="21">Legal</option><option value="22">Marketing</option><option value="23">Non-Profit</option><option value="24">Online Services</option><option value="25">Recreation &amp; Leisure</option><option value="26">Seasonal</option><option value="27">Sports &amp; Fitness</option><option value="28">Telecommunications</option><option value="29">Travel</option></select></td></tr>

					<?php
						if($errorMessage != "") echo "<tr><div class='error'>$errorMessage</div></tr>";
					?>
					<tr>
						<td></td>						
						<td>
							<input type='submit' class="button btn-fix" value="Sign Up">
						</td>
					</tr>
				</table>	
			</div>
			</div>
		</form>
	</div>
	<?php
}

function LinkITSyncProfile() {
  $private_key = get_option("LinkITPrivateKey");
  $params = array("private_key" => $private_key);
  $result = LinkITApiGetStats($params);
  $result = json_decode($result['data']);
  
  update_option("LinkITNPosts", $result->{"nposts"});
  update_option("LinkITNLinks", $result->{"nlinks"});
  update_option("LinkITAvgLinks", $result->{"avglinks"});
  
  update_option("LinkITNCommissions", $result->{"ncommissions"});
  update_option("LinkITTotalSelf", $result->{"totalself"});
  
  update_option("LinkITNReferrals", $result->{"nreferrals"});
  update_option("LinkITTotalReferrals", $result->{"totalreferrals"});
  
  update_option("LinkITReceivedMoney", $result->{"receivedmoney"});
  update_option("LinkITRemainingBalance", $result->{"remainingbalance"});
}

function LinkITRestoreDefaultSettings() {
  $private_key = get_option("LinkITPrivateKey");
  $params = array("private_key" => $private_key);
  $result = LinkITApiRestoreDefaultSettings($params);
}

function LinkITUpload() {
	$title = $_POST['title'];
	$guid = $_POST['guid'];
	$content = $_POST['content'];
	$private_key = get_option("LinkITPrivateKey");

	$params = array("guid" => $guid,
									"title" => $title,
									"content" => $content,
									"private_key" => $private_key,
									"blog_url" => get_bloginfo("url"));
	LinkITAPIUpload($params);
}

function LinkITDownload() {
	global $wpdb;
	$poststable = $wpdb->prefix . "posts";
	
	$guid = $_POST['guid'];
	$private_key = get_option("LinkITPrivateKey");
	$params = array("guid" => $guid,
									"private_key" => $private_key,
									"blog_url" => get_bloginfo("url"));
	$result = LinkITAPIDownload($params);
	$result = json_decode($result['data']);
	$new_content = mysql_real_escape_string($result->{"content"});
	
	$wpdb->query("UPDATE $poststable SET post_content = '$new_content' WHERE post_status = 'publish' AND guid = '$guid'");
}

function LinkITShowPost($post) {
	$link = $post->{"guid"};
	
	echo "<tr><td><a target='_blank' href='$link'>" . $post->{"post_title"} . "</a></td>";
	echo     "<td>" . $post->{"post_modified"} . "</td>";
	echo     "<td>Not Synchronized</td>";
	echo     "<td>";
	
	echo 				 "<form method='post' action='?page=LinkITPluginCentral&step=upload' style='display: inline;'>";
	echo 				 "<input type='hidden' name='title' value=\"" . htmlspecialchars($post->{"post_title"}) . "\">";
	echo 				 "<input type='hidden' name='content' value=\"" . htmlspecialchars($post->{"post_content"}) . "\">";
	echo 				 "<input type='hidden' name='guid' value=\"" . htmlspecialchars($post->{"guid"}) . "\">";
	echo 				 "<input type='submit' value='Upload'></form>";
	
	echo 				 "<form method='post' action='?page=LinkITPluginCentral&step=download' style='display: inline;'>";
	echo         "<input type='hidden' name='guid' value='" . $post->{"guid"} . "'>";
	echo 				 "<input type='submit' value='Download'></form>";
	echo 		 "</td></tr>";
}

function LinkITShowPostNew($post) {
	$title = $post->{"post_title"};
	$content = $post->{"post_content"};
	$guid = $post->{"guid"};
	$date = $post->{"post_date"};
	
	echo "<tr><td>$date</td><td><a target='_blank' href='$guid'>$title</a></td>";
	echo "<td style='text-align: center;'>";
	echo 				 "<form method='post' action='?page=LinkITPluginCentral&step=sync' style='display: inline;'>";
	echo 				 "<input type='hidden' name='title' value=\"" . htmlspecialchars($title) . "\">";
	echo 				 "<input type='hidden' name='content' value=\"" . htmlspecialchars($content) . "\">";
	echo 				 "<input type='hidden' name='guid' value=\"" . htmlspecialchars($guid) . "\">";
	echo 				 "<input type='submit' value='Sync Post'></form>";
	echo "</td><td style='text-align: center;'>";
	echo 				 "<form method='post' action='?page=LinkITPluginCentral&step=preview' style='display: inline;'>";
	echo 				 "<input type='hidden' name='title' value=\"" . htmlspecialchars($title) . "\">";
	echo 				 "<input type='hidden' name='content' value=\"" . htmlspecialchars($content) . "\">";
	echo 				 "<input type='hidden' name='guid' value=\"" . htmlspecialchars($guid) . "\">";
	echo 				 "<input type='submit' value='Preview Post'></form>";
	echo "</td></tr>";
}

function LinkITShowAllPosts() {
	global $wpdb;
	$poststable = $wpdb->prefix . "posts";
	
	$myrows = $wpdb->get_results("SELECT * FROM $poststable WHERE post_status = 'publish'");
	
	foreach($myrows as $row)
		LinkITShowPostNew($row);
}

function LinkITHeader() {
	echo '<div id="menu" class="box">';
	echo '<span class="caption">Welcome, ' . get_option("LinkITEmail") .'</span>';
	echo '<div class="inner">';
		echo '<ul class="menu">';
		echo "<li><a class='menu-link' href='?page=LinkITPluginCentral'><img src='http://www.123linkit.com/images/plugin/user.png'/>My Profile</a></li>";
		echo "<li><a class='menu-link' href='?page=LinkITPluginCentral&step=options'><img src='http://www.123linkit.com/images/plugin/wrench.png'/>Settings</a></li>";
		echo "<li><a class='menu-link' href='?page=LinkITPluginCentral&step=challenge'><img src='http://www.123linkit.com/images/plugin/trophy.png'/>123LinkIt Challenge</a></li>";
		echo "<li><a class='menu-link menu-last' href='?page=LinkITPluginCentral&step=logout'><img src='http://www.123linkit.com/images/plugin/door_open.png'/>Logout</a></li>";
		echo "<li><a class='menu-link menu-last reportbug' href='?page=LinkITPluginCentral&step=bugform'>Report Bug</a></li>";
		echo '</ul>';
		echo '<div id="clear"></div>';
	echo '</div>';
	echo '</div>';
}

function LinkITDashBoard() {
	echo '<div class="box">';
	echo '<span class="caption"><img class="icon" src="http://www.123linkit.com/images/plugin/lightning.png"/>Get Started Using 123LinkIt</span>';
	
	echo "<form method='post' action='?page=LinkITPluginCentral&step=syncAll'>";
	echo "<div class='inner' style='height: 40px;'>";
	echo "<table>";
	echo "<tr><td>";
	echo "Press the \"Synchronize Posts\" button to get started<br> and we'll add affiliate links to all your posts instantly.<br>";
  echo "</td><td>";
  echo "<input id='linkitsavesettings' class='button-primary btn-fix' style='margin-left: 85px; margin-top: 5px;' type='submit' value='Synchronize Posts'>";
  echo "</td></tr></table>";
  echo "</div>";
  echo "</form>";
  echo "</div>";
  
  echo '<div class="box">';
	echo '<span class="caption"><img class="icon" src="http://www.123linkit.com/images/plugin/user.png"/>My Profile</span>';
	echo '<div class="inner">';
      
  echo "<table class=options>";
	echo '<tr>';
		echo '<td class="description">';
			echo '<ul>';
			echo '<li class="list-header">Posts & Links</li>';
			echo '<li>Number of Posts Analyzed</li>';
			echo '<li>Total Links Added</li>';
			echo '<li>Average number of links per post</li>';
						
			echo '<li class="list-header">Your referrals</li>';
			echo '<li>Number of referrals</li>';
			echo '<li>Total amount of commissions from referrals</li>';
			
			echo '</ul>';
		echo '</td>';

		echo '<td class="description-narrow">';
      echo '<ul>';
      echo '<li class="list-header">&nbsp;</li>';
      echo '<li>&nbsp;' . get_option("LinkITNPosts") . '</li>';
      echo '<li>&nbsp;' . get_option("LinkITNLinks") . '</li>';
      echo '<li>&nbsp;' . get_option("LinkITAvgLinks") . '</li>';
      
      echo '<li class="list-header">&nbsp;</li>';
      echo '<li>&nbsp;' . get_option("LinkITNReferrals") . '</li>';
      echo '<li>&nbsp;' . get_option("LinkITTotalReferrals") . '</li>';
      
      echo '</ul>';

    	echo "<form method='post' action='?page=LinkITPluginCentral&step=syncProfile'>";
    	echo "<input class='button-primary btn-fix' style='' type='submit' value='Update'>";
    	echo "</form>";
		echo '</td>';
	echo '</tr>';
	echo "</table>";	
	
	echo '</div>';
	echo '</div>';
}

function LinkITUpdateOptions() {
	if(!empty($_POST)) {
		$cloaked = $_POST['options_cloaked'];
		$nofollow = $_POST['options_nofollow'];
		$newwindow = $_POST['options_new_window'];
		$num_links = $_POST['options_num_links'];
		$blog_category = $_POST['LinkITBlogCategory'];
		$params = array("private_key" => get_option("LinkITPrivateKey"),
										"cloak_links" => $cloaked,
										"nofollow_tags" => $nofollow,
										"links_new_window" => $newwindow,
										"maximum_links" => $num_links);
		
		update_option("LinkITConfig","done");
		update_option("LinkITBlogCategory",$blog_category);
		LinkITApiUpdateOptions($params);
		LinkITAPISetCategory($blog_category);
	}
}

function LinkITSyncpubl($guid, $title, $content) {
	echo "<p>Syncing <strong>$title</strong></p>"; flush(); ob_flush();
	$private_key = get_option("LinkITPrivateKey");

	$params = array("guid" => $guid,
									"title" => $title,
									"content" => $content,
									"private_key" => $private_key,
									"blog_url" => get_bloginfo("url"));

	$result = LinkITAPISync($params);
	$result = json_decode($result['data']);
	$new_content = mysql_real_escape_string(stripslashes($result->{"content"}));
	
	global $wpdb;
	$poststable = $wpdb->prefix . "posts";
	$wpdb->query("UPDATE $poststable SET post_content = '$new_content' WHERE post_status = 'publish' AND guid = '$guid'");
	
	echo "<p>Blog post <strong>$title</strong> has been updated successfully!</p>"; flush(); ob_flush();
}

function LinkITSyncAll() {
	global $wpdb;
	$poststable = $wpdb->prefix . "posts";

	add_option("last_sync","0");
	$last_sync = get_option("last_sync");
  if ($last_sync == "")
    $last_sync = 0;
	$datetime=date('Y-m-d H:i:s', $last_sync);
	$myrows = $wpdb->get_results("SELECT post_modified, id, post_title  FROM $poststable WHERE post_status = 'publish' AND post_type = 'post' AND post_modified > '$datetime'");
	echo "<hr>";

	// make sure the option last_sync exists
	if (!$myrows)
		echo "All posts are synchronized";
	else
		foreach($myrows as $row) {
		$title = $row->{"post_title"};
		$modified = strtotime($row->{"post_modified"});
		if ($modified >= get_option("last_sync"))
			{
			LinkItPublish($row->{"id"});
			echo "<b>" . $title . "</b> - synchronizing"."<br>"; flush(); ob_flush();
			}
		else
			echo $title . " - allready synchronized <br>"; flush(); ob_flush();
		}
	echo "<hr>";
	update_option("last_sync",time());
	LinkITSyncProfile();
}

function LinkITChallenge() {
  $results = LinkITApiGetRandomKeywords();  
  $results = json_decode($results['data']);
  echo '<div id="options" class="box">';
	echo '<span class="caption"><img class="icon" src="http://www.123linkit.com/images/plugin/trophy.png"/>The 123LinkIt Challenge</span>';
	echo '<div class="inner" style="height: 150px;">';
	
	echo 'Lacking ideas for your blog post? We challenge you to write one using the following random keywords:';
	echo '<ul style="margin-left: 20px; margin-top: 10px; list-style: circle; width: 50em;">';
	foreach($results as $value)
	  echo '<li style="float: left; width: 25em;">' . $value;
	echo '</ul>';
	echo '</div>';
	echo '</div>';
}

function LinkITOptions() {
	LinkITUpdateOptions();
	
	$params = array("private_key" => get_option("LinkITPrivateKey"));
	$results = LinkITApiGetOptions($params);
	$results = json_decode($results['data']);
	
	$cloaked = $results->{"cloak_links"} == 1 ? "checked" : "";
	$nofollow = $results->{"nofollow_tags"} == "1" ? "checked" : "";
	$newwindow = $results->{"links_new_window"} == "1" ? "checked" : "";

	if($results->{"maximum_links"} == "1") $maximumlinks = "checked";
	else $onelink = "checked";
	
	echo '<div id="options" class="box">';
	echo '<span class="caption"><img class="icon" src="http://www.123linkit.com/images/plugin/wrench.png"/>Options</span>';
	echo '<div class="inner">';
	
	echo "<form method='post' action='?page=LinkITPluginCentral&step=options'>";
	echo "<table class='options'>";
	echo '<tr>';
		echo '<td class="description">';
			echo '<ul>';
			echo '<li class="list-header">Links</li>';
			echo "<li><input id='cloak' type='checkbox' name='options_cloaked' $cloaked>&nbsp;<label for='cloak'>Automatically cloak all links</label>&nbsp;</li>";
			echo "<li><input id='nofollow' type='checkbox' name='options_nofollow' $nofollow>&nbsp;<label for='nofollow'>Add nofollow tags to all links</label>&nbsp;</li>";
			echo "<li><input id='newwindow' type='checkbox' name='options_new_window' $newwindow>&nbsp;<label for='newwindow'>Open links in new window</label>&nbsp;</li>";
			echo '<li class="list-header">Blog Category</li>'; //blog category
			echo '<li>';
			LinkITRenderCategory();
			echo '</li>';
			echo "</ul>";
	echo "</td><td class='description'>";			
	    echo '<ul>';
			echo '<li class="list-header">Need help?</li>';
			echo "<li><a href='http://www.123linkit.com/general/faq'>See our FAQ page</a></li>";
			echo "<li><a href='http://getsatisfaction.com/123linkit'>Search our support forum</a></li>";
			echo "<li><a href='http://www.123linkit.com/general/contact_us'>Contact us</a></li>";
			echo "<li>If you're having problems with the current settings, you can roll back any changes and settings you made by <a href='?page=LinkITPluginCentral&step=resetAll'>resetting the plugin.</a></li>";
			echo '<li class="list-header">Support 123LinkIt</li>';
			echo "<li>Give it a good rating on <a href='http://wordpress.org/extend/plugins/bb-login.php?re=http://wordpress.org/extend/plugins/123linkit-affiliate-marketing-tool/'>WordPress.org</a></li>";
			echo '<li>&nbsp;</li>';
			echo '<li>&nbsp;</li>';
			echo '</ul>';
			echo '</td></tr>';
	echo "<tr><td class='description'>";
		echo "<input id='linkitsavesettings' class='button-primary btn-fix-options' type='submit' value='Save Changes'>";
		echo "</form>";	
		
		echo "<form method='post' action='?page=LinkITPluginCentral&step=restoreDefaultSettings' style='display: block; float: left;'>";
		echo "<input class='button btn-fix-options' type='submit' value='Restore Defaults'>";
		echo "</form>";
		
		//reset button
		// echo "<form method='post' action='?page=LinkITPluginCentral&step=resetAll' style='display: block; float: left;'>";
		// echo "<input class='button btn-fix-options' type='submit' value='Reset Plugin'>";
		// echo "</form>";
	echo "</td></tr></table>";
	echo '</div>';
	echo '</div>';
}

function TryOptions() {
	add_option("LinkITEmail", "0", false, true);
	add_option("LinkITPrivateKey", "0", false, true);
	add_option("LinkITPublicKey", "0", false, true);
	
	add_option("LinkITNPosts", "0", false, true);
	add_option("LinkITNLinks", "0", false, true);
	add_option("LinkITAvgLinks", "N/A", false, true);
	
	add_option("LinkITNCommissions", "0", false, true);
	add_option("LinkITTotalSelf", "0 USD", false, true);
	
	add_option("LinkITNReferrals", "0", false, true);
	add_option("LinkITTotalReferrals", "0 USD", false, true);
	
	add_option("LinkITReceivedMoney", "0 USD", false, true);
	add_option("LinkITRemainingBalance", "0 USD", false, true);
}
function LinkITSetCategory(){
  $blog_category = $_POST['LinkITBlogCategory'];
  update_option("LinkITBlogCategory", $blog_category);
  LinkITAPISetCategory($blog_category);
}

function LinkITGetCategory(){
  $result = LinkITAPIGetCategory();
  $result = json_decode($result['data']);
  if ($result->{'error'} == "0") {
    $blog_category = $result -> {'blog_category'};
    update_option("LinkITBlogCategory", $blog_category);
  }
}

function LinkITBugReport() {
	$msg = $_POST['LinkITMsg'];
	global $wpdb;
	$table = $wpdb->prefix . "linkit_requests";
	$rows = $wpdb->get_results("SELECT * FROM $table ORDER BY time DESC");
	$data = "request | sent | recived | time <br/>";;
	foreach ($rows as $row) {
		$request = $row -> {'request'};
		$sent = $row -> {'data_sent'};
		$recived = $row -> {'data_recived'};
		$time = $row -> {'time'};
		$data=$data . "$request | $sent | $recived | $time <br/>";
		
	}
	LinkITAPIBugReport($msg,$data);
}


function LinkITRenderBugReport() {
	?>
	<form method='post' action='?page=LinkITPluginCentral&step=reportbug'>
	  <div class="box">
		<span class="caption">Report Bug</span>
		<div class="inner">
		<table style="margin: 0px auto;">
					<tr><td><label for="message">Message:</label></td>
						  <td><textarea style="width: 500px;" id="message" type='text' name='LinkITMsg' onclick="this.value=''">Tell us what went wrong</textarea></td></tr>

					<?php
						if($errorMessage != "") echo "<tr><div class='error'>$errorMessage</div></tr>";
					?>
					<tr>
						<td></td>						
						<td>
							<input type='submit' class="button btn-fix" value="Send">
						</td>
					</tr>
				</table>	
			</div>
			</div>
		</form>
	<?php

}
function LinkITRenderCategory(){?>
<script type="text/javascript">
jQuery(document).ready(function($) {
<?php
  $blog_category=get_option('LinkITBlogCategory');
  echo '$(\'#blogcategory\').'."val(\"$blog_category\");";
?>
});
</script>
<label for="blogcategory">Select your blog's category:</label>
<select name="LinkITBlogCategory" id="blogcategory" style="width:200px;"><option value="1">Accessories</option><option value="2">Art/Photo/Music</option><option value="3">Automotive</option><option value="4">Beauty</option><option value="5">Books/Media</option><option value="6">Business</option><option value="7">Buying and Selling</option><option value="8">Careers</option><option value="9">Clothing/Apparel</option><option value="10">Computer &amp; Electronics</option><option value="11">Department Stores/Malls</option><option value="12">Education</option><option value="13">Entertainment</option><option value="14">Family</option><option value="15">Financial Services</option><option value="16">Food &amp; Drinks</option><option value="17">Games &amp; Toys</option><option value="18">Gifts &amp; Flowers</option><option value="19">Health and Wellness</option><option value="20">Home &amp; Garden</option><option value="21">Legal</option><option value="22">Marketing</option><option value="23">Non-Profit</option><option value="24">Online Services</option><option value="25">Recreation &amp; Leisure</option><option value="26">Seasonal</option><option value="27">Sports &amp; Fitness</option><option value="28">Telecommunications</option><option value="29">Travel</option></select>
	<?php

}

function LinkITResetAll() {

	update_option("LinkITConfig","");
	update_option("last_sync","");
	update_option("LinkITEmail","");
	update_option("LinkITPrivateKey","");
	update_option("LinkITPublicKey","");
	update_option("LinkITNPosts","");
	update_option("LinkITNLinks","");
	update_option("LinkITAvgLinks","");
	update_option("LinkITNCommissions","");
	update_option("LinkITTotalSelf","");
	update_option("LinkITNReferrals","");
	update_option("LinkITTotalReferrals","");
	update_option("LinkITReceivedMoney","");
	update_option("LinkITRemainingBalance","");
	update_option("LinkITBlogCategory","");
	
  global $wpdb;
  $table_name = $wpdb->prefix . "linkit_cached_posts";
  $wpdb -> query("delete all from $table_name");
  $datetime=date('Y-m-d H:i:s');
//  $_REQUEST['step'] = '';
//  LinkITRenderLogin($errorMessage);
}

function LinkITPluginCentral() {
/*  if (strlen(get_option("LinkITEmail")) > 4)     
    LinkITGetCategory();*/
  if (strlen(get_option("LinkITEmail")) <= 4)
    {
    update_option("LinkITEmail","");
    update_option("LinkITPrivateKey", "");
		update_option("LinkITPublicKey", "");
    }
  register_activation_hook('lib/proxy.php','LinkITCreateCacheTable');
  echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/123linkit-affiliate-marketing-tool/123linkit.css" />' . "\n";
	TryOptions();
	LinkITRenderHeader();

	$errorMessage = "";
	$step = $_GET['step'];
  if ($step == "resetAll") {
    LinkITResetAll(); 
    $step = "";
  }
	if($step =='login') {
		$userResult = LinkITLogin();
		if($userResult->{'error'} != "0") {
			$errorMessage = $userResult->{'error'};
		} else {
			update_option("LinkITEmail", $_REQUEST['LinkITEmail']);
			update_option("LinkITPrivateKey", $userResult->{"private_key"});
			update_option("LinkITPublicKey", $userResult->{"public_key"});
      LinkITGetCategory();			
		}
		$step = "";
	} else if($step == 'logout') {
		update_option("LinkITEmail", "");
		update_option("LinkITPrivateKey", "");
		update_option("LinkITPublicKey", "");
		$step = "";
	}
		else if ($step == 'signup') {
			LinkITRenderSignUp();
		}
			else if ($step == 'createuser') {
				$userResult = LinkITCreateUser();
				if ($userResult -> {'error'} != "0")
					$errorMessage = $userResult -> {'error'};				
				else 
					{
					$userResult = LinkITLogin();
					update_option("LinkITEmail", $_REQUEST['LinkITEmail']);
					update_option("LinkITPrivateKey", $userResult->{"private_key"});
					update_option("LinkITPublicKey", $userResult->{"public_key"});
					}
				$step = "";
			}
      else if ($step == 'install') {
        LinkITCreateCacheTable();
      }

	
	if(get_option("LinkITPublicKey") != "") {
		LinkITHeader();

  //report bug
  if ($step == 'reportbug') {
	  $userResult = LinkITBugReport();
		if ($userResult -> {'error'} != "0")
	  	$errorMessage = $userResult -> {'error'};				
	}
		// actions
		if($step == 'syncAll') {
			LinkITSyncAll();
		} else if($step == 'syncProfile') {
		  LinkITSyncProfile();
		} else if($step == 'restoreDefaultSettings') {
		  LinkITRestoreDefaultSettings();
		  $step = 'options';
		}
		
		// and pages
		if($step == 'options') {
			LinkITOptions();
		} else if($step == 'challenge') {
		  LinkITChallenge();
		} else if ($step == 'bugform') 
	      LinkITRenderBugReport();
      else 
			LinkITDashBoard();
	}
//	else if($step == "") LinkITRenderLogin($errorMessage);
  if (strlen(get_option("LinkITEmail")) < 4) 
    LinkITRenderLogin($errorMessage);
	if (get_option("LinkITConfig") == "done"){ 
	    ?>
			<script type="text/javascript">
      jQuery(document).ready(function($){
        $("#linkitnotification").hide();
      });
	  	</script>
	  	<?php
	}
}
?>
