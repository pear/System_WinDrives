<?php
/**
* Get drive information on windows systems
*
* PHP Versions 4 and 5
*
* @category System
* @package  System_WinDrives
* @author   Christian Weiske <cweiske@php.net>
* @license  http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
* @version  CVS: $Id$
* @link     http://pear.php.net/package/System_WinDrives
*/

require_once 'PEAR.php';

if (!defined('SYSTEM_WINDRIVE_REMOVABLE')) {
    define('SYSTEM_WINDRIVE_ERROR'    , 1);
    define('SYSTEM_WINDRIVE_REMOVABLE', 2);
    define('SYSTEM_WINDRIVE_FIXED'    , 3);
    define('SYSTEM_WINDRIVE_REMOTE'   , 4);
    define('SYSTEM_WINDRIVE_CDROM'    , 5);
    define('SYSTEM_WINDRIVE_RAMDISK'  , 6);
}

/**
* Get drive information on windows systems
*
* This class gives back a list of existing drives
* (like a:\, c:\ and so) as well as the drive types
* (hard disk, cdrom, network, removable)
* and drive names if any
*
* The class requires the php_w32api.dll on php4 and
* the php_ffi.dll for php5
*
* Note that the php_win32api.dll shipped with normal php
* packages has a problem with many parameters
* This means that the script will crash when trying
* to get the drive name. use "setReadName(false)" to
* prevent this.
*
* On php5, the drive _names_ are always ''.
*
* You should not use this on non-Windows operating systems
*
* If you use this class in your projects, I ask you
*   to send a real-world postcard to:
*     Christian Weiske
*     Dorfstrasse 42
*     04683 Threna
*     Germany
*
* @category System
* @package  System_WinDrives
* @author   Christian Weiske <cweiske@php.net>
* @license  http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
* @link     http://pear.php.net/package/System_WinDrives
*/
class System_WinDrives
{
    /**
    * If the drive names shall be enumerated
    * This can cause problems with some versions
    * of win32api.dll, so it's disabled by default
    * @access protected
    * @var boolean
    */
    var $bReadName = false;

    /**
    * The win32api object to use
    * If it's null, it can't be used
    * @access protected
    * @var object
    */
    var $objApi   = null;

    /**
    * The php_ffi object to use
    * If it's null, it can't be used
    * php_ffi replaces win32api in php5
    * @access protected
    * @var object
    */
    var $objFFI   = null;

    /**
    * List with titles for the drive types
    * @access public
    * @var array
    */
    var $arTypeTitles = array(
        'A' => '3.5" Floppy',
        SYSTEM_WINDRIVE_ERROR     => 'non-existent',
        SYSTEM_WINDRIVE_REMOVABLE => 'Removable',
        SYSTEM_WINDRIVE_FIXED     => 'Harddisk',
        SYSTEM_WINDRIVE_REMOTE    => 'Network drive',
        SYSTEM_WINDRIVE_CDROM     => 'CD-Rom',
        SYSTEM_WINDRIVE_RAMDISK   => 'RAM-Disk'
    );



    /**
    * Constructs the class and checks if the api
    * is available
    *
    * @param boolean $bReadName If the drive names shall be read
    *
    * @access public
    */
    function System_WinDrives($bReadName = false)
    {
        if (version_compare(phpversion(), '5.0.0', '>=')) {
            //PHP 5
            if (class_exists('FFI') || PEAR::loadExtension('ffi')) {
                //we've got the dll
                $strFuncs     = "[lib='kernel32.dll'] long GetLogicalDriveStringsA(long nBufferLength, char *lpBuffer);"
                              . "[lib='kernel32.dll'] long GetLogicalDrives();"
                              . "[lib='kernel32.dll'] int GetDriveTypeA(char *lpRootPathName);";
                $this->objFFI = new FFI($strFuncs);
            }
        } else {
            //PHP 4
            if (class_exists('win32') || PEAR::loadExtension('w32api')) {
                //we have the dll
                $this->objApi =& new win32();
                $this->objApi->registerfunction("long GetLogicalDriveStrings Alias GetLogicalDriveStrings (long &BufferLength, string &Buffer) From kernel32.dll");
                $this->objApi->registerfunction("long GetLogicalDrives Alias GetLogicalDrives () From kernel32.dll");
                $this->objApi->registerfunction("int GetDriveType Alias GetDriveType (string lpRootPathName) From kernel32.dll");
                $this->objApi->registerfunction("long GetVolumeInformationA Alias GetVolumeInformation (string lpRootPathName, string &lpVolumeNameBuffer, int nVolumeNameSize, int &lpVolumeSerialNumber, int &lpMaximumComponentLength, int &lpFileSystemFlags, string &lpFileSystemNameBuffer, int nFileSystemNameSize) From kernel32.dll");
            }
        }
        $this->setReadName($bReadName);
    }



    /**
    * returns an array containing information about all drives
    *
    * @return array Array with drive infomation
    *
    * @access public
    */
    function getDrivesInformation()
    {
        $arInfo   = array();
        $arDrives = $this->getDriveList();
        foreach ($arDrives as $strDrive) {
            $arInfo[$strDrive]->type      = $this->getDriveType($strDrive);
            $arInfo[$strDrive]->name      = $this->getDriveName($strDrive);
            $arInfo[$strDrive]->typetitle = $this->getTypeTitle(
                $arInfo[$strDrive]->type, $strDrive
            );
        }
        return $arInfo;
    }



