<?php
if(!defined('DB_DRIVER') || DB_DRIVER != 'xml') {	
	$oCollection = umiObjectsCollection::getInstance();
	$iTypeId     = umiObjectTypesCollection::getInstance()->getBaseType('webforms', 'address');
	
	$sSQL    = 'SELECT `id`, `email`, `descr` FROM cms_webforms';
	$rResult = l_mysql_query($sSQL);
	while($aRow = mysql_fetch_assoc($rResult)) {
		$iId     = $oCollection->addObject($aRow['id'], $iTypeId);
		$oObject = $oCollection->getObject($iId);
		$oObject->setValue('address_description', $aRow['descr']);
		$oObject->setValue('address_list', $aRow['email']);
		$oObject->setValue('insert_id', $aRow['id']);
		$oObject->commit();
	}
	l_mysql_query('TRUNCATE TABLE cms_webforms');
}
$oPCollection = permissionsCollection::getInstance();
$iUserTypeID  = umiObjectTypesCollection::getInstance()->getBaseType('users', 'user');
$oSelection   = new umiSelection();
$oSelection->addObjectType($iUserTypeID);
$aUIDs 		  = umiSelectionsParser::runSelection($oSelection);
if(is_array($aUIDs) && !empty($aUIDs))
foreach($aUIDs as $iUserID) {
	$oPCollection->setModulesPermissions($iUserID, 'webforms', 'add');
}
?>
