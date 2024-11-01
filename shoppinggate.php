<?php
/**
 * @package ShoppingGate
 * @version 1
 */
/*
Plugin Name: ShoppingGate for WooCommerce
Plugin URI: 
Description: This plugin will allow your customers to post feedback about their shopping experience on ShoppingGate. This Plugin works with WooCommerce only and you must have the API information from ShoppingGate.
Author: ShoppingGate
Version: 1
Author URI: http://shoppinggate.dk
*/

// create a custom plugin settings menu
add_action('admin_menu', 'shoppinggate_menu');
register_activation_hook(   __FILE__, 'shoppinggate_on_activation' );
register_deactivation_hook(   __FILE__, 'shoppinggate_on_deactivation' );
register_uninstall_hook(   __FILE__, 'shoppinggate_on_deactivation' );

// create a hook for WooCommerce order_status_completed
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	//update_option('message', 'WooCommerce is installed');
	add_action('woocommerce_order_status_completed', 'shoppinggate_order_completed');
} else {
	update_option('message', 'WooCommerce is not installed');
}

function shoppinggate_menu() {
	
	// create new top-level menu
	add_menu_page('ShoppingGate settings', 'ShoppingGate', 'administrator', __FILE__, 'shoppinggate_settings_page', plugins_url('/images/icon.png', __FILE__));
	
	// call register settings function
	add_action('admin_init', 'register_shoppinggate_settings');
}

function shoppinggate_on_activation() {
	// first time installation
	$url = 'http://shoppinggate.dk/system/api.php';
	$post_data = array(
		'action' => 'generateActivationCode'
	);
	
	$result = wp_remote_post ( $url, array('body' => $post_data ) );
	
	// saving result
	update_option('activationcode', $result['body']);
}

function shoppinggate_on_deactivation() {
	// deactivate so people are allowed to post feedback without first-hand-checking order number.
	$url = 'http://shoppinggate.dk/system/api.php';
	$post_data = array(
		'action' => 'disableAPIAccess'
	);
	
	$result = wp_remote_post ( $url, array('body' => $post_data ) );
}

function register_shoppinggate_settings() {
	// register our settings
	register_setting('shoppinggate-settings-group', 'apitoken');
	register_setting('shoppinggate-settings-group', 'apiid');
	
	// check for valid information
	$url = 'http://shoppinggate.dk/system/api.php';
	$post_data = array(
		'apitoken' => urlencode(esc_attr(get_option('apitoken'))),
		'apiid' => urlencode(esc_attr(get_option('apiid'))),
		'action' => 'checkLogin'
	);
	
	$result = wp_remote_post ( $url, array('body' => $post_data ) );
	
	// saving result to filter errors
	update_option('message', $result['body']);
}

function shoppinggate_order_completed($order_id) {
	// call shoppinggate to create an access card for this order
	$url = 'http://shoppinggate.dk/system/api.php';
	$post_data = array(
		'apitoken' => urlencode(esc_attr(get_option('apitoken'))),
		'apiid' => urlencode(esc_attr(get_option('apiid'))),
		'action' => 'addAccessCard',
		'customs' => urlencode(array("order_id" => $order_id))
	);
	
	$result = wp_remote_post ( $url, array('body' => $post_data ) );
	
	// saving result to filter errors
	update_option('message', $result['body']);
}

function shoppinggate_admin_tabs( $current = 'homepage' ) {
    $tabs = array( 'homepage' => 'Welcome', 'settings' => 'Settings', 'email' => 'Email' );
    echo '<div id="icon-themes" class="icon32"><br></div>';
    echo '<h2 class="nav-tab-wrapper">';
    foreach( $tabs as $tab => $name ){
        $class = ( $tab == $current ) ? ' nav-tab-active' : '';
        echo "<a class='nav-tab$class' href='" . basename($_SERVER['REQUEST_URI']) . "&tab=$tab'>$name</a>";

    }
    echo '</h2>';
}

function shoppinggate_settings_page() {
	$tab = $_GET['tab'] ?: "homepage";
	shoppinggate_admin_tabs($tab);
echo '<div class="wrap">';
	switch ($tab) {
		case 'homepage':
?>
	<h1>How to get started</h1>
	<p>Its really easy to get started using ShoppingGate. We Promise. Follow theese steps below.</p>
	<ol>
		<li>First find your webshop / e-store on <a href="http://shoppinggate.dk/" target="_blank">ShoppingGate.dk</a> through the search function. <small>(If you did not find your site, please add / create it.)</small></li>
		<li>Before moving on, please create a email-address called <b>shoppinggate@your-domain.dk</b> so we can insure you are the correct owner of this site.</li>
		<li>Look at your site profile on shoppinggate and, add following to the browser URL: <b>/plugin</b> <small>(so it would look like this: http://shoppinggate.dk/e-butik/YOUR-LINK/plugin)</small></li>
		<li>On that page please put this code <b><?php echo esc_attr(get_option('activationcode')); ?></b> in the field and click OK. You will receive an email with API information.</li>
		<li>You will receive the API informations through your shoppinggate@ email.</li>
	</ol>
	<small><i>Bemærk: Har du haft afinstalleret eller slettet app'en skal du gøre ovenstående igen, da din API oplysninger vil være ugyldige.</i></small>
	<p>Okay, it can sound confusing. But if you have the slightest need of help, just contact us <a href="http://shoppinggate.dk/kontakt" target="_blank">here.</a></p>
	<?php
	break;
	case 'settings';
	?>
	<h2>ShoppingGate</h2>
	<form method="post" action="options.php">
		<?php settings_fields('shoppinggate-settings-group'); ?>
		<?php do_settings_sections('shoppinggate-settings-group'); ?>
		<h1>Settings</h1>
		<table class="form-table">
			<tr valign="top">
			<th scope="row">API Token</th>
			<td><input type="text" name="apitoken" value="<?php echo esc_attr(get_option('apitoken')); ?>" /></td>
			</tr>
			
			<tr valign="top">
			<th scope="row">API ID</th>
			<td><input type="text" name="apiid" value="<?php echo esc_attr(get_option('apiid')); ?>" /></td>
			</tr>
			
			<tr valign="top">
			<th scope="row">Latest API message</th>
			<td><input type="text" name="apiid" value="<?php echo esc_attr(get_option('message')); ?>" disabled /></td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
	<?php
	break;
	case 'email':
	?>
	<h1>Email Reminder</h1>
	<p>Let us remind your customer to give you feedback at ShoppingGate. 2 days after you have completed the order, we will send and email to the customer about validating you.</p>
	<p>Please insure that you write in your sales terms that when purchasing they must accept that ShoppingGate will contact the customer once per sale. Failing to do so will result in your API access will be blocked.</p>
	<p><b>This function will soon be available to you.</b></p>
	<?php
	break;
	}
echo '</div>';
}
?>
