document.observe('dom:loaded', function(){
  $$('.feed2js').each(function(elm){
    var params = {
      'src': elm.readAttribute('data-src'),
      'num': elm.readAttribute('data-num'),
      'desc': elm.readAttribute('data-desc'),
      'date': elm.readAttribute('data-date'),
      'tz': elm.readAttribute('data-tz'),
      'utf': elm.readAttribute('data-utf')
    };
    new Ajax.Request('feed2js.php', {
      parameters: params,
      method: 'get',
      onCreate: function(){ elm.addClassName('loading'); },
      onSuccess: function(transport){
        var src = transport.responseText.gsub(/[\r\n]+/, '').gsub(/\\'/,'’');
        if(src.slice(0,5) == '<?php') src = 'You need a PHP server to use this tool.'
        elm.update(src);
        elm.removeClassName('loading');
      }
    });
  });
});