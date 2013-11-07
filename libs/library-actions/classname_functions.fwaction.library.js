<action-encoding>UTF-8</action-encoding>
<library-action name="classname_functions">
<action-javascript>
if(!'test'.strip) String.prototype.strip = function() {
  return this.replace(/^\s+/, '').replace(/\s+$/, '');
};

FWTag.prototype.hasClassName = function(className) {
  className = fwQuote(className,'','"'); //"
  var elementClassName = (this["class"]) ? fwQuote(this["class"],'','"') : ''; //"
  return ((elementClassName.length > 0) && (elementClassName == className || new RegExp("\\b" + className + "\\b").test(elementClassName)));
};

FWTag.prototype.addClassName = function(className) {
  className = fwQuote(className,'','"'); //"
  if (! this.hasClassName(className)){
    var elementClassName = (this["class"]) ? fwQuote(this["class"],'','"') : ''; //"
    var out = (elementClassName + ' ' + className).strip();
    return this["class"] = fwQuote(out);
  }
};

FWTag.prototype.removeClassName = function(className) {
  var className = fwQuote(className,'','"');//"
  var elementClassName = (this['class']) ? fwQuote(this['class'],'','"') : '';//"
  elementClassName = elementClassName.replace(
    new RegExp("(^|\\s+)" + className + "(\\s+|$)"), ' ').strip();
  return this['class'] = (elementClassName.length > 0) ? fwQuote(elementClassName) : null;
};
</action-javascript>
</library-action>