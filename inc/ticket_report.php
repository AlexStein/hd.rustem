<?php
session_start();
include ("../functions.inc.php");

if (validate_user($_SESSION['helpdesk_user_id'], $_SESSION['code'])) {
  if ($_SESSION['helpdesk_user_id']) {
    include ("head.inc.php");
    include ("navbar.inc.php");

    $startdate = date('d.m.Y', strtotime("first day of -2 month"));
    $enddate = date("d.m.Y", time());
?>
<div class="container">
  <input type="hidden" id="main_last_new_ticket" value="<?php echo get_last_ticket_new($_SESSION['helpdesk_user_id']); ?>">
  <div class="page-header" style="margin-top: -15px;">
    <div class="row">
      <div class="col-md-6">
        <h3><i class="fa fa-bar-chart-o"></i> <?php echo lang('REPORT_main'); ?></h3>
      </div>
    </div>
  </div>
  <div class="row" >
    <div class="col-md-9" style="width:100%;">
      <h4><center><?php echo lang('REPORT_unit_ago'); ?> <time id="a" datetime="2017-04-01 00:00:00"></center></h4>
      <h5><center><?php echo lang('T_from') . ' ' .$startdate .' ' .lang('T_to') .' ' .$enddate ?></center></h5>
<?php
    $user_id=id_of_user($_SESSION['helpdesk_user_login']);
    $unit_user=unit_of_user($user_id);
    $priv_val=priv_status($user_id);
    $ee=explode(",", $unit_user);
    $in_query = "";
    $vv = array();
    foreach($ee as $key=>$value) {
        $in_query = $in_query . ' :val_' . $key . ', ';
        $vv[":val_" . $key] = $value;
    }
    $in_query = substr($in_query, 0, -2);
    if ($priv_val == 0) {
        $stmt = $dbConnection->prepare('SELECT
                            id, prio, user_init_id, user_to_id, date_create, subj, msg, client_id, unit_id, status, hash_name, is_read, lock_by, ok_by, ok_date
                            from tickets
                            where (unit_id IN ('. $in_query. ') or user_init_id=:user_id)
                            and date_create > date_add(date_add(LAST_DAY(now()), interval 1 DAY),interval - 3 MONTH)
                            order by id DESC');
        $paramss=array(':user_id'=>$user_id);
        $stmt->execute(array_merge($vv,$paramss));
        $res1 = $stmt->fetchAll();
    } else if ($priv_val == 1) {
        $stmt = $dbConnection->prepare('SELECT
                            id, prio, user_init_id, user_to_id, date_create, subj, msg, client_id, unit_id, status, hash_name, is_read, lock_by, ok_by, ok_date
                            from tickets
                            where (((user_to_id=:user_id or user_to_id=0) and unit_id IN ('.$in_query.')) or user_init_id=:user_id2 )
                            and date_create > date_add(date_add(LAST_DAY(now()), interval 1 DAY),interval - 3 MONTH)
                            order by id DESC');
        $paramss=array(':user_id'=>$user_id, ':user_id2'=>$user_id);
        $stmt->execute(array_merge($vv,$paramss));
        $res1 = $stmt->fetchAll();
    } else if ($priv_val == 2) {
        $stmt = $dbConnection->prepare('SELECT
                            id, prio, user_init_id, user_to_id, date_create, subj, msg, client_id, unit_id, status, hash_name, is_read, lock_by, ok_by, ok_date
                            from tickets
                            where date_create > date_add(date_add(LAST_DAY(now()), interval 1 DAY),interval - 3 MONTH)
                            order by id DESC');

        $stmt->execute();
        $res1 = $stmt->fetchAll();
    }

    $aha = count($res1);
    if ($aha == "0") {
?>
        <div id="spinner" class="well well-large well-transparent lead">
            <center><?=lang('MSG_no_records');?></center>
        </div>
<?php
    } else if ($aha <> "0") {
?>
        <input type="hidden" value="<?php echo get_total_pages('arch', $user_id); ?>" id="val_menu">
        <input type="hidden" value="<?php echo $user_id; ?>" id="user_id">
        <input type="hidden" value="" id="total_tickets">
        <input type="hidden" value="" id="last_total_tickets">

        <table class="table table-bordered table-hover" style=" font-size: 14px; ">
            <thead>
                <tr>
                    <th><center>#</center></th>
                    <th><center>
                        <div id="sort_prio" action="<?=$_SESSION['helpdesk_sort_prio'];?>">
                            <i class="fa fa-info-circle" data-toggle="tooltip" data-placement="bottom" title="<?=lang('t_LIST_prio');?>"></i><?=$prio_icon;?>
                        </div></center>
                    </th>
                    <th><center><?=lang('t_LIST_subj');?></center></th>
                    <th><center><?=lang('t_LIST_worker');?></center></th>
                    <th><center><?=lang('t_LIST_create');?></center></th>
                    <th><center><?=lang('t_LIST_ago');?></center></th>
                    <th><center><?=lang('t_LIST_init');?></center></th>
                    <th><center><?=lang('t_LIST_to');?></center></th>
                    <th><center><?=lang('t_LIST_status');?></center></th>
                    <th><center><?=lang('t_list_a_user_ok');?></center></th>
                    <th><center><?=lang('t_list_a_date_ok');?></center></th>
                </tr>
            </thead>
            <tbody>

<?php
        foreach($res1 as $row) {
            if ($row['user_to_id'] <> 0 ) {
                $to_text="<div class=''>".nameshort(name_of_user_ret($row['user_to_id']))."</div>";
            }

            if ($row['user_to_id'] == 0 ) {
                $to_text="<strong data-toggle=\"tooltip\" data-placement=\"bottom\" title=\"".view_array(get_unit_name_return($row['unit_id']))."\">".lang('t_list_a_all')."</strong>";
            }
            ////////Раскрашивает и подписывает кнопки//////////////////////////////////////////////
            if ($row['is_read'] == "0") { $style="bold_for_new"; }
            if ($row['is_read'] <> "0") { $style=""; }
            if ($row['status'] == "1") {
                $style="success";
            }

            if ($row['status'] == "0") {
                if ($lb <> "0") {
                    if ($lb == $user_id) {$style="warning";}
                    if ($lb <> $user_id) {$style="active";}
                }
            }
            ////////////////////////////////////////////////////////////////////////////////////////

            ////////Показывает приоритет////////////////////////////////////////////////////////////
            $prio="<span class=\"label label-info\" data-toggle=\"tooltip\" data-placement=\"bottom\" title=\"".lang('t_list_a_p_norm')."\"><i class=\"fa fa-minus\"></i></span>";

            if ($row['prio'] == "0") {
                $prio= "<span class=\"label label-primary\" data-toggle=\"tooltip\" data-placement=\"bottom\" title=\"".lang('t_list_a_p_low')."\"><i class=\"fa fa-arrow-down\"></i></span>";
            }

            if ($row['prio'] == "2") {
                $prio= "<span class=\"label label-danger\" data-toggle=\"tooltip\" data-placement=\"bottom\" title=\"".lang('t_list_a_p_high')."\"><i class=\"fa fa-arrow-up\"></i></span>";
            }
            ////////////////////////////////////////////////////////////////////////////////////////

            ////////Показывает labels///////////////////////////////////////////////////////////////
            if ($row['status'] == 1) {$st=  "<span class=\"label label-success\"><i class=\"fa fa-check-circle\"></i> ".lang('t_list_a_oko')." ".nameshort(name_of_user_ret($ob))."</span>";
                $t_ago=get_date_ok($row['date_create'], $row['id']);
            }
            if ($row['status'] == 0) {
                $t_ago=$row['date_create'];
                if ($lb <> 0) {
                    if ($lb == $user_id) {
                        $st="<span class=\"label label-warning\"><i class=\"fa fa-gavel\"></i> ".lang('t_list_a_lock_i')."</span>";
                    }

                    if ($lb <> $user_id) {
                        $st="<span class=\"label label-default\"><i class=\"fa fa-gavel\"></i> ".lang('t_list_a_lock_u')." ".nameshort(name_of_user_ret($lb))."</span>";
                    }
                }
                if ($lb == 0) {
                    $st="<span class=\"label label-primary\"><i class=\"fa fa-clock-o\"></i> ".lang('t_list_a_hold')."</span>";
                }
            }
            ////////////////////////////////////////////////////////////////////////////////////////

?>
                <tr id="tr_<?php echo $row['id']; ?>" class="<?=$style?>">
                    <td style=" vertical-align: middle; "><small><center><?php echo $row['id']; ?></center></small></td>
                    <td style=" vertical-align: middle; "><small class="<?=$muclass;?>"><center><?=$prio?></center></small></td>
                    <td style=" vertical-align: middle; "><small><a href="ticket?<?php echo $row['hash_name']; ?>" title="<?php cutstr_title($row['msg']); ?>"><?php cutstr(make_html($row['subj'], 'no')); ?></a></small></td>
                    <td style=" vertical-align: middle; "><small><?php name_of_client($row['client_id']); ?></small></td>
                    <td style=" vertical-align: middle; "><small><center><time id="c" datetime="<?=$row['date_create']; ?>"></time></center></small></td>
                    <td style=" vertical-align: middle; "><small class="<?=$muclass;?>"><center><time id="a" datetime="<?=$t_ago;?>"></time></center></small></td>
                    <td style=" vertical-align: middle; "><small><?=nameshort(name_of_user_ret($row['user_init_id'])); ?></small></td>
                    <td style=" vertical-align: middle; "><small><?=$to_text?></small></td>
                    <td style=" vertical-align: middle; "><small><center><?=$st;?> </center></small></td>
                    <td style=" vertical-align: middle; "><small><?=nameshort(name_of_user_ret($row['ok_by'])); ?></small></td>
                    <td style=" vertical-align: middle; "><small><center><time id="c" datetime="<?=$row['ok_date']; ?>"></time></center></small></td>
                </tr>
<?php
        }
?>
            </tbody>
        </table>
    </div>
  </div>
</div>
<?php
include ("footer.inc.php");
?>

<?php
    }
  }
} else {
  include '../auth.php';
}
?>
