<?php

declare(strict_types=1);

namespace Frontender\Platform\Model\Wordpress;

use Slim\Container;

class UsersModel extends AbstractModel
{
	public function __construct( Container $container ) {
		parent::__construct( $container );

		$this->getState()
			->insert('slug')
			->insert('roles')
			->insert('who');
	}

	public function getPopertyPosts(): array {
		$postsModel = $this->getModel('PostsModel');
		$postsModel->setState([
			'author' => [$this['id']]
		]);

		return $postsModel->fetch();
	}
}