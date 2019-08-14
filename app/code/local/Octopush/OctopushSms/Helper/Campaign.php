<?php

class Octopush_OctopushSms_Helper_Campaign extends Mage_Core_Helper_Abstract {

    public static function get_status_array() {
        return array(
            0 => __('In construction'),
            1 => __('Transfer in progess'),
            2 => __('Waiting for validation'),
            3 => __('Sent'),
            4 => __('Canceled'),
            5 => __('Error'),
        );
    }

    public static function get_status($status) {
        $status_array = self::get_status_array();
        if (array_key_exists($status, $status_array)) {
            return $status_array[$status];
        }
    }

    /**
     * Calculate the totals and setthe status
     */
    public static function compute_campaign($campaign, $status = 0) {
        $resource = Mage::getSingleton('core/resource');
//TODO remove $tableCampaign = $resource->getTableName('octopushsms/campaign');
//$tableRecipient = $resource->getTableName('octopushsms/recipient');
        $readConnection = $resource->getConnection('core_read');

        $query = 'SELECT COUNT(id_sendsms_recipient) AS nb_recipients, SUM(nb_sms) AS nb_sms, SUM(price) AS price
		FROM `' . $resource->getTableName('octopushsms/recipient') . '`
		WHERE `id_sendsms_campaign`=' . (int) $campaign->getData('id_sendsms_campaign') . '
		AND `status`=0';
        /**
         * Execute the query and store the results in $results
         */
        $results = $readConnection->fetchAll($query);
        $result = $results[0];
        $campaign->setData('nb_recipients', (int) $result['nb_recipients']);
        $campaign->setData('nb_sms', (int) $result['nb_sms']);
        $campaign->setData('price', (float) $result['price']);
        $campaign->setData('status', $status);
        $campaign->save();
    }

    /**
     * Get the nbr of french recipients (=phone begin by +33) of the campaign 
     * @param type $campaign
     * @return type
     */
    public static function nbr_french_recipient($campaign) {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $query = "
                SELECT count(*) as count
                FROM " . $resource->getTableName('octopushsms/recipient') . "
                WHERE id_sendsms_campaign=" . (int) $campaign->getData('id_sendsms_campaign') . "
                AND phone like '+33%' "; 
        /**
         * Execute the query 
         */
        $results = $readConnection->fetchAll($query);
        return $results[0]['count'];
    }

    public static function duplicate($campaign) {
        $old_id = $campaign->getData('id_sendsms_campaign');
        $newCampaign = Mage::getModel('octopushsms/campaign');
        $newCampaign->addData($campaign->getData());
        $newCampaign->setData('id_sendsms_campaign', null);
        $newCampaign->setData('status', 0);
        $newCampaign->setData('nb_recipients', 0);
        $newCampaign->setData('nb_sms', 0);
        $newCampaign->setData('price', 0);
        $newCampaign->setData('ticket', (string) time());
        $newCampaign->setData('date_transmitted', NULL);
        $newCampaign->setData('date_validation', NULL);
        $newCampaign->setData('date_add', NULL);
        $newCampaign->setData('date_upd', NULL);
        $newCampaign->save();

        //duplicate the recipients
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $query = 'INSERT INTO `' . $resource->getTableName('octopushsms/recipient') . '` (`id_sendsms_campaign`, `id_customer`, `firstname`, `lastname`, `phone`, `iso_country`, `transmitted`, `price`, `nb_sms`, `status`, `ticket`, `date_add`, `date_upd`)
                    SELECT ' . $newCampaign->getData('id_sendsms_campaign') . ', `id_customer`, `firstname`, `lastname`, `phone`, `iso_country`, 0, 0, 0, 0, NULL, NOW(), NOW() FROM `' . $resource->getTableName('octopushsms/recipient') . '` WHERE `id_sendsms_campaign`=' . $old_id;
        $writeConnection->query($query);
        self::compute_campaign($newCampaign);
        return $newCampaign;
        /* TODO remove $nb_recipients = $wpdb->get_var('SELECT count(*) AS total FROM `' . $wpdb->prefix . 'octopushsms_recipient` WHERE `id_sendsms_campaign`=' . $indexController->_campaign->id_sendsms_campaign);
          $indexController->_campaign->nb_recipients = $nb_recipients;
          $indexController->_campaign->save(); */
    }

