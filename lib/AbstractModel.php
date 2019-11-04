<?php

namespace Frontender\Platform\Model\Wordpress;

use Doctrine\Common\Inflector\Inflector;
use GuzzleHttp\Client;
use Slim\Container;

class AbstractModel extends \Frontender\Core\Model\AbstractModel
{
	protected $_client;
	protected $_config;

	public function __construct( Container $container ) {
		parent::__construct( $container );

		$this->_config = $container->config;

		$this->getState()
			->insert('id')
			->insert('limit')
			->insert('offset')
			->insert('search')
			->insert('before')
			->insert('after')
			->insert('author')
			->insert('exclude')
			->insert('include')
			->insert('order')
			->insert('orderby')
			->insert('slug');
	}

	/**
	 * This method will return the configured guzzle client.
	 *
	 * @return Client The configured guzzle client.
	 */
	protected function getClient(): Client
	{
		if(!$this->_client) {
			$config = $this->getConfig();
			$this->_client = new Client([
				'base_uri' => $config['url'] . '/wp-json/wp/v2/',
				'auth' => [$config['username'], $config['password']]
			]);
		}

		return $this->_client;
	}

	/**
	 * This method will return the config belonging to the current wordpress installation.
	 * The config is retrieved based on namespace.
	 *
	 * This allows us to specify other config for custom installations and a default installation.
	 *
	 * @return array The config for the current adapter installation
	 */
	protected function getConfig(): array
	{
		$class = get_class($this);
		$class = str_replace('Frontender\\Platform\\Model\\', '', $class);
		$parts = explode('\\', $class);
		$namespace = strtolower(array_shift($parts));

		if($namespace === 'wordpress') {
			$namespace = 'default';
		}

		return [
			'username' => $this->_config->{'wordpress_' . $namespace . '_username'},
			'password' => $this->_config->{'wordpress_' . $namespace . '_password'},
			'url' => $this->_config->{'wordpress_' . $namespace . '_url'}
		];
	}

	/**
	 * This method will always return an array.
	 * If an ID is given it will also return an array of 1.
	 *
	 * @return array The fetched items.
	 */
	public function fetch(): array {
		$response = $this->getClient()->get(
			$this->getEndpoint(),
			$this->getRequestOptions()
		);
		$content = $response->getBody()->getContents();
		$result = json_decode($content, true);

		// If we have an ID in the request we have a single
		if($this->getState()->id) {
			$itemModel = new $this($this->container);
			$itemModel->setData($result);
			$itemModel->setState([
				'id' => $result['id']
			]);

			return [$itemModel];
		} else {
			return array_map(function($item) {
				$itemModel = new $this($this->container);
				$itemModel->setData($item);
				$itemModel->setState([
					'id' => $item['id']
				]);

				return $itemModel;
			}, $result);
		}
	}

	/**
	 * This method will get all the request options for Guzzle.
	 * If we have set an ID then we know which item we want and no other options are needed (unless overwritten).
	 *
	 * @return array Guzzle request options.
	 */
	protected function getRequestOptions(): array {
		$query = $this->getState()->getValues();

		if(isset($query['id'])) {
			unset($query['id']);
		}

		// Map the limit to per_page as is used in the API of WordPress.
		if(isset($query['limit'])) {
			$query['per_page'] = $query['limit'];
			unset($query['limit']);
		}

		return $query;
	}

	/**
	 * This method will return the url to be called.
	 * This will be based on the model name concatenated with the ID (if present).
	 *
	 * @return string
	 */
	protected function getEndpoint(): string {
		$class = explode('\\', get_class($this));
		$modelName = array_pop($class);
		$modelName = strtolower(str_replace('Model', '', $modelName));

		if($modelName === 'abstract') {
			return '';
		}

		$state = $this->getState()->getValues();
		if(isset($state['id']) && !empty($state['id'])) {
			$modelName .= '/' . $state['id'];
		}

		return $modelName;
	}
}