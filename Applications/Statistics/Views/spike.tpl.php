<div class="container">
    <div class="row clearfix">
        <div class="col-md-12 column">
            <ul class="nav nav-tabs">
                <li><a href="/">概述</a></li>
                <li><a href="/?fn=statistic">监控</a></li>
                <li><a href="/?fn=logger">日志</a></li>
                <li><a href="/?fn=rank">排行</a></li>
                <li class="active"><a href="/?fn=spike">突刺检测</a></li>
                <li class="disabled"><a href="#">告警</a></li>
                <li class="dropdown pull-right">
                    <a href="#" data-toggle="dropdown" class="dropdown-toggle">其它<strong class="caret"></strong></a>
                    <ul class="dropdown-menu">
                        <li><a href="/?fn=admin&act=detect_server">探测数据源</a></li>
                        <li><a href="/?fn=admin">数据源管理</a></li>
                        <li><a href="/?fn=setting">设置</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>

    <!-- 日期选择 -->
    <div class="row clearfix" style="margin-top:10px;">
        <div class="col-md-12 column text-center">
            <?php echo $date_btn_str; ?>
        </div>
    </div>

    <!-- 窗口大小选择 -->
    <div class="row clearfix" style="margin-top:10px;">
        <div class="col-md-12 column">
            <span>时间窗口：</span>
            <?php foreach([1,5,10,30,60] as $w): ?>
                <?php $active = ($w == $window) ? 'btn-primary' : 'btn-default'; ?>
                <a href="/?fn=spike&date=<?php echo $date; ?>&spike_time=<?php echo $spike_time; ?>&window=<?php echo $w; ?>"
                   class="btn btn-xs <?php echo $active; ?>"><?php echo $w; ?>分钟</a>
            <?php endforeach; ?>
            <span style="margin-left:20px;color:#999;">
                <?php if($spike_time): ?>
                    当前查看：<?php echo $date; ?> <?php echo $spike_time; ?> ~ <?php echo $spike_time_end; ?>
                <?php else: ?>
                    当前查看：最近 <?php echo $window; ?> 分钟
                <?php endif; ?>
            </span>
        </div>
    </div>

    <!-- 时间点选择器 -->
    <div class="row clearfix" style="margin-top:10px;">
        <div class="col-md-12 column">
            <div style="max-height:120px;overflow-y:auto;border:1px solid #ddd;padding:8px;border-radius:4px;">
                <?php echo $time_selector; ?>
            </div>
        </div>
    </div>

    <!-- 结果表格 -->
    <div class="row clearfix" style="margin-top:15px;">
        <div class="col-md-12 column">
            <p style="color:#999;font-size:12px;">倍数 = 当前请求数 / 过去7天同时段均值。≥2倍标红，≥1.5倍标黄。有历史数据时按倍数排序，否则按请求量排序。</p>
            <?php if(!$spike_time && !$is_today): ?>
                <div class="alert alert-warning">查看历史日期请点击上方时间点</div>
            <?php elseif(empty($result)): ?>
                <div class="alert alert-info">该时间段内暂无数据</div>
            <?php else: ?>
                <?php echo $table_str; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
