<?php

/**
 * Network module class
 *
 * @package munkireport
 * @author
 **/
class Network_controller extends Module_controller
{
    public function cidr2ipList($cidr)
    {
        $ip_arr = explode('/', $cidr);
        $start = ip2long($ip_arr[0]);
        $nm = $ip_arr[1];
        $num = pow(2, 32 - $nm);
        $end = $start + $num - 1;

        $ipList = array();
        for ($i = 0; $i < $num; $i++) {
            $ipList[$i] = long2ip($start + $i);
        }
        return $ipList;
    }


    
    /*** Protect methods with auth! ****/
    public function __construct()
    {
        // Store module path
        $this->module_path = dirname(__FILE__);
    }

    /**
     * Default method
     *
     * @author AvB
     **/
    public function index()
    {
        echo "You've loaded the network module!";
    }

    /**
     * REST interface, returns json with ip address ranges
     * defined in conf('ipv4router')
     * or passed with GET request
     *
     * @return void
     * @author AvB
     **/
    public function routers()
    {
        
        if (! $this->authorized()) {
            die('Authenticate first.'); // Todo: return json?
        }

        $router_arr = array();
        
        // See if we're being parsed a request object
        if (array_key_exists('req', $_GET)) {
            $router_arr = (array) json_decode($_GET['req']);
        }

        if (! $router_arr) { // Empty array, fall back on default ip ranges
            $router_arr = conf('ipv4routers', array());
        }
        
        $out = array();
        $reportdata = new Reportdata_model();

        // Compile SQL
        $cnt = 0;
        $sel_arr = array('COUNT(1) as count');
        foreach ($router_arr as $key => $value) {
            if (is_scalar($value)) {
                $value = array($value);
            }
            $when_str = '';
            foreach ($value as $k => $v) {
                    if (strpos($v, '/') != FALSE) {
                        // we do the CIDR stuff
                        $inlist = "(";
                        foreach ($this->cidr2ipList($v) as $ipaddress) {
                            $inlist = $inlist . "'$ipaddress',"; 
                        }
                        $inlist = substr_replace($inlist, "", -1) . ")";
			#print($inlist);
                        $when_str .= sprintf(" WHEN ipv4router in %s THEN 1", $inlist);
                    } else {
                        $when_str .= sprintf(" WHEN ipv4router LIKE '%s%%' THEN 1", $v);
                    }
            }
            $sel_arr[] = "SUM(CASE $when_str ELSE 0 END) AS r${cnt}";
            $cnt++;
        }
        
        $sql = "SELECT " . implode(', ', $sel_arr) . " FROM network
			LEFT JOIN reportdata USING (serial_number)
			WHERE ipv4router != '(null)' AND ipv4router != ''".get_machine_group_filter('AND');

        // Create Out array
        if ($obj = current($reportdata->query($sql))) {
            $cnt = $total = 0;
            foreach ($router_arr as $key => $value) {
                $col = 'r' . $cnt++;

                $out[] = array('key' => $key, 'cnt' => intval($obj->$col));

                $total += $obj->$col;
            }

            // Add Remaining IP's as other
            if ($obj->count - $total) {
                $out[] = array('key' => 'Other', 'cnt' => $obj->count - $total);
            }
        }

        $obj = new View();
        $obj->view('json', array('msg' => $out));
    }
} // END class default_module
