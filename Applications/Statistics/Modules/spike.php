<?php
/**
 * 流量突刺检测模块
 * 按时间窗口找出请求量最高的接口
 */
namespace Statistics\Modules;

function spike($module, $interface, $date, $start_time, $offset, $count)
{
    if (!$date) {
        $date = date('Y-m-d');
    }

    // 时间窗口：默认查最近1小时，可通过 ?spike_time=HH:MM 指定某分钟
    $spike_time = isset($_GET['spike_time']) ? $_GET['spike_time'] : '';
    $window     = isset($_GET['window']) ? (int)$_GET['window'] : 5;
    // 防止 window=0 导致死循环
    if ($window <= 0) $window = 5;
    // 校验 spike_time 格式 HH:MM
    if ($spike_time && !preg_match('/^\d{2}:\d{2}$/', $spike_time)) {
        $spike_time = '';
    }

    $result  = getSpikeData($date, $spike_time, $window);
    $is_today = ($date === date('Y-m-d'));

    // 只有有数据时才排序
    if (!empty($result)) {
        $has_history = !empty(array_filter(array_column($result, 'avg7')));
        $sort_col    = $has_history ? array_column($result, 'ratio') : array_column($result, 'num');
        array_multisort($sort_col, SORT_DESC, $result);
    }

    // 构建表格（只在有数据时）
    $table_str = '';
    if (!empty($result)) {
        $table_str = '<table class="table table-bordered table-hover">
    <thead><tr>
      <th>#</th>
      <th>接口名称</th>
      <th>当前请求数</th>
      <th>7日同期均值</th>
      <th>倍数</th>
      <th>平均耗时(ms)</th>
      <th>失败数</th>
    </tr></thead><tbody>';

        $i = 0;
        foreach ($result as $v) {
            $i++;
            $avg   = $v['num'] > 0 ? (int)($v['time'] / $v['num']) : 0;
            $ratio = $v['ratio'];
            $avg7  = $v['avg7'];

            if ($avg7 > 0 && $ratio >= 2) {
                $row_class = 'class="danger"';
                $ratio_str = "<strong style='color:#a94442'>{$ratio}x ▲</strong>";
            } elseif ($avg7 > 0 && $ratio >= 1.5) {
                $row_class = 'class="warning"';
                $ratio_str = "<strong>{$ratio}x ▲</strong>";
            } else {
                $row_class = $i <= 3 ? 'class="info"' : '';
                $ratio_str = $avg7 > 0 ? "{$ratio}x" : '<span style="color:#999">无历史</span>';
            }

            $table_str .= "<tr {$row_class}>
            <td>{$i}</td>
            <td>{$v['name']}</td>
            <td><strong>{$v['num']}</strong></td>
            <td>{$avg7}</td>
            <td>{$ratio_str}</td>
            <td>{$avg}</td>
            <td>{$v['fail']}</td>
        </tr>";
        }

        $table_str .= '</tbody></table>';
    }

    // 构建时间段选择器（按 $window 步长生成当天所有时间点）
    $time_selector = buildTimeSelectorStr($date, $spike_time, $window);

    // 日期按钮
    $date_btn_str = '';
    for ($i = 13; $i >= 1; $i--) {
        $the_date     = date('Y-m-d', strtotime("-$i day"));
        $html_the_date = $date == $the_date ? "<b>$the_date</b>" : $the_date;
        $date_btn_str .= '<a href="/?fn=spike&date=' . $the_date . '" class="btn" type="button">' . $html_the_date . '</a>';
        if ($i == 7) $date_btn_str .= '</br>';
    }
    $the_date     = date('Y-m-d');
    $html_the_date = $date == $the_date ? "<b>$the_date</b>" : $the_date;
    $date_btn_str .= '<a href="/?fn=spike&date=' . $the_date . '" class="btn" type="button">' . $html_the_date . '</a>';

    // 计算显示用的结束时间
    $spike_time_end = '';
    if ($spike_time) {
        $spike_time_end = date('H:i', strtotime("{$date} {$spike_time}") + $window * 60);
    }

    include ST_ROOT . '/Views/header.tpl.php';
    include ST_ROOT . '/Views/spike.tpl.php';
    include ST_ROOT . '/Views/footer.tpl.php';
}

/**
 * 扫描所有模块下的接口文件，按时间窗口聚合请求量
 * 实际路径: data/statistic/statistic/{module}/{interface}.{date}
 */
