<?php
global $wpdb, $purchase_log;

// This file is beeing copied to root of your website when activating the plugin. So it is not used when in production !
// To have access to wp variables and functions :
require_once('wp-load.php');
$options = get_option('simplepaypal_options');
$purch_log_email=get_option('purch_log_email');
if (!$purch_log_email){$purch_log_email=get_bloginfo('admin_email');}
// Initialisation du chemin du fichier de log :

error_reporting(E_ALL ^ E_NOTICE); 
$header = ""; 
$emailtext = ""; 
// Read the post from PayPal and add 'cmd' 
$req = 'cmd=_notify-validate'; 
if(function_exists('get_magic_quotes_gpc')){$get_magic_quotes_exits = true;} 
foreach ($_POST as $key => $value){
// Handle escape characters, which depends on setting of magic quotes 
	if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1)
		{$value = urlencode(stripslashes($value));}
	else {$value = urlencode($value);}
	$req .= "&$key=$value";
} 
// Post back to PayPal to validate 
$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n"; 
$header .= "Content-Type: application/x-www-form-urlencoded\r\n"; 
$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
if ($options['sandbox']){ 
	$fp = fsockopen ('ssl://sandbox.paypal.com', 443, $errno, $errstr, 30);
}
else{
	$fp = fsockopen ('ssl://www.paypal.com', 443, $errno, $errstr, 30);			
}

// Process validation from PayPal 
if (!$fp){ // HTTP ERROR
	}
else{
	// NO HTTP ERROR 
	fputs ($fp, $header . $req); 
	while (!feof($fp)){
		$res = fgets ($fp, 1024); 
		if (strcmp ($res, "VERIFIED") == 0)	{
			if ($_POST['payment_status']=='Completed'){
				if (WP_DEBUG){
					wp_mail($purch_log_email, "IPN Completed Payement",$req);
				}
				$sessionid=$_POST['item_number'];
				$wpdb->query("UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `processed`= '3' WHERE `sessionid`=".$sessionid);
				$purchase_log = $wpdb->get_row("SELECT * FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `sessionid`= ".$sessionid." LIMIT 1",ARRAY_A) ;
				transaction_results($sessionid,false); // false -> no echo !
			}//End if completed
		}
		elseif (strcmp ($res, "INVALID") == 0){
			// If 'INVALID', send an email. TODO: Log for manual investigation. 
			if (WP_DEBUG){
				wp_mail($purch_log_email, "Live-INVALID IPN",$req);
			}
			$wpdb->query("UPDATE `".WPSC_TABLE_PURCHASE_LOGS."` SET `processed`= '5' WHERE `sessionid`=".$sessionid);
		}  
	}
	fclose ($fp);
}
?>