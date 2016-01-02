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
namespace yourCMDB\rest;

use yourCMDB\controller\ObjectController;
use \Exception;

/**
* REST resource for a list of CMDB objects
*
* usage:
* /rest/objectlist/by-fieldvalue/<value>
* - GET 		/rest/objectlist/by-fieldvalue/<value>
* /rest/objectlist/by-objecttype/<type>
* - GET		/rest/objectlist/by-objecttype/<type>
*
* @author Michael Batz <michael@yourcmdb.org>
*/
class RestResourceObjectlist extends RestResource
{

	public function getResource()
	{
		$objectController = ObjectController::create();

		//try to get a list of objects
		try
		{
			$listtype = $this->uri[1];
			$searchvalue = $this->uri[2];
			switch($listtype)
			{
				case "by-fieldvalue":
					$objects = $objectController->getObjectsByFieldvalue(array($searchvalue), null, null, 0, 0, $this->user); 
					break;

				case "by-objecttype":
					$objects = $objectController->getObjectsByType(array($searchvalue), null, "ASC", null, 0, 0, $this->user);
					break;

				default:
					return new RestResponse(400);
					break;
			}

			//generate output
			$output = Array();
			foreach($objects as $object)
			{
				$output[] = $object->getId();
			}
		}
		catch(Exception $e)
		{
			return new RestResponse(404);
		}
		return new RestResponse(200, json_encode($output));
	}

	public function deleteResource()
	{
		return new RestResponse(405);
	}

	public function postResource($data)
	{
		return new RestResponse(405);
	}

	public function putResource($data)
	{
		return new RestResponse(405);
	}
}
?>
