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
namespace yourCMDB\controller;

use yourCMDB\exceptions\CmdbObjectNotFoundException;
use \Exception;

/**
* Class to define available data types and data interpretation
* used in functions that add/write/delete objects
* not used in functions, that only read objects (for performance reasons)
* @author Michael Batz <michael@yourcmdb.org>
*/
class DataTypeInterpreter
{

	//datatypes
	private static $types = Array("text", "textarea", "boolean","date", "objectref", "password");

	//object controller
	private $objectController;
	
	/**
	* Creates a new data type interpreter
	* @param ObjectController $objectController	ObjectController instance
	*/
	public function __construct($objectController)
	{
		$this->objectController = $objectController;
	}


	/**
	* Returns an array with all available data types
	*/
	public function getTypes()
	{
		return self::$types;
	}

	/**
	* Returns the interpreted value for the given input value and data type
	* @param $value		value to interpret
	* @param $type		data type
	*/
	public function interpret($value, $type)
	{
		//get type parameter (field type in format <type>-<typeparameter>)
		$typeParameter = "";
		if(preg_match('/^(.*?)-(.*)/', $type, $matches) == 1)
		{
			$type = $matches[1];
			$typeParameter = $matches[2];
		}

		//interpret value
		switch($type)
		{
			case "boolean":
				$value = $this->interpretBoolean($value);
				break;

			case "objectref":
				$value = $this->interpretObjectref($value, $typeParameter);
				break;

		}

		//return interpreted valze
		return $value;
	}

	/**
	* Returns the interpreted value for the given value and boolean data type
	*/
	private function interpretBoolean($value)
	{
		if($value == "TRUE" || $value == "true" || $value == 1)
		{
			return "true";
		}
		else
		{
			return "false";
		}
	}

	/**
	* Check if the referenced object exists and has the correct type
	* returns the assetId, if the reference is okay
	* returns an empty string, if the referenced object does not exist
	*/
	private function interpretObjectref($value, $objecttype)
	{
		//check if referenced object exists
		try
		{
			$referencedObject = $this->objectController->getObject($value, "yourCMDB backend");
			if($referencedObject->getType() == $objecttype)
			{
				return $value;
			}
			else
			{
				return "";
			}
		}
		catch(Exception $e)
		{
			return "";
		}
	}
}
?>
