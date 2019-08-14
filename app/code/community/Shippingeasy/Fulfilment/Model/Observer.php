<?php

/**
 * @category Shippingeasy
 * @package  Shippingeasy_Fulfilment
 * 
 */
require_once(Mage::getBaseDir('lib') . '/ShippingEasy.php');

class Shippingeasy_Fulfilment_Model_Observer {

    const SHIPEASY_ENABLED = 'shippingeasy/fulfilment/active';
    const SHIPEASY_STORE_APIKEY = 'shippingeasy/fulfilment/apikey';
    const SHIPEASY_SECRET_APIKEY = 'shippingeasy/fulfilment/secretkey';
    const SHIPEASY_STORE_URL = 'shippingeasy/fulfilment/url';
    const STORE_APIKEY = 'shippingeasy/fulfilment/store_apikey';
    const WEIGHT_UNIT = 'shippingeasy/fulfilment/weight_unit';
    const BIN_PICKING_NUMBER = 'shippingeasy/fulfilment/bin_picking_number';

    public function multiShippingOrders($observer) {

        $orderIds = $observer->getOrderIds();
        if (is_array($orderIds)):
            foreach ($orderIds as $i => $id):
                $this->submitOrder($id, TRUE);
            endforeach;
        endif;
    }

    public function submitOrder($observer, $missing = false) {

        if (Mage::getStoreConfig(self::SHIPEASY_ENABLED) == false):
            return;
        endif;

        $orderId = Mage::getSingleton('checkout/type_onepage')->getCheckout()->getLastOrderId();
        $checkOutType = Mage::getSingleton('checkout/type_onepage')->getCheckoutMethod();
        if ($missing == true) {
            $orderId = $observer;
        } else if (empty($orderId)) { // backend order
            $order = $observer->getEvent()->getOrder();
            $orderId = $order->getData('entity_id');
        }

        $shipEasy = new Varien_Object();

        $order = Mage::getModel('sales/order')->load($orderId);
        $shipping = $order->getShippingAddress();

        $shipEasy->setExternalOrderIdentifier($order->getIncrementId());
        $shipEasy->setOrderedAt($order->getCreatedAt());
        $shipEasy->setOrderStatus('awaiting_shipment');
        $shipEasy->setSubtotalIncludingTax($this->fnumb($order->getSubtotalInclTax()));
        $shipEasy->setTotalIncludingTax($this->fnumb($order->getGrandTotal()));
        $shipEasy->setTotalExcludingTax($this->fnumb( ($order->getGrandTotal() - $order->getTaxAmount() ) ));

        $shipEasy->setDiscountAmount($this->fnumb( $order->getDiscountAmount() ));
        $shipEasy->setCouponDiscount($this->fnumb(0));
        $shipEasy->setSubtotalIncludingTax($this->fnumb($order->getSubtotalInclTax() ));
        $shipEasy->setSubtotalExcludingTax($this->fnumb($order->getSubtotal() ));
        $shipEasy->setSubtotalTax($this->fnumb($order->getTaxAmount() ));

        $shipEasy->setTotalTax($this->fnumb( $order->getTaxAmount()));
        $shipEasy->setBaseShippingCost($this->fnumb($order->getBaseShippingAmount()));
        $shipEasy->setShippingCostIncludingTax($this->fnumb($order->getShippingAmount()));
        $shipEasy->setShippingCostExcludingTax($this->fnumb($order->getShippingAmount()));

        $shipEasy->setShippingCostTax($this->fnumb($order->getBaseShippingTaxAmount()));
        $shipEasy->setBaseHandlingCost(0.00);
        $shipEasy->setHandlingCostExcludingTax(0.00);
        $shipEasy->setHandlingCostIncludingTax(0.00);
        $shipEasy->setHandlingCostTax(0.00);
        $shipEasy->setBaseWrappingCost(0.00);
        $shipEasy->setWrappingCostExcludingTax(0.00);
        $shipEasy->setWrappingCostIncludingTax(0.00);
        $shipEasy->setWrappingCostTax(0.00);
        $shipEasy->setNotes("");


        //billing 
        $billing = $order->getBillingAddress();
        //$bill_country = Mage::getModel('directory/country')->load($billing->getCountryId())->getData('iso3_code');
		$bill_country = Mage::getModel('directory/country')->load($billing->getCountryId())->getName();
        if (is_array($billing->getStreet())):
            $billAddress = $billing->getStreet();
            if (isset($billAddress[1])):
                $shipEasy->setBillingAddress($billAddress[0]);
                $shipEasy->setBillingAddress2($billAddress[1]);
            else:
                $shipEasy->setBillingAddress($billAddress[0]);
            endif;
        else:
            $shipEasy->setBillingAddress($billing->getStreet());
        endif;

        $shipEasy->setBillingCompany($billing->getCompany());
        $shipEasy->setBillingFirstName($billing->getFirstname());
        $shipEasy->setBillingLastName($billing->getLastname());
        $shipEasy->setBillingCity($billing->getCity());
        $shipEasy->setBillingState($billing->getRegion());
        $shipEasy->setBillingPostalCode($billing->getPostcode());

        $shipEasy->setBillingCountry($bill_country);
        $shipEasy->setBillingPhoneNumber($billing->getTelephone());
        $shipEasy->setBillingEmail($billing->getEmail());

        // shipping
        $recipients = new Varien_Object();
        $shipping = $order->getShippingAddress();
        $shipCountry = Mage::getModel('directory/country')->load($shipping->getCountryId())->getData('iso3_code');
		$shipCountry = Mage::getModel('directory/country')->load($shipping->getCountryId())->getName();
        if (is_array($shipping->getStreet())):
            $shipAddress = $shipping->getStreet();
            if (isset($shipAddress[1])):
                $recipients->setAddress($shipAddress[0]);
                $recipients->setAddress2($shipAddress[1]);
            else:
                $recipients->setAddress($shipAddress[0]);
            endif;
        else:
            $recipients->setAddress($shipping->getStreet());
        endif;

        $recipients->setCompany($shipping->getCompany());
        $recipients->setFirstName($shipping->getFirstname());
        $recipients->setLastName($shipping->getLastname());
        $recipients->setCity($shipping->getCity());
        $recipients->setState($shipping->getRegion());
        $recipients->setPostalCode($shipping->getPostcode());

        $recipients->setCountry($shipCountry);
        $recipients->setPhoneNumber($shipping->getTelephone());
        $recipients->setEmail($shipping->getEmail());
        $recipients->setResidential(true);
        $recipients->setShippingMethod($order->getShippingDescription());
        $recipients->setBaseCost($this->fnumb( $order->getBaseShippingInclTax()));
        $recipients->setCostExcludingTax($this->fnumb(($order->getBaseShippingAmount() - $order->getBaseShippingTaxAmount())));
        $recipients->setCostTax($this->fnumb($order->getBaseShippingTaxAmount()));
        $recipients->setBaseHandlingCost(0.00);
        $recipients->setHandlingCostExcludingTax(0.00);
        $recipients->setHandlingCostIncludingTax(0.00);
        $recipients->setHandlingCostTax(0.00);
        $recipients->setShippingZoneId("123");
        $recipients->setShippingZoneName("XYZ");

		
		
        // line items 
        $lineItems = array();
        $items = $order->getAllVisibleItems();
        foreach ($items as $i => $item):

            //  if ($item->getData('product_type') == 'simple') :
            $_unit = Mage::getStoreConfig(self::WEIGHT_UNIT);
            if ($_unit == 'KGS')
                $weight = $item->getWeight() * "35.274";
            if ($_unit == 'LBS')
                $weight = $item->getWeight() * "16";

            $lineItem = new Varien_Object();
            $lineItem->setItemName($item->getName());
            $lineItem->setSku($item->getSku());
            $lineItem->setBinPickingNumber(Mage::getStoreConfig(self::BIN_PICKING_NUMBER));
            $lineItem->setUnitPrice($this->fnumb($item->getPrice()));
            $lineItem->setTotalExcludingTax($this->fnumb($item->getPrice()));
            $lineItem->setWeightInOunces($weight);
            $lineItem->setQuantity((int) $item->getQtyOrdered());
			/*
			if ($item->getData('product_type') == 'configurable') :
				$_option = $this->getItemOptions($item);
				$optStr = "";
				foreach($_option as $lb=>$vall) {
						$optStr .= "#".$vall['label']."=".$vall['value'].":";
				}
	            $lineItem->setProductOptions($optStr);
			endif;
			*/

            $lineItems[] = $lineItem->toArray();


        //array_push($lineItems, $lineItem->toArray());
        //endif;
        endforeach;
			
        $recipients->setItemsTotal(count($lineItems));
        $recipients->setItemsShipped(0);
        $recipients->setLineItems($lineItems);
        $recpArray = array();
        $recpArray[] = $recipients->toArray();
        $shipEasy->setRecipients($recpArray);

        $_succ = false;		
        try {
            $this->setShippingEasyConfiguration();
            $_shippingEasy = new ShippingEasy_Order(Mage::getStoreConfig(self::STORE_APIKEY), $shipEasy->toArray());
            $result = $_shippingEasy->create();
            $_succ = true;
            Mage::log("Order #" . $order->getData('increment_id') . " exported to shipping Easy", null, 'shippingEasy.log');
        } catch (Exception $e) {
            $_succ = false;
			$_errorMsg = "Unable to connect with shippingEasy " . $e->getMessage();
            Mage::log("Unable to connect with shippingEasy " . $e->getMessage().".Order #".$order->getData('increment_id')." failed to export.", null, 'shippingEasy.log');
            Mage::log("Unable to connect with shippingEasy " . $e->getMessage().".Order #".$order->getData('increment_id')." failed to export.".print_r($shipEasy->toArray(), true), null, 'shippingEasyDetails.log');
  
        }
		
		if($_succ == true) {
			$order->setData('is_exported', '1');
            $comment = 'Order successfully exported to Shipping Easy';
            $order->addStatusHistoryComment($comment, false);
            $order->save();
		} else {
  		    $comment = $_errorMsg;
            $order->addStatusHistoryComment($comment, false);
            $order->save();
		}
		if($missing == true) 
			return $_succ;
    }

