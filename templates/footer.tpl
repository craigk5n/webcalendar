{if $include_nav_links && !$WC->friendly() && ! $menu_date_top }
  {date_selectors}
{/if}
  {$footer_template}
{if $s._DEMO_MODE }
  <p><a href="http://validator.w3.org/check?uri=referer">
    <img src="http://www.w3.org/Icons/valid-xhtml10" alt="Valid XHTML 1.0!" class="valid"  border="0" /></a>
  </p>
{/if}
</body>
</html>
{$WC->closeDb()}

