<?php
/*
Plugin Name: WooCommerce FastSpring
Plugin URI: http://wordpress.org/plugins/PaymentGatewayOfFastSpringForWooCommerce/
Description: Integrates your FastSpring payment gateway into your WooCommerce installation.
Version: 1.1
Author: Deligence Technologies Pvt Ltd.
Text Domain: woo-fastspring
Author URI: http://www.deligence.com/
*/
add_action('plugins_loaded', 'init_fastspring_gateway', 0);

function init_fastspring_gateway() {
	
	if ( ! class_exists( 'WC_Payment_Gateway' )) { return; }
	
	function fastspring_disable_checkout_script(){
			wp_dequeue_script( 'wc-checkout' );
		}
	add_action( 'wp_enqueue_scripts', 'fastspring_disable_checkout_script' );
	
	
	class FastSpring extends WC_Payment_Gateway
	{
		public static $_instance = NULL;	
					
        public $log;
	    
		
		public static function get_instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}
        
		
		public function __construct() 
		{
		    $this->id			= 'fastspring';
		    $this->method_title = 'FastSpring';
			$this->method_description ='Pay via FastSpring. Allows you to pay with your credit card via FastSpring.';
		    $this->icon 		= plugins_url().'/paymentgatewayoffastspringforwoocommerce/fastspring.png';
		    $this->has_fields 	= false;	
		    $this->supports = array( 
		    	'products', 
		    );
			$this->init_form_fields();
			$this->init_settings();
			
			if ( is_admin() ) {
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	       }
		   
		   foreach ( $this->settings as $setting_key => $value ) {
			$this->$setting_key = $value;
			}
		}
		
		// Build the administration fields for this specific Gateway
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
					'title'		=> 'Enable / Disable',
					'label'		=> 'Enable this payment gateway',
					'type'		=> 'checkbox',
					'default'	=> 'no',
				),
				'title' => array(
					'title'		=> 'Title',
					'type'		=> 'text',
					'desc_tip'	=> 'Payment title the customer will see during the checkout process.',
					'default'	=> 'FastSpring',
				),
				'description' => array(
					'title'		=> 'Description',
					'type'		=> 'textarea',
					'desc_tip'	=> 'Payment description the customer will see during the checkout process.',
					'css'		=> 'max-width:350px;'
				),
				
				'company_id' => array(
					'title'		=> 'Company Id',
					'type'		=> 'text',
					'desc_tip'	=> 'Company id of FastSpring',
				),
				
				'environment' => array(
					'title'		=> 'Test Mode',
					'label'		=> 'Enable Test Mode',
					'type'		=> 'checkbox',
					'description' =>'Place the payment gateway in test mode.',
					'default'	=> 'no',
				)
			);		
		}
		
		
		public function process_payment( $order_id ) {
			global $woocommerce;
			
			$customer_order = new WC_Order( $order_id );
			
			$environment = ( $this->environment == "yes" ) ? '1' : '0';
			
			$form_array=array(
			'operation'=>'create',
			'destination'=>'contents',
			'contact_email'=>$customer_order->billing_email,
			'mode'=>$environment,
			'contact_fname' =>$customer_order->billing_first_name,
			'contact_lname' =>$customer_order->billing_last_name,
			'contact_company' =>$customer_order->billing_company,
			'contact_email' =>$customer_order->billing_email,
			'contact_phone' =>$customer_order->billing_phone,
			'referrer'=>base64_encode($customer_order->id)
			);
			
			$i=1;
			foreach ( $customer_order->get_items() as $item ) {
				$product = get_post($item['product_id']); 
				$slug = $product->post_name;
				$key='product_'.$i.'_path';
			    $form_array[$key] = '/'.$slug;
				$key_q='product_'.$i.'_quantity';
				$form_array[$key_q] = $item['qty'];
				$i++;
		   }
			
			$payment_gateway_url="http://sites.fastspring.com/".$this->get_option( 'company_id' )."/api/order";
			
			$this->generate_form($payment_gateway_url, $form_array);
			return array(
				'result'   => 'success',
				//'redirect' => 'index.php',
			);
		 
		}
		
		
		function generate_form($payment_gateway_url, $form_array){
			echo "<center><h2>Please wait, your order is being processed and you";
		    echo " will be redirected to the FastSpring website.</h2></center>\n";
			echo "<form method=\"post\" name=\"fastspring_form\"  ";
            echo "action=\"".$payment_gateway_url."\">\n";
            foreach ($form_array as $name => $value) {
                echo "<input type=\"hidden\" name=\"$name\" value=\"$value\"  />\n";
            }
            echo "<center><br/><br/>If you are not automatically redirected to ";
            echo "FastSpring within 5 seconds...<br/><br/>\n";
            echo "<input name=\"submitbutton\" type=\"submit\" value=\"ClICK HERE\"></center>\n";
            echo "</form>\n";
			?>
            <script language="javascript">
			document.fastspring_form.submit();
			</script>
            <?php
		}
	}
		
		add_filter( 'woocommerce_payment_gateways', 'fastspring_gateway' );
		function fastspring_gateway( $methods ) {
			$methods[] = 'FastSpring';
			return $methods;
		}
}
	
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'fastspring_links' );
		function fastspring_links( $links ) {
			$plugin_links = array(
				'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=fastspring' ) . '">Settings</a>',
			);
		    return array_merge( $plugin_links, $links );	
         }
		 
?>