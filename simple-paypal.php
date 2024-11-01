<?php
/*
Plugin Name: WP e-Commerce Simple Paypal
Plugin URI: http://wpcb.fr/plugin-simple-paypal
Description: Simple (and working) Paypal payement gateway (Plugin requis : WP e-Commerce)
Version: 1.1.3
Author: 6WWW
Author URI: http://6www.net
*/

if (!defined('__WPRoot__')){define('__WPRoot__',dirname(dirname(dirname(dirname(__FILE__)))));}
if (!defined('__ServerRoot__')){define('__ServerRoot__',dirname(dirname(dirname(dirname(dirname(__FILE__))))));}
if (!defined('__WPUrl__')){define('__WPUrl__',site_url());}
if (!defined('__PluginDir__')){	define('__PluginDir__',dirname(__FILE__));}
$purch_log_email=get_option('purch_log_email');
if (!$purch_log_email){$purch_log_email=get_bloginfo('admin_email');}

// Actions lors de la desactivation du plugin :
register_deactivation_hook( __FILE__, 'simplepaypal_deactivate');
function simplepaypal_deactivate(){
	// On deactivate, remove files :
	unlink(__WPRoot__.'/wp-content/plugins/wp-e-commerce/wpsc-merchants/simple-paypal.merchant.php');
}

// Actions lors de la mise en jour du plugin :
add_action( 'admin_init', 'simplepaypal_update' );
function simplepaypal_update(){
	$wp_version_required="3.0";
	global $wp_version;
	$plugin=plugin_basename( __FILE__ );
	$plugin_data=get_plugin_data( __FILE__,false);
	if ( version_compare($wp_version,$wp_version_required,"<")){
		if(is_plugin_active($plugin)){
			deactivate_plugins($plugin);
			wp_die( "'".$plugin_data['Name']."' requires WordPress ".$wp_version_required." or higher, and has been deactivated! Please upgrade WordPress and try again.<br /><br />Back to <a href='".admin_url()."'>WordPress admin</a>." );
		}
	}
	// Check if it is a plugin update :
	$options = get_option('simplepaypal_options');
	if (version_compare($options['version'],$plugin_data['Version'],"<")){
		simplepaypal_activate(); // So that the 2 files simple-paypal.merchant.php & ipn.php are copied again
	}
}


// Lors de la desinstallation : 
register_uninstall_hook(__FILE__, 'simplepaypal_delete_plugin_options');
function simplepaypal_delete_plugin_options() {
	simplepaypal_deactivate();
	delete_option('simplepaypal_options');
}


register_activation_hook(__FILE__, 'simplepaypal_activate');
function simplepaypal_activate() {
	$options = get_option('simplepaypal_options');
	$sourceFile = __PluginDir__.'/simple-paypal.merchant.php';
	$destinationFile = __WPRoot__.'/wp-content/plugins/wp-e-commerce/wpsc-merchants/simple-paypal.merchant.php';
	copy($sourceFile, $destinationFile);
    if(!is_array($options)) {
		delete_option('simplepaypal_options'); // so we don't have to reset all the 'off' checkboxes too! (don't think this is needed but leave for now)
		$options = array("business" => admin_url(),
						"return" => __WPUrl__,
						"cancel_return" => __WPUrl__,
						"wpec_gateway_image" => __WPUrl__."/wp-content/plugins/wp-e-commerce/images/paypal.gif",
						"wpec_display_name" => "Paypal",
						"notify_url" => __WPUrl__.'/wp-content/plugins/wp-e-commerce-paypal/ipn.php',
						"version"=>$plugin_data['Version'],"spreadsheetKey"=>"LJhhjdl",
						"sandbox"=>"0","apiKey"=>"00","emailapiKey"=>"your@email.com","googleemail"=>"your@email.com","googlepassword"=>"**");
		update_option('simplepaypal_options',$options);
	}
}

