<?php


class ListingComponent extends Object
{
	/**
	 * Controller object
	 *
	 * @var object
	 */
	public $controller = null;


	/**
	 * Filters, sorting, etc. from a form in view
	 *
	 * @var array
	 */
	public $userParams = null;


	/**
	 * Every listing you create will increase this variable (counter)
	 *
	 * @var integer
	 */
	public $currentId = 0;


	/**
	 * URI prefix (useful for the helper)
	 *
	 * @var string
	 */
	public $URIPrefix = null;


	/**
	 * URI sufix (useful for the helper)
	 *
	 * @var string
	 */
	public $URISuffix = null;


	/**
	 * Default allowed user parameters
	 *
	 * @var array
	 */
	public $defaultAllowedUserParams = array (
		'order' => array (),
		'limit' => array (),
		'search' => array (),
		'searchExact' => array (),
		'filters' => array (),
	);


	/**
	 * Initialization
	 * 
	 * @param object $controller
	 */
	public function initialize (&$controller)
	{
		$this->controller =& $controller;

		// setting the url prefix and suffix
		if (empty($this->URIPrefix) && empty($this->URISuffix))
		{
			$alreadyInURI = preg_match('#(.*/)(listing:[a-zA-z0-9=]*)(.*)#', $this->controller->here, $matches);

			if ($alreadyInURI)
			{
				$this->URIPrefix = $matches[1] . 'listing:';
				$this->URISuffix = $matches[3];
			}
			else
			{
				$this->URIPrefix = $this->controller->here;
				$this->URISuffix = '';

				if (substr($this->URIPrefix, 0, -1) != '/')
					$this->URIPrefix .= '/';

				$this->URIPrefix .= 'listing:';
			}
		}
	}


	/**
	 * Saves user parameters from POST and GET to $this->userParams
	 *
	 * @param object $controller
	 */
	public function startup (&$controller)
	{
		// Save listingVars (the param in config/routes.php) to $this->userParams - or just empty array if not any
		if (
			empty($this->controller->params['named']['listing']) ||
			!($this->userParams = json_decode(base64_decode($this->controller->params['named']['listing']), true)) ||
			!is_array($this->userParams)
		)
			$this->userParams = array ();
		
		// Saves user filters from POST (form) to $this->userParams['filters']
		if (!empty($this->controller->data))
		{
			foreach ($this->controller->data as $key => $val)
			{
				$exploded = explode('-', $key);
				if ($exploded[0] == 'ListingVars')
				{
					$id = $exploded[1];

					if (!isset($this->userParams[$id]))
						$this->userParams[$id] = array ();

					$this->userParams[$id] = array_merge($this->userParams[$id], $val);

					// Go to first page when params are changed.
					$this->userParams[$id]['page'] = 1;

					$encoded = base64_encode(json_encode($this->userParams));
					$newURI =  $this->URIPrefix . $encoded . $this->URISuffix;
					$controller->redirect($newURI);
				}
			}
		}

		// This will save all params to $controller->data, so it'll be visible in forms.
		foreach ($this->userParams as $id => $val)
			$controller->data["ListingVars-$id"] = $val;
	}


