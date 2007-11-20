{$doctype}
{if $disableAJAX eq false }
  <script type="text/javascript" src="includes/js/prototype.js"></script>
  <script type="text/javascript" src="includes/js/prototip.js"></script>
{/if}
{if $menu_enabled }
  <script type="text/javascript" src="includes/js/JSCookMenu.js"></script>
{/if}
{if $disableUTIL eq false }
  <script type="text/javascript" src="includes/js/util.js"></script>
{/if}
{section name=inc loop=$jsincludes}
  <script type="text/javascript" src="includes/js/{$jsincludes[inc]}"></script>
{/section} 
{if $smarty.const.CALTYPE}
  <script type="text/javascript">
    initEntries( '{$WC->userLoginId()}', '{$smarty.const.CALTYPE}', '{$WC->getDate()}', '{$WC->catId()}' );
  </script>
{/if}        
{$HeadX}

{if $disableStyle eq false}
  <link rel="stylesheet" type="text/css" href="includes/styles.css" />
  <link rel="stylesheet" title="{$smarty.const._WC_SCRIPT}" type="text/css" href="{$smarty.const._WC_PUB_CACHE}/css/{$cachedCSS}" />
{/if} 
{$css_template}
{if $WC->friendly() }
  <link rel="stylesheet" type="text/css" media="print" href="includes/print_styles.css" />
{/if}
{if $rss_enabled }
  <link rel="alternate" type="application/rss+xml" title="{$appStr} [RSS 2.0]" href="rss.php?user={$WC->loginId()}" />
{/if}
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
{$menuScript}
  </head>
  <body {$direction} id="{$smarty.const._WC_BODY_ID}" {$BodyX}>
{if $menu_enabled && $menu_above}
  {include file="menu.tpl"}
{/if}
{$header_template}
{if $menu_enabled && $menu_above eq false}
  {include file="menu.tpl"}
{/if}


