<?php

class ListingHelper extends AppHelper
{
	public function pagination ($listing)
	{
		$output = "<div class='pagination'>";

		// |<
		$output .= $this->paginationLink('&laquo;', 
			$this->getURI($listing, array (
				'page' => 1,
			)), $listing['page'] > 1);

		// <
		$output .= $this->paginationLink('&lsaquo;', 
			$this->getURI($listing, array (
				'page' => $listing['page'] - 1,
			)), $listing['page'] > 1);

		// zacatek strankovani
		$first = 1;
		if ($listing['page'] > 5)
			$first = $listing['page'] - 5;

		// konec strankovani
		$last = $listing['pages'];
		if ($listing['page'] < $listing['pages'] - 5)
			$last = $listing['page'] + 5;

		for ($i = $first; $i <= $last; $i++)
			$output .= $this->paginationLink($i, 
				$this->getURI($listing, array (
					'page' => $i,
				)), $listing['page'] != $i);

		// >
		$output .= $this->paginationLink('&rsaquo;', 
			$this->getURI($listing, array (
				'page' => $listing['page'] + 1,
			)), $listing['page'] < $listing['pages']);

		// |<
		$output .= $this->paginationLink('&raquo;', 
			$this->getURI($listing, array (
				'page' => $listing['pages'],
			)), $listing['page'] < $listing['pages']);
		
		$output .= "</div>";

		return $output;
	}


	public function paginationLink ($text, $uri, $clickable = true)
	{
		if ($clickable)
			return "<a href='$uri' title='$text'>$text</a>";

		return "<span>$text</span>";
		
	}


	public function getURI ($listing, $changes = null)
	{
		$userParams = ClassRegistry::getObject('view')->viewVars['listingUserParams'];

		// pripadne upravime..
		if (!empty($changes))
			$userParams[$listing['id']] = array_merge($userParams[$listing['id']], $changes);

		// zakodujem..
		$encoded = base64_encode(serialize($userParams));

		// vytvorie uri..
		$uri = preg_replace('/:listingVars/', $encoded, $listing['URIRegex']);

		return $uri;
	}


	public function count ($listing)
	{
		return $listing['count'];
	}


	public function getFilterValue ($listing, $name)
	{
		$id = $listing['id'];
		$userParams =& ClassRegistry::getObject('view')->viewVars['listingUserParams'][$id];

		if (isset($userParams['filters'][$name]))
			return $userParams['filters'][$name];

		return '';
	}
}
