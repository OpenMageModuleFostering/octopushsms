<?php

/**
 * Observer: Mage::dispatchEvent('checkout_submit_all_after', array('order' => $order, 'quote' => $quote));
 *
 */
class Octopush_OctopushSms_Model_Observer_Observer extends Varien_Event_Observer {

    public function __construct() {
        
    }

    public function checkoutSubmitAllAfterObserve($observer) {
        $order = $observer->getEvent()->getOrder();
        Mage::log('Event - ' . $observer->getEvent()->getName() . ' order id:' . $order->getId(), Zend_Log::DEBUG);
        $messageHelper = Mage::helper('octopushsms/message');
        $params = array('order_id' => $order->getId());
        $messageHelper->send('action_validate_order', $params);
        //test quantity if it is needed to send low stock sms
        $orderIncrementId = $order->getIncrementId();
        $salesOrder = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        $items = $salesOrder->getAllVisibleItems();
        foreach ($items as $i) {
            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($i->getProductId());
            if ($stockItem->getQty() < $stockItem->getNotifyStockQty()) {
                $params = array('product' => $i);
                $messageHelper->send('action_update_quantity', $params);
            }
        }
        Mage::register('checkout_submit_all_after_' . $order->getId(), true);
    }

    /**
     * Observer method for event cataloginventory_stock_item_save_after
     * @param type $observer
     */
    public function cataloginventoryStockItemSaveAfterObserve($observer) {
        Mage::log("SLIPS8 cataloginventoryStockItemSaveAfterObserve", Zend_Log::DEBUG, "octopushsms.log");

        $messageHelper = Mage::helper('octopushsms/message');
        $i = Mage::getModel('catalog/product')->load($observer->getEvent()->getDataObject()->getProductId());
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($i->getProductId());
        if ($stockItem->getQty() < $stockItem->getNotifyStockQty()) {
            $params = array('product' => $i);
            $messageHelper->send('action_update_quantity', $params);
        }
    }

    /**
     * controller_action_postdispatch_contacts_index_post
     * 
     * controller_action_postdispatch_review
     */
    public function controllerActionPostdispatchContactsIndexPostObserve($observer) {
        $data = $observer->getData(); //['controller_action'];
        Mage::log("SLIPS " . print_r($data['controller_action'], true), Zend_Log::DEBUG, "octopushsms.log");
        $controller_action = $data['controller_action'];
        $post = $controller_action->getRequest()->getPost();
        if ($post) {
            Mage::log("SLIPS " . print_r($post, true), Zend_Log::DEBUG, "octopushsms.log");
            $messageHelper = Mage::helper('octopushsms/message');
            $params = array('comment' => $post);
            $messageHelper->send('action_send_message', $params);
        }
    }

    public function dailyReportCron() {
        $messageHelper = Mage::helper('octopushsms/message');
        $messageHelper->send('action_daily_report', array());
    }

    public function createAccountObserve($observer) {
        $customer = $observer->getEvent()->getCustomer();
        if (Mage::registry('customer_save_after_' . $customer->getId())) {
            return $this;
        }
        Mage::log('Event - ' . $observer->getEvent()->getName() . ' customer id:' . $customer->getId(), Zend_Log::DEBUG);
        /* $currentTimestamp = Mage::getModel('core/date')->timestamp(time());
          echo $currentDate = date('Y-m-d H:i:s', $currentTimestamp);
          Mage::log('Event - ' . $currentDate.' customer id:' . print_r($customer->debug(),true), Zend_Log::DEBUG,"octopushsms.log"); */

        $messageHelper = Mage::helper('octopushsms/message');
        if ($customer->getData('created_at') == $customer->getData('updated_at')) {
            $params = array('customer_id' => $customer->getId());
            $messageHelper->send('action_create_account', $params);
            Mage::register('customer_save_observer_executed_' . $customer->getId(), true);
        }
    }