	/**
	 * Creates a listing
	 * - creates an array of listing method parameters - $modelParams
	 * - then gets the results from the model
	 *
	 * @param object $model
	 * @param array $params Parameters for the listing method
	 */
	public function create (&$model, $params = array ())
	{
		$modelParams = array (
			'conditions' => array (),
			'page' => 1,
		);

		// Default parameters
		$modelParams = array_merge($modelParams, $params['default']);

		// The default method is 'listing'
		if (empty($params['method']))
			$params['method'] = 'find';

		// User parameters
		if (empty($this->userParams[$this->currentId]))
			$this->userParams[$this->currentId] = array();
		else
			$modelParams = $this->processUserParams($modelParams, $params['user']);

		// <<< GET THE RESULTS >>>
		$results = array();

		$results['data'] = $model->$params['method']('all', $modelParams);

		// Count results
		$modelCountParams = $modelParams;
		if (isset($modelCountParams['fields']))
			unset($modelCountParams['fields']);
		if (isset($modelCountParams['page']))
			unset($modelCountParams['page']);
		if (isset($modelCountParams['offset']))
			unset($modelCountParams['offset']);
		$results['count'] = $model->$params['method']('count', $modelCountParams);

		$results['schema'] = $this->getSchema($results['data']);

		$results['page'] = $modelParams['page'];
		$results['URIPrefix'] = $this->URIPrefix;
		$results['URISuffix'] = $this->URISuffix;

		// Allowed params (userful for the helper)
		if (!isset($params['user']) || !is_array($params['user']))
			$params['user'] = array ();
		$params['user'] = array_merge($this->defaultAllowedUserParams, $params['user']);
		$results['allowedUserParams'] = $params['user'];

		$results['modelName'] = $model->alias;
		
		// An important variable for the Listing helper
		$this->controller->viewVars['listingUserParams'][$this->currentId] = $this->userParams[$this->currentId];
		$this->controller->data["ListingVars-{$this->currentId}"] = $this->userParams[$this->currentId];

		// Counts pages
		if (empty($modelParams['limit']))
			$results['pages'] = 1;
		else
		{
			$results['pages'] = (int)($results['count'] / $modelParams['limit']);
			if (($results['count'] % $modelParams['limit']) > 0)
				$results['pages']++;
		}

		// Assigning an id to this listing
		$results['id'] = $this->currentId;
		$this->currentId++;

		// Finally, return the results..
		return $results;
	}


	/**
	 * Validates user parameters and merge them into model parameters
	 *
	 * @param array $modelParams
	 * @param array $allowed what is allowed to user
	 * @return array
	 */
	private function processUserParams ($modelParams, $allowed)
	{
		// Gets user parameters for the listing
		if (isset($this->userParams[$this->currentId]))
			$userParams = $this->userParams[$this->currentId];
		else
			$userParams = array ();
		
		// Setting page
		if (isset($userParams['page']) && is_numeric($userParams['page']))
			$modelParams['page'] = $userParams['page'];
		else
			$modelParams['page'] = 1;

		// Validation of parameters from user
		
		if (isset($userParams['search']))
		{
			foreach ($userParams['search'] as $key => $val)
				foreach ($val as $key2 => $val2)
				{
					// search (LIKE %x%)
					if (in_array("$key.$key2", $allowed['search']))
						$modelParams['conditions']["$key.$key2 LIKE"] = "%$val2%";
					// searchExact (= x)
					elseif (in_array("$key.$key2", $allowed['searchExact']))
						$modelParams['conditions']["$key.$key2"] = "$val2";
				}
		}

		if (isset($userParams['limit']) && isset($allowed['limit']))
			if (in_array($userParams['limit'], $allowed['limit']))
				$modelParams['limit'] = $userParams['limit'];

		if (isset($userParams['order']) && isset($allowed['order']))
			if (in_array($userParams['order'], $allowed['order']))
				$modelParams['order'] = $userParams['order'];

		if (isset($userParams['filter']) && isset($allowed['filters']))
			if (isset($allowed['filters'][$userParams['filter']]))
				$modelParams['conditions'] = array_merge($modelParams['conditions'], $allowed['filters'][$userParams['filter']]);

		return $modelParams;
	}


	/**
	 * Gets schema (column names) of results given
	 *
	 * @var array $data
	 * @return array
	 */
	private function getSchema ($data)
	{
		$schema = array ();

		if (empty($data) || !is_array($data) || count($data) == 0)
			return null;
		else
		{
			$row = $data[0];
			foreach ($row as $modelName => $model)
			{
				// hasMany
				if (isset($model[0]))
					$model = $model[0];

				if (is_array($model))
					foreach ($model as $field => $val)
						$schema[$modelName][] = $field;
				else
					$schema['standalone'][] = $modelName;
			}
		}

		return $schema;
	}
}
