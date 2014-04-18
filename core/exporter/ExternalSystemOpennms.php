<?php
/********************************************************************
* This file is part of yourCMDB.
*
* Copyright 2013-2014 Michael Batz
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

/**
* Export API - API for access to OpenNMS (http://www.opennms.org)
* @author Michael Batz <michael@yourcmdb.org>
*/
class ExternalSystemOpennms implements ExternalSystem
{
	//ExportVariables
	private $variables;

	//URL for OpenNMS REST API
	private $restUrl;

	//Username for OpenNMS REST API
	private $restUser;

	//Password for OpenNMS REST API
	private $restPassword;

	//Name of OpenNMS requisition to use
	private $requisition;

	//XML for requisition
	private $requisitionXml;

	//static var: asset field names -> length
	private static $assetfields = array(
					"category"		=> 64,
					"manufacturer"		=> 64,
					"vendor"		=> 64,
					"modelnumber"		=> 64,
					"serialnumber"		=> 64,
					"description"		=> 128,
					"circuitid"		=> 64,
					"assetnumber"		=> 64,
					"operatingsystem"	=> 64,
					"rack"			=> 64,
					"slot"			=> 64,
					"port"			=> 64,
					"region"		=> 64,
					"division"		=> 64,
					"department"		=> 64,
					"address1"		=> 256,
					"address2"		=> 256,
					"city" 			=> 64,
					"state"			=> 64,
					"zip"			=> 64,
					"building"		=> 64,
					"floor"			=> 64,
					"room"			=> 64,
					"vendorphone"		=> 64,
					"vendorfax"		=> 64,
					"vendorassetnumber"	=> 64,
					"dateinstalled"		=> 64,
					"lease"			=> 64,
					"leaseexpires"		=> 64,
					"supportphone"		=> 64,
					"maintcontract"		=> 64,
					"maintcontractexpires"	=> 64,
					"displaycategory"	=> 64,
					"notifycategory"	=> 64,
					"pollercategory"	=> 64,
					"thresholdcategory"	=> 64,
					"comment"		=> 512,
					"username"		=> 32,
					"password"		=> 32,
					"enable"		=> 32,
					"connection"		=> 32,
					"cpu"			=> 64,
					"ram"			=> 10,
					"storagectrl"		=> 64,
					"hdd1"			=> 64,
					"hdd2"			=> 64,
					"hdd3"			=> 64,
					"hdd4"			=> 64,
					"hdd5"			=> 64,
					"hdd6"			=> 64,
					"admin"			=> 32,
					"snmpcommunity"		=> 32,
					"country"		=> 32
					);

	public function setUp(ExportDestination $destination, ExportVariables $variables)
	{
		//get variables
		$this->variables = $variables;

		//check, if parameters are set
		$parameterKeys = $destination->getParameterKeys();
		if(!(	in_array("resturl", $parameterKeys) && 
			in_array("restuser", $parameterKeys) && 
			in_array("restpassword", $parameterKeys) &&
			in_array("requisition", $parameterKeys)))
		{
			throw new ExportExternalSystemException("Parameters for ExternalSystem not set correctly");
		}

		//get parameters for OpenNMS access
		$this->restUrl = $destination->getParameterValue("resturl");
		$this->restUser = $destination->getParameterValue("restuser");
		$this->restPassword = $destination->getParameterValue("restpassword");
		$this->requisition = $destination->getParameterValue("requisition");

		//check connection to OpenNMS
		if(!($this->checkConnection()))
		{
			throw new ExportExternalSystemException("Cannot establish connection to OpenNMS REST API");
		}
		
		//start requisitionXml
		$this->requisitionXml = "";
	}

	public function addObject(CmdbObject $object)
	{
		//define node parameters
		$nodeLabel = $this->variables->getVariable("nodelabel")->getValue($object);
		$nodeForeignId = $object->getId();
		$nodeInterfaces = array($this->variables->getVariable("ip")->getValue($object));
		$nodeServices = array();
		$nodeCategories = array();

		//define asset fields for node
		$nodeAssets = array();
		//walk through all variables
		foreach($this->variables->getVariableNames() as $variableName)
		{
			//check if it is an "asset_" variable
			if(preg_match('/^asset_(.*)$/', $variableName, $matches) == 1)
			{
				//check if the asset field exists
				$fieldname = $matches[1];
				if(isset(self::$assetfields[$fieldname]))
				{
					$fieldvalue = $this->variables->getVariable($variableName)->getValue($object);
					$fieldlength = self::$assetfields[$fieldname];
					$nodeAssets[$fieldname] = $this->formatAssetfield($fieldvalue, $fieldlength);
				}
			}
		}
		

		//add nodes to requisition
		$xml = $this->addNode($nodeLabel, $nodeForeignId, $nodeInterfaces, $nodeServices, $nodeCategories, $nodeAssets);
		$this->requisitionXml .= $xml;

		//ToDo: SNMP config
		
		
	}

	public function finishExport()
	{
		//add requisition to OpenNMS
		$this->addRequisition($this->requisition, $this->requisitionXml);
		
		//synchronize requisiion
		$this->importRequisition($this->requisition);
	}

