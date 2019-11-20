<?php

declare(strict_types=1);

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
			->insert('sticky')
			->insert('author');
	}

	public function getPropertyFeaturedImage() {
		if(!$this['featured_media']) {
			return false;
		}

		$mediaModel = $this->getModel('MediaModel');
		$mediaModel->setState([
			'id' => $this['featured_media']
		]);
		$media = $mediaModel->fetch();
		$medium = array_shift($media);

		if(!$medium) {
			return false;
		}

		return $medium;
	}
}