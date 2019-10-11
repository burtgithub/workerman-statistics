<?php
/**
 * Created by PhpStorm.
 * User: qinqin
 * Date: 2019-10-11
 * Time: 15:36
 */

namespace Statistics\Modules;



function setRank($date){



    //文件不存在
    $dataroot   =   __DIR__."/../data/statistic/statistic/api/*{$date}";
    $all        =   [];
    foreach(glob($dataroot) as $php_file)
    {
        $tinfo  =   file_get_contents($php_file);
        $tArr   =   explode("\n",$tinfo);
        $rankname_f =   explode(".",basename($php_file));
        $rankname_f =   $rankname_f[0];
        $all[$rankname_f]['num']    =   0;
        $all[$rankname_f]['time']   =   0;

        foreach($tArr AS $tv){
            if($tv){


                $tvArr  =   explode("\t",$tv);

                $all[$rankname_f]['num']+=$tvArr[2];
                $all[$rankname_f]['time']+=(int)($tvArr[2]*$tvArr[3]*1000);

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

setRank(date("Y-m-d"));