<?php
class LW_XML
{
	var $parser; #a reference to the XML parser
	var $document; #the entire XML structure built up so far
	var $current; #a pointer to the current item - what is this
	var $parent; #a pointer to the current parent - the parent will be an array
	var $parents; #an array of the most recent parent at each level

	var $last_opened_tag;

	function LW_XML($data=null)
	{
		$this->parser = xml_parser_create();

		xml_parser_set_option ($this->parser, XML_OPTION_CASE_FOLDING, 0);
		xml_set_object($this->parser, $this);
		xml_set_element_handler($this->parser, "open", "close");
		xml_set_character_data_handler($this->parser, "data");
		register_shutdown_function(array(&$this, 'destruct'));
	}

	function destruct()
	{
		xml_parser_free($this->parser);
	}

	function parse($data)
	{
		$this->document = array();
		$this->parent = &$this->document;
		$this->parents = array();
		$this->last_opened_tag = NULL;
		xml_parse($this->parser, $data);
		return $this->document;
	}

	function open($parser, $tag, $attributes)
	{
		$this->data = '';
		$this->last_opened_tag = $tag;

		if(array_key_exists($tag, $this->parent))
		{
			if(is_array($this->parent[$tag]) and array_key_exists(0, $this->parent[$tag]))
			{
				$key = LW_XML::count_numeric_items($this->parent[$tag]);
			}
			else
			{
				#echo "There is only one instance. Shifting everything around<br>\n";
				$temp = &$this->parent[$tag];
				unset($this->parent[$tag]);
				$this->parent[$tag][0] = &$temp;
				$key = 1;
			}

			$this->parent = &$this->parent[$tag];
		}
		else
		{
			$key = $tag;
		}

		$this->parent[$key] = array();

		if($attributes)
		{
			$this->parent["$key"] = $attributes;
		}

		$this->parent = &$this->parent[$key];
		$this->array_unshift_ref($this->parents, $this->parent);
	}

	function data($parser, $data)
	{
		if($this->last_opened_tag != NULL && !empty($data))
		{
			$this->data .= $data;
		}
	}

	function close($parser, $tag)
	{
		$temp = str_replace(array("\n","\t",' '),'',$this->data);

		if($this->last_opened_tag == $tag && !empty($temp))
		{
			$this->parent = $this->data;
			$this->last_opened_tag = NULL;
		}

		array_shift($this->parents);
		$this->parent = &$this->parents[0];
	}

	function array_unshift_ref(&$array, &$value)
	{
		$return = array_unshift($array,'');
		$array[0] =& $value;

		return $return;
	}

	function count_numeric_items($array)
	{
		return is_array($array) ? count(array_filter(array_keys($array), 'is_numeric')) : 0;
	}
}
?>