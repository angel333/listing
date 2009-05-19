<?php

class ListingComponent extends Object
{
    public $controller = null;

	/**
	 * Inicializace
	 */
	public function initialize (&$controller)
	{
		$this->controller =& $controller;
	}


	/**
	 * Vytvori listing
	 */
	public function make (&$model, $method, $params = array ())
	{
		// najdeme spravnej listingVars - kdyz ne, tak array()
		if (
			empty($this->controller->params['listingVars']) ||
			!($userParams = unserialize(base64_decode($this->controller->params['listingVars']))) ||
			!is_array($userParams)
		)
	   		$userParams = array ();
		elseif (isset($params['name']))
			if
			(
				isset($userParams[$params['name']]) &&
				is_array($userParams[$params['name']])
			)
				$userParams = $userParams[$params['name']];
			else
				$userParams = array ();

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

		// resulty sem..
		$results = $model->$method($modelParams);

		// kvuli linkum pridame i to, co zadal user..
		$results['userParams'] = $userParams;

		// pocet stranek
		$results['pages'] = (int)($results['meta']['count'] / $modelParams['limit']);
		if (($results['meta']['count'] % $modelParams['limit']) > 0)
			$results['pages']++;

		if (isset($params['name']))
			$results['name'] = $params['name'];

		if (isset($params['URIRegex']))
			$results['URIRegex'] = $params['URIRegex'];

		$results['page'] = $modelParams['page'];

		if (isset($results['meta']['count']))
			$results['count'] = $results['meta']['count'];

		// a vratime..
		return $results;
	}
}
