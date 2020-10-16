<?php

namespace App\Serializer;

use App\Entity\UserManagement\User;

/**
 * UserSerializer
 *
 * @author Free
 */
class UserSerializer
{
    /**
     * @param User $user
     *
     * @return array
     */
    public function serialize(User $user)
    {
        return [
            'id' => $user->getId(),
            'name' => $user->getFirstName() . ' ' . $user->getLastName(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
        ];
    }
}