    /**
     * Stat of transfer in progress campaign
     * @param type $campaign
     */
    public static function get_recipient_to_transmit($campaign) {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $query = "SELECT SQL_CALC_FOUND_ROWS *
                  FROM `" . $resource->getTableName('octopushsms/recipient') . "`
                  WHERE id_sendsms_campaign=" . (int) $campaign->getData('id_sendsms_campaign') . "
                  AND transmitted = 0
                  ORDER BY id_sendsms_recipient ASC
                LIMIT 200";
        /**
         * Execute the query 
         */
        $results = $readConnection->fetchAll($query);
        return $results;
    }

    public static function get_total_nbr_of_recipient_to_transmit() {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $query = "SELECT FOUND_ROWS() AS NbRows";
        /**
         * Execute the query 
         */
        $results = $readConnection->fetchAll($query);
        return $results[0]['NbRows'];
    }

    public static function get_recipients_from_query($indexController, $campaignModel, $count = true) {
        //date format (if ingnore years is set or not)
        $birthday_format = $register_format = $lastivisit_format = '%Y-%m-%d';
        if ($indexController->getRequest()->getParam('sendsms_query_birthday_years'))
            $birthday_format = '%m-%d';
        if ($indexController->getRequest()->getParam('sendsms_query_registered_years'))
            $register_format = '%m-%d';
        if ($indexController->getRequest()->getParam('sendsms_query_connected_years'))
            $lastivisit_format = '%m-%d';

        $collection = Mage::getResourceModel('customer/customer_collection')
                ->addNameToSelect()
                ->addAttributeToSelect('email')
                ->addAttributeToSelect('created_at')
                ->addAttributeToSelect('group_id')
                //->addExpressionAttributeToSelect('created_at_f', "date_format({{created_at}},'$register_format')", "created_at")
                //->addExpressionAttributeToSelect('login_at_f', "date_format({{login_at}},'$lastivisit_format')", "login_at")
                ->joinAttribute('billing_postcode', 'customer_address/postcode', 'default_billing', null, 'left')
                ->joinAttribute('billing_city', 'customer_address/city', 'default_billing', null, 'left')
                ->joinAttribute('billing_telephone', 'customer_address/telephone', 'default_billing', null, 'inner')
                ->joinAttribute('billing_region', 'customer_address/region', 'default_billing', null, 'left')
                ->joinAttribute('billing_country_id', 'customer_address/country_id', 'default_billing', null, 'left');

        if ($indexController->getRequest()->getParam('sendsms_query_group') && $indexController->getRequest()->getParam('sendsms_query_group') != '*') {
            $collection->addAttributeToFilter('group_id', $indexController->getRequest()->getParam('sendsms_query_group'));
        }
        if ($indexController->getRequest()->getParam('sendsms_query_shop') && $indexController->getRequest()->getParam('sendsms_query_shop') != '*') {
            $collection->addAttributeToFilter('store_id', $indexController->getRequest()->getParam('sendsms_query_shop'));
        }
        if ($indexController->getRequest()->getParam('sendsms_query_country') && $indexController->getRequest()->getParam('sendsms_query_country') != '*') {
            $collection->addAttributeToFilter('billing_country_id', $indexController->getRequest()->getParam('sendsms_query_country'));
        }
        if ($indexController->getRequest()->getParam('sendsms_query_newsletter') == '0') {
            $collection->joinTable(Mage::getSingleton('core/resource')->getTableName('newsletter_subscriber'), 'customer_id=entity_id', array('subscriber_status' => 'subscriber_status'), null, 'left');
            $collection->addAttributeToFilter(array(
                array('attribute' => 'subscriber_status', 'eq' => $indexController->getRequest()->getParam('sendsms_query_newsletter')),
                array('attribute' => 'subscriber_status', 'null' => true)
            ));
        }
        if ($indexController->getRequest()->getParam('sendsms_query_newsletter') == '1') {
            $collection->joinTable(Mage::getSingleton('core/resource')->getTableName('newsletter_subscriber'), 'customer_id=entity_id', array('subscriber_status' => 'subscriber_status'), null, 'left');
            //$collection->addAttributeToFilter('subscriber_status', $indexController->getRequest()->getParam('sendsms_query_newsletter'));
            $collection->addAttributeToFilter(array(
                array('attribute' => 'subscriber_status', 'eq' => $indexController->getRequest()->getParam('sendsms_query_newsletter')),
                array('attribute' => 'subscriber_status', 'is null')
            ));
        }

        //created_at
        if ($indexController->getRequest()->getParam('sendsms_query_registered_from') || $indexController->getRequest()->getParam('sendsms_query_registered_to')) {
            if (!empty($indexController->getRequest()->getParam('sendsms_query_registered_from'))) {
                $from = $indexController->getRequest()->getParam('sendsms_query_registered_from');
                $collection->getSelect()->where("date_format(created_at,'$register_format') >= date_format('$from','$register_format')");
            }
            if (!empty($indexController->getRequest()->getParam('sendsms_query_registered_to'))) {
                $to = $indexController->getRequest()->getParam('sendsms_query_registered_to');
                $collection->getSelect()->where("date_format(created_at,'$register_format') <= date_format('$to','$register_format')");
            }
        }

        //log
        if ($indexController->getRequest()->getParam('sendsms_query_connected_from') || $indexController->getRequest()->getParam('sendsms_query_connected_to')) {
            $collection->joinTable(Mage::getSingleton('core/resource')->getTableName('log_customer'), 'customer_id=entity_id', array('login_at' => 'login_at'), null, 'left');

            if ($indexController->getRequest()->getParam('sendsms_query_connected_from')) {
                $from = $indexController->getRequest()->getParam('sendsms_query_connected_from');
                /* $collection->addAttributeToFilter('login_at', array(
                  'from' => $from,
                  'date' => true, // specifies conversion of comparison values
                  )); */
                $collection->getSelect()->where("date_format(login_at,'$lastivisit_format') >= date_format('$from','$lastivisit_format')");
            }
            if ($indexController->getRequest()->getParam('sendsms_query_connected_to')) {
                $to = $indexController->getRequest()->getParam('sendsms_query_connected_to');
                /* $collection->addAttributeToFilter('login_at', array(
                  'to' => $to,
                  'date' => true, // specifies conversion of comparison values
                  )); */
                $collection->getSelect()->where("date_format(login_at,'$lastivisit_format') <= date_format('$to','$lastivisit_format')");
            }
        }
        //birth
        if ($indexController->getRequest()->getParam('sendsms_query_birthday_from') || $indexController->getRequest()->getParam('sendsms_query_birthday_to')) {
            $collection->joinAttribute('dob', 'customer/dob', 'entity_id', null, 'left');
            if ($indexController->getRequest()->getParam('sendsms_query_birthday_from')) {
                $from = $indexController->getRequest()->getParam('sendsms_query_birthday_from');
                $collection->getSelect()->where("date_format(at_dob.value,'$birthday_format') >= date_format('$from','$birthday_format')");
            }
            if ($indexController->getRequest()->getParam('sendsms_query_birthday_to')) {
                $to = $indexController->getRequest()->getParam('sendsms_query_birthday_to');
                $collection->getSelect()->where("date_format(at_dob.value,'$birthday_format') <= date_format('$to','$birthday_format')");
            }
        }

        //orders
        if ($indexController->getRequest()->getParam('sendsms_query_orders_from') 
                || $indexController->getRequest()->getParam('sendsms_query_orders_to')
                || $indexController->getRequest()->getParam('sendsms_query_orders_none')) {
            //$collection->addAttributeToSelect('order_id');
            $collection->joinTable(Mage::getSingleton('core/resource')->getTableName('sales_flat_order'), 'customer_id=entity_id', array('order_id' => 'entity_id'), null, 'left');
            $collection->addExpressionAttributeToSelect('order_nbr', 'COUNT({{entity_id}})', 'entity_id');
            $collection->groupByAttribute('entity_id');
            //$collection->getSelect()->select("COUNT(mag_sales_flat_order.customer_id) AS `order_nbr`");

            if ($indexController->getRequest()->getParam('sendsms_query_orders_from')) {
                $collection->getSelect()->having('COUNT(mag_sales_flat_order.customer_id) >= ' . $indexController->getRequest()->getParam('sendsms_query_orders_from'));
                //$collection->addAttributeToFilter('order_nbr', array('gteq'=>$indexController->getRequest()->getParam('sendsms_query_orders_from')));
            }
            if ($indexController->getRequest()->getParam('sendsms_query_orders_to')) {
                $collection->getSelect()->having('COUNT(mag_sales_flat_order.customer_id) <= ' . $indexController->getRequest()->getParam('sendsms_query_orders_to'));
            }
            if ($indexController->getRequest()->getParam('sendsms_query_orders_none')) {
                $collection->getSelect()->having('COUNT(mag_sales_flat_order.customer_id) = 0 or order_nbr is null');
            }
        }
        
        if ($count) {
            return count($collection);
        } else {
            return $collection;
        }
    }

}
