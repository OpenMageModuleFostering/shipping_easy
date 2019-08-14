<?php

/**
 * @category Shippingeasy
 * @package  Shippingeasy_Fulfilment
 * 
 */
class Shippingeasy_Fulfilment_Adminhtml_FulfilmentController extends Mage_Adminhtml_Controller_action {

    public function exportAction() {
        $_orderId = $this->getRequest()->getParam('order_id');
        if ($_orderId) {
            try {
                $resp = Mage::getModel('fulfilment/observer')->submitOrder($_orderId, true);
				if($resp == true)
	                $this->_getSession()->addSuccess($this->__('The order has been sent to ShippingEasy.'));
				else	
					$this->_getSession()->addError($this->__('Failed to send the order to ShippingEasy.'));
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addError($this->__('Failed to send the order to ShippingEasy. %s', $e->getMessage()));
                Mage::logException($e);
            }
        }
        Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/view", array('order_id' => $_orderId)));
    }

    public function cancelAction() {
        $_orderId = $this->getRequest()->getParam('order_id');
        if ($_orderId) {
            try {
                Mage::getModel('fulfilment/fulfilment')->cancelOrder($_orderId);
                $this->_getSession()->addSuccess($this->__('The order has been successfully cancelled in ShippingEasy.'));
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addError($this->__('Unable to cancel the order in ShippingEasy. %s', $e->getMessage()));
                Mage::logException($e);
            }
        }
        Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/view", array('order_id' => $_orderId)));
    }

}