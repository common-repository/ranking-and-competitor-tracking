<?php
/**
 * Project: HUB5050 Digital Insights and Competitor Assessment Portal
 * Copyright: (C) 2018 Clinton
 * Developer:  Clinton
 * Created May Dec 2018
 *
 * Description: View class for DICAP
 *
 * This program is the property of the developer and is not intended for distribution. However, if the
 * program is distributed for ANY reason whatsoever, this distribution is WITHOUT ANY WARRANTEE or even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 */

class creator_chart_data {
    
    private $chartsAllowed = array('bar', 'line', 'scatter', 'horizontalBar', 'stackedBar', 'pie', 'doughnut', 'radar', 'polarArea', 'bubble', 'xy', 'trend');
    private $positionsAllowed = array('top', 'left', 'bottom', 'right');
    private $alignmentAllowed = array('start', 'center', 'end');
    private $timeUnitsAllowed = array('millisecond', 'second', 'minute', 'hour', 'day', 'week', 'month', 'quarter', 'year');
    public $colorSet1 = array("#3e95cd", "#8e5ea2","#3cba9f","#c45850","#b4ba1a", "e4701b");
	public $colorSet2 = array('#e6194b', '#3cb44b', '#ffe119', '#0082c8', '#f58231', '#911eb4', '#46f0f0', '#f032e6', '#d2f53c',
		'#fabebe', '#008080', '#e6beff', '#aa6e28', '#fffac8', '#800000', '#aaffc3', '#808000', '#ffd8b1', '#000080');
	public $colorSet3 = array("#e6194b", "#ffe119");
    private $pointer = 0; //dataset tracker
    public $chartType; //chart type tracker
    private $baseTemplate = array(
	    'type' => 'line',
	    'data' => array(
	    	'datasets' => array()
	    ),
	    'options' => array(
		    'responsive' => true,
		    'legend'=> array('display' => false),
		    'title' => array('display' => false),
		    'scales' => array('display' => false)
	    )
    );
	private $pointStyle = array(
		'fill'      => false,
		'backgroundColor'  => '#efefef',
		'borderColor'      => '#ff0000',
		'borderWidth'      => 2,
		'lineTension'      => 0,
        'pointRadius'      => 2
	);
    private $hoverPointStyle = array(
	    'hoverBorderColor' => '#777',
	    'hoverBorderWidth' => 3,
	    'hoverRadius'      => 2
    );
    
    /**
     * Constructor for Metrics
     */
    function __construct($chartType){
        if (isset($chartType)){
            $this->chartType = in_array($chartType, $this->chartsAllowed)? $chartType: 'bar';
        	$this->baseTemplate['type'] = in_array($this->chartType, array('xy', 'trend'))? 'line': ($this->chartType=='stackedBar'? 'bar': $this->chartType);
        	if (in_array($this->chartType, array('bar', 'line', 'scatter', 'horizontalBar', 'stackedBar'))){
        		$this->baseTemplate['options']['scales'] = array(
        			'xAxes' => array(
        				array(
					        'position' => 'bottom',
					        'stacked' => false,
					        'scaleLabel' => array(
						        'display' => true,
                                'labelString' => 'X-Axis'
					        )
				        )
			        ),
			        'yAxes' => array(
				        array(
					        'position' => 'left',
					        'stacked' => false,
					        'scaleLabel' => array(
						        'display' => true,
						        'labelString' => 'Y-Axis'
					        )
				        )
			        )
		        );
	        };
	        if (in_array($this->chartType, array('stackedBar'))){
	        	$this->stackAxes('both');
	        }
	        if (in_array($this->chartType, array('scatter', 'xy', 'trend'))){
		        $this->baseTemplate['options']['scales']['xAxes'][0]['type'] = 'linear';
		        $this->baseTemplate['options']['scales']['yAxes'][0]['type'] = 'linear';
	        }
	        if ($this->chartType == 'trend'){
		        $this->setXTimeAxis();
	        }
	        if ($this->chartType == 'doughnut'){
		        $this->baseTemplate['options']['cutoutPercentage'] = 50;
	        }
	        //$this->baseTemplate['options']['title'] = array('display' => true, 'text' => 'Hi there');
        } else {
        	$this->error .= " |"."Chart Type must be specified";
        }
    }

