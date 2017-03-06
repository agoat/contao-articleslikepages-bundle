<?php

 /**
 * Contao Extension - ArticleUrls
 *
 * Copyright (c) 2017 Arne Stappen (aGoat)
 *
 *
 * @package   articleurls-bundle
 * @author    Arne Stappen <http://agoat.de>
 * @license	  LGPL-3.0+
 */


// Additional save callback for the alias
$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['save_callback'][] = array('tl_page_articleurls', 'checkAlias');

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Arne Stappen (alias aGOAT) <https://github.com/agoat>
 */
class tl_page_articleurls extends Backend
{
	/**
	 * Check if the article alias exists as page alias
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return string
	 *
	 * @throws Exception
	 */
	public function checkAlias($varValue, DataContainer $dc)
	{
		$db = Database::getInstance();
		
		$objAlias = $db->prepare("SELECT id FROM tl_article WHERE alias=?")
					   ->execute($varValue);

		// Check whether the page alias exists
		if ($objAlias->numRows > 0)
		{
			if ($varValue == StringUtil::generateAlias($dc->activeRecord->title))
			{
				$varValue .= '-' . $dc->id;
			}
			else if ($varValue == StringUtil::generateAlias($dc->activeRecord->title.'-'.$dc->activeRecord->id))
			{
				$varValue = 'page-' . $varValue;
			}
			else
			{
				throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));				
			}
		}
		
		return $varValue;
	}
}
