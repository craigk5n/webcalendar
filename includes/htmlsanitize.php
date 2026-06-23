<?php
/**
 * Dependency-free server-side HTML sanitizer for WebCalendar rich-text fields.
 *
 * When ALLOW_HTML_DESCRIPTION is enabled, WebCalendar stores the raw HTML the
 * TinyMCE editor produces and used to render it verbatim. The client editor
 * cannot be trusted — a crafted POST bypasses it entirely — so the stored HTML
 * MUST be sanitized server-side before it is rendered. Without this, a value
 * such as <img src=x onerror=alert(1)>, <svg onload=...>, or
 * <a href="javascript:..."> is stored XSS (finding XSS-3).
 *
 * Approach: parse the fragment with PHP's built-in DOM extension, walk the
 * tree keeping only an allow-list of tags and attributes, validate URL schemes
 * on href/src, and drop everything else. Disallowed *dangerous* elements
 * (script/iframe/style/svg/...) are removed together with their contents;
 * other unknown elements are unwrapped so their text survives. This is an
 * allow-list (default-deny) control, not a block-list.
 *
 * @package WebCalendar
 */

/**
 * tag => allowed attributes. Any tag not listed is either dangerous (removed
 * with content, see webcal_sanitize_dangerous_tags) or unwrapped.
 */
function webcal_sanitize_allowed_tags() {
  return [
    'a'          => ['href', 'title', 'name', 'target'],
    'abbr'       => ['title'],
    'b'          => [], 'strong' => [], 'i' => [], 'em' => [], 'u' => [],
    's'          => [], 'strike' => [], 'del' => [], 'ins' => [], 'mark' => [],
    'sub'        => [], 'sup' => [], 'small' => [], 'big' => [], 'tt' => [],
    'p'          => [], 'br' => [], 'hr' => [],
    'span'       => [], 'div' => [],
    'blockquote' => ['cite'], 'pre' => [], 'code' => [], 'kbd' => [], 'samp' => [],
    'ul'         => [], 'ol' => ['start'], 'li' => [], 'dl' => [], 'dt' => [], 'dd' => [],
    'h1'         => [], 'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [],
    'table'      => [], 'thead' => [], 'tbody' => [], 'tfoot' => [], 'caption' => [],
    'tr'         => [], 'td' => ['colspan', 'rowspan'],
    'th'         => ['colspan', 'rowspan', 'scope'],
    'img'        => ['src', 'alt', 'title', 'width', 'height'],
    'figure'     => [], 'figcaption' => [],
  ];
}

/**
 * Elements removed together with all their contents. These can execute script
 * or load active/external content, so their text must NOT be preserved.
 */
function webcal_sanitize_dangerous_tags() {
  return array_flip([
    'script', 'style', 'iframe', 'object', 'embed', 'applet', 'form', 'input',
    'button', 'textarea', 'select', 'option', 'optgroup', 'label', 'fieldset',
    'legend', 'link', 'meta', 'base', 'title', 'head', 'body', 'html', 'frame',
    'frameset', 'noscript', 'noembed', 'svg', 'math', 'template', 'xml',
    'marquee', 'audio', 'video', 'source', 'track', 'canvas', 'map', 'area',
  ]);
}

/**
 * Returns true if a URL is safe to keep in an href/src attribute.
 * Allows relative URLs/anchors and the http(s)/mailto/ftp(s) schemes; blocks
 * javascript:, data:, vbscript:, etc. Obfuscation via embedded control
 * characters/whitespace is stripped before the scheme is examined.
 */
function webcal_sanitize_url_ok($url) {
  $url = trim((string) $url);
  if ($url === '') {
    return false;
  }
  // Relative path, query, or in-page anchor.
  if ($url[0] === '/' || $url[0] === '#' || $url[0] === '?') {
    return true;
  }
  // Remove control chars / whitespace that can hide a scheme
  // (e.g. "java\tscript:" or "java\nscript:").
  $stripped = preg_replace('/[\x00-\x20]+/', '', $url);
  if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $stripped, $m)) {
    return in_array(strtolower($m[1]),
      ['http', 'https', 'mailto', 'ftp', 'ftps'], true);
  }
  // No scheme and not starting with a special char => relative reference.
  return true;
}

