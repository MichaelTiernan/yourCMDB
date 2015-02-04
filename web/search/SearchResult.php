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
* WebUI element: search results
* loaded using AJAX call
* @author Michael Batz <michael@yourcmdb.org>
*/

	//include base functions
	include "../include/base.inc.php";
	include "../include/auth.inc.php";
	include "SearchFunctions.php";

	//get all searched objects
	$objects = null;
	if(count($searchstrings) > 0)
	{
		if($paramTypeGroup != "")
		{
			$searchTypes = $objectTypes[$paramTypeGroup];
			$objects = $datastore->getObjectsByFieldvalue($searchstrings, $searchTypes, $paramActiveOnly);
		}
		else if($paramType != "")
		{
			$objects = $datastore->getObjectsByFieldvalue($searchstrings, array($paramType), $paramActiveOnly);
		}
		else
		{
			$objects = $datastore->getObjectsByFieldvalue($searchstrings, null, $paramActiveOnly);
		}
	}

	//calculate list view
        $objectCount = count($objects);
        $listPage = $paramPage;
        $listPages = floor((($objectCount - 1) / $paramMax) + 1);
        if($listPages < 1)
        {
                $listPages = 1;
        }
        //check, if $listPage makes sense
        if($listPage > $listPages)
        {
                $listPage = $listPages;
        }
        if($listPage < 1)
        {
                $listPage = 1;
        }
        //calculate start and end
        $listStart = ($listPage - 1) * $paramMax;
        $listEnd = $listStart + $paramMax -1;
        if($listEnd >= $objectCount)
        {
                $listEnd = $objectCount - 1;
        }


	//urls
	$listnavUrlBase = "search/SearchResult.php?typegroup=".urlencode($paramTypeGroup)."&amp;type=".urlencode($paramType)."&amp;max=".urlencode($paramMax)."&amp;activeonly=".urlencode($paramActiveOnly);
	$listnavUrlBase .= "&amp;searchstring=".urlencode($paramSearchString);
	$listnavUrlBase .= "&amp;page=";


	//<!-- title -->
	echo "<h1>";
	echo sprintf(gettext("Search Results (%s)"), $objectCount);
	echo "</h1>";
	

	echo "<table class=\"list\">";
	//print found objects
	if($objects != null)
	{
		//print object summary
		for($i = $listStart; $i <= $listEnd; $i++)
		{
			//get all data
			$objectType = $objects[$i]->getType();
			$objectId = $objects[$i]->getId();
			$objectStatus = $objects[$i]->getStatus();
			$objectFields = $objects[$i]->getFieldNames();
			$objectSummaryFields = $config->getObjectTypeConfig()->getSummaryFields($objectType);
			//get fields that matched to search string
			$objectMatchFields = Array();
			foreach($objectFields as $fieldname)
			{
				foreach($searchstrings as $searchstring)
				{
					if(stristr($objects[$i]->getFieldValue($fieldname), $searchstring) !== FALSE)
					{
						$objectMatchFields[] = $fieldname;
						break;
					}
				}
			}
		
			//get status image
			$statusIcon = "<img src=\"img/icon_active.png\" alt=\"".gettext("active")."\" title=\"".gettext("active object")."\" />";
			if($objectStatus != 'A')
			{
				$statusIcon = "<img src=\"img/icon_inactive.png\" alt=\"".gettext("inactive")."\" title=\"".gettext("inactive object")."\" />";
			}

			//print headline
			echo "<tr><td>";
			echo "<p><a href=\"object.php?action=show&amp;id=$objectId\">";
			echo "$statusIcon $objectType: $objectId</a><br />";

			//print matches
			echo gettext("Matches: ");
			for($j = 0; $j < count($objectMatchFields); $j++)
			{
				$fieldname = $objectMatchFields[$j];
				$fieldlabel = $config->getObjectTypeConfig()->getFieldLabel($objectType, $fieldname);
				$fieldvalue = $objects[$i]->getFieldValue($fieldname);
				//mark search string in fieldvalues (use case insensitive match)
				foreach($searchstrings as $searchstring)
				{
					if(preg_match("/.*?((?i:$searchstring)).*?/", $fieldvalue, $matchSearchString) == 1)
					{
						$fieldvalue = str_replace($matchSearchString[1], "<em>$matchSearchString[1]</em>", $fieldvalue);
					}
				}
				echo "$fieldlabel: $fieldvalue";
				if($j < count($objectMatchFields) - 1)
				{
					echo " | ";
				}
			}
			echo "<br />";

			//print object summary
			echo gettext("Summary: ");
			$fieldnames = array_keys($objectSummaryFields);
			for($j = 0; $j < count($fieldnames); $j++)
			{
				$fieldname = $fieldnames[$j];
				$fieldlabel = $config->getObjectTypeConfig()->getFieldLabel($objectType, $fieldname);
				$fieldvalue = $objects[$i]->getFieldValue($fieldname);
				echo "$fieldlabel: $fieldvalue";
				if($j < count($fieldnames) - 1)
				{
					echo " | ";
				}
			}
			echo "</p></td></tr>";
		}
		echo "</table>";

		//<!-- list navigation  -->
		echo "<p class=\"listnav\">";
		//print prev button
		if($listPage != 1)
		{
			$listnavUrl = $listnavUrlBase .($listPage - 1);
			echo "<a href=\"javascript:openUrlAjax('$listnavUrl', '#searchbarResult', true, true)\">&lt; ";
			echo gettext("previous");
			echo "</a>";
		}
		else
		{
			echo "<a href=\"#\" class=\"disabled\">&lt; ";
			echo gettext("previous");
			echo "</a>";
		}
		//print page numbers
		for($i = 1; $i <= $listPages; $i++)
		{
			$listnavUrl = $listnavUrlBase .$i;
			if($i == $listPage)
			{
				echo "<a href=\"javascript:openUrlAjax('$listnavUrl', '#searchbarResult', true, true)\" class=\"active\">$i</a>";
			}
			else
			{
				echo "<a href=\"javascript:openUrlAjax('$listnavUrl', '#searchbarResult', true, true)\">$i</a>";
			}

			//jump to current page
			if($i == 3 && $listPage > 5)
			{
				$i = $listPage - 2;
				echo "...";
			}
			//jump to last page
			if($i > 3 && $i > $listPage && $i < ($listPages - 2))
			{
				$i = $listPages - 2;
				echo "...";
			}
		}
		//print next button
		if($listPage != $listPages)
		{
			$listnavUrl = $listnavUrlBase .($listPage + 1);
			echo "<a href=\"javascript:openUrlAjax('$listnavUrl', '#searchbarResult', true, true)\">";
			echo gettext("next");
			echo " &gt;</a>";
		}
		else
		{
			echo "<a href=\"#\" class=\"disabled\">";
			echo gettext("next");
			echo " &gt;</a>";
		}
		echo "</p>";

	}
	else
	{
		echo "<p>";
		echo gettext("No objects found for searchstring ");
		echo "<i>$paramSearchString</i>";
		echo "</p>";
	}



?>
