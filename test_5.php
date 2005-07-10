<?php
dl ("php_ffi.dll");

$strFunc = "[lib='kernel32.dll'] long GetLogicalDriveStrings (long &BufferLength, string &Buffer);";
/*$this->objApi->registerfunction("long GetLogicalDrives Alias GetLogicalDrives () From kernel32.dll");
$this->objApi->registerfunction("int GetDriveType Alias GetDriveType (string lpRootPathName) From kernel32.dll");
$this->objApi->registerfunction("long GetVolumeInformationA Alias GetVolumeInformation (string lpRootPathName, string &lpVolumeNameBuffer, int nVolumeNameSize, int &lpVolumeSerialNumber, int &lpMaximumComponentLength, int &lpFileSystemFlags, string &lpFileSystemNameBuffer, int nFileSystemNameSize) From kernel32.dll"); 
*/

$strFunc = "[lib='kernel32.dll'] long GetLogicalDriveStringsA(long nBufferLength, char *lpBuffer);";
$strFunc .= "[lib='kernel32.dll'] long GetLogicalDrives();";
$strFunc .= "[lib='kernel32.dll'] int GetDriveTypeA(char *lpRootPathName);";
$strFunc .= "[lib='kernel32.dll'] long GetVolumeInformationA(char lpRootPathName, char *lpVolumeNameBuffer, int nVolumeNameSize, "
          . "long *lpVolumeSerialNumber, long *lpMaximumComponentLength, long *lpFileSystemFlags, char *lpFileSystemNameBuffer, int nFileSystemNameSize);";
$ffi = new FFI($strFunc);


/*
$len = 105;
$buffer = str_repeat("\0", $len + 1);
$ret = $ffi->GetLogicalDriveStringsA($len, $buffer);
var_dump($ret, $buffer);

$drive_list = $ffi->GetLogicalDrives();
$arDrives = array();
for ($i=1, $drv=ord('A'); $drv<=ord('Z'); $drv++) {
    if ($drive_list & $i) {
        $arDrives[] = chr($drv) . ":\\";
    }
    $i = $i << 1;
}
var_dump($arDrives);

var_dump($ffi->GetDriveTypeA("Z:\\"));
*/

$strDrive = 'C:\\';
$strName                = '';
$serialNo               = 0; 
$MaximumComponentLength = 0; 
$FileSystemFlags        = 0; 
$VolumeNameSize         = 260; 
$VolumeNameBuffer       = str_repeat("\0", $VolumeNameSize); 
$FileSystemNameSize     = 260; 
$FileSystemNameBuffer   = str_repeat("\0", $FileSystemNameSize); 
if ($result = $ffi->GetVolumeInformationA( 
    $strDrive, $VolumeNameBuffer, $VolumeNameSize, 
    $serialNo, $MaximumComponentLength, 
    $FileSystemFlags, $FileSystemNameBuffer, $FileSystemNameSize)) {
    
    $strName = trim($VolumeNameBuffer);
}
var_dump($strName);        
?>