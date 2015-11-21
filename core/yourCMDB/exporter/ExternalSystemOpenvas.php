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
namespace yourCMDB\exporter;

use yourCMDB\entities\CmdbObject;

/**
* Export API - External System: OpenVAS Targets and Tasks
* Creates targets and tasks for OpenVAS using the OpenVAS
* Manager Protocol (OMP)
* @author Michael Batz <michael@yourcmdb.org>
*/
class ExternalSystemOpenvas implements ExternalSystem
{
	//ExportVariables
	private $variables;

	//OMP Hostname
	private $ompHost;

	//OMP Port
	private $ompPort;
	
	//OMP Username
	private $ompUser;

	//OMP Password
	private $ompPassword;

	//prefix for OpenVAS target and task names
	private $namespacePrefix;

	//ID of the OpenVAS scanner
	private $scannerId;

	//ID of the OpenVAS scan config to use
	private $configId;

	//store for targets and tasks information
	private $openvasTasks;


	public function setUp(ExportDestination $destination, ExportVariables $variables)
	{
		//get variables
		$this->variables = $variables;

		//check, if parameters are set
		$parameterKeys = $destination->getParameterKeys();
		if(!(	in_array("ompHost", $parameterKeys) && 
			in_array("ompPort", $parameterKeys) && 
			in_array("ompUser", $parameterKeys) &&
			in_array("ompPassword", $parameterKeys) &&
			in_array("scannerId", $parameterKeys) &&
			in_array("configId", $parameterKeys)))
		{
			throw new ExportExternalSystemException("Parameters for ExternalSystem not set correctly");
		}

		//get parameters for OpenVAS access
		$this->ompHost = $destination->getParameterValue("ompHost");
		$this->ompPort = $destination->getParameterValue("ompPort");
		$this->ompUser = $destination->getParameterValue("ompUser");
		$this->ompPassword = $destination->getParameterValue("ompPassword");

		//setup namespace for OpenVAS targets and tasks
		$this->namespacePrefix = "yourCMDB_";
		if(in_array("namespacePrefix", $parameterKeys))
		{
			$this->namespacePrefix = $destination->getParameterValue("namespacePrefix");
		}

		//setup OpenVAS scannerID and configID
		$this->scannerId = $destination->getParameterValue("scannerId");
		$this->configId = $destination->getParameterValue("configId");
		

		//init store for OpenVAS tasks
		$this->openvasTasks = Array();
	}

	public function addObject(\yourCMDB\entities\CmdbObject $object)
	{
		//get taskname and ip
		$taskname = $this->variables->getVariable("taskname")->getValue($object);
		$taskname = $this->namespacePrefix . $taskname;
		if($taskname == "")
		{
			$taskname = "_empty_";
		}
		$ip = $this->variables->getVariable("ip")->getValue($object);

		//save information in OpenVAS tasks
		if(!isset($this->openvasTasks[$taskname]))
		{
			$this->openvasTasks[$taskname] = Array();
		}
		$this->openvasTasks[$taskname][] = $ip;
	}

	public function finishExport()
	{
		//open connection
		$ompConnection = fsockopen("tls://$this->ompHost", $this->ompPort);
		if(!$ompConnection)
		{
			throw new ExportExternalSystemException("Error connecting to host $this->ompHost on Port $ompPort");
		}

		//omp: authentication
		$result = $this->ompAuthenticate($ompConnection, $this->ompUser, $this->ompPassword);

		//omp: get all exististing OpenVAS tasks and targets in namespace
		$existingTargets = $this->ompGetTargets($ompConnection);
		$existingTasks = $this->ompGetTasks($ompConnection);

		//walk through all tasks for export
		foreach(array_keys($this->openvasTasks) as $taskName)
		{
			//if task exists
			if(isset($existingTasks[$taskName]))
			{
				//get data
				$existingTaskId = $existingTasks[$taskName]['id'];
				$existingTargetId = $existingTasks[$taskName]['targetId'];
				$existingTargetName = $existingTargets[$existingTargetId]['name'];
				$existingTargetHosts = $existingTargets[$existingTargetId]['hosts'];
				$createTargetHosts = $this->generateHostList($this->openvasTasks[$taskName]);

				//check, if hostlist has changed
				if($existingTargetHosts != $createTargetHosts)
				{
					//create a new target with new hostlist and a temporary name
					$createTargetId = $this->ompCreateTarget($ompConnection, $existingTargetName."_tmp", $createTargetHosts);

					//change task to use new target
					$this->ompUpdateTask($ompConnection, $existingTaskId, $createTargetId);

					//delete old target
					$this->ompDeleteTarget($ompConnection, $existingTargetId);

					//rename new target
					$this->ompUpdateTarget($ompConnection, $createTargetId, $existingTargetName);
					
				}

				//remove target and task from lists
				unset($existingTasks[$taskName]);
				unset($existingTargets[$existingTargetId]);
			}
			//if task does not exsist
			else
			{
				//create target
				$createTargetName = $taskName;
				$createTargetHosts = $this->generateHostList($this->openvasTasks[$taskName]);
				$createTargetId = $this->ompCreateTarget($ompConnection, $createTargetName, $createTargetHosts);

				//create task
				$this->ompCreateTask($ompConnection, $createTargetName, $createTargetId, $this->scannerId, $this->configId);
			}
		}

		//walk through all tasks that still exists in OpenVAS but not in yourCMDB export
		foreach($existingTasks as $existingTask)
		{
			//remove task
			$this->ompDeleteTask($ompConnection, $existingTask['id']);
		}
		//walk through all targets that still exists in OpenVAS but not in yourCMDB export
		foreach(array_keys($existingTargets) as $existingTargetId)
		{
			//remove target
			$this->ompDeleteTarget($ompConnection, $existingTargetId);
		}

		//close connection
		fclose($ompConnection);
	}

