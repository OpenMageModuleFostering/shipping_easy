<?php

require_once(Mage::getBaseDir('lib') . '/ShippingEasy/ShippingEasy.php');

class Shippingeasy_Fulfilment_IndexController extends Mage_Core_Controller_Front_Action {        

    const SHIPEASY_API_KEY = 'shippingeasy/fulfilment/apikey';
    const SHIPEASY_API_SECRET = 'shippingeasy/fulfilment/secretkey';
    const SHIPEASY_API_URL = 'shippingeasy/fulfilment/baseurl';

    public function indexAction() {

		$response = array();

		ShippingEasy::setApiKey(Mage::getStoreConfig(self::SHIPEASY_API_KEY));
		ShippingEasy::setApiSecret(Mage::getStoreConfig(self::SHIPEASY_API_SECRET));
		ShippingEasy::setApiBase(Mage::getStoreConfig(self::SHIPEASY_API_URL));

		//$json_payload = json_decode(file_get_contents('php://input'));
		//$authenticator = new ShippingEasy_Authenticator('POST', '/shippingeasy_fetch', $_REQUEST);
		$authenticator = new ShippingEasy_Authenticator('GET', '/shippingeasy_fetch', $_REQUEST);

		if ($authenticator->isAuthenticated()) {
			$response['response'] = 'VALID REQUEST';
			//$response['apikey'] = Mage::getStoreConfig(self::SHIPEASY_API_KEY);
			//$response['secretkey'] = Mage::getStoreConfig(self::SHIPEASY_API_SECRET);
			//$response['url'] = Mage::getStoreConfig(self::SHIPEASY_API_URL);
		} else {
			$response['response'] = 'INVALID REQUEST';
		}

		echo json_encode($response);

    }

}

?>