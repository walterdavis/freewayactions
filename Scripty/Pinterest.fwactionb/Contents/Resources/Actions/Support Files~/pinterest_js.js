document.observe('dom:loaded', function(){
	$$('.pin-it-btn').invoke('observe','click', function(evt){
		var pin = new Element('script',{
			type: 'text/javascript',
			charset: 'UTF-8',
			src: 'http://assets.pinterest.com/js/pinmarklet.js?r=' + Math.random()*99999999
		});
		this.insert({after: pin});
	});
});