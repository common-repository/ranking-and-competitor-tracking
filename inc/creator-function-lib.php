<?php
/**
 * Project: Hub5050 Ranking and Competitor Tracking (creator-function-lib.php)
 * Copyright: (C) 2011 Clinton
 * Developer:  Clinton [CreatorSEO]
 * Created on 10 March 2018
 *
 * Description: Utilities that will be used in a range of plugins from CreatorSEO hence the naming convention
 *
 * This program is the property of the developer and is not intended for distribution. However, if the
 * program is distributed for ANY reason whatsoever, this distribution is WITHOUT ANY WARRANTEE or even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 */

if (!function_exists('creator_color_picker')){
	/**
	 * Color picker for form
	 * @param string $name name of the color picker field
	 * @param string $color - chosen color
	 * @param string $default - default color
	 * @param string $class optional class name for the option field
	 * @param string $display - echo or return the html
	 * @return string - return sting if $display is false
	 */
	function creator_color_picker($name,$color,$default='#efefef',$class='',$display=true){
		$out = "";
		$cls = $class? " ".$class."": "";
		$color = preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $color )? $color: null;
		$default = preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $default )? $default: "#ff0000";
		if ($color){
			$out .= "<div class='creator-color-picker'>";
			$out .= "<input type='text' class='creator-color-pick".$cls."' name='".esc_attr($name)."' value='".esc_attr($color)."' data-default-color='".$default."'>";
			$out .= "</div>";
		} else {
			$out = "<p>ERROR: Color not recognized</p>";
		}
		if ($display) echo $out;
		else return $out;
	}
}


if (!function_exists('creator_input_field')){
    /**
     * Create an input field based on the type provided. This has the functionality needed to render Bootstrap
     * consistent controls.
     * @param array $def
     *  - type input field type (allowed types are 'text','number','range','date','datetime')
     *  - name is the name of the input field
     *  - value default value
     *  - min minimum value
     *  - max maximum value
     *  - label control label to display
     *  - placeholder (not used yet)
     *  - div_class optional class name for the encapsulating div (no div if missing)
     *  - control_class optional class name for the field control
     *  - label_class optional class name for the field label
     * @param string $display - echo or return the html
     * @return string - return sting if $display is false
     */
    function creator_input_field($def, $display=true){
        $out = '';
        $def = array_merge(array(
            'type'=>'text',
            'name'=>'',
            'value'=>'',
            'placeholder'=>'',
            'min'=>null,
            'max'=>null,
            'label'=>null,
            'div_class'=>'',
            'control_class'=>'',
            'label_class'=>''
        ),$def);
        $def['type'] = in_array($def['type'],array('text','number','range','date','datetime','password'))? $def['type']: 'text';
        if (strlen($def['name'])){
            $out .= strlen($def['div_class'])? '<div class="'.esc_attr($def['div_class']).'">': '';
            if (strlen($def['label'])){
                $out .= '<label for="'.esc_attr($def['name']).'" '.(strlen($def['label_class'])? 'class="'.esc_attr($def['label_class']).'"': '').' >'.
                        esc_html($def['label']).'</label>';
            }
            $out .='<input type="'.$def['type'].'" name="'.esc_attr($def['name']).'" id="'.esc_attr($def['name']).'" value="'.esc_attr($def['value']).'" ';
            $out .= strlen($def['control_class'])? ' class="'.$def['control_class'].'"': '';
            if (!is_null($def['min'])) {
                $out .= $def['type']=='text'? '':
                    ($def['type']=='date'? (' min="'.strval($def['min'].'"')): (' min="'.(int) $def['min'].'"'));
            }
            if (!is_null($def['max'])) {
                $out .= $def['type']=='text'? (' size="'.(int) $def['max'].'"'):
                    ($def['type']=='date'? (' max="'.strval($def['max'].'"')): (' max="'.(int) $def['max'].'"'));
            }
            $out .= ' />';
            $out .= strlen($def['div_class'])? '</div>': '';
        } else {
            $out .= 'ERROR: Control name not specified';
        }
        if ($display) echo $out;
        else return $out;
    }
}


