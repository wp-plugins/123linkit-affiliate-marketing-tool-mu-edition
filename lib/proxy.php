<?php

define('BASE_URL', "http://www.123linkit.com/");

function LinkITCreateRequestsTable()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "linkit_requests";
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {

      $sql = "CREATE TABLE ". $table_name ."(
              request varchar(255) NOT NULL,
	      data_sent text NOT NULL,
	      data_recived text NOT NULL,
              time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	      id int NOT NULL auto_increment,
	      UNIQUE KEY id (id)			
              );";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    }

//    $rows_affected = $wpdb->insert( $table_name, array( 'updated' => current_time('mysql'), 'guid' => 'hello', 'contents' => 'ciao' ) );
}

function LinkITAddRequest($request,$data,$response)
{
// make sure we have the table 
    LinkITCreateRequestsTable();

    global $wpdb;
    $table_name = $wpdb->prefix . "linkit_requests";

    $variables_url = "";
    foreach($data as $key=>$value) {
    	$variables_url .= urlencode($key) . '=' . urlencode($value) . '&';
    }

    $wpdb->insert( $table_name, array( 'request' => $request, 'data_sent' => $variables_url, 'data_recived' => $response ));
    
    $rows=$wpdb->get_results("SELECT id FROM $table_name WHERE request = '$request' ORDER BY time DESC");
    $i=0;
    foreach ($rows as $row){
	$i++;
	$id=$row -> {'id'};
	if ($i>3)
	$wpdb->query("DELETE from $table_name WHERE id = '$id'");
    }
	
}

function LinkITCreateCacheTable()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "linkit_cached_posts";
//    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {

      $sql = "CREATE TABLE " . $table_name . " (
              guid varchar(255) NOT NULL,
              contents text NOT NULL,
              hash varchar(255) NOT NULL,
              updated datetime NOT NULL,
              UNIQUE KEY guid (guid)
              );";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
//    }

//    $rows_affected = $wpdb->insert( $table_name, array( 'updated' => current_time('mysql'), 'guid' => 'hello', 'contents' => 'ciao' ) );
}

function LinkITDeleteCachedPost($guid)
{
    global $wpdb;
    $table_name = $wpdb->prefix . "linkit_cached_posts";

    $wpdb->query("DELETE FROM $table_name WHERE guid = '$guid'");
}

function LinkITGetCachedPost($guid)
{

    global $wpdb;
    $table_name = $wpdb->prefix . "linkit_cached_posts";
    $post = $wpdb->get_row("SELECT * FROM $table_name WHERE guid = '$guid'");
    $t1=time();
    $t2=strtotime($post->updated);
    if ( $t1 - $t2 <= 5*60 ) {
      //check for new version
      $private_key = get_option('LinkITPrivateKey');
      $params = array("guid" => $guid,
                          "private_key" => $private_key,
                          "blog_url" => get_bloginfo("url"));
      $result = LinkITAPIGetPostHash($params);
      $result = json_decode($result['data']);
        
      $server_hash = $result -> hash;
      $local_hash = $post -> hash;

      if ($server_hash == $local_hash)
        return $post -> contents;
    }
    return 0;
}


function LinkITAddCachedPost($guid, $contents, $hash)
{
    global $wpdb;
    $table_name = $wpdb->prefix . "linkit_cached_posts";
    
    $rows_affected = $wpdb->insert( $table_name, array( 'updated' => current_time('mysql'), 'guid' => $guid, 'contents' => $contents, 'hash' => $hash) );
}

function LinkITCurlOpen($url, $vars, $timeout = 10) {
	$c = curl_init($url);
	$variables_url = "";
	foreach($vars as $key=>$value) {
	  $variables_url .= urlencode($key) . '=' . urlencode($value) . '&';
  }

  curl_setopt($c, CURLOPT_USERAGENT, "123LinkIT Plugin");
  curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($c, CURLOPT_POST, true);
  curl_setopt($c, CURLOPT_HTTPHEADER, array('Expect:'));
  curl_setopt($c, CURLOPT_TIMEOUT, $timeout);
  if($variables_url)
          curl_setopt($c, CURLOPT_POSTFIELDS, $variables_url);
	$response['data'] = curl_exec($c);
	$response['code'] = curl_getinfo($c, CURLINFO_HTTP_CODE);
	return $response;
}

