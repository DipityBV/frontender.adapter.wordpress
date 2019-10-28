<?php

namespace Prototype\Model\WordPress;

use Slim\Container;
use Frontender\Core\Model\AbstractModel as FrontenderAbstractModel;

abstract class AbstractModel extends FrontenderAbstractModel
{
    protected $name;

    public function __construct(Container $container)
    {
        parent::__construct($container);

        \Crew\WordPress\HttpClient::init([
            'applicationId'    => $container->config->wordpress_token,
            'secret'        => $container->config->wordpress_secret,
            'utmSource' => 'Frontender'
        ]);
    }
}