if (!function_exists('creator_dynamic_select')){
    /**
     * Create a dynamic option list based on an option name, an array of elements and a default element
     * @param array $def
     *  - type input field type (allowed types are 'text','number','range','date','datetime')
     *  - name name of the input field
     *  - elements array list of elements to show
     *  - selected array of selected elements
     *  - min minimum value
     *  - max maximum value
     *  - label label to display
     *  - div_class optional class name for the encapsulating div (no div if missing)
     *  - control_class optional class name for the field control
     *  - label_class optional class name for the field label
     * @param string $display - echo or return the html
     * @return string - return sting if $display is false
     */
    function creator_dynamic_select($def,$display=true){
        $out = ''; $i=0;
        $def = array_merge(array(
            'type'=>'radio',
            'name'=>'',
            'elements'=>array(),
            'selected'=>array(),
            'label'=>null,
            'div_class'=>'',
            'control_class'=>'',
            'label_class'=>''
        ),$def);
        //creator_debug_log('INPUT CHECK', $def);
        $def['type'] = in_array($def['type'], array('checkbox', 'radio'))? $def['type']: 'radio';
        if (strlen($def['name'])){
            if (is_array($def['elements'])){
                foreach ($def['elements'] as $ix=>$emt){
                    $out .= strlen($def['div_class'])? '<div class="'.esc_attr($def['div_class']).'">': '';
                    $chk = is_array($def['selected'])? (in_array($emt,$def['selected'])? 'checked="checked"': ''):
                        ($emt==$def['selected']? 'checked="checked"': '');
                    $lbl = $def['type']=='radio'? esc_attr($def['name']): esc_attr($def['name']).'['.$i.']';
                    $id = $def['name'].'_'.str_pad($i,3, "0", STR_PAD_LEFT);
                    $out .= '<input type="'.$def['type'].'" name="'.$lbl.'" id="'.$id.'" value="'.$emt.'" ';
                    $out .= (strlen($def['control_class'])? 'class="'.esc_attr($def['control_class']).'"': '').' '.$chk.' />';
                    $out .= '<label for="'.$lbl.'" '.(strlen($def['label_class'])? 'class="'.esc_attr($def['label_class']).'"': '').'>'.esc_html($emt).'</label>';
                    //$out .= '<label for="'.$lbl.'" '.(strlen($def['label_class'])? 'class="'.esc_attr($def['label_class']).'"': '').'">'.esc_html($emt).'</label>';
                    $i++;
                    $out .= strlen($def['div_class'])? '</div>': '';
                }
            } else {
                $out = '<p>ERROR: Elements not defined for list</p>';
            }
        } else {
            $out .= 'ERROR: Control name not specified';
        }
        if ($display) echo $out;
        else return $out;
    }
}

if (!function_exists('creator_dynamic_select1')){
    function creator_dynamic_select1($type,$name,$elements,$selected=array(),$class='',$display=true){
        $out = ''; $i=0;
        $type = in_array($type, array('checkbox', 'radio'))? $type: 'radio';
        //$selected = (is_array($selected) && count($selected) && $type=='radio')? $selected[0]: $selected;
        $cls = strlen($class)? esc_attr($class): 'dicap';
        if (strlen($name)){
            if (is_array($elements)){
                foreach ($elements as $ix=>$emt){
                    $chk= in_array($emt,$selected)? 'checked="checked"': '';
                    $lbl = $type=='radio'? esc_attr($name): esc_attr($name).'['.$i.']';
                    $out .= '<div class="'.$cls.'">';
                    $out .= '<input type="'.$type.'" class="'.$cls.'-input" name="'.$lbl.'" id="'.$lbl.'" value="'.$emt.'" '.$chk.' />';
                    $out .= '<label for="'.$lbl.'" class="'.$cls.'-label">'.esc_html($emt).'</label>';
                    $out .= '</div>';
                    $i++;
                }
            } else {
                $out = '<p>ERROR: Elements not defined for list</p>';
            }
        } else {
            $out .= 'ERROR: Control name not specified';
        }
        if ($display) echo $out;
        else return $out;
    }
}

