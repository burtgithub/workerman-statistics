<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Statistics\Modules;
function rank($module, $interface, $date, $start_time, $offset, $count)
{
        $module_str ='';
        foreach(\Statistics\Lib\Cache::$modulesDataCache as $mod => $interfaces)
        {
                if($mod == 'WorkerMan')
                {
                    continue;
                }
                $module_str .= '<li><a href="/?fn=statistic&module='.$mod.'">'.$mod.'</a></li>';
                if($module == $mod)
                {
                    foreach ($interfaces as $if)
                    {
                        $module_str .= '<li>&nbsp;&nbsp;<a href="/?fn=statistic&module='.$mod.'&interface='.$if.'">'.$if.'</a></li>';
                    }
                }
        } 
        
        $log_data_arr = getStasticLog($module, $interface, $start_time ,$offset, $count);
        unset($_GET['fn'], $_GET['ip'], $_GET['offset']);
        $log_str = '';
        foreach($log_data_arr as $address => $log_data)
        {
            list($ip, $port) = explode(':', $address);
            $log_str .= $log_data['data'];
            $_GET['ip'][] = $ip;
            $_GET['offset'][] = $log_data['offset'];
        }
        $log_str = nl2br(str_replace("\n", "\n\n", $log_str));
        $next_page_url = http_build_query($_GET);
        $log_str .= "</br><center><a href='/?fn=logger&$next_page_url'>下一页</a></center>";




    // date btn
    $date_btn_str = $html_class= '';
    $query='';
    for($i=13;$i>=1;$i--)
    {
        $the_time = strtotime("-$i day");
        $the_date = date('Y-m-d',$the_time);
        $html_the_date = $date == $the_date ? "<b>$the_date</b>" : $the_date;
        $date_btn_str .= '<a href="/?fn=rank&date='."$the_date&$query".'" class="btn '.$html_class.'" type="button">'.$html_the_date.'</a>';
        if($i == 7)
        {
            $date_btn_str .= '</br>';
        }
    }
    $the_date = date('Y-m-d');
    $html_the_date = $date == $the_date ? "<b>$the_date</b>" : $the_date;
    $date_btn_str .=  '<a href="/?fn=rank&date='."$the_date&$query".'" class="btn" type="button">'.$html_the_date.'</a>';


    include ST_ROOT . '/Views/header.tpl.php';
        include ST_ROOT . '/Views/rank.tpl.php';
        include ST_ROOT . '/Views/footer.tpl.php';
}