	function setChartTitle($title, $fontSize=0){
		if (strlen($title)){
			$this->baseTemplate['options']['title'] = array('display' => true, 'text' => $title);
			if ($fontSize){
				$this->baseTemplate['title']['fontSize'] = (int) $fontSize;
			}
		} else {
			$this->baseTemplate['options']['title'] = array('displayz' => false);
		}
	}

	function setChartLegend($position='right', $align='center', $fontSize=0){
		if (in_array($position, $this->positionsAllowed)){
			$this->baseTemplate['options']['legend'] = array('display' => true, 'position' => $position);
			if (in_array($position, $this->alignmentAllowed)){
                $this->baseTemplate['options']['legend']['align'] = $align;
            }
			if ($fontSize){
				$this->baseTemplate['options']['legend']['labels']['fontSize'] = (int) $fontSize;
			}
		} else {
			$this->baseTemplate['options']['legend'] = array('display' => false);
		}
	}

	/**
	 * Cut out a percentage of the pie chart centre to make a doughnut chart
	 * Applies only to Pie and doughnut charts
	 * @param int $percentage the percentage to cut out of the pie chart centre to make a doughnut
	 */
	function setPieCutoutPercentage($percentage=50){
		if (in_array($this->baseTemplate['type'], array('pie', 'doughnut'))){
			$percentage = ($percentage < 98)? $percentage: 50;
			$this->baseTemplate['options']['cutoutPercentage'] = $percentage;
		}
	}

	/**
	 * Label the x- or y- axis
	 *
	 * @param $axes - either x, y, xAxes or yAxes indicates with axis to label
	 * @param $label - the title to use, blank to remove the title
	 * @param $ix - index of the axis this will usually be 0 and therefore can be left as the default
	 */
	function setAxisTitle($axes, $label, $ix=0){
    	$axes = in_array($axes, array('x', 'X', 'xAxes'))? 'xAxes': (in_array($axes, array('y', 'Y', 'yAxes'))? 'yAxes': $axes);
		if (in_array($axes, array('xAxes', 'yAxes'))){
			if (strlen($label)){
				$this->baseTemplate['options']['scales'][$axes][$ix]['scaleLabel'] = array(
					'display' => true,
					'labelString' => $label
				);
			} else {
				unset($this->baseTemplate['options']['scales'][$axes][$ix]['scaleLabel']);
			}
		}
	}

	/**
	 * Stack the data in an axis
	 *
	 * @param $axes - either x, y, xAxes, yAxes or both indicates with axis to label
	 * @param $stacked - true (stacked) or false (not stacked)
	 * @param $ix - index of the axis this will usually be 0 and therefore can be left as the default
	 */
	function stackAxes($axes, $stacked=true, $ix=0){
		if (in_array($this->baseTemplate['type'], array('line', 'bar', 'horizontalBar', 'stackedBar'))){
			$axes = $axes == 'both'? 'both': ($axes == 'x'? 'xAxes': ('y'? 'yAxes': $axes));
			$axes = in_array($axes, array('xAxes', 'yAxes', 'both'))? $axes: 'both';
			$stacked = $stacked===false? false: true;
			if ($axes=='xAxes' || $axes=='both'){
				$this->baseTemplate['options']['scales']['xAxes'][$ix]['stacked'] = $stacked;
			}
			if ($axes=='yAxes' || $axes=='both'){
				$this->baseTemplate['options']['scales']['yAxes'][$ix]['stacked'] = $stacked;
			}
		}
	}

    /**
     * Set the time axis
     * @param string $timeUnit
     * @param string $timeFormat
     */
	function setXTimeAxis($timeUnit='', $timeFormat='YYYY-MM-DD'){
		if (in_array($this->baseTemplate['type'], array('line', 'scatter', 'bar', 'horizontalBar', 'stackedBar'))){
			$timeUnit = in_array($timeUnit, $this->timeUnitsAllowed)? $timeUnit: 'day';
			$this->baseTemplate['options']['scales']['xAxes'][0]['type'] = 'time';
			$this->baseTemplate['options']['scales']['xAxes'][0]['time'] = array(
				'format' => $timeFormat,
//				'unit' => $timeUnit,
				'tooltipFormat' => 'll'
			);
			if (strlen($timeUnit)>0 && in_array($timeUnit, $this->timeUnitsAllowed)){
                $this->baseTemplate['options']['scales']['xAxes'][0]['time']['unit'] = $timeUnit;
            }
		}
	}