if (!function_exists('creator_dynamic_options')){
	/**
	 * Create a dynamic option list based on an option name, an array of elements and a default element. This includes
     * the option to use an index to identify the element selected i.e. array('ix1'=>'val1', 'ix2=>'val2).
	 * @param array $elements elements to display in the drop-down
	 * @param string $name name of the option field
	 * @param string $selected default or selected option (index value if $index_val is true)
	 * @param string $class optional class name for the option field
	 * @param string $display - echo or return the html
     * @param string $index_val - include an index as the value for the selected
	 * @return string - return sting if $display is false
	 */
	function creator_dynamic_options($elements,$name,$selected,$class='',$display=true,$index_val=false){
		$out = '';
		if (strlen($name)){
			if (is_array($elements)){
				$out .= '<select name="'.esc_attr($name).'"';
				$out .= strlen($class)? ' class="'.esc_attr($class).'"': '';
				$out .= '>';
				$out .= ($selected=='')? '<option selected="selected">-- Select --</option>': '';
				foreach ($elements as $ix=>$emt){
					$chk=((!$index_val && $emt==$selected) || ($index_val && $ix==$selected))? 'selected="selected"': '';
					$out .= '<option value="'.($index_val? $ix: esc_attr($emt)).'" '.$chk.'>'.esc_html($emt).'</option>';
				}
				$out .= '</select>';
			} else {
				$out = '<p>ERROR: Elements not defined for list</p>';
			}
		} else {
			$out .= 'ERROR: Control name not specified';
		}
		if ($display) echo $out;
		else return $out;
	}
}

if (!function_exists('creator_dynamic_options_att')){
	/**
	 * Create a dynamic option list based on an option name, an array of elements and a default element.
	 * This differs from creator_dynamic_options in that the array provided has elements as the index and
	 * values in a named column.
	 * @param array $elements elements to display in the drop-down
	 * @param string $name name of the option field
	 * @param string $selected default or selected option
	 * @param string $column is the name of the column to select in $arr
	 * @param string $class optional class name for the option field
	 * @param string $display - echo or return the html
	 * @return string - return sting if $display is false
	 */
	function creator_dynamic_options_att($elements,$name,$selected,$column,$class='',$display=true){
		$out = '';
		if (strlen($name) && strlen($column)){
			if (is_array($elements)){
				$out .= '<select name="'.esc_attr($name).'"';
				$out .= strlen($class)? ' class="'.esc_attr($class).'"': '';
				$out .= '>';
				$out .= ($selected=='')? '<option selected="selected">-- Select --</option>': '';
				foreach ($elements as $emt=>$arr){
					if (isset($arr[$column])){
						$chk=($emt==$selected)? 'selected="selected"': '';
						$out .= '<option value="'.esc_attr($emt).'" '.$chk.'>'.esc_html($arr[$column]).'</option>';
					}
				}
				$out .= '</select>';
			} else {
				$out = '<p>ERROR: Elements not defined for list</p>';
			}
		} else {
			$out .= 'ERROR: Control name not specified';
		}
		if ($display) echo $out;
		else return $out;
	}
}