add_action('admin_init', 'simplepaypal_init' );
function simplepaypal_init(){
	register_setting('simplepaypal_plugin_options','simplepaypal_options','simplepaypal_validate_options');
}

add_filter( 'plugin_action_links', 'simplepaypal_plugin_action_links',10,2);
add_action('admin_menu', 'simplepaypal_add_options_page');
function simplepaypal_add_options_page() {
	add_options_page('simplepaypal Options Page', 'Simple Paypal', 'manage_options', __FILE__, 'simplepaypal_render_form');
}



function simplepaypal_render_form() {
	global $wpdb;
	$options = get_option('simplepaypal_options');
	$plugin_data = get_plugin_data( __FILE__,false);
	?>
	<div class="wrap">
		<!-- Display Plugin Icon, Header, and Description -->
		<div class="icon32" id="icon-options-general"><br></div>
		<h2>Options WP e-Commerce Simple Paypal par 6WWW</h2>
		<ol>
		<?php
		$sourceFile = __PluginDir__.'/simple-paypal.merchant.php';
		$destinationFile = __WPRoot__.'/wp-content/plugins/wp-e-commerce/wpsc-merchants/simple-paypal.merchant.php';
		if (
		(!file_exists($destinationFile)) ||
		((isset($_GET['action'])) && ($_GET['action']=='copymerchant'))
		){
			copy($sourceFile, $destinationFile);
		}
		if(!file_exists($destinationFile)) {
			$nonce_url=wp_nonce_url(__WPUrl__.'/wp-admin/options-general.php?page=wp-e-commerce-paypal/simple-paypal.php&action=copymerchant');
			echo '<li><span style="color:red;">Copier le fichier '.__PluginDir__.'/simple-paypal.merchant.php vers '.$destinationFile.' <a href="'.$nonce_url.'">en cliquant ici</a></span></li>';
		} 
		else {
			echo '<li><span style="color:green">Le fichier '.$destinationFile.' est bien au bon endroit -> OK!</span></li>';
		}
		$simplepaypal_checkout_page=$wpdb->get_row("SELECT ID FROM $wpdb->posts WHERE `post_content` LIKE '%[simplepaypal]%' AND `post_status`='publish' LIMIT 1");
		if ($simplepaypal_checkout_page!=NULL){
			echo '<li><span style="color:green">Le shortcode [simplepaypal] est sur la page : <a href="'.site_url('?page_id='.$simplepaypal_checkout_page->ID).'">'.$simplepaypal_checkout_page->ID.'</a> -> OK!</span></li>';
		}
		else {
			echo '<li><span style="color:red">Vous devez placer le shortcode [simplepaypal] quelque part sur votre site</span></li>';
		}
		// API
		$post_data['apiKey']=$options['apiKey'];
		$post_data['emailapiKey']=$options['emailapiKey'];
		$response=wp_remote_post('http://wpcb.fr/api/simplepaypal/valid.php',array('body' =>$post_data));
		$valid=unserialize($response['body']);
		if ($valid[0]){
			echo '<li><span style="color:green">Votre clé API est valide -> OK!</span></li>';
		}
		else {
			echo '<li><span style="color:red">Optionel : Vous pouvez débloquer l\'assistance et des fonctions supplémentaires en <a href="http://wpcb.fr/api-key/" target="_blank">achetant une clé API</a></span> C\'est pas cher et ça m\'aide à améliorer mes plugins.</li>';
		}
		// END OF API
		?>
		<?php
		$GoogleConnection=true;
		try {$client = Zend_Gdata_ClientLogin::getHttpClient($options['googleemail'],$options['googlepassword']);}
		catch (Zend_Gdata_App_AuthException $ae){echo $ae->exception();$GoogleConnection=false;}
		if ($GoogleConnection){
			echo '<li>Your google connection is living-> Ok!</li>';
		}
		else {
			echo '<li>Your google connection is not ok, check email and pass below</li>';
		}
		// Todo : catch error if spreadsheetKey is wrong
		?>
		<li>Remplissez les informations ci dessous.</li>
		</ol>
		<form method="post" action="options.php">
			<?php settings_fields('simplepaypal_plugin_options'); ?>
			<table class="form-table">
				<tr>
				<th scope="row">Business</th>
				<td><input type="text" size="57" name="simplepaypal_options[business]" value="<?php echo $options['business']; ?>" /></td>
				</tr>
				<tr>
				<th scope="row">Return</th>
				<td><input type="text" size="57" name="simplepaypal_options[return]" value="<?php echo $options['return']; ?>" /></td>
				</tr>
				<tr>
				<th scope="row">cancel_return</th>
				<td><input type="text" size="57" name="simplepaypal_options[cancel_return]" value="<?php echo $options['cancel_return']; ?>" /></td>
				</tr>
				<tr>
				<th scope="row">wpec_gateway_image</th>
				<td><input type="text" size="57" name="simplepaypal_options[wpec_gateway_image]" value="<?php echo $options['wpec_gateway_image']; ?>" /></td>
				</tr>
				<tr>
				<th scope="row">wpec_display_name</th>
				<td><input type="text" size="57" name="simplepaypal_options[wpec_display_name]" value="<?php echo $options['wpec_display_name']; ?>" /></td>
				</tr>
				<tr>
				<th scope="row">notify_url</th>
				<td><input type="text" size="3" name="simplepaypal_options[notify_url]" value="<?php echo $options['notify_url']; ?>" /></td>
				</tr>
				<tr>
				<th scope="row">Clé API (Optionel: permet de débloquer des options et donne accès à l'assistance et aux mises à jour de sécurité)</th>
				<td><input type="text" size="57" name="simplepaypal_options[apiKey]" value="<?php echo $options['apiKey']; ?>" /></td>
				</tr>
				<tr>
				<th scope="row">Email paypal utilisé pour l'achat de votre clé API (Optionel)</th>
				<td><input type="text" size="57" name="simplepaypal_options[emailapiKey]" value="<?php echo $options['emailapiKey']; ?>" /></td>
				</tr>
				<tr>
				<th scope="row">Clé de la feuille de calcul Google Drive (Optionel : pour l'ajout des ventes dans Google Drive)</th>
				<td><input type="text" size="57" name="simplepaypal_options[spreadsheetKey]" value="<?php echo $options['spreadsheetKey']; ?>" /></td>
				</tr>
				<tr>
				<th scope="row">Email Gmail ou Google App (Optionel : pour l'ajout des ventes dans Google Drive)</th>
				<td><input type="text" size="57" name="simplepaypal_options[googleemail]" value="<?php echo $options['googleemail']; ?>" /></td>
				</tr>
				<tr>
				<th scope="row">Mot de passe Gmail (Optionel : pour l'ajout des ventes dans Google Drive)</th>
				<td><input type="password" size="57" name="simplepaypal_options[googlepassword]" value="<?php echo $options['googlepassword']; ?>" /></td>
				</tr>
				<!-- Checkbox Buttons -->
				<tr valign="top">
				<th scope="row">Pour les developpeur</th>
				<td>
				<label><input name="simplepaypal_options[sandbox]" type="checkbox" value="1" <?php if (isset($options['sandbox'])) { checked('1', $options['sandbox']); } ?> /> Sandbox</em></label><br />
				</td>
				</tr>
			</table>
			<input type="hidden" name="simplepaypal_options[version]" value="<?php echo $plugin_data['Version']; ?>" />
			<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>
		</form>
		<p style="margin-top:15px;">
		
		<?php
		echo '<p>Infos:</p>';
		echo '<ul>';
		echo '<li><p>Plugin version : '.$options['version'].'</li>';
		echo '<li><p>Racine wordpress : '.__WPRoot__.'</p></li>';
		echo '<li>Racine site : '.__ServerRoot__.'</li>';
		$nonce_url=wp_nonce_url(__WPUrl__.'/wp-admin/options-general.php?page=wp-e-commerce-paypal/simple-paypal.php&action=copymerchant');
		echo '<li>Developpeur : Copier le fichier '.__PluginDir__.'/simple-paypal.merchant.php vers '.$destinationFile.' <a href="'.$nonce_url.'">en cliquant ici</a></li>';
		?>
			<li><a href="http://www.seoh.fr" target="_blank">Référencer votre site e-commerce avec l'agence SEOh</a></li>
			<li><a href="http://profiles.wordpress.org/6www">Les autres plugins de 6WWW</a></li>
			</ul>
		</p>
	</div>
	
<?php	
} // Fin de la fonction des réglages

// Sanitize and validate input. Accepts an array, return a sanitized array.
function simplepaypal_validate_options($input) {
	foreach ($input as $key=>$value){$input[$key]=wp_filter_nohtml_kses($input[$key]);}
	return $input;
}

// Display a Settings link on the main Plugins page
function simplepaypal_plugin_action_links( $links, $file ) {
	if ($file==plugin_basename( __FILE__ )){
		$simplepaypal_links = '<a href="'.get_admin_url().'options-general.php?page=wp-e-commerce-paypal/simple-paypal.php">'.__('Settings').'</a>';
		array_unshift( $links, $simplepaypal_links );
	}
	return $links;
}

add_shortcode( 'simplepaypal', 'shortcode_simplepaypal_handler' );
function shortcode_simplepaypal_handler( $atts, $content=null, $code="" ) {
	global $wpdb, $purchase_log;
	$sessionid=$_GET['sessionid'];
	$options = get_option('simplepaypal_options');
	$purch_log_email=get_option('purch_log_email');
	if (!$purch_log_email){$purch_log_email=bloginfo('admin_email');}
	if (isset($_GET['sessionid']))
	{
	$purchase_log=$wpdb->get_row("SELECT * FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `sessionid`= ".$sessionid." LIMIT 1") ;
		if ($options['sandbox']){
			$message='<form action="https://sandbox.paypal.com/cgi-bin/webscr" method="post">';
		}
		else{
			$message='<form action="https://www.paypal.com/cgi-bin/webscr" method="post">';
		}
		$message.='<input type="hidden" name="cmd" value="_xclick">';
		$message.='<input type="hidden" name="business" value="'.$options['business'].'">';
		$message.='<input type="hidden" name="lc" value="FR">';
		$message.='<input type="hidden" name="item_name" value="Commande #'.$purchase_log->id.'">';
		$message.='<input type="hidden" name="item_number" value="'.$sessionid.'">';
		$amount=number_format($purchase_log->totalprice,2);
		$message.='<input type="hidden" name="amount" value="'.$amount.'">';
		$message.='<input type="hidden" name="no_note" value="1">';
		$message.='<input type="hidden" name="return" value="'.$options['return'].'">';
		$message.='<input type="hidden" name="cancel_return" value="'.$options['cancel_return'].'">';
		$message.='<input type="hidden" name="notify_url" value="'.$options['notify_url'].'">';
		$message.='<input type="hidden" name="no_shipping" value="1"><input type="hidden" name="currency_code" value="EUR"><input type="hidden" name="button_subtype" value="services"><input type="hidden" name="no_note" value="0"><input type="hidden" name="bn" value="PP-BuyNowBF:btn_paynowCC_LG.gif:NonHostedGuest"><input type="image" src="https://www.paypalobjects.com/fr_FR/FR/i/btn/btn_paynowCC_LG.gif" border="0" name="submit" alt="PayPal - la solution de paiement en ligne la plus simple et la plus sécurisée !"><img alt="" border="0" src="https://www.paypalobjects.com/fr_XC/i/scr/pixel.gif" width="1" height="1"></form>';
	}
	else
	{
		$message='<p>Accès direct à cette page interdit</p>';
	}
	return $message;
} // Fin de la fonction d'affichage du shortcode


// A venir, ajout de vos clients dans mailchimp pour leur envoyer des emails ensuite.

?>
