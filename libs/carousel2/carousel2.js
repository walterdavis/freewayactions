document.observe('dom:loaded', function(){
  var carousel2version = '0.1.8';
  var links = $$('a[href*="#"]').select(function(elm){
    var target = $(elm.href.split('#').last());
    if(target && (target.hasClassName('carousel_master') || target.hasClassName('carousel_pane'))){
      return true;
    }
  });
  function moveTo(elm, target, dur, callback){
    var vertical = elm.up('div.vertical'), first = elm.down();
    var move = vertical ? 'top' : 'left';
    elm.links.invoke('removeClassName', 'active');
    if(target.hasClassName('dup')){
      first.links.invoke('addClassName','active');
    }else{
      target.links.invoke('addClassName','active');
    }
    elm['current'] = target;
    elm.morph(move + ': -' + target.positionedOffset()[move] + 'px', {
      duration: dur,
      afterFinish: function(){
        elm.moving = false;
        if(target.hasClassName('dup')){
          elm.setStyle(move + ': 0');
          elm['current'] = first;
        }
        if(!!callback){
          callback();
        }
      }
    });
  };
  Element.addMethods({
    cloneStyle: function(element, source){
      var element = $(element), source = $(source);
      $A(document.defaultView.getComputedStyle(source, null)).each(function(s){
        var s = s.toString();
        element.setStyle(s + ':' + source.getStyle(s));
      });
      return element;
    }
  });
  $$('.carousel_master').each(function(elm){
    var vertical = elm.hasClassName('vertical');
    var infinite = elm.hasClassName('infinite');
    var interval = elm.readAttribute('data-auto') || 0;
    var duration = elm.readAttribute('data-duration') || 0.4;
    var tweakBorders = elm.readAttribute('data-borders') || false;
    var wrap = elm.wrap('div');
    if(vertical) wrap.addClassName('vertical')
    var slider = new Element('div');
    slider['current'] = elm;
    slider['moving'] = false;
    slider['links'] = [];
    var borderStyle = (tweakBorders) ? elm.getStyle('border') : 'none';
    if(tweakBorders) elm.setStyle('border: none');
    var positioned = (elm.up('div').id == 'PageDiv' && (elm.getStyle('top') || elm.getStyle('left') || elm.getStyle('bottom') || elm.getStyle('right'))) ? true : false;
    if(positioned){
      wrap.clonePosition(elm, {offsetLeft: - $('PageDiv').cumulativeOffset()['left']});
    }else{
      wrap.clonePosition(elm, {setLeft: false, setTop: false});
    }
    wrap.setStyle({cssFloat: elm.getStyle('float'), clear: elm.getStyle('clear'), zIndex: elm.getStyle('z-index'), right: elm.getStyle('right'), top: elm.getStyle('top'), left: elm.getStyle('left'), bottom: elm.getStyle('bottom'), position: elm.getStyle('position'), margin: elm.getStyle('margin'), overflow: 'hidden', border: borderStyle});
    wrap.insert(slider);
    slider.setStyle({position: 'absolute', overflow: 'hidden', top: 0, left: 0, width: wrap.getWidth() + 'px', height: wrap.getHeight() + 'px'});
    var d = 0;
    var panes = [elm].concat($$('div.' + elm.id));
    if(infinite){
      slider['dup'] = elm.clone(true);
      slider.dup.addClassName('dup');
      slider.dup.writeAttribute('id', null);
      slider.dup.cloneStyle(elm);
      $$('body').first().insert(slider.dup);
      panes.push(slider.dup);
    }
    panes.each(function(el){
      slider.insert(el.remove());
      el.setStyle({position: 'relative', top: 'auto', left: 'auto', right: 'auto', bottom: 'auto', margin: 0, zIndex: 'auto', display: 'block', clear: 'none', cssFloat: 'none'});
      if(tweakBorders) el.setStyle('border: none');
      if(!vertical) el.setStyle({float: 'left'});
      d += (vertical) ? el.getHeight() : el.getWidth();
      el['links'] = links.select(function(link){
        var re = new RegExp('#' + el.id + '$');
        return el.id && link.href.match(re);
      });
      el.links.each(function(link){
        slider.links.push(link);
        link.observe('click', function(evt){
          evt.stop();
          if(slider.auto) clearTimeout(slider.auto);
          moveTo(slider, el, duration)
        });
      });
    });
    if(vertical){
      slider.setStyle({height: d + 'px'});
    }else{
      slider.setStyle({width: d + 'px'});
    }
    slider.down().links.invoke('addClassName','active');
    if(interval > 0){
      if(interval < duration) interval = duration;
      interval = interval * 1000;
      var autoGlide = function(){
        slider.moving = true;
        var nextPane = $(slider.current).next() || slider.down();
        moveTo(slider, nextPane, duration, function(){ if(slider.moving == false) slider['auto'] = setTimeout(autoGlide, interval); });
      };
      if(slider.moving == false) slider['auto'] = setTimeout(autoGlide, interval);
    }
    $$('a.' + elm.id).each(function(elm){
      var move = vertical ? 'top' : 'left';
      if(elm.hasClassName('next')){
        elm.observe('click', function(evt){
          evt.stop();
          if(slider.auto) clearTimeout(slider.auto);
          var nextPane = $(slider.current).next() || slider.down();
          moveTo(slider, nextPane, duration);
        });
      }
      if(elm.hasClassName('previous')){
        elm.observe('click', function(evt){
          evt.stop();
          if(slider.auto) clearTimeout(slider.auto);
          if(slider.current == slider.down() && infinite){
            slider.setStyle(move + ': -' + slider.dup.positionedOffset()[move] + 'px');
            slider.current = slider.dup;
            var prevPane = slider.childElements().last().previous();
          }else{
            var prevPane = slider.current.previous() || slider.childElements().last();
          }
          moveTo(slider, prevPane, duration);
        });
      }
    });
  });
});
