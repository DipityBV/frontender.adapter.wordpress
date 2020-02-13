<?php

declare(strict_types=1);

namespace Frontender\Platform\Model\Wordpress;

use Doctrine\Common\Inflector\Inflector;
use Frontender\Platform\Model\Wordpress\Traits\Sortable;
use GuzzleHttp\Client;
use Slim\Container;

class AbstractModel extends \Frontender\Core\Model\AbstractModel {
	protected $_client;
	protected $_config;

	use Sortable;

	public function __construct( Container $container ) {
		parent::__construct( $container );

		$this->_config = $container->config;

		$this->getState()
		     ->insert( 'id' )
		     ->insert( 'limit', 20 )
		     ->insert( 'offset' )
		     ->insert( 'search' )
		     ->insert( 'before' )
		     ->insert( 'after' )
		     ->insert( 'author' )
		     ->insert( 'exclude' )
		     ->insert( 'include' )
		     ->insert( 'order' )
		     ->insert( 'orderby' )
		     ->insert( 'slug' );
	}

	/**
	 * This method will return the configured guzzle client.
	 *
	 * @return Client The configured guzzle client.
	 */
	protected function getClient(): Client {
		if ( ! $this->_client ) {
			$config        = $this->getConfig();
			$this->_client = new Client( [
				'base_uri' => $config['url'] . '/wp-json/wp/v2/',
				'auth'     => [ $config['username'], $config['password'] ]
			] );
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
	protected function getConfig(): array {
		$class     = get_class( $this );
		$class     = str_replace( 'Frontender\\Platform\\Model\\', '', $class );
		$parts     = explode( '\\', $class );
		$namespace = strtolower( array_shift( $parts ) );

		if ( $namespace === 'wordpress' ) {
			$namespace = 'default';
		}

		return [
			'username' => $this->_config->{'wordpress_' . $namespace . '_username'},
			'password' => $this->_config->{'wordpress_' . $namespace . '_password'},
			'url'      => $this->_config->{'wordpress_' . $namespace . '_url'}
		];
	}

	public function getTotal(): int {
		// Do we need to get them all?
		try {
			$model = clone $this;
			$model->setState( [
				'limit' => 1
			] );

			$response = $this->getClient()->get(
				$model->getEndpoint(),
				$model->getRequestOptions()
			);

			$header = $response->getHeader( 'X-WP-Total' );

			if($header) {
				return (int) $header[0];
			}
		} catch(\Exception $e) {
			// NOOP
		} catch(\Error $e) {
			// NOOP
		}

		return 0;
	}

	/**
	 * This method will always return an array.
	 * If an ID is given it will also return an array of 1.
	 *
	 * @return array The fetched items.
	 */
	public function fetch(): array {
		
		if($this->getState()->id && is_array($this->getState()->id)) {
		    return array_map(function($id) {
			$model = new $this($this->container);
			$model->setState([
			    'id' => $id
			]);
			$item = $model->fetch();
			if(count($item)) {
			    return array_shift($item);
			}
			return false;
		    }, $this->getState()->id);
		}
		
		$response = $this->getClient()->get(
			$this->getEndpoint(),
			$this->getRequestOptions()
		);
		$content  = $response->getBody()->getContents();
		$result   = json_decode( $content, true );

		// If we have an ID in the request we have a single
		if ( $this->getState()->id ) {
			$itemModel = new $this( $this->container );
			$itemModel->setData( $result );
			$itemModel->setState( [
				'id' => $result['id']
			] );

			return [ $itemModel ];
		} else {
			return array_map( function ( $item ) {
				$itemModel = new $this( $this->container );
				$itemModel->setData( $item );
				$itemModel->setState( [
					'id' => $item['id']
				] );

				return $itemModel;
			}, $result );
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
		$options = [];

		if ( isset( $query['id'] ) ) {
			unset( $query['id'] );
		}

		// Map the limit to per_page as is used in the API of WordPress.
		if ( isset( $query['limit'] ) ) {
			$query['per_page'] = $query['limit'];
			unset( $query['limit'] );
		}

		$options['query'] = $query;

		return $options;
	}

	/**
	 * This method will return the url to be called.
	 * This will be based on the model name concatenated with the ID (if present).
	 *
	 * @return string
	 */
	protected function getEndpoint(): string {
		$class     = explode( '\\', get_class( $this ) );
		$modelName = array_pop( $class );
		$modelName = strtolower( str_replace( 'Model', '', $modelName ) );

		if ( $modelName === 'abstract' ) {
			return '';
		}

		$state = $this->getState()->getValues();
		if ( isset( $state['id'] ) && ! empty( $state['id'] ) ) {
			$modelName .= '/' . $state['id'];
		}

		return $modelName;
	}

	/**
	 * This method is only for internal usage!
	 * This will get a new model based on the current namespace,
	 * as we can't rely on the current namespace because of the config.
	 *
	 * The container will automatically be given because it is correct in the current context.
	 *
	 * @param string $modelName The model name to retrieve.
	 *
	 * @return AbstractModel The new model instance within the current namespace.
	 */
	protected function getModel(string $modelName): AbstractModel {
		$className = get_class($this);

		$parts = explode('\\', $className);
		$parts = array_slice($parts, 0, 4);
		$parts[] = $modelName;

		$className = implode('\\', $parts);

		return new $className( $this->container );
	}
}
