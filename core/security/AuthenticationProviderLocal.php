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
* Authentication provider for local user management
* userdata are stored in local yourCMDB database
* @author Michael Batz <michael@yourcmdb.org>
*/
class AuthenticationProviderLocal implements AuthenticationProvider
{

	function __construct($parameters)
	{
		//no parameters needed - so doing nothing here
		;
	}

	public function authenticate($username, $password)
	{
		$config = new CmdbConfig();
		$datastoreClass = $config->getDatastoreConfig()->getClass();
		$datastore = new $datastoreClass;

		$userobject = $datastore->getUser($username);
		$passwordHash = $this->createHash($username, $password);
		if($userobject != null && $userobject->getPasswordHash() == $passwordHash)
		{
			return true;
		}
		return false;
	}

	public function getAccessGroup($username)
	{
		$config = new CmdbConfig();
		$datastoreClass = $config->getDatastoreConfig()->getClass();
		$datastore = new $datastoreClass;

		$userobject = $datastore->getUser($username);
		if($userobject == null)
		{
			return null;
		}
		return $userobject->getAccessGroup();
	}

	public function addUser($username, $password, $accessgroup)
	{
		//check if username and password is a valid value
		if($username == "" || $password == "")
		{
			throw new SecurityChangeUserException("Inavlid username or password");
		}
		$config = new CmdbConfig();
		$datastoreClass = $config->getDatastoreConfig()->getClass();
		$datastore = new $datastoreClass;

		$passwordHash = $this->createHash($username, $password);
		return $datastore->addUser(new CmdbLocalUser($username, $passwordHash, $accessgroup));
	}

	public function getUsers()
	{
		$config = new CmdbConfig();
		$datastoreClass = $config->getDatastoreConfig()->getClass();
		$datastore = new $datastoreClass;

		return $datastore->getUsers();
	}

	public function deleteUser($username)
	{
		$config = new CmdbConfig();
		$datastoreClass = $config->getDatastoreConfig()->getClass();
		$datastore = new $datastoreClass;

		return $datastore->deleteUser($username);
	}

	public function resetPassword($username, $newPassword)
	{
		//check if username and password is a valid value
		if($newPassword == "")
		{
			throw new SecurityChangeUserException("Inavlid username or password");
		}

		$config = new CmdbConfig();
		$datastoreClass = $config->getDatastoreConfig()->getClass();
		$datastore = new $datastoreClass;

		//create new user object
		$newAccessGroup = $this->getAccessGroup($username);
		$newPasswordHash = $this->createHash($username, $newPassword);
		$newUserObject = new CmdbLocalUser($username, $newPasswordHash, $newAccessGroup);

		//change user in datastore
		return $datastore->changeUser($username, $newUserObject);
	}

	public function setAccessGroup($username, $newAccessGroup)
	{
		$config = new CmdbConfig();
		$datastoreClass = $config->getDatastoreConfig()->getClass();
		$datastore = new $datastoreClass;

		//create new user object
		$user = $datastore->getUser($username);
		if($user == null)
		{
			return false;
		}
		$newPasswordHash = $user->getPasswordHash();
		$newUserObject = new CmdbLocalUser($username, $newPasswordHash, $newAccessGroup);

		//change user in datastore
		return $datastore->changeUser($username, $newUserObject);
	}


	private function createHash($username, $password)
	{
		$passwordHash = hash("sha256", "yourcmdb".$username.$password);
		return $passwordHash;
	}
}
?>