    /**
    * Setter for "bReadName"
    *
    * @param boolean $bReadName If the drive's names shall be read
    *
    * @return void
    *
    * @access public
    */
    function setReadName($bReadName)
    {
        $this->bReadName = $bReadName;
    }



    /**
    * Returns the "bReadName" setting
    *
    * @return boolean If drive names should be read
    *
    * @access public
    */
    function getReadName()
    {
        return $this->bReadName;
    }



    /**
    * checks if the win32 api/ffi is available
    *
    * @return boolean True if the api can be used, false if the dll is missing
    *
    * @access public
    */
    function isApiAvailable()
    {
        return ($this->objApi !== null || $this->objFFI !== null);
    }



    /**
    * returns a list with all drive paths
    * like "A:\", "C:\" and so
    *
    * @return array Array with all drive paths
    *
    * @access public
    */
    function getDriveList()
    {
        $arDrives = array();

        if ($this->objApi !== null) {
            //set the length your variable should have
            $len = 105;
            //prepare an empty string
            $buffer = str_repeat("\0", $len + 1);

            if ($this->objApi->GetLogicalDriveStrings($len, $buffer)) {
                $arDrives = explode("\0", trim($buffer));
            } elseif ($drive_list = $this->objApi->GetLogicalDrives()) {
                $arDrives = $this->splitDriveNumner($drive_list);
            }
        } else if ($this->objFFI !== null) {
            $drive_list = $this->objFFI->GetLogicalDrives();
            $arDrives   = $this->splitDriveNumber($drive_list);
        }

        if (count($arDrives) == 0) {
            //nothing found... we'll guess
            $arDrives = $this->guessDriveList();
        }

        return $arDrives;
    }



    /**
    * splits a number returned by GetLogicalDrives*()
    * into an array with drive strings ("A:\", "C:\")
    *
    * @param int $nDrives The number the function gave back
    *
    * @return array Array with drives
    *
    * @access protected
    */
    function splitDriveNumber($nDrives)
    {
        $arDrives = array();
        //A=65, Z=90
        for ($i=1, $drv = 65; $drv <= 90; $drv++) {
            if ($nDrives & $i) {
                $arDrives[] = chr($drv) . ":\\";
            }
            $i = $i << 1;
        }
        return $arDrives;
    }



    /**
    * Tries to guess the drive list
    * The floppy "A:\" will no be in the list
    *
    * @return array Array with all the drive paths like "A:\" and "C:\"
    *
    * @access public
    */
    function guessDriveList()
    {
        $arDrives = array();
        //DON'T begin with A or B as a message box will pop up
        //if no floppy is provided
        //A=65, C=67, Z=90
        for ($i = 1, $drv = 67; $drv <= 90; $drv++) {
            if (is_dir(chr($drv) . ':\\')) {
                $arDrives[] = chr($drv) . ':\\';
            }
        }
        return $arDrives;
    }



    /**
    * Returns the drive type
    *
    * @param string $strDrive Drive path like "C:\"
    *
    * @return int Drive type, use "DRIVE_*" constants to enumerate it
    *
    * @access public
    */
    function getDriveType($strDrive)
    {
        if ($this->objApi !== null) {
            $nType = $this->objApi->GetDriveType($strDrive);
        } else if ($this->objFFI !== null) {
            $nType = $this->objFFI->GetDriveTypeA($strDrive);
        } else {
            //no api available...
            $nType = SYSTEM_WINDRIVE_FIXED;
        }
        return $nType;
    }



    /**
    * Returns the title for a given drive type.
    *
    * @param int    $nType    The drive type
    * @param string $strDrive The drive path (like "A:\")
    *
    * @return string  The type title like "Harddisk"
    *
    * @access public
    */
    function getTypeTitle($nType, $strDrive)
    {
        if ($nType == SYSTEM_WINDRIVE_REMOVABLE && $strDrive[0] == 'A') {
            $nType = 'A';
        }
        return $this->arTypeTitles[$nType];
    }



    /**
    * returns the name of the drive
    *
    * The name is the one the user has given the drive
    * like "Windows" or "Data"
    *
    * @param string $strDrive Drive path like "C:\"
    *
    * @return string The name of the drive
    *
    * @access public
    */
    function getDriveName($strDrive)
    {
        if ($this->objApi === null || $this->bReadName === false) {
            return '';
        }

        $strName                = '';
        $serialNo               = 0;
        $MaximumComponentLength = 0;
        $FileSystemFlags        = 0;
        $VolumeNameSize         = 260;
        $VolumeNameBuffer       = str_repeat("\0", $VolumeNameSize);
        $FileSystemNameSize     = 260;
        $FileSystemNameBuffer   = str_repeat("\0", $FileSystemNameSize);
        if ($result = $this->objApi->GetVolumeInformation(
            $strDrive, $VolumeNameBuffer, $VolumeNameSize,
            $serialNo, $MaximumComponentLength,
            $FileSystemFlags, $FileSystemNameBuffer, $FileSystemNameSize)) {
            $strName = trim($VolumeNameBuffer);
        }

        return $strName;
    }
}//class System_WinDrives
?>