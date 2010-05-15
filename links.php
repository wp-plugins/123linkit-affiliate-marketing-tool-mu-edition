<?php
    $api_address = "www.123linkit.com";


    $url = "http://$api_address/api/click";

    if(isset($_GET)){
				if(isset($_GET['vals'])) {
		        $vals = explode(",", $_GET['vals']);
						$post_id = "";
						$link_id = "";
		        $blog_id = $vals[0];
		        $ad_id = $vals[1];
		        $key = $vals[2];
		    } else {
		    		$post_id = $_GET['pid'];
		        $blog_id = $_GET['bid'];
		        $ad_id = $_GET['aid'];
		        $link_id = $_GET['lid'];
		        $key = $_GET['key'];
		    }
    
        header("Location: $url?post_id=$post_id&blog_id=$blog_id&advertiser_id=$ad_id&link_id=$link_id&_pubkey=$key");
    }
    
?>
