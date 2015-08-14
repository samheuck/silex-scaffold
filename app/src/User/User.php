<?php

namespace App\User;

use Symfony\Component\Security\Core\User\AdvancedUserInterface;

class User implements AdvancedUserInterface, \Serializable
{
	private $username;
	private $password;
	private $roles;
	private $enabled;
	private $dateCreated;

	public function __construct($username = null, $roles = array(), $enabled = true) {
		$this->username = $username;
		$this->roles = $roles;
		$this->enabled = $enabled;
	}

	public function __set($property, $value)
	{
		if (!property_exists($this, $property)) {
			throw new Exception(sprintf('Property %s does not exist.', $property));
		}

		$method = sprintf("set%s", ucfirst($property));
		$this->$method($value);
	}

	public function __get($property)
	{
		if (!property_exists($this, $property)) {
			throw new Exception(sprintf('Property %s does not exist.', $property));
		}

		$method = sprintf("get%s", ucfirst($property));
		return $this->$method();
	}

	public function setUsername($username) 
	{
		$this->username = $username;
	}

	public function getUsername() 
	{
		return $this->username;
	}

	public function setPassword($password)
	{
		$this->password = $password;
	}

	public function getPassword()
	{
		return $this->password;
	}

	public function setRoles(array $roles)
	{
		if (!in_array('ROLL_USER', $roles)) {
			array_unshift($roles, 'ROLE_USER');
		}

		$this->roles = array_unique($roles);
	}

	public function getRoles()
	{
		return $this->roles;
	}

	public function setEnabled($enabled)
	{
		$this->enabled = $enabled;
	}

	public function isEnabled()
	{
		return $this->enabled;
	}

	public function setDateCreated(\DateTime $date)
	{
		$this->dateCreated = $date;
	}

	public function getDateCreated()
	{
		return $this->dateCreated;
	}

	public function getSalt()
	{
		return null;
	}

	public function eraseCredentials()
	{}

	public function isAccountNonExpired()
	{
		return true;
	}

	public function isAccountNonLocked()
	{
		return true;
	}

	public function isCredentialsNonExpired()
	{
		return true;
	}

	public function serialize()
	{
		return serialize([
			$this->username,
			$this->password,
		]);
	}

	public function unserialize($me)
	{
		list (
			$this->username,
			$this->password
		) = unserialize($me);
	}

	public function __toString() 
	{
		return sprintf(
			"user: %s\nroles: %s\ncreated: %s", 
			$this->username, 
			implode(',', $this->roles),
			$this->dateCreated->format('r')
		);
	}
}
