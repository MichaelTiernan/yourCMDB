<?php
/********************************************************************
* This file is part of yourCMDB.
*
* Copyright 2013-2015 Michael Batz
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
* REST resource for a CMDB object log
*
* usage:
* /rest.php/objectlogs/<assetId>
* - GET 	/rest.php/objectlogs/<assetId>
*
* @author Michael Batz <michael@yourcmdb.org>
*/
class RestResourceObjectLog extends RestResource
{

	public function getResource()
	{
		//try to get object log
		try
		{
			$objectId = $this->uri[1];
			$objectlog = $this->datastore->getObjectLog($objectId);

			//generate output
			$output = Array();
			$output['objectId'] = $objectId;
			$output['logEntries'] = Array();
			foreach($objectlog->getLogEntries() as $logEntry)
			{
				$output['logEntries'][] = Array(
								'date'	 => $logEntry->getDate(),
								'action' => $logEntry->getAction()
								);
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
