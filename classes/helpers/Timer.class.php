<?php

class Timer
{
    private $start = 0;
    private $end = 0;

    public function start()
    {
    	$this->start = microtime(true);
    }

    public function stop()
    {
    	if ($this->start === 0)
    	{
    		return 0;
    	}

    	$this->end = microtime(true);

    	return $this->end - $this->start;
    }
}

?>