<?php
namespace App\User;

use App\Application;
use App\User\User;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

class UserProvider implements UserProviderInterface
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function loadUserByUsername($username)
    {
        $query = $this->app['db']->executeQuery('SELECT * FROM users WHERE username = ?', array(strtolower($username)));

        if (!$userData = $query->fetch()) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
        }

        $user = new User();
        $user->username = $userData['username'];
        $user->password = $userData['password'];
        $user->roles = explode(',', $userData['roles']);
        $user->enabled = (bool) $userData['enabled'];
        $user->dateCreated = date_create($userData['date_created']);

        return $user;
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return $class === 'App\User\User';
    }
}