/**
 * Sanitize a rich-text HTML fragment, returning safe HTML.
 *
 * @param string $html Untrusted HTML (e.g. a stored event description).
 * @return string Sanitized HTML safe to emit into a page.
 */
function sanitize_html($html) {
  if ($html === null || $html === '') {
    return '';
  }
  // Fail closed if the DOM extension is somehow unavailable.
  if (!class_exists('DOMDocument')) {
    return htmlspecialchars((string) $html, ENT_QUOTES);
  }

  $allowed   = webcal_sanitize_allowed_tags();
  $dangerous = webcal_sanitize_dangerous_tags();

  $dom  = new DOMDocument('1.0', 'UTF-8');
  $prev = libxml_use_internal_errors(true);
  // The XML encoding prolog forces loadHTML to treat the input as UTF-8.
  // A known wrapper element lets us extract just the sanitized fragment.
  $wrapped = '<?xml encoding="UTF-8"?><div>' . $html . '</div>';
  $loaded = $dom->loadHTML($wrapped, LIBXML_NONET);
  libxml_clear_errors();
  libxml_use_internal_errors($prev);
  if (!$loaded) {
    return htmlspecialchars((string) $html, ENT_QUOTES);
  }

  // The wrapper is the first <div> in document order (loadHTML nests it under
  // html > body). Operate on its children.
  $divs = $dom->getElementsByTagName('div');
  $root = $divs->length ? $divs->item(0) : null;
  if (!$root) {
    return '';
  }

  webcal_sanitize_walk($root, $allowed, $dangerous);

  $out = '';
  foreach (iterator_to_array($root->childNodes) as $child) {
    $out .= $dom->saveHTML($child);
  }
  return $out;
}

/**
 * Recursively sanitize the children of $node in place.
 */
function webcal_sanitize_walk($node, $allowed, $dangerous) {
  // Snapshot children because we mutate the tree while iterating.
  foreach (iterator_to_array($node->childNodes) as $child) {
    if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
      // Comments can carry conditional-comment / mXSS tricks.
      $child->parentNode->removeChild($child);
      continue;
    }
    if ($child->nodeType !== XML_ELEMENT_NODE) {
      // Text/CDATA nodes are kept; the DOM re-encodes them safely on output.
      continue;
    }

    $tag = strtolower($child->nodeName);

    if (isset($dangerous[$tag])) {
      $child->parentNode->removeChild($child);
      continue;
    }

    if (!isset($allowed[$tag])) {
      // Unknown, non-dangerous tag: sanitize its children, then unwrap it.
      webcal_sanitize_walk($child, $allowed, $dangerous);
      webcal_sanitize_unwrap($child);
      continue;
    }

    // Allowed tag: drop every attribute not explicitly allowed (this removes
    // all on* event handlers, style, etc.), and validate URL attributes.
    $allowedAttrs = $allowed[$tag];
    if ($child->hasAttributes()) {
      foreach (iterator_to_array($child->attributes) as $attr) {
        $aname = strtolower($attr->nodeName);
        if (!in_array($aname, $allowedAttrs, true)) {
          $child->removeAttribute($attr->nodeName);
          continue;
        }
        if (($aname === 'href' || $aname === 'src')
            && !webcal_sanitize_url_ok($attr->nodeValue)) {
          $child->removeAttribute($attr->nodeName);
        }
      }
    }

    webcal_sanitize_walk($child, $allowed, $dangerous);
  }
}

/**
 * Replace an element with its children (preserving their order), removing the
 * element itself.
 */
function webcal_sanitize_unwrap($element) {
  $parent = $element->parentNode;
  if (!$parent) {
    return;
  }
  while ($element->firstChild) {
    $parent->insertBefore($element->firstChild, $element);
  }
  $parent->removeChild($element);
}
