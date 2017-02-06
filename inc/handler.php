<?php
require_once(dirname(__FILE__).'/../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../init.php');
require_once(dirname(__FILE__).'/../customblockmenu.php');

$cbm = new CustomBlockMenu();
return $cbm->update($_POST);