<?php

class Octopush_OctopushSms_Helper_Statistic extends Mage_Core_Helper_Abstract {

    public function getSales() {
        $orders = Mage::getModel('sales/order')->getCollection()
                //->addAttributeToFilter('status', Mage_Sales_Model_Order::STATE_COMPLETE)
                //->addAttributeToFilter('status', 'complete')
                ->addAttributeToFilter('created_at', array('from' => date('Y-m-d')))
                //->addAttributeToFilter( 'created_at', array( 'from' => date( 'Y-m-d', strtotime( '-100 days' ) ), 'to' => date( 'Y-m-d' ) ) )
                ->addAttributeToSelect('grand_total')
                ->getColumnValues('grand_total');

        $day_sales = Mage::getModel('sales/order')->getCollection()
                ->addAttributeToFilter('status', Mage_Sales_Model_Order::STATE_COMPLETE)
                ->addAttributeToFilter('status', 'complete')
                ->addAttributeToFilter('created_at', array('from' => date('Y-m-d')))
                //->addAttributeToFilter( 'created_at', array( 'from' => date( 'Y-m-d', strtotime( '-100 days' ) ), 'to' => date( 'Y-m-d' ) ) )
                ->addAttributeToSelect('grand_total')
                ->getColumnValues('grand_total');

        $month_sales = Mage::getModel('sales/order')->getCollection()
                ->addAttributeToFilter('status', Mage_Sales_Model_Order::STATE_COMPLETE)
                ->addAttributeToFilter('status', 'complete')
                ->addAttributeToFilter('created_at', array('from' => date('Y-m-01')))
                //->addAttributeToFilter( 'created_at', array( 'from' => date( 'Y-m-d', strtotime( '-100 days' ) ), 'to' => date( 'Y-m-d' ) ) )
                ->addAttributeToSelect('grand_total')
                ->getColumnValues('grand_total');

        $totalMonthSum = Mage::helper('core')->currency(array_sum($month_sales), true, false);
        $totalDaySum = Mage::helper('core')->currency(array_sum($day_sales), true, false);
        return array('{day_sales}' => $totalDaySum, '{month_sales}' => $totalMonthSum,'{orders}'=>count($orders));
    }
    
    public function getVisits() {
        $collectionVisitor = Mage::getModel('log/visitor')->getCollection()->addFieldToFilter('first_visit_at', array('from' => date('Y-m-d')));
        return array('{visitors}'=>count($collectionVisitor));//,'visitors'=>count($collectionCustomer));
    }
    
    public function getSubscriptions() {
        $collection = Mage::getResourceModel('customer/customer_collection')
                ->addNameToSelect()
                ->addAttributeToSelect('created_at')
                ->addAttributeToFilter('created_at', array('from' => date('Y-m-d')));
        return array('{subs}'=>count($collection));
    }

}
