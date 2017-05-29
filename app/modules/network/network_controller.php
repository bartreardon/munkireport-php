<?php

/**
 * Network module class
 *
 * @package munkireport
 * @author
 **/
class Network_controller extends Module_controller
{
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

    private function ipInSubnet($cidr, $ip)
    {
        $ip_arr = explode('/', $cidr);
        $start = ip2long($ip_arr[0]);
        $nm = $ip_arr[1];
        $num = pow(2, 32 - $nm);
        $end = $start + $num - 1;

        if (($start <= ip2long($ip)) && (ip2long($ip) <= $end)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * REST interface, returns json with ip address ranges
     * defined in conf('ipv4router')
     * or passed with GET request
     *
     * @return void
     * @author AvB
     **/
    
    public function routers() //not routers anymore - more like "clients"
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
        //$sql = "select ipv4router, count(distinct serial_number) as count from network WHERE ipv4router != '(null)' group by ipv4router;";

        $sql = "select remote_ip from reportdata;";

        // Create Out array
        if ($obj = $reportdata->query($sql)) {
            foreach ($router_arr as $key => $value) { // loop through vlan entries
                //$cnt++;
                if (is_scalar($value)) {
                    $value = array($value);
                }
                $ipcount = 0;
                foreach($obj as $IPdata) {                              // loop though all unique ipv4router with count
                    foreach ($value as $k => $v) {                      // loop through vlans for each vlan entry
                        if ($this->ipInSubnet($v,$IPdata->remote_ip)){ 
                            $out[] = array('key' => $key, 'cnt' => 1);
                            break;
                        }
                    }
                    $ipcount += 1;
                }  
            }
        }
        
        $totals = array();
        $final = array();

        foreach($out as $item => $item_count) {
            $pid = $item_count['key'];
            if(!isset($totals[$pid])) {
                $totals[$pid] = $item_count;
            } else {
                $totals[$pid]['cnt'] += $item_count['cnt'];
            }
        }
        //rename the keys - TODO - figure out a better way rather than this double handling
        $vlanTotal = 0;
        foreach($totals as $vlanName => $vlanCount){
            $final[] = array('key' => $vlanName, 'cnt' => $totals[$vlanName]['cnt']);
            $vlanTotal += $totals[$vlanName]['cnt'];
        }

        // update "Other" total
        $otherTotal = $ipcount - $vlanTotal;
        $final[] = array('key' => 'Other', 'cnt' => $otherTotal);

        $obj = new View();
        $obj->view('json', array('msg' => $final));
        //$obj->view('json', array('msg' => $out));
    }
} // END class default_module
