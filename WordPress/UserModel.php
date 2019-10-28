<?php

namespace Frontender\Platform\Model\WordPress;

use Slim\Container;

class UserModel extends AbstractModel
{
    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->getState()
            ->insert('id', null, true);
    }

    public function fetch()
    {
        
    }
}