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
* Class for access to exporter configuration
* @author Michael Batz <michael@yourcmdb.org>
*/
class ExporterConfig
{

	//exporter tasks
	private $tasks;

	//exporter sources
	private $sources;

	//exporter destinations
	private $destinations;


	/**
	* creates a ExporterConfig object from xml file exporter-configuration.xml
	*/
	public function __construct($xmlfile)
	{
		$xmlobject = simplexml_load_file($xmlfile);

		//initialise arrays
		$this->tasks = Array();
		$this->sources = Array();
		$this->destinations = Array();

		foreach($xmlobject->xpath('//task')  as $task)
		{
			//save taskname
			$taskname = (string)$task['name'];
			$this->tasks[] = $taskname;
			
			//get sources
			foreach($task[0]->sources->source as $source)
			{
				$sourceType = (string)$source['objecttype'];
				$sourceStatus = (string)$source['status'];
				$sourceFieldname = (string)$source['fieldname'];
				$sourceFieldvalue = (string)$source['fieldvalue'];

				//generate and save new ExportSource object
				$this->sources[$taskname][] = new ExportSource($sourceType, $sourceStatus, $sourceFieldname, $sourceFieldvalue);

			}

			//get destination
			$destination = $task[0]->destination[0];
			$destinationClass = (string)$destination['class'];
			$destinationParameter = Array();
			foreach($destination->parameter as $parameter)
			{
				$key = (string)$parameter['key'];
				$value = (string)$parameter['value'];
				$destinationParameter[$key] = $value;
			}
			$this->destinations[$taskname] = new ExportDestination($destinationClass, $destinationParameter);
		}
	}

	/**
	* Returns an array with all export tasks
	*/
	public function getTasks()
	{
		return $this->tasks;
	}

	/**
	* Returns the an array with all sources for an export task
	* @param $taskname	Name of the export task
	*/
	public function getSourcesForTask($taskname)
	{
		return $this->sources[$taskname];
	}

	/**
	* Returns an export destination for an export task
	* @param $taskname	Name of the export task
	*/
	public function getDestinationForTask($taskname)
	{
		return $this->destinations[$taskname];
	}
}

?>
