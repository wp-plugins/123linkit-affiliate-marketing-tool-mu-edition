<?php

$api_address = "www.123linkit.com";
header("Cache-Control: no-cache");

$path = "http://$api_address/api/";
$agent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-GB; rv:1.9.0.5) Gecko/2008120122 Firefox/3.0.5";

function posturl($url, $data){
    $url = parse_url($url);

    if ($url['scheme'] != 'http') die('Only HTTP request are supported !');

    // extract host and path:
    $host = $url['host'];
    $path = $url['path'];

    // open a socket connection on port 80
    $fp = fsockopen($host, 80);

    // send the request headers:
    fputs($fp, "POST $path HTTP/1.1\r\n");
    fputs($fp, "Host: $host\r\n");
    fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
    fputs($fp, "Content-length: ". strlen($data) ."\r\n");
    fputs($fp, "Connection: close\r\n\r\n");
    fputs($fp, $data);

    $result = ''; 
    while(!feof($fp)) {
        // receive the results of the request

        $result .= fgets($fp, 128);
    }

    // close the socket connection:
    fclose($fp);

    // split the result header from the content
    $result = explode("\r\n\r\n", $result, 2);
    $header = isset($result[0]) ? $result[0] : '';
    $content = isset($result[1]) ? $result[1] : '';

    // return as array:
    return $content;
}

//When we get a post request...
if($_SERVER['REQUEST_METHOD'] == 'POST') {	   
	$postvars = '';
	$url = '';
	while ( ($element = current( $_POST ))!==FALSE ) {
			 if(key($_POST) == "url") {
				$url = $element;
				next($_POST);
			 } else {
				$new_element = str_replace( '&', '%26', $element );
				$new_element = str_replace( ';', '%3B', $new_element );
				$postvars .= key( $_POST ).'='.$new_element.'&';
				next( $_POST );
			}
	}
	
	$results = posturl($path.$url, $postvars);
	header("Content-type: text/plain");
	
	$results = str_replace("123linkit-affiliate-marketing-tool", "123linkit-affiliate-marketing-tool-mu-edition", $results);
	echo $results;
} else {
	 echo "buzz off";	
}
?>
