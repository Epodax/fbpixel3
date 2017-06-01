<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

class plgSystememFacetracking extends JPlugin
{
	protected $autoloadLanguage = true;
	
	public function onAfterDispatch(){
		$app = JFactory::getApplication();
		if(!$app->isAdmin()){
			//Get URL for specific page matching
			$uri = 	JUri::getInstance(); 
			$path = $uri->getPath(); 
			
			$exitOnPath = '/checkout'; //Don't include pixel code from this function on any checkout pages
			
			//Exit if we're on any of the checkout pages.
			if(strpos($path,$exitOnPath) !== false){
				return;
				die();
			}
			
			$plgParams = $this->params; //Fetch plugin params
		
			$fb_pixel = 	$plgParams->get('fb_pixel'); //Load pixel code
			$trackEvents = 	$plgParams->get('tracking_event'); //Get specific page events to track
			
			$trackAllPage = ($plgParams->get('track_pages') == 1) ? TRUE : FALSE; //Load enabled / disabled status
			$trackSpecial = ($plgParams->get('track_special') == 1) ? TRUE : FALSE; //Load enabled / disabled status
			$matchOnSpecial = FALSE; //Initialise variable - Used to determine if a match was found.
			$insertTracking = FALSE; //Initialise variable - Used to determine if tracking code should be inserted.
							
			$lang = JFactory::getLanguage(); //Determine displayed language
			$languages = array
			(
				'es-ES' => '/es', 
				'fr-FR' => '/fr', 
				'it-IT' => '/it'
			);
		
			//Remove language tag from URL for easier matching against specific page events.
			if(array_key_exists($lang->getTag(),$languages)){
				$cPage = str_replace($languages[$lang->getTag()],'',$path);
			}else{
				$cPage = $path;
			}
			
			//Check if specific page tracking is enabled.
			if($trackSpecial === TRUE){
				//Loop specific page events and check for matches, if match is found, prepare tracking code
				foreach($trackEvents as $trackData){
					if($cPage == $trackData->page_url){
						$fb_pixel = str_replace('%eventCode%',$trackData->tracking_code,$fb_pixel);
						$matchOnSpecial = TRUE;
						$insertTracking = TRUE;
						break;
					}
				}
			}
			//Check if specific page event was found and if the normal tracking is enabled. If specific page event was found, skip normal tracking.
			if($matchOnSpecial === FALSE && $trackAllPage === TRUE){
				$fb_pixel = str_replace('%eventCode%','',$fb_pixel);
				$insertTracking = TRUE;
			}
			
			//Check if tracking should be inserted into the header, and if so, insert the code.
			if($insertTracking === TRUE){
				$document = JFactory::getDocument();
				$document->addCustomTag($fb_pixel);
			}
		}
	}
	function onAfterOrderUpdate(&$order,&$send_email){
		$plgParams = $this->params; //Fetch plugin params
		
		//Check if purchases should be tracked.
		if($plgParams->get('track_purchase') == 1){
			$fb_pixel = $plgParams->get('fb_pixel'); //Load pixel code
			$order_status = strtolower($order->order_status); //Fetch order status.
			
			//Check if order status is confirmed and if so, prepare the tracking code with relevant data from order.
			if($order_status == 'confirmed' || $order_status == 'confirmado' || $order_status == 'confirmée' || $order_status == 'confermato'){
				$price = !empty($order->old->order_full_price) ? number_format($order->old->order_full_price,2) : '';
				
				if(!empty($order->old->order_currency_info)){
					$currency = unserialize($order->old->order_currency_info);
					$currency = $currency->currency_code;
				}else{
					$currency = '';
				}
				//Prepare the tracking code
				$fb_pixel = str_replace('%eventCode%',"fbq('track', 'Purchase', {value: '" . $price . "', currency: '" . $currency . "'});",$fb_pixel);
				
				//Insert the tracking code.
				$document = JFactory::getDocument();
				$document->addCustomTag($fb_pixel);
			}
		}
	}
	function onCheckoutStepDisplay($layoutName, &$html, &$view){
		$plgParams = $this->params; //Fetch plugin params
		
		//Check if checkout should be tracked.
		if($plgParams->get('track_checkout_start') == 1){
			$fb_pixel = $plgParams->get('fb_pixel'); //Load tracking code
			
			//Prepare tracking code
			$fb_pixel = str_replace('%eventCode%',"fbq('track', 'InitiateCheckout');",$fb_pixel);
			
			//Insert tracking code
			$document = JFactory::getDocument();
			$document->addCustomTag($fb_pixel);
		}
	}
}
?>