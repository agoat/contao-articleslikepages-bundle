<?php
 
 /**
 * Contao Open Source CMS - SSL Domain extension
 *
 * Copyright (c) 2016 Arne Stappen (aGoat)
 *
 *
 * @package   ssldomain-bundle
 * @author    Arne Stappen <http://agoat.de>
 * @license	  LGPL-3.0+
 */


namespace Agoat\ArticleUrls;
 


class Controller extends \Contao\Controller
{
	
	// Search for articles
	public function getArticlesAsPages ($arrFragments)
	{
		// Find article for pages when the url scheme looks like domain.tld/page/article
		if ($arrFragments[1] == 'auto_item' && $arrFragments[2] != '')
		{
			list($strSection, $strArticle) = explode(':', $arrFragments[2]);
			
			if ($strArticle === null)
			{
				$strArticle = $strSection;
			}
			
			$objArticle = \ArticleModel::findPublishedByIdOrAliasAndPid($strArticle, false);

			if ($objArticle !== null)
			{
				$arrFragments[1] = 'articles';
			}
		}
		// Find article for root pages when the url scheme looks like domain.tld/article
		else if ($arrFragments[1] == '' && $arrFragments[2] == '' && \PageModel::findByIdOrAlias($arrFragments[0]) === null)
		{
			list($strSection, $strArticle) = explode(':', $arrFragments[2]);
			
			if ($strArticle === null)
			{
				$strArticle = $strSection;
			}
			
			$objArticle = \ArticleModel::findPublishedByIdOrAliasAndPid($strArticle, false);
			$objPage = \PageModel::findById($objArticle->pid);
			
			if ($objArticle !== null)
			{
				$arrFragments[2] = $arrFragments[0];
				$arrFragments[1] = 'articles';
				$arrFragments[0] = $objPage->alias;
			}
		}

		return $arrFragments;
	}

	// Remove the articles key word from the url
	public function stripArticlesParameter ($arrRow, $strParams, $strUrl)
	{
		return preg_replace('/(index\/)?articles\//', '', $strUrl);
	}

}

