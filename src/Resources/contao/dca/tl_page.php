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

// Replace the generateArticle callback
foreach ($GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'] as &$callback)
{
	if ($callback[0] == 'tl_page' && $callback[1] == 'generateArticle')
	{
		$callback[0] = 'tl_page_articleurls';
	}
}
unset ($callback);

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
		$colArticles = \ArticleModel::findByAlias($varValue);

		// No article alias at all
		if (null === $colArticles)
		{
			return $varValue;
		}
		
		$objPage = \PageModel::findWithDetails($dc->id);

		if (null === $objPage)
		{
			return $varValue;
		}

		foreach ($colArticles as $objArticle)
		{
			$objArticlePage = \PageModel::findWithDetails($objArticle->pid);
	
			if (null !== $objArticlePage)
			{
				// Article alias exist in the same root
				if ($objArticlePage->rootId == $objPage->rootId)
				{
					if ($varValue == StringUtil::generateAlias($dc->activeRecord->title))
					{
						$varValue .= '-' . $dc->id;
					}
					else if ($varValue == StringUtil::generateAlias($dc->activeRecord->title.'-'.$dc->id))
					{
						$varValue = 'page-' . $varValue;
					}
					else
					{
						throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));				
					}				
				}
			}
		
		}
			
		return $varValue;
	}
	
	/**
	 * Automatically create an article in the main column of a new page
	 *
	 * @param DataContainer $dc
	 */
	public function generateArticle(DataContainer $dc)
	{
		// Return if there is no active record (override all)
		if (!$dc->activeRecord)
		{
			return;
		}

		// No title or not a regular page
		if ($dc->activeRecord->title == '' || !in_array($dc->activeRecord->type, array('regular', 'error_403', 'error_404')))
		{
			return;
		}

		/** @var Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface $objSessionBag */
		$objSessionBag = System::getContainer()->get('session')->getBag('contao_backend');

		$new_records = $objSessionBag->get('new_records');

		// Not a new page
		if (!$new_records || (is_array($new_records[$dc->table]) && !in_array($dc->id, $new_records[$dc->table])))
		{
			return;
		}

		// Check whether there are articles (e.g. on copied pages)
		$objTotal = $this->Database->prepare("SELECT COUNT(*) AS count FROM tl_article WHERE pid=?")
								   ->execute($dc->id);

		if ($objTotal->count > 0)
		{
			return;
		}

		$this->import('BackendUser', 'User');
	
		// Create article
		$arrSet['pid'] = $dc->id;
		$arrSet['sorting'] = 128;
		$arrSet['tstamp'] = time();
		$arrSet['author'] = $this->User->id;
		$arrSet['inColumn'] = 'main';
		$arrSet['title'] = $dc->activeRecord->title;
		$arrSet['alias'] = str_replace('/', '-', $dc->activeRecord->alias); // see #5168
		$arrSet['published'] = $dc->activeRecord->published;

		$this->Database->prepare("INSERT INTO tl_article %s")->set($arrSet)->execute();
		
		// Add id to article alias
		$objArticle = $this->Database->prepare("UPDATE tl_article SET alias=CONCAT_WS('-',alias,id) WHERE pid=? AND alias=?")
								   ->execute($dc->id, $arrSet['alias']);
		
	}
	
}