if (!function_exists( 'creator_replace_array_keys' )) {
	/**
	 * Recursive function for renaming array keys anywhere in a multidimensional array
	 *
	 * @param array $arr - multidimensional array where changes are to be made
	 * @param array $replace of the form array(oldkey => newkey)
	 *
	 * @return array - array after replacement
	 */
	function creator_replace_array_keys( array $arr, array $replace ) {
		$return = array();
		foreach ( $arr as $key => $value ) {
			foreach ( $replace as $oldKey => $newKey ) {
				if ( $key === $oldKey ) {
					$key = $newKey;
				}
			}
			if ( is_array( $value ) ) {
				$value = creator_replace_array_keys( $value, $replace );
			}
			$return[ $key ] = $value;
		}
		return $return;
	}
}

if (!function_exists( 'creator_sort_array_by_column' )) {
	/**
	 * Sort a multidimensional array by column name. Keys are preserved.
	 * @param $arr - array to sort
	 * @param $col - name of the column to sort
	 * @param int $dir direction of the sort (use SORT_ASC or SORT_DESC)
	 */
	function creator_sort_array_by_column(&$arr, $col, $dir = SORT_ASC) {
		$sort_col = array();
		foreach ($arr as $key=> $row) {
			$sort_col[$key] = $row[$col];
		}
		array_multisort($sort_col, $dir, $arr);
	}
}

if (!function_exists( 'creator_debug_log' )) {
	/**
	 * Send a debug message to the console (logto = 1) otherwise log to a file called debug_file.txt in the root
	 * This can be viewed by switching on the developer tools console in the browser.
	 * @param Label on the debug console $label
	 * @param object to display $object
	 * @param priority number for indent $priority
	 * @param logto number $logto (0=php log file, 1=console)
	 */
	function creator_debug_log( $label = null, $object = null, $priority = 1, $logto = 0 ) {
		$priority = $priority < 1 ? 1 : $priority;
		$logto    = $logto > 0 ? true : false;
		$message  = json_encode( $object, JSON_PRETTY_PRINT );
		$stamp    = date( 'Y-m-d H:i:s' );
		$label    = "[" . $stamp . "] " . ( $label ? ( " " . $label . ": " ) : ': ' );
		if ( $logto ) {
			echo "<script>console.log('" . str_repeat( "-", $priority - 1 ) . $label . "', " . $message . ");</script>";
		} else {
			//error_log($label."".$message);
			error_log( $label . "" . $message . "\r\n", 3, ABSPATH . "debug_file.txt" );
		}
	}
}

if (!function_exists( 'creator_update_visit_counter' )){
	/**
	 * Capture or create a counter for the attribute. The increment is always 1 for every call. The counter also monitors
	 * the number of visits for each of the past week.
	 * @param string $att attribute name
	 * @param string $action action to perform to the log file (count, delete, reset)
	 * @param boolean $use_time time log format true - include a date as Y-m-d H:i:s (default) false - use micro-time stamp
	 * @param integer $max_elements maximum number of elements in the attribute array
	 * @param string $opt_name - name of the option stored by WP
	 */
	function creator_update_visit_counter( $att, $opt_name='cseo_count', $action = 'count'){
		$opt_name = strlen($opt_name)? $opt_name: 'cseo_count'; //name of the option to use
		$stamp = time();
		$att = strtoupper($att);
		$tmp = [0=>0, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0];
		//$value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		$action = strtolower($action);
		if (strlen($att) && in_array($action, ['count', 'delete', 'reset'])) {
			if ( (($data = get_option( $opt_name ) ) !== false) && ($action != 'reset') ) {
				// The option already exists, so we just update it.
				if (isset($data[$att]) ){
					if ($action == 'delete'){
						if (isset($data[ $att ])){
							unset($data[ $att ]);
						}
					} else {
						if (isset($data[$att]['stamp'])){
							$last = $data[$att]['stamp'];
							$daysAgo = floor(($stamp-$last) / 86400);
							if ($daysAgo){
								for ($ix=0; $ix<=6; $ix++){
									if ($ix>=$daysAgo){
										$tmp[$ix] = $data[$att]['history'][$ix-$daysAgo];
									}
								}
								$data[$att]['history'] = $tmp;
								$data[$att]['stamp'] = strtotime(date('Y-m-d'));
							}
							$data[$att]['total']++;
							$data[$att]['history'][0]++;
						}
					}
				} else {
					$data[$att] = [
							'stamp' => strtotime(date('Y-m-d')),
							'total' => 10,
							'history' => [0=>1, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0]
						];
				}
				update_option( $opt_name, $data );
			} else {
				// The option hasn't been added yet. We'll add it with $autoload set to 'no'.
				$data = array(
					$att => [
						'stamp' => strtotime(date('Y-m-d')),
						'total' => 10,
						'history' => [0=>1, 1=>0, 2=>0, 3=>0, 4=>0, 5=>0, 6=>0]
					]
				);
				add_option( $opt_name, $data);
			}
		}
	}
}

