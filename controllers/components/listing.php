<?php

class ListingComponent extends Object
{
    public $controller = null;
	public $userParams = null;
	public $currentId = 0;


	/**
	 * Inicializace
	 */
	public function initialize (&$controller)
	{
		$this->controller =& $controller;
	}


	/**
	 * Startup
	 */
	public function startup (&$controller)
	{
		// najdeme spravnej listingVars - kdyz ne, tak array()
		if (
			empty($this->controller->params['listingVars']) ||
			!($this->userParams = unserialize(base64_decode($this->controller->params['listingVars']))) ||
			!is_array($this->userParams)
		)
	   		$this->userParams = array ();
	}


	/**
	 * Vytvori listing
	 */
	public function make (&$model, $method, $params = array ())
	{
		if (isset($this->userParams[$this->currentId]))
			$userParams = $this->userParams[$this->currentId];
		else
			$userParams = array (
				'page' => 1,
			);

		// de facto default
		$modelParams = array (
			'page' => 1,
			'limit' => 50,
		);

		// nahazime veci od usera do modelParams..
		if (
			isset($userParams['page']) &&
			is_numeric($userParams['page']) &&
			$userParams['page'] > 0
		)
			$modelParams['page'] = $userParams['page'];

		// limit je z controlleru, ne od usera
		if (isset($params['limit']))
			$modelParams['limit'] = $params['limit'];

		// filtry - z controlleru..
		if (isset($params['filters']))
			$modelParams['filters'] = $params['filters'];

		// <<< resulty sem >>>
		$results = $model->$method($modelParams);

		// pocet stranek
		$results['pages'] = (int)($results['meta']['count'] / $modelParams['limit']);
		if (($results['meta']['count'] % $modelParams['limit']) > 0)
			$results['pages']++;

		if (isset($params['URIRegex']))
			$results['URIRegex'] = $params['URIRegex'];

		$results['page'] = $userParams['page'];

		if (isset($results['meta']['count']))
			$results['count'] = $results['meta']['count'];

		$this->controller->viewVars['listingUserParams'][$this->currentId] = $userParams;
		$results['id'] = $this->currentId;
		$this->currentId++;

		// a vratime..
		return $results;
	}
}