    public function cancelOrder($observer) {

        try {
            $_orderId = $observer->getOrder()->getEntityId();
            Mage::getModel('fulfilment/fulfilment')->cancelOrder($_orderId);
        } catch (Exception $e) {
            Mage::log("Unable to connect with shippingEasy " . $e->getMessage(), null, 'shippingEasy.log');
        }
    }

    public function setShippingEasyConfiguration() {
        ShippingEasy::setApiKey(Mage::getStoreConfig(self::SHIPEASY_STORE_APIKEY)); //api key
        ShippingEasy::setApiSecret(Mage::getStoreConfig(self::SHIPEASY_SECRET_APIKEY));
        ShippingEasy::setApiBase(Mage::getStoreConfig(self::SHIPEASY_STORE_URL));
    }
	
	public function fnumb($number) {
		return	number_format($number, 2, '.', '');
	}
	
	public function getItemOptions($item)
    {
        $result = array();
        if ($options = $item->getProductOptions()) {
            if (isset($options['options'])) {
                $result = array_merge($result, $options['options']);
            }
            if (isset($options['additional_options'])) {
                $result = array_merge($result, $options['additional_options']);
            }
            if (isset($options['attributes_info'])) {
                $result = array_merge($result, $options['attributes_info']);
            }
        }
        return $result;
    }
}