if ( !function_exists( 'creator_fetch_visit_counter' ) ){
	/**
	 * Retrieve the counter for the attribute $att from the log file $opt_name.
	 * @param string $att attribute name
	 * @param string $opt_name - name of the option stored by WP
	 * @param bool $reverse - reverse the order of the array
	 * @return array[]|mixed
	 */
	function creator_fetch_visit_counter( $att, $opt_name='cseo_count'){
		$arr = array();
		$opt_name = strlen($opt_name)? $opt_name: 'cseo_count'; //name of the option to use
		$att = strtoupper($att);
		if ( ( $data = get_option( $opt_name ) ) !== false ) {
			if ( $att=='*' ) {
				$arr = $data;
			} elseif (isset($data[$att]) && is_array($data[$att])) {
				$arr = $data;
				//$arr = $data[$att];
			} else {
				$arr = array($att => array());
			}
		} else {
			// The option hasn't been added yet. We'll add it with $autoload set to 'no'.
			$arr = array($att => array());
		}
		return $arr;
	}
}

if (!function_exists( 'creator_update_log_file' )){
	/**
	 * Save a debug or log result to the option table against record labelled in $opt_name (default: hub_ract_log).
	 * Records may be created, deleted, replaced or appended.
	 * @param string $att attribute name
	 * @param string $value the value / results to be written to the log file
	 * @param string $action action to perform to the log file (update, delete, replace)
	 * @param boolean $use_date date log format true - include a date as Y-m-d H:i:s (default) false - use micro-time stamp
	 * @param integer $max_elements maximum number of elements in the attribute array
     * @param string $opt_name - name of the option stored by WP
	 */
	function creator_update_log_file( $att, $value, $action = 'append', $use_date=true, $max_elements=200, $opt_name='hub_ract_log'){
        $opt_name = strlen($opt_name)? $opt_name: 'hub_ract_log'; //name of the option to use
		$stamp = $use_date? date('Y-m-d H:i:s'): 'M'.(microtime(true)*1000);
		$att = strtoupper($att);
		$value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		$action = strtolower($action);
		$max_elements = $max_elements>0 && $max_elements<300? $max_elements: 200;
		if (strlen($att) && ($action == 'delete' || strlen($value))) {
			if ( ( $data = get_option( $opt_name ) ) !== false ) {
				// The option already exists, so we just update it.
				if ($action == 'delete'){
					if (isset($data[ $att ])){
						unset($data[ $att ]);
					}
				} else {
					if (isset($data[$att])){
						//attribute exists
						if ($action == 'replace') {
							$data[ $att ] = array( $stamp => $value );
						} else {
							//$action == 'append' is the catch-all
							if (creator_count($data[$att])>$max_elements){
								//$start = creator_count($data[$attribute])-$max_elements;
								$data[$att] = array_slice($data[$att], -$max_elements);
								//array_shift($data[$attribute]);
							}
							$data[$att][$stamp] = $value;
						}
					} else {
						//attribute does not exist, add it
						$data[$att] = array( $stamp => $value );
					}
				}
				update_option( $opt_name, $data );
			} else {
				// The option hasn't been added yet. We'll add it with $autoload set to 'no'.
				$data = array(
					$att => array( $stamp => $value )
				);
				add_option( $opt_name, $data);
			}
		}
	}
}

