<?php

/**
 * Block to dislay newsreceive from octopush
 */
class Octopush_OctopushSms_Block_News extends Mage_Core_Block_Template {

    public function __construct(array $args = array()) {
        parent::__construct($args);
    }

    public function displayNews() {
        $helperAPI = Mage::helper('octopushsms/API');
        $xml = $helperAPI->get_news();
        if (!key_exists('error_code', $xml) || $xml->error_code == '000') {
            //display information message if the language receive is different of your language
            $locale = Mage::app()->getLocale()->getLocaleCode();
            if (!isset($xml->news) ) {
                //Mage::getSingleton('adminhtml/session')->addError(__('No news available'));
                echo '<ul class="messages"><li class="error-msg"><ul><li>'.__('No news available').'</li></ul></li></ul>';
                return;
            }
            if (substr($locale, 0, 2) != $xml->news->new[0]->lang) {
                echo '<ul class="messages"><li class="warning-msg"><ul><li>'._('News in your language are not available.').'</li></ul></li></ul>';                
            }
            //display each news
            echo '<ul>';
            foreach ($xml->news->new as $new) {
                ?>
                <li class="box">
                    <a class="rsswidget" href="http://octopush.com">
                <?php echo str_replace(']]>', '', str_replace('<![CDATA[', '', $new->title)); ?>
                    </a>
                    &nbsp;<span class="rss-date"><?php echo $new->date . ' ' . __('author', 'octopush-sms') . ': ' . $new->author; ?></span>
                    <div class="rssSummary">
                <?php echo str_replace(']]>', '', str_replace('<![CDATA[', '', $new->text)); ?>
                    </div>
                </li>
                <?php
            }
            echo '</ul>';
        } else {
            Mage::getSingleton('adminhtml/session')->addError($helperAPI->get_error_SMS($xml->error_code));
        }
    }

}
