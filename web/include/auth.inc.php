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
* WebUI element: authenication with user session
*/

	session_start();
	$authProvider = $controller->getAuthProvider("web");
	$authAuthenticated = false;
	$authUser = "";
	$authAccessgroup = "";
	if(isset($_SESSION['authAuthenticated'], $_SESSION['authUser'], $_SESSION['authAccessgroup']) && $_SESSION['authAuthenticated'] == true)
	{
		//if user is authenticated, set session vars
		$authAuthenticated = true;
		$authUser = $_SESSION['authUser'];
		$authAccessgroup = $_SESSION['authAccessgroup'];
	}
	else
	{
		//if user is not authenticated, try to get authentication data from HTTP POST Vars (authUser, authPassword)
		$authUser = getHttpPostVar("authUser", "");
		$authPassword = getHttpPostVar("authPassword", "");
	
		//authentication function
		if($authProvider->authenticate($authUser,$authPassword))
		{
			$authAuthenticated = true;
			$_SESSION['authAuthenticated'] = true;
			$_SESSION['authUser'] = $authUser;
			$_SESSION['authAccessgroup'] = $authProvider->getAccessGroup($authUser);
		}
	
	}
	
	//check authentication
	if(!$authAuthenticated)
	{
		//get baseUrl from config
		$baseUrl = $config->getViewConfig()->getBaseUrl();
	
		header("Location: $baseUrl/login.php");
		exit();
	}
?>