if ( !function_exists( 'creator_fetch_log_attribute' ) ){
    /**
     * Retrieve log result from hub_dicap_log.
     *
     * @param string $att attribute name
     * @param string $opt_name - name of the option stored by WP
     * @param bool $reverse - reverse the order of the array
     * @return array[]|mixed
     */
    function creator_fetch_log_attribute( $att, $opt_name='hub_ract_log', $reverse=false){
        $arr = array();
        $opt_name = strlen($opt_name)? $opt_name: 'hub_ract_log'; //name of the option to use
        $att = strtoupper($att);
        if ( ( $data = get_option( $opt_name ) ) !== false ) {
            if ( $att=='*' ) {
                $arr = $data;
            } elseif (isset($data[$att]) && is_array($data[$att])){
                $tmp = $data[$att];
                if (creator_count($tmp) && $reverse){
                    krsort($tmp);
                }
                foreach ($tmp as $ix => $itm) {
                    $arr[$ix] = json_decode($itm, true);
                }
            } else {
                $arr = array($att => array());
            }
        } else {
            // The option hasn't been added yet. We'll add it with $autoload set to 'no'.
            $arr = array($att => array());
        }
        return $arr;
    }
}

if ( !function_exists('creator_easy_crypt')){
	/**
	 * Encrypt or decrypt a string the easy way
	 *
	 * @param string $input string to encrypt or decrypt
	 * @param boolean $encrypt -  true to encrypt and false to decrypt
	 * @return string - the encrypted or decrypted value based on the $encrypt setting
	 */
	function creator_easy_crypt($input, $encrypt=false){
		$trans = array("a"=>"N","b"=>"M","c"=>"L","d"=>"K","e"=>"J","f"=>"I","g"=>"H","h"=>"G","i"=>"F","j"=>"E","k"=>"D","l"=>"C","m"=>"B","n"=>"A",
		               "A"=>"n","B"=>"m","C"=>"l","D"=>"k","E"=>"j","F"=>"i","G"=>"h","H"=>"g","I"=>"f","J"=>"e","K"=>"d","L"=>"c","M"=>"b","N"=>"a");
		if (strlen($input)){
			if ($encrypt){
				$result = strtr(base64_encode($input), $trans);
			} else {
				//decrypt
				$result = base64_decode(strtr($input, array_flip($trans)));
			}
		} else {
			$result = false;
		}
		return $result;
	}
}

if ( !function_exists('creator_easy_table') ){
	/**
	 * Create a table based on a headers array and an array of corresponding fields
	 * Note: If a record field is labelled 'summary' then this will be a fullwidth row
	 *
	 * @param array $headers - format [field=>label]
	 * @param array $records - format [index => [field=>label]]
	 * @param string $class - include a class if not blank
	 * @param bool $escape escape the text (default) or allow tags
	 *
	 * @return string - html to create the table
	 */
	function creator_easy_table($headers, $records, $class='', $escape = true){
		$out = ''; $num = 0;
		if (is_array($headers) && creator_count($headers)){
			$keys = array_keys($headers);
			$out .= '<table ' . (strlen($class)? ('class="'.$class.'" '):'') . '>';
			$out .= '<tbody>';
			$out .= '<tr>';
			foreach ( $headers as $k => $header ) {
				if (strtolower($k) != 'summary'){
					$out .= '<th>' . $header . '</th>';
					$num++;
				}
			}
			$out .= '</tr>';
			if (is_array($records) && count($records)){
				foreach ( $records as $j => $record ) {
					$summary = '';
					$out .= '<tr>';
					foreach ( $keys as $key ) {
						if ($key != 'summary'){
							if ($escape){
								$out .= '<td>' . ((isset($record[$key]) && strlen($record[$key]))? esc_html($record[$key]): '-') . '</td>';
							} else {
								$out .= '<td>' . ((isset($record[$key]) && strlen($record[$key]))? $record[$key]: '-') . '</td>';
							}
						} else {
							$summary = substr($record[$key],0,256);
						}
					}
					$out .= '</tr>';
					if (strlen($summary)){
						$out .= '<tr><td colspan = "'.$num.'">'.$summary.'</td></tr>';
					}
				}
			}
			$out .= '</tbody>';
			$out .= '</table>';
		} else {
			$out .= '<p>Table not correctly specified</p>';
		}
		return $out;
	}
}