function getSpikeData($date, $spike_time, $window)
{
    $is_today = ($date === date('Y-m-d'));

    // 确定时间范围
    if ($spike_time) {
        $ts_start = strtotime("{$date} {$spike_time}");
        $ts_end   = $ts_start + ($window * 60);
    } elseif ($is_today) {
        // 今天且未指定时间：查最近 $window 分钟
        $ts_end   = time();
        $ts_start = $ts_end - ($window * 60);
    } else {
        // 历史日期未指定时间：返回空，让视图提示用户选择时间点
        return [];
    }

    if ($ts_start >= $ts_end) return [];

    // 当天数据
    $today_data = scanSpikeByDate($date, $ts_start, $ts_end);

    // 过去7天同时段数据，用于计算均值
    // 用时分秒偏移量，避免直接减秒数跨夏令时等问题
    $time_of_day_start = $ts_start - strtotime($date . ' 00:00:00'); // 当天秒偏移
    $time_of_day_end   = $ts_end   - strtotime($date . ' 00:00:00');

    $history_map = [];
    for ($d = 1; $d <= 7; $d++) {
        $past_date     = date('Y-m-d', strtotime("-$d day", strtotime($date)));
        $past_day_base = strtotime($past_date . ' 00:00:00');
        $past_ts_start = $past_day_base + $time_of_day_start;
        $past_ts_end   = $past_day_base + $time_of_day_end;

        $past_data = scanSpikeByDate($past_date, $past_ts_start, $past_ts_end);
        foreach ($past_data as $item) {
            $history_map[$item['name']][] = $item['num'];
        }
    }

    // 合并：给每个接口附上7日均值和倍数
    $result = [];
    foreach ($today_data as $item) {
        $name  = $item['name'];
        $avg7  = 0;
        $ratio = 0;
        if (!empty($history_map[$name])) {
            $avg7  = round(array_sum($history_map[$name]) / count($history_map[$name]), 1);
            $ratio = $avg7 > 0 ? round($item['num'] / $avg7, 2) : 0;
        }
        $item['avg7']  = $avg7;
        $item['ratio'] = $ratio;
        $result[] = $item;
    }

    return $result;
}

/**
 * 扫描指定日期和时间戳范围内的接口数据
 */
function scanSpikeByDate($date, $ts_start, $ts_end)
{
    $dataroot = \Statistics\Config::$dataPath . "statistic/statistic/*/*.{$date}";
    $result   = [];

    foreach (glob($dataroot, GLOB_BRACE) as $file) {
        $basename       = basename($file);
        $interface_name = substr($basename, 0, strrpos($basename, '.' . $date));
        if (!$interface_name) continue;
        $module_name  = basename(dirname($file));
        $display_name = $module_name . '::' . $interface_name;

        $suc_count  = 0;
        $fail_count = 0;
        $suc_time   = 0;
        $fail_time  = 0;

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $cols = explode("\t", $line);
            if (count($cols) < 6) continue;
            $ts = (int)$cols[1];
            if ($ts < $ts_start || $ts > $ts_end) continue;

            $suc_count  += (int)$cols[2];
            $suc_time   += (float)$cols[3] * 1000;
            $fail_count += (int)$cols[4];
            $fail_time  += (float)$cols[5] * 1000;
        }

        $total = $suc_count + $fail_count;
        if ($total > 0) {
            $result[] = [
                'name' => $display_name,
                'num'  => $total,
                'time' => $suc_time + $fail_time,
                'fail' => $fail_count,
            ];
        }
    }

    return $result;
}

/**
 * 构建时间段选择器
 */
function buildTimeSelectorStr($date, $current_spike_time, $window)
{
    $str = '<div class="btn-group" style="flex-wrap:wrap;display:flex;gap:4px;">';
    // 按 $window 步长生成当天 00:00 ~ 23:59 的时间点按钮
    $base = strtotime($date . ' 00:00:00');
    for ($m = 0; $m < 1440; $m += $window) {
        $t    = $base + $m * 60;
        $hhmm = date('H:i', $t);
        $active = ($hhmm === $current_spike_time) ? 'btn-danger' : 'btn-default';
        $str .= '<a href="/?fn=spike&date=' . $date . '&spike_time=' . $hhmm . '&window=' . $window
              . '" class="btn btn-xs ' . $active . '">' . $hhmm . '</a>';
    }
    $str .= '</div>';
    return $str;
}
