<?php


class ListingHelper extends AppHelper
{
	public $helpers = array ('Form');


	/**
	 * Complete pagination for a listing
	 *
	 * @param array $listing View listing variable
	 * @return string
	 */
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


	/**
	 * Just creates <a> (or <span>) tag.
	 *
	 * @param string $text What user will see
	 * @param string $uri Href parameter of the link
	 * @param bool $clickable If false, returns just <span>
	 * @return string
	 */
	public function paginationLink ($text, $uri, $clickable = true)
	{
		if ($clickable)
			return "<a href='$uri' title='$text'>$text</a>";

		return "<span>$text</span>";
		
	}


	/**
	 * Makes an URI which can be used in links, etc.
	 *
	 * @param array $listing View listing variable
	 * @param array $changes Listing parameters which you want to modify
	 * @return strin
	 */
	public function getURI ($listing, $changes = null)
	{
		$userParams = ClassRegistry::getObject('view')->viewVars['listingUserParams'];

		// merge changes
		if (!empty($changes))
			$userParams[$listing['id']] = array_merge($userParams[$listing['id']], $changes);

		// encode!
		$encoded = base64_encode(serialize($userParams));

		// and make the uri..
		$uri = preg_replace('/:listingVars/', $encoded, $listing['URIRegex']);

		return $uri;
	}


	/**
	 * Returns number of results
	 *
	 * @param array $listing View listing variable
	 * @return int
	 */
	public function count ($listing)
	{
		return $listing['count'];
	}


	/**
	 * Gets value of a filter
	 *
	 * @param array $listing View listing variable
	 * @param string $name Name of filter
	 * @return string
	 */
	public function getFilterValue ($listing, $name)
	{
		$id = $listing['id'];
		$userParams =& ClassRegistry::getObject('view')->viewVars['listingUserParams'][$id];

		if (isset($userParams['filters'][$name]))
			return $userParams['filters'][$name];

		return '';
	}


	/**
	 * Creates a user params form (for a listing)
	 *
	 * @param array $listing View listing variable
	 * @return string 
	 */
	public function formCreate ($listing)
	{
		$output = $this->Form->create('ListingVars', array ('url' => $this->getURI($listing)));
		//$output .= $this->Form->hidden('ListingVars.id', array('value'=>$listing['id']));
		return $output;
	}


	/**
	 * Closes a user params form (for a listing)
	 *
	 * @param array $listing View listing variable
	 * @return string 
	 */
	public function formEnd ($listing)
	{
		return $this->Form->end();
	}

	
	/**
	 * Returns <select> for limits
	 *
	 * @param array $listing View listing variable
	 * @return string 
	 */
	public function limitSelect ($listing)
	{
		$options = array ();

		foreach ($listing['allowedUserParams']['limit'] as $item)
			$options[$item] = $item;

		return $this->Form->select("ListingVars-{$listing['id']}.limit", $options);
	}


	/**
	 * Returns link for sorting
	 *
	 * @param array $listing View listing variable
	 * @param string $field Name of the field you want to sort by
	 * @return string 
	 */
	public function sortLink ($listing, $field)
	{
		if (
			isset($listing[$listing['id']]['order']) &&
			$listing[$listing['id']]['order'] == $field
		)
			return $field;

		$changes = array ('order' => $field);
		$uri = $this->getURI($listing, $changes);
		return "<a href='$uri'>$field</a>";
	}