	function setAxisTicks($axes, $reverse=false, $zeroStart=false, $min=null, $max=null, $stepSize=null){
    	if (in_array($axes, array('xAxes', 'yAxes'))){
    		$ticks = array(
			    'beginAtZero' => $zeroStart,
			    'reverse' => $reverse
		    );
    		if (isset($min) && is_numeric($min)){
    			$ticks['min'] = $min;
		    }
		    if (isset($max) && is_numeric($max)){
			    $ticks['max'] = $max;
		    }
		    if (isset($stepSize) && is_numeric($stepSize)){
			    $ticks['stepSize'] = $stepSize;
		    }
		    $this->baseTemplate['options']['scales'][$axes][0]['ticks'] = $ticks;
	    }
	}

    function setVerticalXLabels(){
        $ticks = array(
            'autoSkip' => false,
            'maxRotation' => 90,
            'minRotation' => 90
        );
        $this->baseTemplate['options']['scales']['xAxes'][0]['ticks'] = $ticks;
    }

	/**
	 * Update the attributes for the data points, a blank value means unset the attribute
     * setPointStyle() must be called BEFORE add a data set otherwise it will be ignored
     *
	 * $atts = array(
	 *     'fill' => false,
	 *     'backgroundColor' => '#efefef',
	 *     'borderColor' => '#ff0000',
	 *     'borderWidth' => 2,
	 *     'lineTension' => 0,
	 *     'pointRadius' => 2);
	 * @param $atts array of attribute-> value pairs. Attributes are checked against the allowed attributes.
	 */
	function setPointStyle($atts){
		$allowedAtts = array('fill', 'backgroundColor', 'borderColor', 'borderWidth', 'lineTension', 'pointRadius');
		if (is_array($atts)){
			foreach ( $atts as $att => $val ) {
				if (in_array($att, $allowedAtts)){
					if ($val === '' && isset($this->pointStyle[$att])){
						unset($this->pointStyle[$att]);
					} else {
						$this->pointStyle[$att] = $val;
					}
				}
			}
		}
	}

	/**
	 * Update the hover attributes for the data points, a blank value means unset the attribute
     * setHoverPointStyle() must be called BEFORE add a data set otherwise it will be ignored
     *
	 * $atts = array(
	 *     'hoverBorderColor' => '#333',
	 *     'hoverBorderWidth' => 4);
	 * @param $atts array of attribute-> value pairs. Attributes are checked against the allowed attributes.
	 */
	function setHoverPointStyle($atts){
		$allowedAtts = array('hoverBorderColor', 'hoverBorderWidth', 'hoverRadius');
		if (is_array($atts)){
			foreach ( $atts as $att => $val ) {
				if (in_array($att, $allowedAtts)){
					if ($val == '' && isset($this->hoverPointStyle[$att])) {
						unset( $this->hoverPointStyle[ $att ] );
					} else {
						$this->hoverPointStyle[$att] = $val;
					}
				}
			}
		}
	}

	/**
	 *
	 * @param $labels - Data must be in the format [x1, x2, .... ]
	 */
	function setDataLabels($labels){
    	if (is_array($labels)){
		    $this->baseTemplate['data']['labels'] = $labels;
	    }
	}

