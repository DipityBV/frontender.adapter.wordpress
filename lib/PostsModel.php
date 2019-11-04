<?php

namespace Frontender\Platform\Model\Wordpress;

use Slim\Container;

class PostsModel extends AbstractModel
{
	public function __construct( Container $container ) {
		parent::__construct( $container );

		$this->getState()
			->insert('status')
			->insert('categories')
			->insert('tags')
			->insert('sticky');
	}
}