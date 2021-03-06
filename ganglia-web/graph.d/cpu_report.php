<?php

/* Pass in by reference! */
function graph_cpu_report( &$rrdtool_graph ) 
{
    global $conf,
           $context,
           $hostname,
           $range,
           $rrd_dir,
           $size;

    if ($conf['strip_domainname']) {
       $hostname = strip_domainname($hostname);
    }

    $title = 'CPU';
    if ($context != 'host') {
       $rrdtool_graph['title'] = $title;
    } else {
       $rrdtool_graph['title'] = "$hostname $title last $range";
    }
    $rrdtool_graph['upper-limit'] = '100';
    $rrdtool_graph['lower-limit'] = '0';
    $rrdtool_graph['vertical-label'] = 'Percent';
    $rrdtool_graph['height'] += ($size == 'medium') ? 28 : 0;
    $rrdtool_graph['extras'] = ($conf['graphreport_stats'] == true) ? ' --font LEGEND:7' : '';

    if ( $conf['graphreport_stats'] ) {
        $rrdtool_graph['height'] += ($size == 'medium') ? 16 : 0;
        $rmspace = '\\g';
    } else {
        $rmspace = '';
    }

    $series = '';

    // RB: Perform some formatting/spacing magic.. tinkered to fit
    //
    $eol1 = '';
    $space1 = '';
    $space2 = '';
    if ($size == 'small') {
       $eol1 = '\\l';
       $space1 = ' ';
       $space2 = '         ';
    } else if ($size == 'medium' || $size == 'default') {
       $eol1 = '';
       $space1 = ' ';
       $space2 = '';
    } else if ($size == 'large') {
       $eol1 = '';
       $space1 = '                 ';
       $space2 = '                 ';
    }

    $cpu_nice_def = '';
    $cpu_nice_cdef = '';

    if (file_exists("$rrd_dir/cpu_nice.rrd")) {
        $cpu_nice_def = "DEF:'cpu_nice'='${rrd_dir}/cpu_nice.rrd':'sum':AVERAGE ";
        $cpu_nice_cdef = "CDEF:'ccpu_nice'=cpu_nice,num_nodes,/ ";
    }

    if ($context != "host" ) {
        $series .= "DEF:'num_nodes'='${rrd_dir}/cpu_user.rrd':'num':AVERAGE ";
    }
    $series .= "DEF:'cpu_user'='${rrd_dir}/cpu_user.rrd':'sum':AVERAGE "
            . $cpu_nice_def
            . "DEF:'cpu_system'='${rrd_dir}/cpu_system.rrd':'sum':AVERAGE "
            . "DEF:'cpu_idle'='${rrd_dir}/cpu_idle.rrd':'sum':AVERAGE ";

    if (file_exists("$rrd_dir/cpu_wio.rrd")) {
        $series .= "DEF:'cpu_wio'='${rrd_dir}/cpu_wio.rrd':'sum':AVERAGE ";
    }

    if ($context != "host" ) {
        $series .= "CDEF:'ccpu_user'=cpu_user,num_nodes,/ "
                . $cpu_nice_cdef
                . "CDEF:'ccpu_system'=cpu_system,num_nodes,/ "
                . "CDEF:'ccpu_idle'=cpu_idle,num_nodes,/ ";

        if (file_exists("$rrd_dir/cpu_wio.rrd")) {
            $series .= "CDEF:'ccpu_wio'=cpu_wio,num_nodes,/ ";
        }

        $plot_prefix ='ccpu';
    } else {
        $plot_prefix ='cpu';
    }

    $series .= "AREA:'${plot_prefix}_user'#${conf['cpu_user_color']}:'User${rmspace}' ";

    if ( $conf['graphreport_stats'] ) {
        $series .= "CDEF:user_pos=${plot_prefix}_user,0,INF,LIMIT "
                . "VDEF:user_last=user_pos,LAST "
                . "VDEF:user_min=user_pos,MINIMUM "
                . "VDEF:user_avg=user_pos,AVERAGE "
                . "VDEF:user_max=user_pos,MAXIMUM "
                . "GPRINT:'user_last':'  ${space1}Now\:%5.1lf%%' "
                . "GPRINT:'user_min':'${space1}Min\:%5.1lf%%${eol1}' "
                . "GPRINT:'user_avg':'${space2}Avg\:%5.1lf%%' "
                . "GPRINT:'user_max':'${space1}Max\:%5.1lf%%\\l' ";
    }

    if (file_exists("$rrd_dir/cpu_nice.rrd")) {
        $series .= "STACK:'${plot_prefix}_nice'#${conf['cpu_nice_color']}:'Nice${rmspace}' ";

        if ( $conf['graphreport_stats'] ) {
            $series .= "CDEF:nice_pos=${plot_prefix}_nice,0,INF,LIMIT " 
                    . "VDEF:nice_last=nice_pos,LAST "
                    . "VDEF:nice_min=nice_pos,MINIMUM "
                    . "VDEF:nice_avg=nice_pos,AVERAGE "
                    . "VDEF:nice_max=nice_pos,MAXIMUM "
                    . "GPRINT:'nice_last':'  ${space1}Now\:%5.1lf%%' "
                    . "GPRINT:'nice_min':'${space1}Min\:%5.1lf%%${eol1}' "
                    . "GPRINT:'nice_avg':'${space2}Avg\:%5.1lf%%' "
                    . "GPRINT:'nice_max':'${space1}Max\:%5.1lf%%\\l' ";
        }
    }

    $series .= "STACK:'${plot_prefix}_system'#${conf['cpu_system_color']}:'System${rmspace}' ";

    if ( $conf['graphreport_stats'] ) {
        $series .= "CDEF:system_pos=${plot_prefix}_system,0,INF,LIMIT "
                . "VDEF:system_last=system_pos,LAST "
                . "VDEF:system_min=system_pos,MINIMUM "
                . "VDEF:system_avg=system_pos,AVERAGE "
                . "VDEF:system_max=system_pos,MAXIMUM "
                . "GPRINT:'system_last':'${space1}Now\:%5.1lf%%' "
                . "GPRINT:'system_min':'${space1}Min\:%5.1lf%%${eol1}' "
                . "GPRINT:'system_avg':'${space2}Avg\:%5.1lf%%' "
                . "GPRINT:'system_max':'${space1}Max\:%5.1lf%%\\l' ";
    }

    if (file_exists("$rrd_dir/cpu_wio.rrd")) {
        $series .= "STACK:'${plot_prefix}_wio'#${conf['cpu_wio_color']}:'Wait${rmspace}' ";

        if ( $conf['graphreport_stats'] ) {
                $series .= "CDEF:wio_pos=${plot_prefix}_wio,0,INF,LIMIT "
                        . "VDEF:wio_last=wio_pos,LAST "
                        . "VDEF:wio_min=wio_pos,MINIMUM "
                        . "VDEF:wio_avg=wio_pos,AVERAGE "
                        . "VDEF:wio_max=wio_pos,MAXIMUM "
                        . "GPRINT:'wio_last':'  ${space1}Now\:%5.1lf%%' "
                        . "GPRINT:'wio_min':'${space1}Min\:%5.1lf%%${eol1}' "
                        . "GPRINT:'wio_avg':'${space2}Avg\:%5.1lf%%' "
                        . "GPRINT:'wio_max':'${space1}Max\:%5.1lf%%\\l' ";
        }
    }

    $series .= "STACK:'${plot_prefix}_idle'#${conf['cpu_idle_color']}:'Idle${rmspace}' ";

    if ( $conf['graphreport_stats'] ) {
                $series .= "CDEF:idle_pos=${plot_prefix}_idle,0,INF,LIMIT "
                        . "VDEF:idle_last=idle_pos,LAST "
                        . "VDEF:idle_min=idle_pos,MINIMUM "
                        . "VDEF:idle_avg=idle_pos,AVERAGE "
                        . "VDEF:idle_max=idle_pos,MAXIMUM "
                        . "GPRINT:'idle_last':'  ${space1}Now\:%5.1lf%%' "
                        . "GPRINT:'idle_min':'${space1}Min\:%5.1lf%%${eol1}' "
                        . "GPRINT:'idle_avg':'${space2}Avg\:%5.1lf%%' "
                        . "GPRINT:'idle_max':'${space1}Max\:%5.1lf%%\\l' ";
    }

    $rrdtool_graph['series'] = $series;

    return $rrdtool_graph;
}

?>
