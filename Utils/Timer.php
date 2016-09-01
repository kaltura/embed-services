<?php
	/**
	*
	*/
	class Timer
	{
		private $total = 0;
		private $start;
		private $stop;

		function start(){
		    $this->start = microtime(true);
		}

		function stop(){
		    $this->stop = microtime(true);
		    $this->total = $this->stop - $this->start;
		}

		function getTime(){
		    return $this->total;
		}

		function getTimeMs(){
		    return number_format(($this->total*1000), 2);
		}
    }
?>