if ( !function_exists('creator_keyword_cloud') ){
	/**
	 * Create a word-cloud from an array of ranked words where the index is the word (or phrase)
	 * and the value is the priority value.
	 * Note: the creator_wordcloud styles should be included
	 * @param $phrases - a list of weighted phrases where the weighting is usually the number of occurrences
	 * @return string - html with formatting tags
	 */
	function creator_keyword_cloud($phrases){
		$out = '<div class="creator_wordcloud">';
		$arr = array();
		$last = -1;
		$index = 0;
		$fontset = [35, 27, 21, 16, 12, 9];
		$colorset = ['#355070', '#6d597a', '#b56576', '#e56b6f', '#eaac8b', '#000000']; //https://venngage.com/blog/pastel-color-palettes/
		//#d4afb9 // #d1cfe2 // #9cadce // #7ec4cf // #52b2cf
		//#79addc // #ffc09f // #ffee93 // #fcf5c7 // #adf7b6
		//#f55c7a // #f57c73 // #f68c70 // #f6ac69 // #f6bc66
		$max = count($fontset)-1;
		if (creator_count($phrases)){
			//sort the keywords descending
			arsort($phrases);
			//$out .= '<pre>'.var_export($phrases,true).'</pre>';
			foreach ( $phrases as $phrase=>$value ) {
				$index = $last==-1? 0: ($value==$last? $index: ($index>=$max? $max: $index+1));
				$arr[] = array('phrase'=>$phrase, 'color'=>$colorset[$index], 'font'=>$fontset[$index]);
				$last = $value;
			}
			shuffle($arr);
		}
		foreach ( $arr as $item ) {
			$out .= '<span style="font-size:'.$item['font'].'px; color:'.$item['color'].'">'.$item['phrase'].'</span> &nbsp;&nbsp;&nbsp;';
		}
		//$out .= '<pre>'.var_export($arr,true).'</pre>';
		$out .= '</div>';
		return $out;
	}
}

if ( !function_exists('is_multi_array') ) {
	/**
	 * Check if an array is multidimensional
	 */
	function is_multi_array($arr){
		rsort( $arr );
		return (isset( $arr[0] ) && is_array( $arr[0] ));
	}
}

if ( !function_exists('array_tree_to_list') ) {
	/**
	 * Convert a hierarchical php array tree to a flat (index) list
	 */
	function array_tree_to_list($arr, $str='', $csv='') {
		$tmp = $str;
		if (is_array($arr) && count($arr)) {
			foreach ($arr as $att => $arr1) {
				$tmp = $str . '[' . $att . ']';
				$csv = array_tree_to_list($arr1, $tmp, $csv);
			}
		} else {
			//$tmp .= $arr;
			$tmp .= '--value--';
			$csv .= ((strlen($csv)? '<br />': '')) . $tmp;
		}
		return $csv;
	}
}

if ( !function_exists('convert_markdown_links') ) {
	/**
	 * Convert all markdown links in a content block to anchors
	 * ref. https://dev.to/mattkenefick/regex-convert-markdown-links-to-html-anchors-f7j
	 *
	 * @param $content - the content to convert
	 * @param string $target - target for URL to open _blank, _self, _parent, _top
	 *
	 * @return array|string|string[]|null
	 */
	function convert_markdown_links($content, $target='_blank') {
		$target = in_array($target, ['_blank', '_self', '_parent', '_top'])? $target: '_blank';
		$pattern = '|\[([^\]]+)\]\(([^\)]+)\)|';
		$content = preg_replace($pattern, '<a href="\2" target="'.$target.'">\1</a>',$content);
		return $content;
	}
}

