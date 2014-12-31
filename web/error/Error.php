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
* WebUI element: error page
* @author Michael Batz <michael@yourcmdb.org>
*/

	//print messagebar
	include "include/messagebar.inc.php";

	echo "<h1>";
	echo gettext("yourCMDB Error");
	echo "</h1>";

	echo "<p>";
	echo gettext("The error above should not be happened. Maybe you use a wrong URL or you found a bug.");
	echo "<br />";
	echo sprintf(gettext("Please check your setup or ask for help on the %s yourCMDB Website %s."), "<a href=\"http://www.yourcmdb.org\">", "</a>");
	echo "</p>";
?>
