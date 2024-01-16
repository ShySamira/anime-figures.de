<?php

namespace App\Service;

use Symfony\Component\Security\Core\Security;

class User
{
    private $security;

    public function __construct(Security $security)
    {

        $this->security = $security;
    }

    public function getIdentifier()
    {
            if($user = $this->security->getUser()){
                $identifier = $user->getUuid();
            }else{
                $identifier = session_id();
            }

            return $identifier;
    }
}