function LinkITFOpen($url, $vars) {
	$variables_url = "/?";
	foreach($vars as $key=>$value) {
		$variables_url .= urlencode($key) . '=' . urlencode($value) . '&';
  	}
	$handle = @fopen($url.$variables_url,"b");
	if ( $handle ) {
		$res = "";
	  while ( !feof($handle) )
	    $res .= fread($handle, 128);
	  $response['data'] = $res;
	  fclose($handle);
	}
	return $response;
}

function LinkITFSockOpen($host, $resource, $vars) {
  $variables_url = "";
	foreach($vars as $key=>$value) {
		$variables_url .= urlencode($key) . '=' . urlencode($value) . '&';
  }
  $fp = fsockopen($host);
  if ($fp) {    
    
    $out = "GET /$resource/?$variables_url HTTP/1.1\r\n";
    
    echo $out;
    
    $out .= "Host: blogurl\r\n";
    $out .= "User-Agent: 123LinkIt Plugin\r\n\r\n";
    $out .= "Connection: Close\r\n\r\n";
    fwrite($fp, $out);
    $response['data'] = "";
    while (!feof($fp)) {
        $response['data'] .= fgets($fp, 256);
    }
    fclose($fp);
  }
	return $response;
}

function LinkITMakeRequest($url, $vars, $timeout = 10) {
	if(function_exists('curl_init')) {
		$response=LinkITCurlOpen($url, $vars, $timeout);
// loging
		LinkITAddRequest($url,$vars,$response['data']);		
		return $response;
	} else if(ini_get('allow_url_fopen') && function_exists('stream_get_contents')) {
		$response=LinkITFOpen($url, $vars);
// loging
		LinkITAddRequest($url,$vars,$response['data']);		
		return $response;

	} else {
	  $host = str_replace("http://","" ,BASE_URL);
	  $host = str_replace("/","" ,$host);
	  $resource = str_replace(BASE_URL, "", $url); 
		$response = LinkITFSockOpen($host, $resource, $vars);
// loging
		LinkITAddRequest($url,$vars,$response['data']);		
		return $response;

	}
}

function LinkITAPIGetPostHash($params) {
	return LinkITMakeRequest(BASE_URL . "api/getHashedPost", $params);
}

function LinkITAPISetCategory($blog_category) {
  $request = array('blog_category' => $blog_category, 'blog_url' => get_bloginfo("url"), 'private_key' => get_option('LinkITPrivateKey'));
  return LinkITMakeRequest(BASE_URL . "api/setBlogCategory", $request);
}

function LinkITAPIGetCategory() {
  $request = array('blog_url' => get_bloginfo("url"), 'private_key' => get_option('LinkITPrivateKey'));
  return LinkITMakeRequest(BASE_URL . "api/getBlogCategory", $request);
}

function LinkITAPIBugReport($msg,$data) {
	$request = array('msg' => $msg, 'data' => $data);
	return LinkITMakeRequest(BASE_URL . "api/reportBug", $request);
}

function LinkITAPICreateUser($email,$password,$passwordc,$blogcategory) {
	$request = array('email' => $email, 'password' => $password, 'passwordc' => $passwordc, 'blogcategory' => $blogcategory, 'blogurl' => get_bloginfo("url"));
	return LinkITMakeRequest(BASE_URL . "api/createuser", $request);
}

function LinkITAPILogin($email, $password) {
	$request = array('email' => $email, 'password' => $password);
	return LinkITMakeRequest(BASE_URL . "api/login", $request);
}

function LinkITApiUpload($params) {
	return LinkITMakeRequest(BASE_URL . "api/createPost", $params);
}

function LinkITApiDownload($params) {
	return LinkITMakeRequest(BASE_URL . "api/downloadPost", $params, 5);
}

function LinkITApiGetOptions($params) {
	return LinkITMakeRequest(BASE_URL . "api/getOptions", $params);
}

function LinkITApiUpdateOptions($params) {
	return LinkITMakeRequest(BASE_URL . "api/updateOptions", $params);
}

function LinkITApiGetStats($params) {
  return LinkITMakeRequest(BASE_URL . "api/getStats", $params);
}

function LinkITApiRestoreDefaultSettings($params) {
  return LinkITMakeRequest(BASE_URL . "api/restoreDefaultSettings", $params);
}

function LinkITApiGetRandomKeywords() {
  return LinkITMakeRequest(BASE_URL . "api/getRandomKeywords", array('nothing' => 'true'));
}


?>
