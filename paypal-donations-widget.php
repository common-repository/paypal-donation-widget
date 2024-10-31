<?php
/*
 Plugin Name: PayPal Donations Widget
 Plugin URI: http://geraeldo.com/paypal-donation-wordpress-plugin
 Description: Accept donations from your readers via PayPal.
 Author: Geraeldo Sinaga
 Version: 1.0.0
 Author URI: http://geraeldo.com/
 Text Domain: Put button Donate from PayPal into your website.
 License: GPL

 Copyright 2010 Geraeldo Sinaga (email: geraeldo@gmail.com)
*/

define("PAYPAL_DONATIONS_WIDGET_VERSION_NUM", "1.0.0");
define("PAYPAL_DONATIONS_WIDGET_RCP_TRANS_DOMAIN", "donations");

global $donations;

$donations = new PayPalDonations();

class PayPalDonations {
	private $uri;
	private $plugin_path;
	
	public function __construct() {

		$version = get_option('donations_version');
		$_file = "web-invoice-scheduler/" . basename(__FILE__);

		$this->path = dirname(__FILE__);
		$this->file = basename(__FILE__);
		$this->directory = basename($this->path);
		$this->uri = WP_PLUGIN_URL."/".$this->directory;
		$this->plugin_path = $this->plugin_path();
		
		register_activation_hook(__FILE__, array(&$this, 'install'));
		register_deactivation_hook(__FILE__, array(&$this, 'uninstall'));

		add_action('init',  array($this, 'init'), 0);
		
		if ( !function_exists('register_sidebar_widget') ) {
			return;
		}
    
		register_sidebar_widget(__('PayPal Donations Widget'), array($this, 'widget_donations'));
		register_widget_control(__('PayPal Donations Widget'), array($this, 'widget_donations_control'), 250, 470);
	}
	
	public function uninstall() {
		global $wpdb;
	}

	public function install() {
		global $wpdb;
		
		add_option('donations_widget_options', '');
		add_option('donations_paypal_email', '');
	}
	
