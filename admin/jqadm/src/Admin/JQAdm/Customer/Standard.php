<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
 * @package Admin
 * @subpackage JQAdm
 */


namespace Aimeos\Admin\JQAdm\Customer;


/**
 * Default implementation of customer JQAdm client.
 *
 * @package Admin
 * @subpackage JQAdm
 */
class Standard
	extends \Aimeos\Admin\JQAdm\Common\Admin\Factory\Base
	implements \Aimeos\Admin\JQAdm\Common\Admin\Factory\Iface
{
	/** admin/jqadm/customer/standard/subparts
	 * List of JQAdm sub-clients rendered within the customer section
	 *
	 * The output of the frontend is composed of the code generated by the JQAdm
	 * clients. Each JQAdm client can consist of serveral (or none) sub-clients
	 * that are responsible for rendering certain sub-parts of the output. The
	 * sub-clients can contain JQAdm clients themselves and therefore a
	 * hierarchical tree of JQAdm clients is composed. Each JQAdm client creates
	 * the output that is placed inside the container of its parent.
	 *
	 * At first, always the JQAdm code generated by the parent is printed, then
	 * the JQAdm code of its sub-clients. The order of the JQAdm sub-clients
	 * determines the order of the output of these sub-clients inside the parent
	 * container. If the configured list of clients is
	 *
	 *  array( "subclient1", "subclient2" )
	 *
	 * you can easily change the order of the output by reordering the subparts:
	 *
	 *  admin/jqadm/<clients>/subparts = array( "subclient1", "subclient2" )
	 *
	 * You can also remove one or more parts if they shouldn't be rendered:
	 *
	 *  admin/jqadm/<clients>/subparts = array( "subclient1" )
	 *
	 * As the clients only generates structural JQAdm, the layout defined via CSS
	 * should support adding, removing or reordering content by a fluid like
	 * design.
	 *
	 * @param array List of sub-client names
	 * @since 2017.07
	 * @category Developer
	 */
	private $subPartPath = 'admin/jqadm/customer/standard/subparts';

	/** admin/jqadm/customer/special/name
	 * Name of the special subpart used by the JQAdm customer implementation
	 *
	 * Use "Myname" if your class is named "\Aimeos\Admin\Jqadm\Customer\Special\Myname".
	 * The name is case-sensitive and you should avoid camel case names like "MyName".
	 *
	 * @param string Last part of the JQAdm class name
	 * @since 2017.06
	 * @category Developer
	 */
	private $subPartNames = [];


	/**
	 * Copies a resource
	 *
	 * @return string HTML output
	 */
	public function copy()
	{
		$view = $this->getView();
		$context = $this->getContext();

		try
		{
			if( ( $id = $view->param( 'id' ) ) === null ) {
				throw new \Aimeos\Admin\JQAdm\Exception( sprintf( 'Required parameter "%1$s" is missing', 'id' ) );
			}

			$manager = \Aimeos\MShop\Factory::createManager( $context, 'customer' );
			$view->item = $manager->getItem( $id, $this->getDomains() );

			$view->itemData = $this->toArray( $view->item, true );
			$view->itemSubparts = $this->getSubClientNames();
			$view->itemBody = '';

			foreach( $this->getSubClients() as $idx => $client )
			{
				$view->tabindex = ++$idx + 1;
				$view->itemBody .= $client->copy();
			}
		}
		catch( \Aimeos\MShop\Exception $e )
		{
			$error = array( 'customer-item' => $context->getI18n()->dt( 'mshop', $e->getMessage() ) );
			$view->errors = $view->get( 'errors', [] ) + $error;
		}
		catch( \Exception $e )
		{
			$error = array( 'customer-item' => $e->getMessage() . ', ' . $e->getFile() . ':' . $e->getLine() );
			$view->errors = $view->get( 'errors', [] ) + $error;
		}

		return $this->render( $view );
	}


	/**
	 * Creates a new resource
	 *
	 * @return string HTML output
	 */
	public function create()
	{
		$view = $this->getView();
		$context = $this->getContext();

		try
		{
			$data = $view->param( 'item' );

			if( !isset( $view->item ) ) {
				$view->item = \Aimeos\MShop\Factory::createManager( $context, 'customer' )->createItem();
			} else {
				$data = $this->toArray( $view->item );
			}

			$data['customer.id'] = $view->item->getId();
			$data['customer.siteid'] = $view->item->getSiteId();

			$view->itemSubparts = $this->getSubClientNames();
			$view->itemData = $data;
			$view->itemBody = '';

			foreach( $this->getSubClients() as $idx => $client )
			{
				$view->tabindex = ++$idx + 1;
				$view->itemBody .= $client->create();
			}
		}
		catch( \Aimeos\MShop\Exception $e )
		{
			$error = array( 'customer-item' => $context->getI18n()->dt( 'mshop', $e->getMessage() ) );
			$view->errors = $view->get( 'errors', [] ) + $error;
		}
		catch( \Exception $e )
		{
			$error = array( 'customer-item' => $e->getMessage() . ', ' . $e->getFile() . ':' . $e->getLine() );
			$view->errors = $view->get( 'errors', [] ) + $error;
		}

		return $this->render( $view );
	}


	/**
	 * Deletes a resource
	 *
	 * @return string|null HTML output
	 */
	public function delete()
	{
		$view = $this->getView();
		$context = $this->getContext();

		$manager = \Aimeos\MShop\Factory::createManager( $context, 'customer' );
		$manager->begin();

		try
		{
			if( ( $id = $view->param( 'id' ) ) === null ) {
				throw new \Aimeos\Admin\JQAdm\Exception( sprintf( 'Required parameter "%1$s" is missing', 'id' ) );
			}

			$view->item = $manager->getItem( $id, $this->getDomains() );

			foreach( $this->getSubClients() as $client ) {
				$client->delete();
			}

			$manager->deleteItem( $id );
			$manager->commit();

			$this->nextAction( $view, 'search', 'customer' );
			return;
		}
		catch( \Aimeos\MShop\Exception $e )
		{
			$error = array( 'customer-item' => $context->getI18n()->dt( 'mshop', $e->getMessage() ) );
			$view->errors = $view->get( 'errors', [] ) + $error;
		}
		catch( \Exception $e )
		{
			$error = array( 'customer-item' => $e->getMessage() . ', ' . $e->getFile() . ':' . $e->getLine() );
			$view->errors = $view->get( 'errors', [] ) + $error;
		}

		$manager->rollback();

		return $this->search();
	}


	/**
	 * Returns a single resource
	 *
	 * @return string HTML output
	 */
	public function get()
	{
		$view = $this->getView();
		$context = $this->getContext();

		try
		{
			if( ( $id = $view->param( 'id' ) ) === null ) {
				throw new \Aimeos\Admin\JQAdm\Exception( sprintf( 'Required parameter "%1$s" is missing', 'id' ) );
			}

			$manager = \Aimeos\MShop\Factory::createManager( $context, 'customer' );

			$view->item = $manager->getItem( $id, $this->getDomains() );
			$view->itemSubparts = $this->getSubClientNames();
			$view->itemData = $this->toArray( $view->item );
			$view->itemBody = '';

			foreach( $this->getSubClients() as $idx => $client )
			{
				$view->tabindex = ++$idx + 1;
				$view->itemBody .= $client->get();
			}
		}
		catch( \Aimeos\MShop\Exception $e )
		{
			$error = array( 'customer-item' => $context->getI18n()->dt( 'mshop', $e->getMessage() ) );
			$view->errors = $view->get( 'errors', [] ) + $error;
		}
		catch( \Exception $e )
		{
			$error = array( 'customer-item' => $e->getMessage() . ', ' . $e->getFile() . ':' . $e->getLine() );
			$view->errors = $view->get( 'errors', [] ) + $error;
		}

		return $this->render( $view );
	}


	/**
	 * Saves the data
	 *
	 * @return string HTML output
	 */
	public function save()
	{
		$view = $this->getView();
		$context = $this->getContext();

		$manager = \Aimeos\MShop\Factory::createManager( $context, 'customer' );
		$manager->begin();

		try
		{
			$view->item = $this->fromArray( $view->param( 'item', [] ) );
			$view->itemBody = '';

			foreach( $this->getSubClients() as $client ) {
				$view->itemBody .= $client->save();
			}

			$manager->commit();

			$this->nextAction( $view, $view->param( 'next' ), 'customer', $view->item->getId() );
			return;
		}
		catch( \Aimeos\Admin\JQAdm\Exception $e )
		{
			// fall through to create
		}
		catch( \Aimeos\MShop\Exception $e )
		{
			$error = array( 'customer-item' => $context->getI18n()->dt( 'mshop', $e->getMessage() ) );
			$view->errors = $view->get( 'errors', [] ) + $error;
		}
		catch( \Exception $e )
		{
			$error = array( 'customer-item' => $e->getMessage() . ', ' . $e->getFile() . ':' . $e->getLine() );
			$view->errors = $view->get( 'errors', [] ) + $error;
		}

		$manager->rollback();

		return $this->create();
	}


	/**
	 * Returns a list of resource according to the conditions
	 *
	 * @return string HTML output
	 */
	public function search()
	{
		$view = $this->getView();
		$context = $this->getContext();

		try
		{
			$total = 0;
			$manager = \Aimeos\MShop\Factory::createManager( $context, 'customer' );
			$search = $this->initCriteria( $manager->createSearch(), $view->param() );

			$view->items = $manager->searchItems( $search, [], $total );
			$view->filterAttributes = $manager->getSearchAttributes( true );
			$view->filterOperators = $search->getOperators();
			$view->total = $total;
			$view->itemBody = '';

			foreach( $this->getSubClients() as $client ) {
				$view->itemBody .= $client->search();
			}
		}
		catch( \Aimeos\MShop\Exception $e )
		{
			$error = array( 'customer-item' => $context->getI18n()->dt( 'mshop', $e->getMessage() ) );
			$view->errors = $view->get( 'errors', [] ) + $error;
		}
		catch( \Exception $e )
		{
			$error = array( 'customer-item' => $e->getMessage() . ', ' . $e->getFile() . ':' . $e->getLine() );
			$view->errors = $view->get( 'errors', [] ) + $error;
		}

		/** admin/jqadm/customer/template-list
		 * Relative path to the HTML body template for the customer list.
		 *
		 * The template file contains the HTML code and processing instructions
		 * to generate the result shown in the body of the frontend. The
		 * configuration string is the path to the template file relative
		 * to the templates directory (usually in admin/jqadm/templates).
		 *
		 * You can overwrite the template file configuration in extensions and
		 * provide alternative templates. These alternative templates should be
		 * named like the default one but with the string "default" replaced by
		 * an unique name. You may use the name of your project for this. If
		 * you've implemented an alternative client class as well, "default"
		 * should be replaced by the name of the new class.
		 *
		 * @param string Relative path to the template creating the HTML code
		 * @since 2016.04
		 * @category Developer
		 */
		$tplconf = 'admin/jqadm/customer/template-list';
		$default = 'customer/list-default.php';

		return $view->render( $view->config( $tplconf, $default ) );
	}


	/**
	 * Returns the sub-client given by its name.
	 *
	 * @param string $type Name of the client type
	 * @param string|null $name Name of the sub-client (Default if null)
	 * @return \Aimeos\Admin\JQAdm\Iface Sub-client object
	 */
	public function getSubClient( $type, $name = null )
	{
		/** admin/jqadm/customer/decorators/excludes
		 * Excludes decorators added by the "common" option from the customer JQAdm client
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to remove a decorator added via
		 * "client/jqadm/common/decorators/default" before they are wrapped
		 * around the JQAdm client.
		 *
		 *  admin/jqadm/customer/decorators/excludes = array( 'decorator1' )
		 *
		 * This would remove the decorator named "decorator1" from the list of
		 * common decorators ("\Aimeos\Admin\JQAdm\Common\Decorator\*") added via
		 * "client/jqadm/common/decorators/default" to the JQAdm client.
		 *
		 * @param array List of decorator names
		 * @since 2017.07
		 * @category Developer
		 * @see admin/jqadm/common/decorators/default
		 * @see admin/jqadm/customer/decorators/global
		 * @see admin/jqadm/customer/decorators/local
		 */

		/** admin/jqadm/customer/decorators/global
		 * Adds a list of globally available decorators only to the customer JQAdm client
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to wrap global decorators
		 * ("\Aimeos\Admin\JQAdm\Common\Decorator\*") around the JQAdm client.
		 *
		 *  admin/jqadm/customer/decorators/global = array( 'decorator1' )
		 *
		 * This would add the decorator named "decorator1" defined by
		 * "\Aimeos\Admin\JQAdm\Common\Decorator\Decorator1" only to the JQAdm client.
		 *
		 * @param array List of decorator names
		 * @since 2017.07
		 * @category Developer
		 * @see admin/jqadm/common/decorators/default
		 * @see admin/jqadm/customer/decorators/excludes
		 * @see admin/jqadm/customer/decorators/local
		 */

		/** admin/jqadm/customer/decorators/local
		 * Adds a list of local decorators only to the customer JQAdm client
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to wrap local decorators
		 * ("\Aimeos\Admin\JQAdm\Customer\Decorator\*") around the JQAdm client.
		 *
		 *  admin/jqadm/customer/decorators/local = array( 'decorator2' )
		 *
		 * This would add the decorator named "decorator2" defined by
		 * "\Aimeos\Admin\JQAdm\Customer\Decorator\Decorator2" only to the JQAdm client.
		 *
		 * @param array List of decorator names
		 * @since 2017.07
		 * @category Developer
		 * @see admin/jqadm/common/decorators/default
		 * @see admin/jqadm/customer/decorators/excludes
		 * @see admin/jqadm/customer/decorators/global
		 */
		return $this->createSubClient( 'customer/' . $type, $name );
	}


	/**
	 * Returns the domain names whose items should be fetched too
	 *
	 * @return string[] List of domain names
	 */
	protected function getDomains()
	{
		/** admin/jqadm/customer/domains
		 * List of domain items that should be fetched along with the customer
		 *
		 * If you need to display additional content, you can configure your own
		 * list of domains (attribute, media, price, customer, text, etc. are
		 * domains) whose items are fetched from the storage.
		 *
		 * @param array List of domain names
		 * @since 2017.07
		 * @category Developer
		 */
		$domains = array( 'customer', 'product' );

		return $this->getContext()->getConfig()->get( 'admin/jqadm/customer/domains', $domains );
	}


	/**
	 * Returns the list of sub-client names configured for the client.
	 *
	 * @return array List of JQAdm client names
	 */
	protected function getSubClientNames()
	{
		return $this->getContext()->getConfig()->get( $this->subPartPath, $this->subPartNames );
	}



	/**
	 * Creates new and updates existing items using the data array
	 *
	 * @param string[] Data array
	 * @return \Aimeos\MShop\Customer\Item\Iface New customer item object
	 */
	protected function fromArray( array $data )
	{
		$manager = \Aimeos\MShop\Factory::createManager( $this->getContext(), 'customer' );

		$item = $manager->createItem();
		$item->fromArray( $data );

		$manager->saveItem( $item );

		return $item;
	}


	/**
	 * Constructs the data array for the view from the given item
	 *
	 * @param \Aimeos\MShop\Customer\Item\Iface $item Customer item object
	 * @return string[] Multi-dimensional associative list of item data
	 */
	protected function toArray( \Aimeos\MShop\Customer\Item\Iface $item, $copy = false )
	{
		$data = $item->toArray( true );

		if( $copy === true )
		{
			$data['customer.siteid'] = $this->getContext()->getLocale()->getSiteId();
			$data['customer.code'] = '';
			$data['customer.id'] = '';
		}

		return $data;
	}


	/**
	 * Returns the rendered template including the view data
	 *
	 * @return string HTML output
	 */
	protected function render( \Aimeos\MW\View\Iface $view )
	{
		/** admin/jqadm/customer/template-item
		 * Relative path to the HTML body template for the customer item.
		 *
		 * The template file contains the HTML code and processing instructions
		 * to generate the result shown in the body of the frontend. The
		 * configuration string is the path to the template file relative
		 * to the templates directory (usually in admin/jqadm/templates).
		 *
		 * You can overwrite the template file configuration in extensions and
		 * provide alternative templates. These alternative templates should be
		 * named like the default one but with the string "default" replaced by
		 * an unique name. You may use the name of your project for this. If
		 * you've implemented an alternative client class as well, "default"
		 * should be replaced by the name of the new class.
		 *
		 * @param string Relative path to the template creating the HTML code
		 * @since 2016.04
		 * @category Developer
		 */
		$tplconf = 'admin/jqadm/customer/template-item';
		$default = 'customer/item-default.php';

		return $view->render( $view->config( $tplconf, $default ) );
	}
}
