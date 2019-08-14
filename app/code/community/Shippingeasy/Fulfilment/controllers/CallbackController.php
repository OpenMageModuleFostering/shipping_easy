<?php

/**
 * @category Shippingeasy
 * @package  Shippingeasy_Fulfilment
 * 
 */
require_once(Mage::getBaseDir('lib') . '/ShippingEasy.php');

class Shippingeasy_Fulfilment_CallbackController extends Mage_Core_Controller_Front_Action {

    const SHIPEASY_ENABLED = 'shippingeasy/fulfilment/active';
    const SHIPEASY_STORE_APIKEY = 'shippingeasy/fulfilment/apikey';
    const SHIPEASY_SECRET_APIKEY = 'shippingeasy/fulfilment/secretkey';
    const SHIPEASY_STORE_URL = 'shippingeasy/fulfilment/url';
    const STORE_APIKEY = 'shippingeasy/fulfilment/store_apikey';
    const WEIGHT_UNIT = 'shippingeasy/fulfilment/weight_unit';

    public function indexAction() {

        $this->setShippingEasyConfiguration();
        $values = file_get_contents('php://input');
        $output = json_decode($values, true);
        $params = $_REQUEST;
        $authenticator = new ShippingEasy_Authenticator("get", "/magento/shippingeasy/callback", $params, $values, Mage::getStoreConfig(self::SHIPEASY_SECRET_APIKEY));
        Mage::log($values, null, "shippingEasy.log");
        Mage::log($output, null, "shippingEasy.log");
        Mage::log($authenticator, null, "shippingEasy.log");

        Mage::log("====" . $authenticator->getExpectedSignature() . "======" . $authenticator->getSuppliedSignatureString(), null, "shippingEasy.log");
        Mage::log("====" . $authenticator->isAuthenticated(), null, "shippingEasy.log");
        $orderIncrementId = $output['shipment']['orders']['0']['external_order_identifier'];
        $trackingNo = $output['shipment']['tracking_number'];
        $carrierKey = $output['shipment']['carrier_key'];
        $carrierServiceKey = $output['shipment']['carrier_service_key'];

        Mage::log($orderIncrementId . "===" . $trackingNo . "===" . $carrierKey . "===" . $carrierServiceKey, null, "shippingEasy.log");
        Mage::getModel('fulfilment/fulfilment')->createShipment($orderIncrementId, $trackingNo, $carrierKey, $carrierServiceKey);
    }

    public function setShippingEasyConfiguration() {
        ShippingEasy::setApiKey(Mage::getStoreConfig(self::SHIPEASY_STORE_APIKEY)); //api key
        ShippingEasy::setApiSecret(Mage::getStoreConfig(self::SHIPEASY_SECRET_APIKEY));
        ShippingEasy::setApiBase(Mage::getStoreConfig(self::SHIPEASY_STORE_URL));
    }

}