	/**
	 * Add a data set to the chart
	 * Note: If $fillColor is specified as an array then each point will have a different color
	 * from the array for Pie Charts or each series will have a different color for other charts
	 *
	 * @param $label
	 * @param $dat - Data must be in the format [y1, y2, .... ]
	 * @param boolean $autoBorder - automatically set the border color sequentially
	 * @param string | array $fillColor - array of colors to use for the elements
	 */
	function addNewDataSet($label, $dat, $autoBorder=true, $fillColor=array()){
		if (is_array($dat)){
			$selectedColor = $this->colorSet1[ (($this->pointer)%count($this->colorSet1)) ];
			$pointData = array(
				'label' => $label,
				'data' => $dat
			);
            $pointData['borderColor'] = $autoBorder? $selectedColor: $pointData['backgroundColor'];
			if ($fillColor){
				if (is_array($fillColor)){
					if (count($fillColor)>1){
						if (in_array($this->baseTemplate['type'], array('pie', 'doughnut'))){
							$pointData['backgroundColor'] = $fillColor;
						} else {
							$pointData['backgroundColor'] = $fillColor[(($this->pointer)%count($fillColor))];
						}
					} else {
						$pointData['backgroundColor'] = $fillColor[0];
					}
				} else {
					$pointData['backgroundColor'] = $fillColor;
				}
			} else {
				$pointData['backgroundColor'] = $selectedColor;
			}
            $pointData['borderColor'] = $autoBorder? $selectedColor: $pointData['backgroundColor'];
			$this->baseTemplate['data']['datasets'][] = array_replace_recursive(array_replace_recursive($this->pointStyle, $this->hoverPointStyle), $pointData);
			$this->pointer ++;
		}
	}

	/**
	 * Add an XY dataset to the chart
	 *
	 * @param $label
	 * @param $xydata - Array must be in the format [['x'=>x1, 'y'=>y1], ['x'=>x2, 'y'=>y2] .... ]
	 * @param boolean $autoBorder - automatically set the border color sequentially
	 * @param string | array $fillColor - array of colors to use for the elements
	 */
	function addXYDataSet($label, $xydata, $autoBorder=true, $fillColor = array()){
		if (in_array($this->baseTemplate['type'], array('line', 'scatter', 'bar', 'horizontalBar', 'stackedBar', 'bubble'))) {
			if (isset($this->baseTemplate['data']['labels'])){
				unset($this->baseTemplate['data']['labels']);
			}
			if ( is_array( $xydata ) ) {
				$selectedColor = $this->colorSet1[ $this->pointer ];
				$pointData = array(
					'label'            => $label,
					'data'             => $xydata
				);
				if ($autoBorder){
					$pointData['borderColor'] = $selectedColor;
				}
				if ($fillColor){
					if (is_array($fillColor)){
						if (count($fillColor)>1){
							$pointData['backgroundColor'] = $fillColor;
						} else {
							$pointData['backgroundColor'] = $fillColor[0];
						}
					} else {
						$pointData['backgroundColor'] = $fillColor;
					}
				}
				//$this->baseTemplate['data']['datasets'][] = array_replace_recursive(array_replace_recursive($this->pointStyle, $this->hoverPointStyle), $pointData);
				$this->baseTemplate['data']['datasets'][] = array_merge(array_merge($this->pointStyle, $this->hoverPointStyle), $pointData);
				$this->pointer ++;
			}
		}
	}

/**
 * Get the Chart data in nested array form
 * @return array
 */
    function get_chart_data(){
    	return $this->baseTemplate;
    }
    
} //end of class


/**
 * Convert a single attribute - value array to a categorised array of x, y values where 'x' is the first column
 * value and 'y' is the second column value
 *  
 * @param $arr - 2 column input array of the form [x1=>y1, x2=>y2, ... xn=>yn]]
 * @param $ymin - the minimum allowed value for the value axis (y)
 * @param $ymax - the maximum allowed value for the value axis (y)
 * @return array - categorised array of the form [{'x'=>x1, 'y'=>y1}, {'x'=>x2, 'y'=>y2}, ... {'x'=>xn, 'y'=>yn}]
 */
function convert2XYData($arr, $ymin=null, $ymax=null, $date2num=false, $rankchart=false){
	$newarr = array();
	if (is_array($arr)){
		foreach ( $arr as $att => $val ) {
			$x = $att;
			if ($date2num){
				$tmp = explode('-',$att);
				if (count($tmp)==3 && checkdate($tmp[1],$tmp[2],$tmp[0])){
					$x = strtotime($att);
				}
			}
			$val = ($rankchart && $val==0)? 50: $val;
			$val = (isset($ymin) && $val<$ymin)? $ymin: $val;
			$val = (isset($ymax) && $val>$ymax)? $ymax: $val;
			$newarr[] = array('x'=>$x, 'y'=>$val);
		}
	}
	return $newarr;
}

