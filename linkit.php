<?php
/*
Plugin Name: 123Linkit Affiliate Marketing Tool
Plugin URI:  http://www.123linkit.com/general/download
Description: 123LinkIt Affiliate Plugin - Generate money easily from your blog by transforming keywords into affiliate links. No need to apply to affiliate networks or advertisers - we do it all for you. Just pick from our list of recommendations and you're good to go! To get started, sign up at 123LinkIt.com for an account and Navigate to Settings -> 123LinkIt configuration to input your API keys.
Version: 0.5
Author: 123Linkit, LLC.
Author URI: http://www.123linkit.com/
*/

$api_address = "www.123linkit.com";

//Action adds all the admin menus that I want
add_action('admin_menu', 'linkit_custom_advertise_box');
add_action("admin_init", "register_linkitsettings");
add_action("admin_print_scripts", "linkit_admin_head");
add_action("admin_print_styles", "linkit_admin_styles");
add_action('wp_print_scripts', 'linkit_ScriptsAction');
if(get_blog_option($blog_id, 'linkit_allow_auto') == 1){
    add_filter('the_content', 'change_content');
}

function linkit_ScriptsAction() {
	echo "<script type='text/javascript'>function getBaseUrl() { return '" . get_bloginfo('url') . "'; }</script>";
	wp_enqueue_script('123LinkIt', "http://www.123linkit.com/javascripts/tracker.js");
}

function register_linkitsettings(){
	register_setting("linkit-options", "linkit_keys");
	register_setting("linkit-options", "linkit_allow_auto");
	wp_register_style('tblcss', WP_PLUGIN_URL . '/123linkit-affiliate-marketing-tool-mu-edition/css/jquery.tablesorter.css');
	wp_register_style('linkitcss', WP_PLUGIN_URL . '/123linkit-affiliate-marketing-tool-mu-edition/css/linkit.css');
}

function linkit_custom_advertise_box(){
	if( function_exists('add_meta_box')){
		$box = add_meta_box('linkit_advertiseid', __('Advertise Post', 'linkit_textdomain'), 'linkit_inner_custom_box', 'post', 'normal', 'high');
		$box = add_meta_box('linkit_advertiseid', __('Advertise Post', 'linkit_textdomain'), 'linkit_inner_custom_box', 'page', 'normal', 'high');
	}
	add_options_page('123Linkit Configuration', "123Linkit Configuration", 8, "123linkit_menu", "linkit_options");

}

function linkit_proxy_url(){
	return get_bloginfo('wpurl').'/wp-content/plugins/123linkit-affiliate-marketing-tool-mu-edition/simpleproxy.php';
}

function linkit_admin_head(){
	global $blog_id;
//We use need some simple convenience functions here
$keys = get_blog_option($blog_id, 'linkit_keys');
?>
	<script type="text/javascript" src="http://www.123linkit.com/javascripts/tiny_mce.js"></script>
    <script type="text/javascript">
        function getPluginDir(){
            return"<?php echo WP_PLUGIN_URL; ?>";
        }
	function getKeys(){ 
            return {'_pubkey': '<?php echo $keys['_pubkey'];?>', '_privkey':'<?php echo $keys['_privkey'];?>'};
        }
        function getBaseUrl(){
            return "<?php echo get_bloginfo('url'); ?>";
        }
    </script>
<?php
	wp_enqueue_script('linkitscripts', "http://www.123linkit.com/javascripts/client3.js", array('jquery'), false);
	wp_enqueue_script('tblsorter', "http://www.123linkit.com/javascripts/jquery.tablesorter.min.js", array('jquery'), false);
}
function linkit_admin_styles(){
	wp_enqueue_style('tblcss');
	wp_enqueue_style('linkitcss');
}

if (!CUSTOM_TAGS){
	$allowedposttags['a'] = array(
		'class' => array (),
		'href' => array (),
		'id' => array (),
		'title' => array (),
		'rel' => array (),
		'rev' => array (),
		'name' => array (),
		'target' => array(),
		'onclick' => array(),
		'onmouseout' => array(),
		'onmousedown' => array()
	);
}

function linkit_inner_custom_box(){
	?>
     <div class='linkit_main'>
        <div class='linkit_content'>
			<?php //echo "<pre>---"; //print_r(CUSTOM_TAGS); echo "---</pre>"; ?>
	        <div class='linkit_header'>
                <img src='http://<?php global $api_address; echo $api_address; ?>/images/plugin_header.jpg' />
                <a href='#' class='update_post'>Add Affiliate Links</a>
                <div class="notify_div">
                    <div class="ajax_working" style='display: none;'>
                        <img src="<?php echo WP_PLUGIN_URL;?>/123linkit-affiliate-marketing-tool-mu-edition/css/ajax-loader.gif"/>
                        Working...
                    </div>
                    <div class="error">
                    </div>
                </div>
            </div>
            <div class='result'>
            </div>
        </div>
    </div> 
<?php
}
function linkit_options(){

	global $blog_id;
	$msg = "";
	if( isset($_POST['linkit_keys_submit']) && $_POST['linkit_keys_submit']=='true' ){
		
		$keys['_pubkey']  = $_POST['_pubkey'];
		$keys['_privkey'] = $_POST['_privkey'];
		update_blog_option($blog_id, 'linkit_keys', $keys);
		if( $new_keys['_pubkey']!="" && $new_keys['_pubkey']!="" ){
			update_blog_option($blog_id, 'linkit_allow_auto', '1');
			$auto = 'checked';
		} else {
			update_blog_option($blog_id, 'linkit_allow_auto', '');
			$auto = '';
		}
		$msg = "API Key value updated successfully.";
		
	} else {
		$keys = get_blog_option($blog_id, 'linkit_keys');
		$auto = get_blog_option($blog_id, 'linkit_allow_auto');
		//Do they have value on? If so echo checked else do nothing
		//Sorry about the ternary but really? who wants to write all those braces!
		$checked = $auto == 1 ? "checked": "";
	}
	
?>
		
<div class="wrap" action="options.php">
	
	<h2>123Linkit Advertising Plugin Settings</h2>
	<p>The form below allows you to append your public and private key that was given to you by 123Linkit. <p>
	<p>If you haven't signed up click <a href="http://www.123linkit.com/users/new">here</a>.</p>
	
	<?php if($msg): ?><div class="updated fade"><p><?php echo $msg; ?></p></div><?php endif; ?>
	
	<form method="post" action="">
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="_pubkey">Public API Key:</label></th>
				<td><input type="text" size="32" name='_pubkey' id="_pubkey" value="<?php echo $keys['_pubkey']; ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="_privkey">Private API Key:</label></th>
				<td><input type="text" size="32" name='_privkey' id="_privkey" value="<?php echo $keys['_privkey']; ?>" /></td>
			</tr>
		</table>
		<p class="submit">
			<input type="hidden" name="linkit_keys_submit" value="true" />
			<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
		</p>
	</form>
</div>
	<?php
}

?>
