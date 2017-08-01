<?php 
    function varDump($data){
		ob_start();
		var_dump($data);
		$ret_val = ob_get_contents();
		ob_end_clean();
		return $ret_val;
	}
 ?>