<?php
/**
*   Example script for the "System_WinDrives" package
*   @author Christian Weiske <cweiske@cweiske.de>
*/

require_once('System/WinDrives.php');

//if you want to read the names, pass "true" as first parameter
//this may crash your php.exe, so it's disabled by default
$wd = new System_WinDrives(false);

//var_dump($wd->guessDriveList());

echo 'API available: ';
echo $wd->isApiAvailable() ? 'yes' : 'no';
echo "\r\n";

echo 'Read drive names: ';
echo $wd->getReadName() ? 'yes' : 'no';
echo "\r\n";

$arInfo = $wd->getDrivesInformation();
foreach ($arInfo as $strDrive => $objInfo) {
    echo $strDrive . "\r\n";
    echo '   Type: ' . $objInfo->type . "\r\n";
    echo '   Type title: ' .  $objInfo->typetitle . "\r\n";
    echo '   Name: ' . $objInfo->name . "\r\n";
}

?>