<?php

namespace Frontender\Platform\Model\Wordpress\Traits;

use Slim\Container;

trait Sortable {
	public function __construct(Container $container) {
		parent::__construct($container);

		$this->getState()
			->insert('sorting');
	}

	public function setState(array $values) {
		if(isset($values['sorting']) && !empty($values['sorting'])) {
			$sorting = explode(',', $values['sorting']);

			$values['orderby'] = $sorting[0];
			$values['order'] = $sorting[1];
		}

		parent::setState($values);
	}
}