	/**
	 * Creates scaffold of the listing
	 *
	 * Also creates a textarea with code you can just copy & paste
	 * to your view to get the same scaffold. You can change the
	 * name of listing variable in second parameter.
	 *
	 * @param array $listing View listing variable
	 * @param string $emulate name of listing var used in code
	 * @return string 
	 */
	public function scaffold ($listing, $emulate = 'data')
	{
		$output = $this->scaffoldStyles();

		$output .= "<div class='listingScaffold'>";
		$code = "<div class='listingScaffold'>\n\n";

		// Searches
		if (!empty($listing['allowedUserParams']['search']))
		{
			$output .= '<fieldset><legend>Search</legend>';
			$code .= "<fieldset><legend>Search</legend>\n";

			$output .= $this->formCreate($listing);
			$code .= "\t<?=\$listing->formCreate(\$$emulate)?>\n";

			foreach ($listing['allowedUserParams']['search'] as $item)
			{
				$output .= "$item: ";
				$code .= "\t\t$item: ";

				$output .= $this->Form->text("ListingVars-$listing[id].search.$item") . '<br/>';
				$code .= "<?=\$form->text(\"ListingVars-\${$emulate}[id].search.$item\")?><br/>\n";
			}

			$output .= $this->Form->submit('Search');
			$code .= "\t\t<?=\$form->submit('Search')?>\n";

			$output .= $this->formEnd($listing);
			$code .= "\t<?=\$listing->formEnd(\$$emulate)?>\n";

			$output .= '</fieldset>';
			$code .= "</fieldset>\n\n";
		}


		// User limits
		if (!empty($listing['allowedUserParams']['limit']))
		{
			$output .= '<fieldset><legend>Limits</legend>';
			$code .= "<fieldset><legend>Limits</legend>\n";

			$output .= $this->formCreate($listing);
			$code .= "\t<?=\$listing->formCreate(\$$emulate)?>\n";

			$output .= $this->limitSelect($listing) . '<br/>';
			$code .= "\t\t<?=\$listing->limitSelect(\$$emulate)?><br/>\n";

			$output .= $this->Form->submit('OK');
			$code .= "\t\t" . '<?=$form->submit(\'OK\')?>' . "\n";

			$output .= $this->formEnd($listing);
			$code .= "\t<?=\$listing->formEnd(\$$emulate)?>\n";

			$output .= '</fieldset>';
			$code .= "</fieldset>\n\n";
		}


		// Results (and sorting)
		$output .= '<fieldset><legend>Results</legend><table><tr>';
		$code .= "<fieldset><legend>Results</legend>\n\t<table>\n\t\t<tr>\n";

		foreach ($listing['schema'] as $item)
		{
			$output .= '<th>' . $this->sortLink($listing, $item) . '</th>';
			$code .= "\t\t\t<th><?=\$listing->sortLink(\$$emulate, \$item)?></th>\n";
		}

		$output .= '</tr>';
		$code .= "\t\t</tr>\n";

		foreach ($listing['data'] as $row)
		{
			$output .= '<tr>';
			foreach ($row[$listing['modelName']] as $column)
				$output .= "<td>$column</td>";
			$output .= '</tr>';
		}

		$code .= "\t\t<?foreach(\${$emulate}['data'] as \$item):?>\n";
		$code .= "\t\t\t<tr>\n";

		foreach ($listing['schema']	as $item)
		{
			$exploded = explode('.', $item);
			$code .= "\t\t\t\t<td><?=\$item['{$exploded[0]}']['{$exploded[1]}']?></td>\n";
		}

		$code .= "\t\t\t</tr>\n";
		$code .= "\t\t<?endforeach?>\n";

		$output .= '</table></fieldset>';
		$code .= "\t</table>\n</fieldset>\n\n";


		// Pagination
		$output .= '<fieldset><legend>Pagination</legend>';
		$code .= "<fieldset><legend>Pagination</legend>\n";

		$output .= $this->pagination($listing);
		$code .= "\t<?=\$listing->pagination(\$$emulate)?>\n";

		$output .= '</fieldset>';
		$code .= "</fieldset>\n\n";

		$code .= '</div>';

		// Code
		$output .= '<fieldset><legend>Code</legend><textarea>';
		$output .= $code;
		$output .= '</textarea></fieldset>';

		$output .= '</div>';

		return $output;
	}


	/**
	 * Just returns useful styles for scaffold
	 *
	 * @return string
	 */
	private function scaffoldStyles ()
	{
		return "
			<style>
			div.listingScaffold th a { color: white; }
			div.listingScaffold table { border-collapse: collapse; width: 100%; }
			div.listingScaffold table th { background: black; color: white; }
			div.listingScaffold table td { border: 1px solid black; }
			div.listingScaffold textarea { width: 100%; height: 500px; background: black; color: white; }
			</style>
		";
	}
}
