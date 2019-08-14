<?php

/**
 * @category Shippingeasy
 * @package  Shippingeasy_Fulfilment
 * 
 */
require_once(Mage::getBaseDir('lib') . '/ShippingEasy.php');

class Shippingeasy_Fulfilment_Model_Fulfilment extends Mage_Core_Model_Abstract {

    const SHIPEASY_ENABLED = 'shippingeasy/fulfilment/active';
    const SHIPEASY_STORE_APIKEY = 'shippingeasy/fulfilment/apikey';
    const SHIPEASY_SECRET_APIKEY = 'shippingeasy/fulfilment/secretkey';
    const SHIPEASY_STORE_URL = 'shippingeasy/fulfilment/url';
    const STORE_APIKEY = 'shippingeasy/fulfilment/store_apikey';
    const WEIGHT_UNIT = 'shippingeasy/fulfilment/weight_unit';

    public function _construct() {
        parent::_construct();
        $this->_init('fulfilment/fulfilment');
    }

    public function isEnabled() {
        if (Mage::getStoreConfig(self::SHIPEASY_ENABLED) == false):
            return FALSE;
        else:
            return TRUE;
        endif;
    }

    public function exportMissingProcessedOrders() {
        Mage::getModel('fulfilment/observer')->submitOrder('36', true);
    }

    public function cancelOrder($orderId) {
        $order = Mage::getModel('sales/order')->load($orderId);

        if ($order) {
            Mage::getModel('fulfilment/observer')->setShippingEasyConfiguration();

            $cancellation = new ShippingEasy_Cancellation(Mage::getStoreConfig(Shippingeasy_Fulfilment_Model_Observer::STORE_APIKEY), $order->getIncrementId());
            $response = $cancellation->create();

            if (isset($response)):
                if ($response['order']['external_order_identifier'] == $order->getIncrementId()):
                    $comment = 'Order successfully cancelled in ShippingEasy';
                    $order->addStatusHistoryComment($comment, false);
                    $order->setIsExported('2')->save();
					return true;
                else:
                    return false;
                endif;
            else:
                return false;
            endif;
        }
    }

    public function createShipment($orderIncrementId, $trackingNo, $carrierKey, $carrierServiceKey) {
        $itemsToship = array();
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        if ($order) {
            if ($order->canShip()) {  /// Making shipments
                $convertor = Mage::getModel('sales/convert_order');
                $shipment = $convertor->toShipment($order);
                $totalToShip = count($order->getAllItems());
                foreach ($order->getAllItems() as $orderItem) {
                    if (!$orderItem->getQtyToShip()) {
                        continue;
                    }
                    if ($orderItem->getIsVirtual()) {
                        continue;
                    }
                    $item = $convertor->itemToShipmentItem($orderItem);
                    $qty = $orderItem->getQtyToShip();
                    $item->setQty($qty);
                    $shipment->addItem($item);
                } //end of foreach

                if ($shipment && $totalToShip != '') {
                    $shipment->register();
                    $order->setIsInProcess(true);
                    $transactionSave = Mage::getModel('core/resource_transaction')
                            ->addObject($shipment)
                            ->addObject($shipment->getOrder())
                            ->save();

                    Mage::log('New Shipment created for ' . $incId, 'shippingEasy.log');
                    $shipment = Mage::getModel('sales/order_shipment')->load($shipment->getEntityId());
                } else { //echo "BBB"; exit;
                    $shipment = Mage::getResourceModel('sales/order_shipment_collection')
                            ->addAttributeToSelect('*')
                            ->setOrderFilter($order->getData('entity_id'));
                    $shipment = $shipment->getFirstItem();    // loading collection and get 1st shipment   
                }
            } else { // shipment already added  -load shipment Object and add tracking information only.
                $shipment = Mage::getResourceModel('sales/order_shipment_collection')
                        ->addAttributeToSelect('*')
                        ->setOrderFilter($order->getData('entity_id'));
                $shipment = $shipment->getFirstItem();    // loading collection and get 1st shipment   
            }

            // add tracking information ///
            if ($shipment)
                $trackingCodes = array();
            if ($shipment->getData('entity_id')) {
                $shipment = Mage::getModel('sales/order_shipment')->load($shipment->getEntityId());
                if ($this->checkAlreadyAdded($trackingNo) && !in_array($trackingNo, $trackingCodes)) {
                    $sendEmail = true;
                    $track = Mage::getModel('sales/order_shipment_track')
                            ->setNumber($trackingNo)
                            ->setCarrierCode(strtolower($carrierKey))
                            ->setTitle($carrierKey . " - " . $carrierServiceKey);
                    $shipment->addTrack($track);
                    array_push($trackingCodes, $trackingNo);
                    unset($track);
                }
                if ($sendEmail == true) {
                    $shipment->save();
                    $shipment->sendEmail(true)->setEmailSent(true)->save();
                    $order->save();
                }
            }
        }
    }

    public function checkAlreadyAdded($trackingCode) {
        $track = Mage::getModel('sales/order_shipment_track')->getCollection();
        $track->addFieldtoFilter('track_number', $trackingCode);
        if ($track->getSize() == 0) {
            return true;
        } else {
            return false;
        }
    }

}