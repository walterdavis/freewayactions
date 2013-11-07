<action-encoding>UTF-8</action-encoding>
<library-action name="style_accessors">
<action-version version="1.2.6">
Style Accessors

Get and set styles on any element, regardless where they were initially defined.

MIT License
Copyright (c) 2013 Walter Lee Davis
</action-version>

<action-javascript>
/**
* Does this page use a root-based Resources folder?
* @return boolean
*/
if(undefined == FWPage.hasRootResourcesFolder){
  FWPage.prototype.hasRootResourcesFolder = function(){
    var head = fwDocument.fwTags.fwFind('head');
    if(!head) fwAbort('Please call hasRootResourcesFolder from fwBeforeEndHead or later.');
    var links = head.fwFindAll('link', 'href');
    for(var i in links){
      if(links[i].href){
        var link = links[i].href.toString();
        var page = this.fwFileName.toString().split('.').shift();
        if(link.match('../css') && link.indexOf(page) > 0){
          return true;
        }
      }
    }
    return false;
  };
}
/**
* Does this page use a Resources folder?
* @return boolean
*/
if(undefined == FWPage.hasNoResourcesFolder){
  FWPage.prototype.hasNoResourcesFolder = function(){
    return (! fwClearGif.toString().match('Resources/'))
  };
}
/**
* Find the folder where the style should be saved
* @return string
*/
if(undefined == FWPage.styleFolder){
  FWPage.prototype.styleFolder = function(){
    var rootFolder = fwDocument.fwPages.fwItems[0].fwFolder.fwHttpPath();
    if(this.hasNoResourcesFolder()){
      return '/' + this.fwFolder.fwHttpPath();
    }
    if(this.hasRootResourcesFolder()){
      return rootFolder + '/css/';
    }
    return '/' + this.fwFolder.fwHttpPath() + 'css/';
  };
}
/**
* Define a URL for the override sheet for this page
* @return string relative URL to override sheet
*/
if(undefined == FWPage.styleOverrideURL){
  FWPage.prototype.styleOverrideURL = function(){
    var folder = this.styleFolder();
    return folder + this.basename() + '_override.css';
  };
}
/**
* Define a path to the override sheet for this page.
* @return string root-relative path to the override sheet
*/
if(undefined == FWPage.styleOverridePath){
  FWPage.prototype.styleOverridePath = function(){
    var rootFolder = fwDocument.fwPages.fwItems[0].fwFolder;
    if(!rootFolder) fwAbort('Could not locate the root folder. Make sure there is at least one page in it.')
    var rootFolderPath =  rootFolder.fwHttpPath(null, true);    
    return (rootFolderPath + this.styleOverrideURL()).replace(/\//g, ':');
  };
}
/**
* Basename for the override sheet
* @return string page filename without extension
*/
if(undefined == FWPage.basename){
  FWPage.prototype.basename = function(){
    var filename = this.fwFileName.toString().split('.');
    if(this.hasRootResourcesFolder()){
      filename = this.fwHttpPath().toString().replace(/\//g,'_').split('.');
    }
    filename.pop();
    return filename.join('.');
  };
}
/**
* Add a link to an override stylesheet, and create the file for that sheet.
* Only alters the page once per publish cycle.
* Must be called from fwBeforeEndHead() or later.
* @return link to sheet
*/
if(undefined == FWPage.addOverride){
  FWPage.prototype.addOverride = function(){
    if(this.overrideLink) return this.overrideLink;
    var head = fwDocument.fwTags.fwFind('head');
    if(!head) fwAbort('Please call addOverride from fwBeforeEndHead or later.')
    var lastLink = head.findLast('link');
    var link = head.fwAddOpt('link', lastLink);
    head.fwAddRawOpt('', lastLink);
    var myFile = new FWFile();
    myFile.fwOpenWrite(fwPage.styleOverridePath());
    myFile.fwWrite(fwPage.currentRules);
    myFile.fwClose();
    fwParameters['styleOverride'].fwSpecify(myFile);
    link.rel = fwQuote('stylesheet');
    link.href = fwQuote(fwParameters['styleOverride'].toString());
    fwParameters['styleOverride'].fwClear();
    link.type = fwQuote('text/css');
    this.overrideLink = link;
    return link;
  };
}
/**
* Erase or initialize the reset stylesheet.
* @return FWFile
*/
if(undefined == FWPage.resetOverride){
  FWPage.prototype.resetOverride = function(){
    if (this.overrideReset)
    return;
    var myFile = new FWFile();
    myFile.fwOpenWrite(this.styleOverridePath(), true);
    myFile.fwSetEncoding('UTF-8');
    myFile.fwWrite('');
    myFile.fwClose();
    this.overrideReset = true;
    return myFile;
  };
}
/**
* Announce our existence so we only publish a file from the last Action on the page
* Must be called in fwBeforeStartHTML()
* @void
*/
if(undefined == FWPage.announce){
  FWPage.prototype.announce = function(){
    if (!fwPage.style_accessors_use)
    fwPage.style_accessors_use = 1;
    else
    fwPage.style_accessors_use += 1;
  };
}
/**
* Renounce our existence so the last Action can run addOverride
* Must be called in fwAfterEndHTML()
* @void
*/
if(undefined == FWPage.renounce){
  FWPage.prototype.renounce = function(){
    fwPage.style_accessors_use -= 1;
    // We're the last one...
    if (fwPage.style_accessors_use == 0 && fwDocument.fwExternalStylesheets && fwPage.currentRules !== undefined)
    fwPage.addOverride();
  };
}
/**
* Convert a string of CSS into a hash.
* @return object JSON of styles
*/
if(undefined == cssToHash){
  var cssToHash = function(string){
    var out = {}, string = string.toString().replace(/^"\s*(.+?)\s*"$/, '$1');
  var pairs = string.split(/\s*;\s*/);
  for (var i in pairs){
  var pair = pairs[i].split(/\s*:\s*/);
  out[pair[0]] = pair[1];
};
return out;
};
}
/**
* Convert a JSON object of style definitions into CSS.
* @return string CSS style rules
*/
if(undefined == hashToCSS){
  var hashToCSS = function(hash){
    var out = [];
    for (var i in hash){
      var pair = i + ': ' + hash[i];
      out.push(pair);
    }
    return out.join('; ').toString();
  };
}
/**
* Get the CSS selector for a single tag.
* @return string
*/
if(undefined == FWTag.getSelector){
  FWTag.prototype.getSelector = function(){
    if(this.id){
      var id = '#' + this.id.toString().slice(1,-1);
      if(id.length > 1){
        return id + ', ' + id + '.f-ms';
      }
    }
    if(this['class']){
      classes = this['class'].toString().slice(1,-1).split(/\s+/);
      return '.' + classes.join('.');
    }
  };
}
if(undefined == FWTag.findLast){
  FWTag.prototype.findLast = function(type) {
    var els = this.fwFindAll(type);
    for(var i = els.length-1; i >= 0; i--)
    {
      if(els[i].fwEnclosing != "!--" && !lastEl)
      var lastEl = els[i];
    }
    return lastEl;
  }
}
/**
* Write CSS into the override sheet.
* @return void
*/
if(undefined == FWTag.setOverride){
  FWTag.prototype.setOverride = function(content){
    fwPage.currentRules = fwPage.currentRules || "";
    var overwrite = false;
    var selector = this.getSelector();
    if(new RegExp(selector).test(fwPage.currentRules)){
      var re = new RegExp(selector + ' \\{ (.+?) \\}');
      fwPage.currentRules = fwPage.currentRules.replace(re, content);
      overwrite = true;
    }else{
      fwPage.currentRules += content + "\n";
    }
    this.style = null;
  };
}
/**
* Read the content of the entire override sheet.
* @return object JSON of styles
*/
if(undefined == FWTag.getOverride){
  FWTag.prototype.getOverride = function(){
    var content = fwPage.currentRules || "";
    var re = new RegExp(this.getSelector() + ' \\{ (.+?) \\}');
    if( re.test(content) )
    return( cssToHash(content.match(re)[1]) );
    return {};
  };
}
/**
* Get one CSS attribute of a single tag.
* attributeName is an attribute (width, padding...) returns value (if set) or null.
* @return mixed
*/
if(undefined == FWTag.getStyle){
  FWTag.prototype.getStyle = function(attributeName){
    var styles = this.getAllStyles();
    return styles[attributeName] || null;
  };
}
/**
* Get override CSS attributes of a single tag, in format for writing to external stylesheet.
* attributeName is an attribute (width, padding...) returns value (if set) or false.
* @return string or false
*/
if(undefined == FWTag.getStyleAsRule){
  FWTag.prototype.getStyleAsRule = function(){
    if(this.style){
      return this.getSelector() + ' { ' + this.style.toString().slice(1,-1) + ' }';
    }else{
      return false;
    }
  };
}
/**
* Get all CSS attributes of a single tag, regardless where they were set.
* @return JSON object
*/
if(undefined == FWTag.getAllStyles){
  FWTag.prototype.getAllStyles = function(){
    if (this==null) return null;
    var styles = {}, overrides = {};
    if(fwPage.fwElementStyle){
      styles = cssToHash(fwPage.fwElementStyle(this));
    }
    if(fwDocument.fwExternalStylesheets){
      overrides = this.getOverride();
      for(var i in overrides){
        styles[i] = overrides[i];
      }
    }
    if(this.style){
      var inline = cssToHash(this.style);
      for (var i in inline) {
        styles[i] = inline[i];
      };
    }
    return styles;
  };
}
/**
* Sets a CSS attribute such as "position:absolute" in a tag value 
* Passing an attribute value of null resets that attribute to default
* @return void
*/
if(undefined == FWTag.setStyle){
  FWTag.prototype.setStyle = function(){
    if(this == null || arguments.length == 0) 
    return;
    var stylesObj = {};
    // If we have two strings, objectify them
    if(arguments.length == 2 && arguments[0].constructor == String)
    stylesObj[arguments[0]] = arguments[1];
    else
    stylesObj = arguments[0];
    for(var i in stylesObj) {
      var attributeName = i,
      value = stylesObj[i];
      if(typeof value == 'string' && value.toLowerCase() == 'null') 
      value = null;
      var styles = {}, external = {};
      var reset = { /* BACKGROUND */ 'background': 'none', 'background-attachment':
      'scroll', 'background-clip': 'border-box', 'background-color': 'inherit',
      'background-image': 'none', 'background-origin': 'padding-box',
      'background-position': '0% 0%', 'background-repeat': 'repeat', 'background-size':
      'auto', /* BORDERS */ 'border-collapse': 'separate', 'border': 'none',
      'border-width': '0', 'border-style': 'none', 'border-color': '#000000',
      'border-width': 'none', /* top */ 'border-top': 'none', 'border-top-color':
      '#000000', 'border-top-style': 'none', 'border-top-width': '0', /* right */
      'border-right': 'none', 'border-right-color': '#000000', 'border-right-style':
      'none', 'border-right-width': '0', /* bottom */ 'border-bottom': 'none',
      'border-bottom-color': '#000000', 'border-bottom-style': 'none',
      'border-bottom-width': '0', /* left */ 'border-left': 'none', 'border-left-color':
      '#000000', 'border-left-style': 'none', 'border-left-width': '0', /* border-image */
      'border-image': 'none', 'border-image-outset': '0', 'border-image-repeat': 'stretch',
      'border-image-slice': '100%', 'border-image-source': 'none', 'border-image-width':
      '1', /* border-radius */ 'border-radius': '0', 'border-top-left-radius': '0',
      'border-top-right-radius': '0', 'border-bottom-left-radius': '0',
      'border-bottom-right-radius': '0', /* BOX ATTRIBUTES */ 'height': 'auto',
      'max-height': 'none', 'min-height': '0', 'width': 'auto', 'max-width': 'none',
      'min-width': '0', 'position': 'static', 'display': 'block', 'visibility': 'visible',
      'top': 'auto', 'right': 'auto', 'bottom': 'auto', 'left': 'auto', 'float': 'none',
      'clear': 'none', 'margin': '0', 'margin-top': '0', 'margin-right': '0',
      'margin-bottom': '0', 'margin-left': '0', 'padding': '0', 'padding-top': '0',
      'padding-right': '0', 'padding-bottom': '0', 'padding-left': '0', 'opacity': '1',
      'overflow': 'visible', 'overflow-x': 'visible', 'overflow-y': 'visible', 'z-index':
      'auto', 'zoom': '1', 'filter': 'none', 'cursor': 'auto', 'box-shadow': 'none',
      'box-sizing': 'content-box', /* TEXT */ 'color': 'inherit', 'font': 'inherit',
      'font-family': 'inherit', 'font-size': '1em', 'font-style': 'inherit',
      'font-variant': 'inherit', 'font-weight': 'inherit', 'letter-spacing': 'inherit',
      'line-height': 'inherit', 'list-style-image': 'none', 'list-style-position':
      'outside', 'list-style-type': 'disc', 'list-style': 'disc', 'text-decoration':
      'none', 'text-indent': '0', 'text-shadow': 'none', 'vertical-align': 'baseline',
      'white-space': 'normal' 
    };
    if(fwPage.fwElementStyle){
      external = cssToHash(fwPage.fwElementStyle(this));
    }
    if(fwDocument.fwExternalStylesheets){
      styles = this.getOverride();
    }
    if(this.style){
      var inlines = cssToHash(this.style);
      for(var i in inlines){
        styles[i] = inlines[i];
      }
    }
    if(value == null){
      if(reset[attributeName] && external[attributeName]){
        styles[attributeName] = reset[attributeName];
      }else{
        delete styles[attributeName];
      }
    }else{
      styles[attributeName] = value;
    }
    this.style = fwQuote(hashToCSS(styles));
    if(fwDocument.fwExternalStylesheets){
      // write styles to override
      this.setOverride(this.getStyleAsRule());
      this.style = null;
    }
  }
};
}
</action-javascript>
</library-action>