	/*
	* Sends XML data to OpenNMS REST API
	* @param $resource 	ReST Resource to use (for example /nodes)
	* @param $httpMethod	HTTP method for access to Rest API (POST/PUT)
	* @param $xml 		XML data to send
	* @returns boolean 	true, if there was no REST error, false if the connection failed
	*/
	private function sendData($resource, $httpMethod, $xml)
	{
		//get httpMethod
		if($httpMethod != "POST")
		{
			$httpMethod = "PUT";
		}

                //curl request
		$curl = curl_init();
		$curlOptions = array(
                        CURLOPT_HTTPAUTH        => CURLAUTH_BASIC,
                        CURLOPT_USERPWD         => "{$this->restUser}:{$this->restPassword}",
			CURLOPT_POSTFIELDS	=> "$xml",
			CURLOPT_CUSTOMREQUEST 	=> "$httpMethod",
			CURLOPT_HTTPHEADER	=> array('Content-Type: application/xml'),
                        CURLOPT_URL             => "{$this->restUrl}/{$resource}",
                        CURLOPT_RETURNTRANSFER  => TRUE);
		curl_setopt_array($curl, $curlOptions);
		$result = curl_exec($curl);
		$curlHttpResponse = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		//check HTTP response code
		if($curlHttpResponse == "200" || $curlHttpResponse == "303")
		{
                        return true;
		}
                return false;
	}

	/**
	* Gets data from OpenNMS REST API
	* @param $resource	resource
	* @returns 		String with XML data that were returned or FALSE if there was an error
	*/
	private function getData($resource)
	{
                //curl request
		$curl = curl_init();
		$curlOptions = array(
                        CURLOPT_HTTPAUTH        => CURLAUTH_BASIC,
                        CURLOPT_USERPWD         => "{$this->restUser}:{$this->restPassword}",
                        CURLOPT_URL             => "{$this->restUrl}/{$resource}",
                        CURLOPT_RETURNTRANSFER  => true);
		curl_setopt_array($curl, $curlOptions);
		$result = curl_exec($curl);
		$curlHttpResponse = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		//error handling
                if($result == false)
                {
                        return false;
                }

		//check HTTP response code and return result
		if($curlHttpResponse == "200" || $curlHttpResponse == "303")
		{
                        return $result;
		}
		return false;
	}

	/**
	* Deletes a resource using OpenNMS REST API
	* @param $resource	resource to delete
	* @return boolean	true if successful, false if not
	*/
	private function deleteData($resource)
	{
		//curl http request
		$curl = curl_init();
                $curlOptions = array(
                        CURLOPT_CUSTOMREQUEST   => "DELETE",
                        CURLOPT_HTTPAUTH        => CURLAUTH_BASIC,
                        CURLOPT_USERPWD         => "{$this->restUser}:{$this->restPassword}",
                        CURLOPT_URL             => "{$this->restUrl}/{$resource}",
                        CURLOPT_RETURNTRANSFER  => TRUE);
                curl_setopt_array($curl, $curlOptions);
                $result = curl_exec($curl);
                $curlHttpResponse = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);

                //check HTTP response code
                if($curlHttpResponse == "200" || $curlHttpResponse == "303")
                {
                        return true;
                }
		return false;
	}

	/**
	* Checks connection to OpenNMS REST API
	* @returns	true, of connection is successful, false if not
	*/
	private function checkConnection()
	{
		$result = $this->getData("foreignSources");
		if($result != false)
		{
			return true;
		}
		return false;
	}

	/**
	* Adds a requisition with the given name and xml data. An existing requisition will be overwritten
	* @param $name		name of the requisition to add
	* @param $xmldata	xml structure of the requistion
	* @returns boolean	true, if the requisition was added, false if there was an error
	*/
	private function addRequisition($name, $xmldata)
	{
		$xml = '<model-import foreign-source="'.$name.'">';
		if(isset($xmldata))
		{
			$xml.= $xmldata;
		}
		$xml.= '</model-import>';
		$resource = "requisitions";
		return $this->sendData($resource, "POST", $xml);
	}

	/**
	* Imports the given requisition (synchronize)
	* @param $name		name of the requisition to import
	* @returns boolean	true, if import was successful, false, if not
	*/
	private function importRequisition($name)
	{
		$xml = "";
		$resource = "requisitions/$name/import";
		return $this->sendData($resource, "PUT", $xml);
	}

	/**
	* Adds or updates a node of a given requisition
	* @param $nodeLabel 	nodelabel of the given node
	* @param $foreignID	foreign id of the node
	* @param $interfaces	Array with all L3 interfaces and services
	* @param $services	Array with all services for L3 interfaces
	* @param $categories	Array with surveillance categories for the node
	* @param $assets	Array with asset information for the node
	* @returns boolean	true, if node was added, false if there was an error adding the node
	*/
	public function addNode($nodelabel, $foreignID, $interfaces, $services, $categories, $assets)
	{
		//adding node information
		$xml = '<node node-label="'.$nodelabel.'" foreign-id="'.$foreignID.'" building="'.$foreignID.'">';

		//adding L3 interfaces and services
		foreach($interfaces as $interface)
		{
			$xml .= '<interface snmp-primary="P" status="1" ip-addr="'.$interface.'" descr="">';
			if(isset($services["$interface"]))
			{
				foreach($services["$interface"] as $service)
				{
					$xml .= '<monitored-service service-name="'.$service.'"/>';
				}
			}
			$xml .= '</interface>';
		}

		//adding surveillance categories
		if(isset($categories))
		{
			foreach($categories as $category)
			{
				$xml .= '<category name="'.$category.'"/>';
			}
		}

		//adding asset information
		if(isset($assets))
		{
			foreach(array_keys($assets) as $assetname)
			{
				$xml .= '<asset name="'.$assetname.'" value="'.$assets[$assetname].'" />';
			}
		}

		//adding footer
		$xml.='</node>';

		//return xml data
		return $xml;
	}

	private function formatAssetfield($value, $length)
	{
		$value = substr($value, 0, $length);
		$value = htmlspecialchars($value);
		return $value;
	}
}
?>