	public function init() {
		global $wp_version;

		if (version_compare($wp_version, '2.6', '<')) // Using old WordPress
			load_plugin_textdomain(WEB_INVOICE_rcp_TRANS_DOMAIN, PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/languages');
		else
			load_plugin_textdomain(WEB_INVOICE_rcp_TRANS_DOMAIN, PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/languages', dirname(plugin_basename(__FILE__)).'/languages');
	}
	
	public function plugin_path() {
		$path =	WP_PLUGIN_URL."/".basename(dirname(__FILE__));
		return $path;
	}

	public function frontend_path() {
		$path =	WP_PLUGIN_URL."/".basename(dirname(__FILE__));
		if(get_option('web_invoice_force_https') == 'true') $path = str_replace('http://','https://',$path);
		return $path;
	}
	
	public function widget_donations($args) {
		global $wpdb;
		
		$options = get_option('donations_widget_options');
		$paypal_email = get_option('donations_paypal_email');
		
		$item_name = get_option('donations_item_name');
		$item_code = get_option('donations_item_code');
		$currency = get_option('donations_currency');
		$amount = get_option('donations_amount');
		
		if ( !is_array($options) ) {
			$options = array(
				'title'=>'PayPal Donations',
				'description'=>''
			);
		}
		
		extract($args);
		echo $before_widget;
		
		echo $before_title; echo $options['title']; echo $after_title;
		
		if (!empty($paypal_email)) {
			?>
			<div align="center">
				<p><?php echo $options['description']; ?></p>
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank" >
					<input type="hidden" name="cmd" value="_donations" />
					<input type="hidden" name="business" value="<?php print $paypal_email; ?>" />
					<input type="hidden" name="item_name" value="<?php print $item_name; ?>" />
					<input type="hidden" name="item_number" value="<?php print $item_code; ?>" />
					<select name="currency_code" value="<?php echo $currency; ?>">
					<?php foreach ($this->_currency_array() as $key=>$val) { ?>
						<option value="<?php print $key ?>" <?php print ($currency == $key) ? 'selected="selected"' : ''; ?> ><?php print $key; ?></option>
					<?php } ?>
					</select>
					<label><input type="text" name="amount" value="<?php print $amount; ?>" size="5" maxlength="5"/></label><br/>
					<input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHostedGuest" />
					<p>
						<input type="image" src="<?php print $this->uri; ?>/images/btn_donateCC_LG.gif" border="0" name="submit" alt="Donate with PayPal" style="border: none; background: none;" />
						<img alt="" border="0" src="<?php print $this->uri; ?>/images/pixel.gif" width="1" height="1" />
					</p>
				</form>
			</div>
		<?php 
		}
		
		echo $after_widget;
	}
	
	public function widget_donations_control() {
		$errors = array();
		if ( $_POST['donations-widget-submit'] ) {
			$options['title'] = trim(strip_tags(stripslashes($_POST['title'])));
			$options['description'] = trim(strip_tags(stripslashes($_POST['description'])));
			update_option('donations_widget_options', $options);
			
			update_option('donations_item_code', trim(strip_tags(stripslashes($_POST['item_code']))));
			update_option('donations_item_name', trim(strip_tags(stripslashes($_POST['item_name']))));
			update_option('donations_currency', trim(strip_tags(stripslashes($_POST['currency']))));
			update_option('donations_amount', trim(strip_tags(stripslashes($_POST['amount']))));

			if (preg_match(
				"/^([*+!.&#$¦\'\\%\/0-9a-z^_`{}=?~:-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,4})$/i",
				trim($_POST['paypal_email']))) {
				update_option('donations_paypal_email', trim(strip_tags(stripslashes($_POST['paypal_email']))));
			} else if (trim($_POST['paypal_email']) != "") {
				$errors['paypal_email'] = true;
			} else {
				update_option('donations_paypal_email', '');
			}
		}
		
		$options = get_option('donations_widget_options');
		$paypal_email = get_option('donations_paypal_email');
		
		$item_code = get_option('donations_item_code');
		$item_name = get_option('donations_item_name');
		$currency = get_option('donations_currency');
		$amount = get_option('donations_amount');
		
		if ( !is_array($options) ) {
			$options = array('title'=>'Donations');
		}
		
		$title = $options['title'];
		$description = $options['description'];
		
		?>
		<p><strong><?php _e("Widget Title", PAYPAL_DONATIONS_WIDGET_RCP_TRANS_DOMAIN); ?></strong></p>
    	<p>
	    	<label for="donations_title"><?php _e("Title text (optional)", PAYPAL_DONATIONS_WIDGET_RCP_TRANS_DOMAIN); ?></label><br />
	    	<input type="text" name="title" id="donations_title" value="<?php echo $title; ?>" class="widefat" />
	    	<label for="donations_description"><?php _e("Description (optional)", PAYPAL_DONATIONS_WIDGET_RCP_TRANS_DOMAIN); ?></label><br />
	    	<textarea type="text" name="description" id="donations_description" class="widefat"><?php echo $description; ?></textarea>
	    </p>
	    <p><strong><?php _e("Donation details", PAYPAL_DONATIONS_WIDGET_RCP_TRANS_DOMAIN); ?></strong></p>
    	<p>
	    	<label for="donations_item_name"><?php _e("Purpose", PAYPAL_DONATIONS_WIDGET_RCP_TRANS_DOMAIN); ?></label><br />
	    	<input type="text" name="item_name" id="donations_item_name" value="<?php echo $item_name; ?>" class="widefat" /><br />
	    	<label for="donations_item_code"><?php _e("Reference", PAYPAL_DONATIONS_WIDGET_RCP_TRANS_DOMAIN); ?></label><br />
	    	<input type="text" name="item_code" id="donations_item_code" value="<?php echo $item_code; ?>" class="widefat" /><br />
	    	<label for="donations_currency"><?php _e("Suggested currency", PAYPAL_DONATIONS_WIDGET_RCP_TRANS_DOMAIN); ?></label><br />
	    	<select name="currency" id="donations_currency" value="<?php echo $currency; ?>">
	    	<?php foreach ($this->_currency_array() as $key=>$val) { ?>
	    		<option value="<?php print $key ?>" <?php print ($currency == $key)?'selected="selected"':''; ?> ><?php print $val; ?></option>
	    	<?php } ?>
	    	</select><br/>
	    	<label for="donations_amount"><?php _e("Suggested amount", PAYPAL_DONATIONS_WIDGET_RCP_TRANS_DOMAIN); ?></label><br />
	    	<input type="text" name="amount" id="donations_amount" value="<?php echo $amount; ?>" class="widefat" /><br />
	    </p>
		<p><strong><?php _e("PayPal Account", DONATIONS_RCP_TRANS_DOMAIN); ?></strong></p>
	    <p>
	    	<label for="donations_paypal_email"><?php _e("PayPal email account (required)", PAYPAL_DONATIONS_WIDGET_RCP_TRANS_DOMAIN); ?></label><br />
	    	<input type="text" name="paypal_email" id="donations_paypal_email" value="<?php echo $paypal_email; ?>" 
	    		class="widefat" style="<?php print isset($errors['paypal_email'])?'border-color:#CC0000;':''; ?>" /><br />
    	</p>
    	<input type="hidden" name="donations-widget-submit" value="1" />
    	<?php 
	}
	

	private function _currency_array() {
		$currency_list = array(
		"AUD"=> __("AUD - Australian Dollars"),
		"CAD"=> __("CAD - Canadian Dollars"),
		"EUR"=> __("EUR - Euros"),
		"GBP"=> __("GBP - Pounds Sterling"),
		"JPY"=> __("JPY - Yen"),
		"USD"=> __("USD - U.S. Dollars"),
		"NZD"=> __("NZD - New Zealand Dollar"),
		"CHF"=> __("CHF - Swiss Franc"),
		"HKD"=> __("HKD - Hong Kong Dollar"),
		"SGD"=> __("SGD - Singapore Dollar"),
		"SEK"=> __("SEK - Swedish Krona"),
		"DKK"=> __("DKK - Danish Krone"),
		"PLN"=> __("PLN - Polish Zloty"),
		"NOK"=> __("NOK - Norwegian Krone"),
		"HUF"=> __("HUF - Hungarian Forint"),
		"CZK"=> __("CZK - Czech Koruna"),
		"ILS"=> __("ILS - Israeli Shekel"),
		"MXN"=> __("MXN - Mexican Peso"),
		"BRL"=> __("BRL - Brazilian Real"));
	
		return $currency_list;
	}
}