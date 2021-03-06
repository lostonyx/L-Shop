<?php
declare(strict_types=1);

namespace App\Handlers\Consoe\User\Roles;

use App\Exceptions\Permission\PermissionNotFoundException;
use App\Exceptions\User\RoleAlreadyAttachedException;
use App\Exceptions\User\UserNotFoundException;
use App\Repository\Role\RoleRepository;
use App\Repository\User\UserRepository;

class AttachHandler
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var RoleRepository
     */
    private $roleRepository;

    public function __construct(UserRepository $userRepository, RoleRepository $roleRepository)
    {
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
    }

    /**
     * @param string $username User who needs to assign a roles. User is identified by username.
     * @param array      $roles    Roles that will be attached to the user. Roles are identified by name.
     *
     * @throws UserNotFoundException
     * @throws PermissionNotFoundException
     * @throws RoleAlreadyAttachedException
     */
    public function handle(string $username, array $roles)
    {
        $user = $this->userRepository->findByUsername($username);

        if ($user === null) {
            throw UserNotFoundException::byUsername($username);
        }

        $rs = $this->roleRepository->findWhereNameIn($roles);
        // Checking that all the passed roles really exist.
        foreach ($roles as $role) {
            $f = true;
            foreach ($rs as $each) {
                if ($each->getName() === $role) {
                    $f = false;
                    break;
                }

            }

            if ($f) {
                throw PermissionNotFoundException::byName($role);
            }
        }

        foreach ($rs as $role) {
            if ($user->hasRole($role)) {
                throw RoleAlreadyAttachedException::withName($role->getName());
            }

            $user->getRoles()->add($role);
            $role->getUsers()->add($user);
        }

        $this->userRepository->update($user);
    }
}