	/**
	* Generates a comma seperated host list from the input array
	* @param array $inputArray	array with hosts
	* @return string		comma seperated host list
	*/
	private function generateHostList($inputArray)
	{
		$output = "";
		for($i = 0; $i < count($inputArray); $i++)
		{
			$output .= $inputArray[$i];
			if($i != count($inputArray) - 1)
			{
				$output .= ",";
			}
		}
		return $output;
	}

	/**
	* send xml request on an existing connection and gets and returns 
	* the repsonse xml
	* @param resource $connection		open socket connection
	* @param string $request	XML request
	* @return string		XML response
	*/
	private function sendRequest($connection, $request)
	{
		//send request
		fwrite($connection, $request, strlen($request));
		fflush($connection);
	
		//get response
		$response = fread($connection, 8192);
		return $response;
	}

	/**
	* OMP helper: user authentication
	* authenticates the user with the given username and password
	* @param resource $connection		connection to OpenVAS server
	* @param string $username		OpenVAS user
	* @param string $password		OpenVAS password
	* @throws ExportExternalSystemException	if authentication failed
	*/
	private function ompAuthenticate($connection, $username, $password)
	{
		$requestXml = "<authenticate><credentials>";
		$requestXml.= "<username>$this->ompUser</username>";
		$requestXml.= "<password>$this->ompPassword</password>";
		$requestXml.= "</credentials></authenticate>";

		$responseXml = $this->sendRequest($connection, $requestXml);
		$responseObject = simplexml_load_string($responseXml);
		$authenticationStatus = $responseObject[0]['status'];
		if($authenticationStatus != 200)
		{
			throw new ExportExternalSystemException("OMP authentication error with username $this->ompUser");
		}
	}

	/**
	* OMP helper: get all existing targets with namespace prefix
	* @param resource $connection	connection to OpenVAS server
	* @return array			Array with targets
	* @throws ExportExternalSystemException	if there was an error
	*/
	private function ompGetTargets($connection)
	{
		//send request
		$requestXml = "<get_targets />";
		$responseXml = $this->sendRequest($connection, $requestXml);

		//check response
		$responseObject = simplexml_load_string($responseXml);
		$responseStatus = $responseObject[0]['status'];
		if($responseStatus != 200)
		{
			throw new ExportExternalSystemException("Error getting targets with OMP: $responseStatus");
		}

		//generate output data
		$targets = Array();
		foreach($responseObject->target as $target)
		{
			//get values from XML
			$targetId = (string) $target['id'];
			$targetName = (string) $target->name[0];
			$targetHosts = (string) $target->hosts[0];

			//check, if target name is in configured namespace
			if($this->namespacePrefix == "" || (strpos($targetName, $this->namespacePrefix) === 0))
			{
				//create array
				$targets[$targetId] = Array();
				$targets[$targetId]['name'] = $targetName;
				$targets[$targetId]['hosts'] = $targetHosts;
			}
		}

		//return output
		return $targets;
	}

	/**
	* OMP helper: get all existing tasks with namespace prefix
	* @param resource $connection	connection to OpenVAS server
	* @return array			Array with tasks
	* @throws ExportExternalSystemException	if there was an error
	*/
	private function ompGetTasks($connection)
	{
		//send request
		$requestXml = "<get_tasks />";
		$responseXml = $this->sendRequest($connection, $requestXml);

		//check response
		$responseObject = simplexml_load_string($responseXml);
		$responseStatus = $responseObject[0]['status'];
		if($responseStatus != 200)
		{
			throw new ExportExternalSystemException("Error getting tasks with OMP: $responseStatus");
		}

		//generate output data
		$tasks = Array();
		foreach($responseObject->task as $task)
		{
			//get values from XML
			$taskId = (string) $task['id'];
			$taskName = (string) $task->name[0];
			$taskTargetId = (string) $task->target[0]['id'];

			//check, if task name is in configured namespace
			if($this->namespacePrefix == "" || (strpos($taskName, $this->namespacePrefix) === 0))
			{
				//create array
				$tasks[$taskName] = Array();
				$tasks[$taskName]['id'] = $taskId;
				$tasks[$taskName]['targetId'] = $taskTargetId;
			}
		}

		//return output
		return $tasks;
	}

