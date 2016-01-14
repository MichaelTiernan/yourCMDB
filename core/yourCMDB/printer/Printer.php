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
namespace yourCMDB\printer;

/**
* Printer for yourCMDB
* @author Michael Batz <michael@yourcmdb.org>
*/
abstract class Printer
{
	//Options for the printer to use
	protected $printerOptions;

	/**
	* Creates a new printer object using the given printer options
	*/
	public function __construct(\yourCMDB\printer\PrinterOptions $printerOptions)
	{
		$this->printerOptions = $printerOptions;
	}

	/**
	* Sends the given data to the printer for printing
	* @param string $data		data for printing
	* @param string $contentType	ContentType of the data. e.g. "application/pdf"
	*/
	public abstract function printData($data, $contentType);
}
?>
