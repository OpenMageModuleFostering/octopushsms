<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Octopush_OctopushSms_Helper_Customer extends Mage_Core_Helper_Abstract {

    /**
     * Find customer id where $q is in firstname or lastname or phone or entity_id.
     * Telephone has to be not null.
     * @return the list of customer id corresponding to the search
     */
    public function find_customer_id($s) {
        $collection = Mage::getResourceModel('customer/customer_collection')
                ->addNameToSelect()
                ->addAttributeToSelect('email')
                ->addAttributeToSelect('created_at')
                ->addAttributeToSelect('group_id')
                ->addAttributeToFilter('entity_id', $s)
                ->joinAttribute('billing_postcode', 'customer_address/postcode', 'default_billing', null, 'left')
                ->joinAttribute('billing_city', 'customer_address/city', 'default_billing', null, 'left')
                ->joinAttribute('billing_telephone', 'customer_address/telephone', 'default_billing', null, 'left')
                ->joinAttribute('billing_region', 'customer_address/region', 'default_billing', null, 'left')
                ->joinAttribute('billing_country_id', 'customer_address/country_id', 'default_billing', null, 'left')
                ->setPageSize('50');
        return $collection;
    }

    /**
     * Find customer id where $q is in firstname or lastname or phone or entity_id.
     * Telephone has to be not null.
     * @return the list of customer id corresponding to the search
     */
    public function find_customer($s) {
        $collection = Mage::getResourceModel('customer/customer_collection')
                ->addNameToSelect()
                ->addAttributeToSelect('email')
                ->addAttributeToSelect('created_at')
                ->addAttributeToSelect('group_id')
                ->joinAttribute('billing_telephone', 'customer_address/telephone', 'default_billing', null, 'left')
                ->joinAttribute('billing_country_id', 'customer_address/country_id', 'default_billing', null, 'left')
                ->addAttributeToFilter(array(
                    array('attribute' => 'billing_telephone', 'like' => '%' . $s . '%'),
                    array('attribute' => 'entity_id', 'like' => '%' . $s . '%'),
                    array('attribute' => 'firstname', 'like' => '%' . $s . '%'),
                    array('attribute' => 'lastname', 'like' => '%' . $s . '%'),
                    
                ))
                ->setPageSize('50');
        return $collection;
    }

    public function find_customer_by_email($email) {
        $customer = Mage::getModel("customer/customer");
        $customer->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
        $customer->loadByEmail($email);
        return $customer;
    }

}
