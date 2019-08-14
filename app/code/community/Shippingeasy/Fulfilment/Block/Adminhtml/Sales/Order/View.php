<?php

/**
 * Adminhtml sales order view
 *
 * @category    Mage
 * @package     Shippingeasy_Fulfilment
 */
class Shippingeasy_Fulfilment_Block_Adminhtml_Sales_Order_View extends Mage_Adminhtml_Block_Sales_Order_View {

    public function __construct() {

        parent::__construct();

        if (Mage::getModel('fulfilment/fulfilment')->isEnabled()):
		
            if ($this->getOrder()->getIsExported() == '0'):
                $message = Mage::helper('fulfilment')->__('Are you sure you want to send this order to ShippingEasy?');
                $this->_addButton('shippingeasy_id', array(
                    'label' => Mage::helper('fulfilment')->__('Export to ShippingEasy'),
                    'onclick' => "confirmSetLocation('{$message}', '{$this->getshippingEastUrl()}')",
                    'class' => 'go'), 0, 100, 'header', 'header');
            endif;

            if ($this->getOrder()->getIsExported() == '1'):  
                $messageCancel = Mage::helper('fulfilment')->__('Are you sure you want to cancel this order in ShippingEasy?');
                $this->_addButton('shippingeasy_cancel', array(
                    'label' => Mage::helper('fulfilment')->__('Cancel Order in ShippingEasy'),
                    'onclick' => "confirmSetLocation('{$messageCancel}', '{$this->getshippingEastCancelUrl()}')",
                    'class' => 'go'), 1, 110, 'header', 'header');
            endif;
        endif;
    }

    public function getshippingEastUrl() {
        return $this->getUrl('shippingeasy/adminhtml_fulfilment/export/');
    }

    public function getshippingEastCancelUrl() {
        return $this->getUrl('shippingeasy/adminhtml_fulfilment/cancel/');
    }

}

