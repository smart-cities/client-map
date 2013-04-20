<?php

require('../common.php');

$db = Dbo::getConnection();
$stm = $db->prepare('SHOW TABLES');
$db->executeStatement($stm,array());
var_dump($stm->fetchAll(Dbo::FETCH_ASSOC));

// $d = new Device();
