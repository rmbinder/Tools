<?php
/*
*	PHP Fortschrittsbalken v1.3
*	(c) 2010 by Fabian Schlieper
*	fabian@fabi.me
*	http://fabi.me/
*/

class progressbar
{
	private static $js_prefix = "_pbr_";
	
	private static function _echo( $string )
	{
		echo $string;
		@ob_flush();
		@flush();
	}
	
	private static function execute_js($code, $short_tag=true)
	{
		$code = trim($code);
		self::_echo($short_tag ? "<script>$code</script>" : "<script type=\"text/javascript\"><!--\n$code\n// --></script>");
	}
		
	private $id=0, $value=0, $steps=0, $width=0, $height=0, $color='', $bgcolor='', $inner_styleclass='', $outer_styleclass='', $show_digits=true;
	
	public function __construct( $value = 0, $steps = 100, $width = 100, $height = 20, $color = '#0C0', $bgcolor = '#FFF', $inner_styleclass = '', $outer_styleclass = '')
	{
		static $progress_bars;		
		if(empty($progress_bars))
			$progress_bars = 0;
			
		$this->id = $progress_bars;
		$this->value = $value;
		$this->steps = $steps;
		$this->width = $width;
		$this->height = $height;
		$this->color = $color;
		$this->bgcolor = $bgcolor;
		$this->inner_styleclass = $inner_styleclass;
		$this->outer_styleclass = $outer_styleclass;
		
		$progress_bars++;
	}
	
	public function set_show_digits($show = true)
	{
		$this->show_digits = !!$show;
	}
	
	public function print_code()
	{
		static $init_printed;
		
		$jsp = self::$js_prefix;
		
		if(empty($init_printed))
		{
			self::execute_js("var {$jsp}bs=[];var {$jsp}ds=[];var {$jsp}ss=[];var {$jsp}ws=[];var {$jsp}dc=[];function {$jsp}s(f,d){var e=(d/{$jsp}ss[f]);if({$jsp}ds[f]){{$jsp}ds[f].innerHTML=\"\"+Math.round(e*100)+\" %\";}{$jsp}bs[f].style.width=\"\"+Math.round(e*{$jsp}ws[f])+\"px\";var c=(e>=0.5);if({$jsp}ds[f]&&{$jsp}dc[f]!=c){var a=(c?document.getElementById(\"{$jsp}b\"+f):{$jsp}bs[f]);{$jsp}ds[f].style.color=a.style.backgroundColor;{$jsp}dc[f]=c}}function {$jsp}i(e,d,a,b){{$jsp}bs[e]=document.getElementById(\"{$jsp}\"+e);{$jsp}ds[e]=document.getElementById(\"{$jsp}d\"+e);{$jsp}dc[e]=false;{$jsp}ss[e]=a;{$jsp}ws[e]=b;{$jsp}s(e,d)};", false);
			$init_printed = true;
		}

		$id = $this->id;
		$w = "width:{$this->width}px;";
		$h = "height:{$this->height}px;";
		$osc = empty($this->outer_styleclass) ? false : $this->outer_styleclass;
		$isc = empty($this->inner_styleclass) ? false : $this->inner_styleclass;
		$cl = "color:{$this->color};";

		self::_echo("<div id=\"{$jsp}b{$id}\" style=\"{$w}{$h}text-align:left;background-color:{$this->bgcolor};overflow:hidden;".($osc?"\" class=\"{$osc}\"":"border:1px solid #000;\"").">");
		if($this->show_digits)
			self::_echo("\n<div id=\"{$jsp}d{$id}\" style=\"{$w}{$h}text-align:center;line-height:{$this->height}px;position:absolute;z-index:3;{$cl}\"></div>");
		self::_echo("<div id=\"{$jsp}{$id}\" style=\"width:0px;{$h}background-{$cl}".($isc?" class=\"{$isc}\"":"\"")."></div>");
		self::_echo("\n</div>");
		self::execute_js("{$jsp}i({$this->id},{$this->value},{$this->steps},{$this->width});");
	}	
	
	public function set($v)
	{
		if($v < 0) $v = 0;
		else if($v >= $this->steps) $v = $this->steps;
		
		if($this->value != $v)
		{
			$this->value = $v;
			self::execute_js(self::$js_prefix.'s('.$this->id.','.$v.');');
		}
	}
	public function step($d=1) { $this->set($this->value+$d); }	
	public function reset() { $this->set(0); }	
	public function complete() { $this->set($this->steps); }
}
?>