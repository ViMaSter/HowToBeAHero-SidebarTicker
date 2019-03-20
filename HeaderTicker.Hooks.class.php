<?php

namespace HeaderTicker {
	class Hooks
	{
		// Borrowed from https://github.com/wikimedia/mediawiki-extensions-ParserFunctions/blob/cf1480cb9629514dd4400b1b83283ae6c83ff163/includes/ExtParserFunctions.php#L314
		public static function pageExists(string $titleText, \Title $title)
		{
			global $wgContLang;
			$wgContLang->findVariantLink( $titletext, $title, true );
			if ( $title )
			{
					if ( $title->getNamespace() === NS_SPECIAL )
					{
						return \SpecialPageFactory::exists( $title->getDBkey() ) ? true : false;
					}
					elseif ( $title->isExternal() )
					{
						return false;
					}
					else
					{
						$pdbk = $title->getPrefixedDBkey();
						$lc = \LinkCache::singleton();
						$id = $lc->getGoodLinkID( $pdbk );
						if ( $id !== 0 )
						{
							return true;
						}
						elseif ( $lc->isBadLink( $pdbk ) )
						{
							return false;
						}
						$id = $title->getArticleID();

						if ( $title->exists() )
						{
							return true;
						}
					}
			}
			return false;
		}

		public static function onSkinTemplateOutputPageBeforeExec( &$skin, &$template )
		{
			$tickerTitle = "HeaderTicker";
			$title = \Title::newFromText( $tickerTitle );

			if (!$title || !Hooks::pageExists($tickerTitle, $title))
			{
				return true;
			}

			global $wgParser;
			$backupParser = $wgParser;
			$wgParser = new \Parser();

			$apiRequest = new \FauxRequest( array(
				'action' => 'parse',
				'page' => $tickerTitle
			) );
			
			$context = new \DerivativeContext( new \RequestContext() );
			$context->setRequest( $apiRequest );
			$api = new \ApiMain( $context, true );
			$api->execute();
			$result = $api->getResult();

			$wgParser = $backupParser;

			ob_start();
?>
<style type="text/css">
.marqueeContainer
{
    position: relative;

    width: calc(100% - 1105px);
    height: 2.9em;
    
    margin-top: 2.1em;
    float:right;
    overflow: hidden;
}

.marqueeContainer p,
.marqueeContainer div
{
    width: max-content;
}

.marqueeContainer .content
{
    position: absolute;
    height: 100%;

    margin: 0;

    line-height: 50px;
    text-align: center;
    -moz-transform:translateX(100%);
    -webkit-transform:translateX(100%);
    transform:translateX(100%);
    -moz-animation: marquee 15s linear infinite;
    -webkit-animation: marquee 15s linear infinite;
    animation: marquee 15s linear infinite;
}

.marqueeContainer .content .entry
{
    display: inline-block;
}

.marqueeContainer .content .entry:first-child::before,
.marqueeContainer .content .entry::after
{
    content: ' +++ ';
    display: inline-block;
}

@-moz-keyframes marquee {
0%   { -moz-transform: translateX(0%); }
100% { -moz-transform: translateX(-100%); }
}

@-webkit-keyframes marquee {
0%   { -webkit-transform: translateX(0%); }
100% { -webkit-transform: translateX(-100%); }
}

@keyframes marquee {
0%   { 
-moz-transform: translateX(100%); /* Firefox bug fix */
-webkit-transform: translateX(100%); /* Firefox bug fix */
transform: translateX(100%); 		
}
100% { 
-moz-transform: translateX(-100%); /* Firefox bug fix */
-webkit-transform: translateX(-100%); /* Firefox bug fix */
transform: translateX(-100%); 
}
}
</style>
<?php
			$format = ob_get_contents();

ob_start();
?>
<div class="marqueeContainer">
    <div class="content">
        %s
    </div>
</div>
<?php
			$content = ob_get_contents();

			$template->data['headerTickerContent'] = $format . sprintf($content, $result->getResultData()["parse"]["text"]);
			return true;
		}
	}
}