<?php
define( 'WCQP_VERSION', '4.3.5' );
if(isset($_REQUEST['task']) and $_REQUEST['task'] == 'notify'){
	include('../../../wp-load.php');
	global $woocommerce;
	$notify_response = $_REQUEST;
	if($notify_response['OrderReferrer'] != ''){
		$order_id = base64_decode($notify_response['OrderReferrer']);
		$customer_order = new WC_Order( $order_id );
		//echo "<pre>";
		//print_r($customer_order);
		$customer_order->add_order_note('FastSpring payment completed.');
		$customer_order->payment_complete();
		// Empty the cart (Very important step)
		$woocommerce->cart->empty_cart();
		$log = new WC_Logger();
		$log->add( 'FastSpring','Order Id '. $order_id .' : Payment has been done successfully.');
	}
    exit;	
}
?>