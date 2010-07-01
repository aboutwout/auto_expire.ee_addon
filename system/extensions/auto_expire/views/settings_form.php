<style>
.nostyles, .mainTable {
  width:100%;
  margin:0 !important;
  border:none;
  table-layout:fixed;
}

.mainTable th {
  text-align:left;
}

.nostyles td {
  padding:0;
  border-left:none !important;
  border-bottom:none !important;
}

.nostyles td:first-child { border-right:1px dotted #D0D7DF; }
table.nostyles td:last-child { border-right:none !important; }

</style>
<?php if(count($vars['weblogs']) == 0) : ?>
<p style="margin-bottom:1.5em">You haven't created any channels yet. Go to the <a href="<?=BASE.AMP.'C=admin_content'.AMP.'M=channel_add';?>">Channel Management</a> and create one first.</p>
<?php else : ?>

  <?php if($settings_saved == true) : ?>
    <div style="background:#FFF6BF;border:1px solid #FFD324;color:#514721;padding:1em;font-size:14px;font-weight:bold">Settings saved</div>
  <?php endif;?>
  
  <?=  $DSP->form_open('C=admin'.AMP.'M=utilities'.AMP.'P=extension_settings'.AMP.'name=auto_expire', array(), array('name' => 'auto_expire')) ?>
  <?php /* $DSP->form_open('C=admin'.AMP.'M=utilities'.AMP.'P=save_extension_settings', array(), array('name' => 'auto_expire')); */ ?>

<table class="tableBorder" border="0" style="margin-top:18px; width:100%" cellspacing="0" cellpadding="0">
  <thead>
    <tr>
      <td class="tableHeading">Channel</td>
      <td class="tableHeading" colspan="2">Settings</td>
    </tr>
  </thead>
  <tbody>
  <?php
    $j = $i = 0;
    foreach($vars['weblogs'] as $weblog) :
  ?>
    <tr class="<?=($i%2) ? 'even' : 'odd';?>">
      <td class="tableCellOne" style="width:25%;"><b><?=$weblog['title']?></b></td>
      <td class="tableCellOne">
        <div style="margin-bottom:.5em"><?=lang('pref_auto_expire')?></div>
        <input dir="ltr" style="width:20%;margin-right:5px" type="text" name="time_diff[<?=$weblog['id']?>]" id="time_diff" value="<?=$weblog['time_diff']?>" size="" maxlength="" class="" tabindex="<?=++$j?>" /> 
        <select name="time_unit[<?=$weblog['id']?>]" class="select" style="width:70%" tabindex="<?=++$j?>">
          <option value="0"><?=lang('select_period')?></option>
      <?php foreach ($vars['time_units'] as $key => $val) : ?>          
          <option value="<?=$key?>" <?php if($key == $weblog['time_unit']) : ?> selected="selected"<?php endif; ?>><?=lang($val)?></option>
      <?php endforeach; ?>
        </select>
      </td>
      <td class="tableCellOne">
        <div style="margin-bottom:.5em"><?=lang('pref_change_status')?></div>
        <select name="status[<?=$weblog['id']?>]" class="select" style="width:20em" tabindex="<?=++$j?>">
          <option value="0"><?=lang('pref_dont_change_status')?></option>
    <?php foreach ($weblog['statuses']->result as $status) : ?>          
          <option value="<?=$status['id']?>" <?php if($status['id'] == $weblog['status']) : ?> selected="selected"<?php endif; ?>><?=ucfirst($status['name'])?></option>
    <?php endforeach; ?>
        </select>
      </td>
    </tr>
  <?php
    $i++;
    endforeach;
  ?>
  </tbody>
</table>
<div style="padding:10px 0"><input type="submit" value="Save settings" class="submit" /></div>
<?=  $DSP->form_close(); ?>
<?php endif; ?>