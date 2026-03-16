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
    $rankroot   =   __DIR__."/../data/statistic/rank";

    @mkdir($rankroot,0777,true);

    if(!$date){
            $date   =   date("Y-m-d");
    }

    $rankfile   =   $rankroot."/rank_".$date;
    if(file_exists($rankfile)){
        $info = file_get_contents($rankfile);
        $all  = json_decode($info, true);
        // 缓存超过5分钟才重新生成
        if(empty($all) || (time() - $all['time']) > 300) {
            $all = setRank2($date);
        }
    } else {
        $all = setRank2($date);
    }
    $lastArr    =   [];
    foreach ($all['data'] AS $k=>$v){
        $tmp    =   [];
        $tmp['name']=$k;
        $tmp['num'] =   $v['num'];
        $tmp['avg'] =   (int)($v['time']/$v['num']);
        $tmp['time'] =   $v['time'];

        $lastArr[]  =   $tmp;
    }

    $last_names = array_column($lastArr,'avg');
    array_multisort($last_names,SORT_DESC,$lastArr);
    $log_str    =   '<div class="container"><div class="row clearfix">
    <div class="col-md-12 column"><table class="table">	<thead>	<tr><th>编号	</th><th>接口名称</th><th>平均时间(ms)</th><th>数目</th></tr>	</thead><tbody>';
    $i=0;
    foreach($lastArr AS $v){
        $i++;
        $log_str .="<tr >
						<td>
							{$i}
						</td>
						<td>
							{$v['name']}
						</td>
						<td>{$v['avg']}
						</td>
						<td>
							{$v['num']}
						</td>
					</tr>";

    }

    $log_str .='
    </tbody>
                </table>
            </div>
        </div>
    </div>
    ';
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




function setRank2($date){
    // 扫描所有模块目录，路径: data/statistic/statistic/{module}/{interface}.{date}
    $dataroot = \Statistics\Config::$dataPath . "statistic/statistic/*/*.{$date}";
    $all      = [];
    foreach(glob($dataroot, GLOB_BRACE) as $php_file)
    {
        $basename    = basename($php_file);
        $rankname_f  = substr($basename, 0, strrpos($basename, '.' . $date));
        if (!$rankname_f) continue;

        $tinfo  = file_get_contents($php_file);
        $tArr   = explode("\n", $tinfo);

        $all[$rankname_f]['num']  = 0;
        $all[$rankname_f]['time'] = 0;

        foreach($tArr as $tv){
            if($tv){
                $tvArr = explode("\t", $tv);
                if(count($tvArr) < 4) continue;
                $all[$rankname_f]['num']  += (int)$tvArr[2];
                $all[$rankname_f]['time'] += (int)($tvArr[3] * 1000);
            }
        }
    }

    $setFile    =   [
        "time"=>time(),
        "data"=>$all,
        "last"=>0
    ];




    $rankroot   =   __DIR__."/../data/statistic/rank";

    @mkdir($rankroot,0777,true);

    if(!$date){
        $date   =   date("Y-m-d");
    }

    $rankfile   =   $rankroot."/rank_".$date;

    file_put_contents($rankfile,json_encode($setFile));
    return $setFile;
}