    public function forgotPasswordObserve($observer) {
        //Mage::log("SLIPS " . print_r($observer, true), Zend_Log::DEBUG, "octopushsms.log");
        $_object = $observer->getEvent()->getObject();
        $_post = Mage::app()->getRequest()->getPost();

        if ($_object instanceof Mage_Log_Model_Visitor) {
            //Mage::log("SLIPS " . $_object->getData('http_referer') . " " . print_r($_post, true), Zend_Log::DEBUG, "octopushsms.log");
            if ($_object->getData('http_referer') && strstr($_object->getData('http_referer'), 'forgotpassword') && isset($_post['email'])) {
                $messageHelper = Mage::helper('octopushsms/message');
                $customerHelper = Mage::helper('octopushsms/customer');
                $customer = $customerHelper->find_customer_by_email($_post['email']);
                Mage::log("SLIPSO " . print_r($customer->debug(), true), Zend_Log::DEBUG, "octopushsms.log");
                $params = array('customer_id' => $customer->getId());
                $messageHelper->send('action_password_renew', $params);
            }
        }
    }

    //sales_order_save_after
    public function orderStateChangeObserve($observer) {
        $order = $observer->getEvent()->getOrder();
        $oldstatus = $order->getOrigData('status');
        $Newstatus = $order->getData('status');
        if ($oldstatus != $Newstatus) {
            $params = array('order' => $order);
            $messageHelper = Mage::helper('octopushsms/message');
            $messageHelper->send('action_order_status_update', $params);
        }
    }

    //sales_order_shipment_track_save_after
    public function shipmentTrackObserve($observer) {
        $track = $observer->getEvent()->getTrack();
        //Mage::log("SLIPS track " . print_r($track->debug(), true), Zend_Log::DEBUG, "octopushsms.log");
        $params = array('track' => $track);
        $messageHelper = Mage::helper('octopushsms/message');
        $messageHelper->send('action_admin_orders_tracking_number_update', $params);
    }

    public function checkStockChangeObserve($observer) {
         Mage::log("registry " . Mage::registry('action_customer_alert'), Zend_Log::DEBUG);
        if (!Mage::registry('action_customer_alert')) {
            $product = Mage::getModel('catalog/product')->load($observer->getEvent()->getDataObject()->getProductId());
            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getProductId());
            if ($stock->getIsInStock() == 1 && $stock->getOrigData('is_in_stock') == 0) {
                $websites = Mage::app()->getWebsites();
                foreach ($websites as $website) {
                    /* @var $website Mage_Core_Model_Website */
                    if (!$website->getDefaultGroup() || !$website->getDefaultGroup()->getDefaultStore()) {
                        continue;
                    }
                    if (!Mage::getStoreConfig(
                                    'catalog/productalert/allow_stock', $website->getDefaultGroup()->getDefaultStore()->getId()
                            )) {
                        continue;
                    }
                    try {
                        $collection = Mage::getModel('productalert/stock')
                                ->getCollection()
                                ->addWebsiteFilter($website->getId())
                                ->addStatusFilter(0)
                                ->setCustomerOrder();
                    } catch (Exception $e) {
                        $this->_errors[] = $e->getMessage();
                        return $this;
                    }

                    $previousCustomer = null;
//                $email->setWebsite($website);
                    foreach ($collection as $alert) {
                        try {
                            $customer = Mage::getModel('customer/customer')->load($alert->getCustomerId());
                            if (!$customer) {
                                continue;
                            }
                            $product = Mage::getModel('catalog/product')
                                    ->setStoreId($website->getDefaultStore()->getId())
                                    ->load($alert->getProductId());
                            /* @var $product Mage_Catalog_Model_Product */
                            if (!$product) {
                                continue;
                            }
                            //TODO send sms
                            $params = array("customer" => $customer, "product" => $product);
                            $messageHelper = Mage::helper('octopushsms/message');
                            $messageHelper->send('action_customer_alert', $params);
                        } catch (Exception $e) {
                            Mage::log("Error to send sms stock notification " . $e->getMessage(), Zend_Log::ERR);
                        }
                    }
                }
            }
            Mage::register('action_customer_alert', true);
        }
    }

}