if ( !function_exists('creator_sanitize_array') ) {
	/**
	 * Recursive sanitization of an array
	 *
	 * @param $array
	 * @return mixed
	 */
	function creator_sanitize_array($array) {
		foreach ( $array as $key => &$arr ) {
			if ( is_array( $arr ) ) {
				$arr = creator_sanitize_array($arr);
			} else {
				$arr = sanitize_text_field( $arr );
			}
		}
		return $array;
	}
}

if ( !function_exists('creator_rollover_array') ) {
	/**
	 * Add an element at the start of the array and remove an element from the end of the array
	 * if there are more than $length elements
	 *
	 * @param $arr - input array
	 * @param $emt - element to add
	 * @param $arr - number of elements in the array
	 * @return array
	 */
	function creator_rollover_array(&$arr, $emt, $length) {
		$length = (int) $length;
		if (is_array($arr)){
			array_unshift($arr, $emt);
			$arr = array_slice($arr, 0, $length);
		} else {
			$arr[] = $emt;
		}
	}
}

if ( !function_exists('creator_sort_array_by_column') ) {
	/**
	 *
	 * Sort a multiple dimension array by column name
	 * @param $arr - the array to be sorted
	 * @param $col - the column name that will be sorted
	 * @param $acdc - Ascending (SORT_ASC) or Descending (SORT_DESC)
	 *
	 * @return void
	 */
	function creator_sort_array_by_column(&$arr, $col, $acdc = SORT_ASC) {
		$sort_col = array();
		foreach ($arr as $key=> $row) {
			$sort_col[$key] = $row[$col];
		}
		array_multisort($sort_col, $acdc, $arr);
	}
}

if ( !function_exists('creator_is_valid_date') ) {
	/**
	 * Check that the date submitted is a valid date format
	 * @param $date - Any English textual datetime description
	 * @return bool - true or false
	 */
	function creator_is_valid_date($date) {
		return (strtotime($date) !== false);
	}
}

if ( !function_exists('creator_get_dates_between_dates') ) {
	/**
	 * Get an array of dates between two given dates
	 * @param $start - start date Y-m-d
	 * @param $end - end date Y-m-d
	 * @param $interval - day, week, month, year (default day)
	 * @return bool - true or false
	 */
	function creator_get_dates_between_dates($start, $end, $interval='day') {
		$arr = [];
		$itl = ['day'=>['x'=>'P1D', 'f'=>'Y-m-d'],
		        'week'=>['x'=>'P1W', 'f'=>'Y-W'],
		        'month'=>['x'=>'P1M', 'f'=>'Y-m'],
		        'year'=>['x'=>'P1Y', 'f'=>'Y']];
		$interval = in_array($interval, ['day', 'week', 'month', 'year'])? $interval: 'day';
		if (strtotime($start)>strtotime($end)){
			$tmp = $start;
			$start = $end;
			$end = $tmp;
		}
		$period = new DatePeriod(
			new DateTime($start),
			new DateInterval($itl[$interval]['x']),
			new DateTime($end.' +1 day')
		);
		foreach ( $period as $dt ) {
			$arr[] = $dt->format($itl[$interval]['f']);
		}
		return $arr;
	}
}

if ( !function_exists('creator_count') ) {
	/**
	 * Count the number of elements in an array - sorts out the PHP8 error if the $val is not an array
	 * @param $val - array to check and count
	 * @return bool - count or 0
	 */
	function creator_count($val) {
		if ( is_array($val) ){
			return (count($val));
		} else {
			return 0;
		}
	}
}