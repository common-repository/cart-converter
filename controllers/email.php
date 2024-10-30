<?php
class EmailController extends Controller{
	/** @var instance keep instance */
	public static $instance;
	/**
	 * Singleton pattern for MainController
	 * @return MainController one instance
	 */
    public static function getInstance()
    {
        if ( is_null( self::$instance ) )
        {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
	/**
	 * Process sending email in queue each 20 minutes
	 * @uses wp_mail( $to, $subject, $message, $headers = '', $attachments = array() )
	 */
	public static function process_queue(){
		$current_time = current_time("timestamp");
		//MainController::getInstance()->logger->debug( 'EmailController::process_queue t=' . $current_time );
		// 1. get all data from email_queue which status = not_send
		$emailModel  = new EmailModel();
		$acartModel  = new CartModel();
		$cart_emails = $emailModel->get_email_list(5);

		// 2. send max=5 emails each time process
		$headers  = "MIME-Version: 1.0\r\n";
        $headers .= "From: " . get_option('woocommerce_email_from_name') . " <" . get_option('woocommerce_email_from_address') . ">\r\n";
        $headers .= "Reply-To: " . get_option('woocommerce_email_from_name') . " <" . get_option('woocommerce_email_from_address') . ">\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
		
		foreach ( $cart_emails as $key => $email ) {
			$mailer = WC()->mailer();
            $mailer->send( $email->send_to, $email->subject, $email->email_content, $headers, '' );
			
			// 3. update email_queue status 
			$emailModel->update_status( $email );
			$acartModel->update_status( $email->cart_id );
		}
		
		// 4. create new next schedule for 20 minutes
		CronController::add_sendmail_job();
	}
}
// init once
EmailController::getInstance();