<?php

namespace Frontender\Platform\Model\Wordpress;

use Slim\Container;

class MediaModel extends AbstractModel
{
	public function __construct( Container $container ) {
		parent::__construct( $container );

		$this->getState()
			->insert('status')
			->insert('media_type')
			->insert('mime_type');
	}
}