<?php
/********************************************************************
* This file is part of yourCMDB.
*
* Copyright 2013-2016 Michael Batz
*
*
* yourCMDB is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* yourCMDB is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with yourCMDB.  If not, see <http://www.gnu.org/licenses/>.
*
*********************************************************************/
namespace yourCMDB\config;


/**
* Class for access to view configuration
* @author Michael Batz <michael@yourcmdb.org>
*/
class ViewConfig
{

	//base url
	private $baseUrl;

	//length of content tables
	private $contentTableLength;

	//qr codes ecclevel
	private $qrCodeEccLevel;

	//qr codes: url base (will be extended with the objectId)
	private $qrCodeUrlBase;

	//i18n language
	private $language;

	//title of installation
	private $installTitle;

	//menu items
	private $menuItems;

	/**
	* creates a ViewConfig object from xml file view-configuration.xml
	*/
	public function __construct($xmlfile)
	{
		$xmlobject = simplexml_load_file($xmlfile);

		//read base url
		$this->baseUrl = (string) $xmlobject->{'url'}[0]['base'];

		//read content table length
		$this->contentTableLength = (int) $xmlobject->{'content-tables'}[0]['length'];

		//read qr-code configuration
		$this->qrCodeEccLevel= "M";
		$this->qrCodeUrlBase = $this->baseUrl."/shortlink.php?id=";
		if(isset($xmlobject->{'qrcodes'}[0]['ecc-level']))
		{
			$this->qrCodeEccLevel= (string) $xmlobject->{'qrcodes'}[0]['ecc-level'];
		}
		if(isset($xmlobject->{'qrcodes'}[0]['urlbase']) && $xmlobject->{'qrcodes'}[0]['urlbase'] != "")
		{
			$this->qrCodeUrlBase = $xmlobject->{'qrcodes'}[0]['urlbase'];
		}

		//read language for internationalization
		$this->language = "en_GB";
		if(isset($xmlobject->{'i18n'}[0]['language']))
		{
			$this->language = (string) $xmlobject->{'i18n'}[0]['language'];
		}

		//read title of installation
		$this->installTitle = "yourCMDB";
		if(isset($xmlobject->{'title'}[0]['name']))
		{
			$this->installTitle = (string) $xmlobject->{'title'}[0]['name'];
		}

		//read menu items
		$this->menuItems = Array();
		foreach($xmlobject->xpath('//menu-item') as $menuItem)
		{
			$this->menuItems[(string)$menuItem['name']] = (string) $menuItem['url'];
		}
	}

	/**
	* Returns base URL
	*/
	public function getBaseUrl()
	{
		return $this->baseUrl;
	}

	/**
	* Returns max length of content tables
	*/
	public function getContentTableLength()
	{
		return $this->contentTableLength;
	}

	/**
	* Returns wether qr codes are enabled
	*/
	public function getQrCodeEnabled()
	{
		return $this->qrCodeEnabled;
	}

	/**
	* Returns qr code url prefix
	*/
	public function getQrCodeUrlPrefix()
	{
		return $this->qrCodeUrlBase;
	}

	/**
	* Returns qr code ecc level for API
	*/
	public function getQrCodeEccLevel()
	{
		switch($this->qrCodeEccLevel)
		{
			case "L": 
				return 1;

			case "M":
				return 0;

			case "Q":
				return 3;

			case "H":
				return 2;

			default:
				return 0;
		}
	}

	/**
	* Returns items for main menu
	*/
	public function getMenuItems()
	{	
		return $this->menuItems;
	}

	/**
	* Returns locale for i18n
	*/
	public function getLocale()
	{
		return $this->language;
	}

	/**
	* Returns title for installation
	*/
	public function getInstallTitle()
	{
		return $this->installTitle;
	}
}

?>
