// $Id$

// Current Page Reference
// copyright Stephen Chapman, 1st Jan 2005
// you may copy this function but please keep the copyright notice with it
// http://javascript.about.com/od/guidesscriptindex/a/url.htm

var uri = getURL(this);

function getURL(uri) {
  uri.dir = uri.dom = location.href.substr(0, location.href.lastIndexOf('\/'));

  if (uri.dom.substr(0, 7) == 'http:\/\/') {
    uri.dom = uri.dom.substr(7);
  }
  uri.path = '';
  var pos = uri.dom.indexOf('\/');

  if (pos > -1) {
    uri.path = uri.dom.substr(pos + 1);
    uri.dom = uri.dom.substr(0, pos);
  }
  uri.page = location.href.substr(uri.dir.length + 1, location.href.length + 1);
  pos = uri.page.indexOf('?');

  if (pos > -1) {
    uri.args = uri.page.substr(pos + 1);
    uri.page = uri.page.substr(0, pos);
  }
  pos = uri.page.indexOf('#');

  if (pos > -1) {
    uri.page = uri.page.substr(0, pos);
  }
  uri.ext = '';
  pos = uri.page.indexOf('.');

  if (pos > -1) {
    uri.ext = uri.page.substr(pos + 1);
    uri.page = uri.page.substr(0, pos);
  }
  uri.file = uri.page;

  if (uri.ext != '') {
    uri.file += '.' + uri.ext;
  }
  if (uri.file == '') {
    uri.page = 'index';
  }
  return uri;
}
