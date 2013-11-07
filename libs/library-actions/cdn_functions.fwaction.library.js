<action-encoding>UTF-8</action-encoding>
<library-action name="cdn_functions">
<action-javascript>
/**
 * Write a script to an external file.
 * name: common name of script (domloaded, windowload, custom)
 * fileParameter: the action-file that holds this file
 * content: string or array of strings containing the content
 * returns URL to generated file, for linking purposes
 */
var createExternalScript = function(name, fileParameter, content){
  if(!content.join){
    content = [content];
  }
  var myFile = new FWFile();
  if(fwParameters[fileParameter].fwHasFile){
    myFile.fwOpenWrite(fwParameters[fileParameter].fwValue, true, 'TEXT');
    myFile.fwSetEncoding('UTF-8');
  }else{
    var path = fwPage.fwHttpPath();
    path = path.replace(/\//g, '_').split('.');
    var filename = path.pop();
    path = path.join('.') + '_' + name + '.js';
    myFile.fwOpenWrite(path, true, 'TEXT');
    myFile.fwSetEncoding('UTF-8');
  }
  for (var i=0; i < content.length; i++) {
    myFile.fwWrite(content[i] + "\n");
  };
  myFile.fwClose();
  fwParameters[fileParameter].fwSpecify(myFile);
  var filePath = fwParameters[fileParameter].toString();
  fwParameters[fileParameter].fwClear();
  return filePath;
}

/**
* Create a CDN link to a JavaScript library. 
* name: common filename for the library (prototype, scriptaculous, jquery)
* path: (optional) fully-qualified URL to the CDN-hosted file
* (path is not needed if you are linking to prototype or scriptaculous)
* WARNING! changes any existing link in the page to this library to the 
* one specified in path or defaults
* returns reference to the script
*/
var findOrCreateScriptLink = function(name, path){
  var head = fwDocument.fwTags.fwFind('head');
  var script = pageHasLinkToScript(name), load = '';
  var libs = {
    'prototype': 'http://ajax.googleapis.com/ajax/libs/prototype/1.7/prototype.js',
    'scriptaculous': 'http://ajax.googleapis.com/ajax/libs/scriptaculous/1.9/scriptaculous.js'
  };
  if(!libs[name]){
    if(!!path){
      libs[name] = path;
    }else{
      fwAbort('Please provide a URL for “' + name + '”. Publishing cannot continue.');
    }
  }
  if(!script){
    script = head.fwAdd('script', true);
    script.fwAddRawOpt('');
    head.fwAddRawOpt('');
  }
  //catch any load variables from scriptaculous
  if(name == 'scriptaculous' && script.src && script.src.toString().match(/\?load=/)){
    load = script.src.toString().match(/(\?load=.+?)"/)[1]; //"
  }
  //overwrite the path to the script to make it current
  script.src = fwQuote(libs[name] + load);
  script.type = fwQuote('text/javascript');
  script.charset = fwQuote('utf-8');
  return script;
}

var pageHasLinkToScript = function(name){
  var script = false;
  var scripts = fwDocument.fwTags.fwFindAll('script');
  for(i in scripts){
    if(scripts[i].src && scripts[i].src.toString().match(new RegExp(name + '\.js'))){
      script = scripts[i];
    }
  }
  return script;
}

var findOrCreateStyleLink = function(name, path){
  var head = fwDocument.fwTags.fwFind('head');
  var styles = head.fwFindAll('link'), re = new RegExp(name + '\.css');
  var findStyleLink = function(re){
    for(i in styles){
      if(styles[i].href && styles[i].href.toString().match(re)){
        return styles[i];
      }
    }
  }
  var style = findStyleLink(re);
  if(!style){
    style = head.fwAdd('link', false);
    head.fwAddRawOpt('');
  }
  style.href = fwQuote(path);
  style.rel = fwQuote('stylesheet');
  style.type = fwQuote('text/css');
  style.charset = fwQuote('utf-8');
  return style;
}

/**
* Wrapper to simplify function call
* returns nothing
*/
var addPrototype = function(){
  findOrCreateScriptLink('prototype');
}

/**
* Add scriptaculous to the page, and load any modules needed if fewer than all.
* modules: comma-separated string or array of scriptaculous modules
* returns nothing
*/

var addScriptaculous = function(modules){
  var scriptaculousLibs = ["builder", "effects", "dragdrop", "controls", "slider", "sound"];
  var load = [];
  if(modules.join){
    modules = modules.join();
  }
  var script = findOrCreateScriptLink('scriptaculous');
  if(script.src.toString().match(/\?load=/)){
    modules += script.src.toString().split(/\?load=/)[1];
  }
  for (var i=0; i < scriptaculousLibs.length; i++) {
    var re = new RegExp(scriptaculousLibs[i]);
    if(modules.match(re))
    load.push(scriptaculousLibs[i]);
  };
  if(load.length > 0 && load.length < 6){
    load = '?load=' + load.join(',');
    script.src = script.src.toString().replace(/\?load=[^"]+/, '').replace(/"$/, load + '"'); //"
  }
}


</action-javascript>
</library-action>
