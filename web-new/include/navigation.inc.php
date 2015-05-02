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
* yourCMDB navigation
* @author Michael Batz <michael@yourcmdb.org>
*/
	//get data
	$menuitems = $config->getViewConfig()->getMenuItems();
	$objectGroups = $config->getObjectTypeConfig()->getObjectTypeGroups();
	//ToDo: enable admin
	//$authorisationProvider = $controller->getAuthorisationProvider("web");

	//navbar header
	echo "<nav class=\"navbar cmdb-navigation\">";
	//mobile view
	echo "<div class=\"navbar-header\">";
	echo "<button type=\"button\" class=\"navbar-toggle collapsed\" data-toggle=\"collapse\" data-target=\"#bs-example-navbar-collapse-1\">";
        echo "<span class=\"sr-only\">Toggle navigation</span>";
        echo "<span class=\"icon-bar\"></span>";
        echo "<span class=\"icon-bar\"></span>";
        echo "<span class=\"icon-bar\"></span>";
	echo "</button>";
	echo "<h1>yourCMDB</h1>";
	echo "</div>";
	echo "<div class=\"collapse navbar-collapse\" id=\"bs-example-navbar-collapse-1\">";
	echo "<ul class=\"nav navbar-nav\">";

	//menu entries
	echo "<li><a href=\"index.php\">".gettext("Home")."</a></li>";
	echo "<li><a href=\"search.php\">".gettext("Search")."</a></li>";
	echo "<li><a href=\"object.php?action=new\">".gettext("New Object")."</a></li>";
	echo "<li><a href=\"import.php\">".gettext("Import-Export")."</a></li>";
	//object dropdown
	echo "<li class=\"dropdown\"><a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\" role=\"button\" aria-expanded=\"false\">".gettext("Objects")."</a>";
	echo "<ul class=\"dropdown-menu\">";
	//walk through all object type groups
	foreach(array_keys($objectGroups) as $groupname)
	{
		echo "<li><a href=\"#\">$groupname</a><ul class=\"dropdown-menu\">";
		foreach($objectGroups[$groupname] as $objectType)
		{
			echo "<li>";
			echo "<a  href=\"object.php?action=list&amp;type=$objectType\">";
			echo "$objectType (".$objectController->getObjectCounts($objectType, $username).")";
			echo "</a>";
			echo "</li>";
		}
		echo "</ul></li>";
	}
	echo "</ul>";
	echo "</li>";
	//optional: admin menu
	//if(isset($authAccessgroup) && $authorisationProvider->authorise($authAccessgroup, "admin") != 0)
	//{
	//	echo "<li><a href=\"admin.php\">".gettext("Admin")."</a></li>";
	//}
	//add additional menu items from configuration
	foreach(array_keys($menuitems) as $itemName)
	{
		echo "<li>";
		echo "<a  href=\"{$menuitems[$itemName]}\">";
		echo gettext($itemName);
		echo "</a>";
		echo "</li>";
	}

	//footer
	echo "</ul>";
	echo "</div>";
	echo "</nav>";
?>