	/**
	* OMP helper: create an OpenVAS target
	* @param resource $connection	connection to OpenVAS server
	* @param string $name		target name
	* @param string $hosts		target host list
	* @return string		ID of the created target
	* @throws ExportExternalSystemException	if there was an error
	*/
	private function ompCreateTarget($connection, $name, $hosts)
	{
		//send request
		$requestXml = "<create_target>";
		$requestXml.= "<name>$name</name>";
		$requestXml.= "<hosts>$hosts</hosts>";
		$requestXml.= "</create_target>";
		$responseXml = $this->sendRequest($connection, $requestXml);

		//check response
		$responseObject = simplexml_load_string($responseXml);
		$responseStatus = $responseObject[0]['status'];
		if($responseStatus > 202)
		{
			throw new ExportExternalSystemException("Error creating target with OMP: $responseStatus");
		}
		$responseId = $responseObject[0]['id'];

		//return ID of created target
		return $responseId;
	}

	/**
	* OMP helper: create an OpenVAS task
	* @param resource $connection	connection to OpenVAS server
	* @param string $name		task name
	* @param string $targetId	target ID
	* @param string $scannerId	scanner ID
	* @param string $configId	config ID
	* @return string		ID of the created task
	* @throws ExportExternalSystemException	if there was an error
	*/
	private function ompCreateTask($connection, $name, $targetId, $scannerId, $configId)
	{
		//send request
		$requestXml = "<create_task>";
		$requestXml.= "<name>$name</name>";
		$requestXml.= "<config id=\"$configId\" />";
		$requestXml.= "<target id=\"$targetId\" />";
		$requestXml.= "<scanner id=\"$scannerId\" />";
		$requestXml.= "</create_task>";
		$responseXml = $this->sendRequest($connection, $requestXml);

		//check response
		$responseObject = simplexml_load_string($responseXml);
		$responseStatus = $responseObject[0]['status'];
		if($responseStatus > 202)
		{
			throw new ExportExternalSystemException("Error creating task with OMP: $responseStatus");
		}
		$responseId = $responseObject[0]['id'];

		//return ID of created task
		return $responseId;
	}

	/**
	* OMP helper: delete an OpenVAS task
	* @param resource $connection	connection to OpenVAS server
	* @param string $id		task ID
	* @throws ExportExternalSystemException	if there was an error
	*/
	private function ompDeleteTask($connection, $id)
	{
		//send request
		$requestXml = "<delete_task task_id=\"$id\" ultimate=\"true\" />";
		$responseXml = $this->sendRequest($connection, $requestXml);

		//check response
		$responseObject = simplexml_load_string($responseXml);
		$responseStatus = $responseObject[0]['status'];
		if($responseStatus > 202)
		{
			throw new ExportExternalSystemException("Error deleting task with OMP: $responseStatus");
		}

	}


	/**
	* OMP helper: delete an OpenVAS target
	* @param resource $connection	connection to OpenVAS server
	* @param string $id		target ID
	* @throws ExportExternalSystemException	if there was an error
	*/
	private function ompDeleteTarget($connection, $id)
	{
		//send request
		$requestXml = "<delete_target target_id=\"$id\" ultimate=\"true\" />";
		$responseXml = $this->sendRequest($connection, $requestXml);

		//check response
		$responseObject = simplexml_load_string($responseXml);
		$responseStatus = $responseObject[0]['status'];
		if($responseStatus > 202)
		{
			throw new ExportExternalSystemException("Error deleting target with OMP: $responseStatus");
		}

	}

	/**
	* OMP helper: modify an OpenVAS task
	* @param resource $connection	connection to OpenVAS server
	* @param string $id		task ID
	* @param string $targetId	updated targetId
	* @throws ExportExternalSystemException	if there was an error
	*/
	private function ompUpdateTask($connection, $id, $targetId)
	{
		//send request
		$requestXml = "<modify_task task_id=\"$id\">";
		$requestXml.= "<target id=\"$targetId\" />";
		$requestXml.= "</modify_task>";
		$responseXml = $this->sendRequest($connection, $requestXml);

		//check response
		$responseObject = simplexml_load_string($responseXml);
		$responseStatus = $responseObject[0]['status'];
		if($responseStatus > 202)
		{
			throw new ExportExternalSystemException("Error updating task with OMP: $responseStatus");
		}
	}

	/**
	* OMP helper: modify an OpenVAS target
	* @param resource $connection	connection to OpenVAS server
	* @param string $id		target ID
	* @param string $name		updated name
	* @throws ExportExternalSystemException	if there was an error
	*/
	private function ompUpdateTarget($connection, $id, $name)
	{
		//send request
		$requestXml = "<modify_target target_id=\"$id\">";
		$requestXml.= "<name>$name</name>";
		$requestXml.= "</modify_target>";
		$responseXml = $this->sendRequest($connection, $requestXml);

		//check response
		$responseObject = simplexml_load_string($responseXml);
		$responseStatus = $responseObject[0]['status'];
		if($responseStatus > 202)
		{
			throw new ExportExternalSystemException("Error updating target name with OMP: $responseStatus");
		}
	}

}
?>
