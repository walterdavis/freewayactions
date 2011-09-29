<?php
class Feed{
	var $xml_parser;
	var $rss_content;
	var $currentTag = '';
	var $currentAttribs = '';
	var $depth = 0;
	var $tag;
	var $item_counter = 0;
	var $max_items = 0;
	var $item_template = '';
	var $item_parts = array();
	var $cache_dir = '_cache';

	function Read($file) {
		$this->xml_parser = xml_parser_create('UTF-8');
		xml_set_object($this->xml_parser, $this);
		xml_set_element_handler($this->xml_parser, 'startElement', 'endElement');
		xml_set_character_data_handler($this->xml_parser, 'cData');
		if($this->cache_time > 0) {
			$usedcache = false;
			$cache_file = $this->cache_dir . '/rsscache_' . md5($file);
			if (file_exists($cache_file)) {
				$timedif = @(time() - filemtime($cache_file));
				if ($timedif < ($this->cache_time * 60)) {
					$file = $cache_file;
					$usedcache = true;
				} 
			}
		}
		if (!($fp = fopen($file, "r"))) {
			die("could not open XML input");
		}
		
		$read = '';
		while ($data = fread($fp, 4096)) {
			$data = preg_replace('/<\/?atom:/','<',$data);
			if (!xml_parse($this->xml_parser, $data, feof($fp))) {
				die(sprintf("XML error: %s at line %d",
							xml_error_string(xml_get_error_code($this->xml_parser)),
							xml_get_current_line_number($this->xml_parser)));
			}
			$read .= $data;
		}
		xml_parser_free($this->xml_parser);
		if($this->cache_time > 0) {
			if (!$usedcache) {
				if (file_exists($cache_file)) { unlink($cache_file);	}
				$f = @fopen($cache_file, 'w');
				fwrite ($f, $read);
				fclose($f);
			}
		}
		echo '<ul class="feedlist">';
		$i = 0;
		$max = ($this->max_items > 0) ? $this->max_items : count($this->rss_content['items']);
		$this->item_parts = array_flip(array_map('strtolower',$this->w('item_parts')));
		if(is_array($this->rss_content['items'])){
			foreach ($this->rss_content['items'] as $content) {
				foreach($this->item_parts as $k=>$v){
					$this->item_parts[$k] = $content[$k];
				}
				if($i < $max){
					echo '<li>' . vsprintf($this->item_template,$this->item_parts) . '</li>';
					$i++;
				}
			}
		}
		echo '</ul>';
	}

	function w($strKey){
		return preg_split('/[\s,\b]+/',$this->$strKey,-1,PREG_SPLIT_NO_EMPTY);
	}

	function startElement($parser, $name, $attrs) {
		$name = strtolower($name);
		switch($name) {
   		case "rss":
   		case "rdf:rdf":
   		case "items":
   			$this->currentTag = "";
   			break;
   		case "channel":
   			$this->tag = "channel";
   			break;
   		case "image":
   			$this->tag = "image";
   			$this->rss_content["image"] = array();
   			break;
   		case "item":
   			$this->tag = "items";
   			break;
   		default:
   			$this->currentTag = $name;
   			break;
   		}
	}

	function endElement($parser, $name) {
		$name = strtolower($name);
		$this->currentTag = "";
        if ($name == "item") {
   			$this->item_counter++;
   		}
	}

	function cData($parser, $data) {
		if ($this->currentTag != "") {
			switch($this->tag) {
				case "channel":
					if (isset($this->rss_content[$this->currentTag])) {
						$this->rss_content[$this->currentTag] .= $data;
					} else {
						$this->rss_content[$this->currentTag] = $data;
					}
					break;
				case "image":
					if (isset($this->rss_content[$this->tag][$this->currentTag])) {
						$this->rss_content[$this->tag][$this->currentTag] .= $data;
					} else {
						$this->rss_content[$this->tag][$this->currentTag] = $data;
					}
					break;
				case "items":
					if (isset($this->rss_content[$this->tag][$this->item_counter][$this->currentTag])) {
						$this->rss_content[$this->tag][$this->item_counter][$this->currentTag] .= $data;
					} else {
						$this->rss_content[$this->tag][$this->item_counter][$this->currentTag] = $data;
					}
					break;
			}
		}
	}
	
}
?>