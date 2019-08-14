<?php
// Exemple de envoi instantane de SMS

require('sms.inc.php');

$user_login = '************@********';
$api_key = '****************'; 

$user_batch_id = '1423514025';
$action = 'status';

$sms = new SMS();
$sms->set_user_login($user_login);
$sms->set_api_key($api_key);
$sms->set_user_batch_id($user_batch_id);

// $sms->set_date(2013, 4, 25, 15, 12); // En cas d'envoi diffÈrÈ.
$xml = $sms->SMSBatchAction($action);

//echo $xml;
//echo '<br />';
echo '<textarea style="width:600px;height:600px;">' . $xml . '</textarea>';
?>