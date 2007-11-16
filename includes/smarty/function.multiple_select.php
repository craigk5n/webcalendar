<?php

function smarty_function_multiple_select ( $params, &$smarty ) {

$select_name = $params['name'];
$select_array = $params['options'];
$ret = '<div id="container">
	<div id="' . $select_name . '_container">
		<select id="' . $select_name . 'select">';
          foreach ( $select_array as $option ) {
			$ret .= '<option value="' .$option . '">' . $option . '</option>';
          }
		$ret .= '</select>
		<a href="" id="' . $select_name . 'select_open">Select Multiple</a>
		<div style="display:none;" id="' . $select_name . 'select_options" class="select_multiple_container">
		<div class="select_multiple_header">Select Multiple Felines</div>
		<table cellspacing="0" cellpadding="0" class="select_multiple_table" width="100%">';
          foreach ( $select_array as $option ) { 
	      $ret .= '<tr class="odd">
            <td class="select_multiple_name">$val</td>
		    <td class="select_multiple_checkbox"><input type="checkbox" value="$options"/></td>
		  </tr>';
          }
		$ret .= '</table>
        <div class="select_multiple_submit">
          <input type="button" value="Done" id="'. $select_name . 'select_close"/></div>
		</div>
	</div>
 </div>';

 $ret .= <<<EOT
	<script>		

		var select_multiple_two = new Control.SelectMultiple('select_multiple_two','select_multiple_two_options',{
			checkboxSelector: 'table.select_multiple_table tr td input[type=checkbox]',
			nameSelector: 'table.select_multiple_table tr td.select_multiple_name',
			afterChange: function(){
				if(select_multiple_two && select_multiple_two.setSelectedRows)
					select_multiple_two.setSelectedRows();
			}
		});
		
		//adds and removes highlighting from table rows
		select_multiple_two.setSelectedRows = function(){
			this.checkboxes.each(function(checkbox){
				var tr = $(checkbox.parentNode.parentNode);
				tr.removeClassName('selected');
				if(checkbox.checked)
					tr.addClassName('selected');
			});
		}.bind(select_multiple_two);
		select_multiple_two.checkboxes.each(function(checkbox){
			$(checkbox).observe('click',select_multiple_two.setSelectedRows);
		});
		select_multiple_two.setSelectedRows();
		
		//link open and closing
		$('select_multiple_two_open').observe('click',function(event){
			$(this.select).style.visibility = 'hidden';
			new Effect.BlindDown(this.container,{
				duration: 0.3
			});
			Event.stop(event);
			return false;
		}.bindAsEventListener(select_multiple_two));
		$('select_multiple_two_close').observe('click',function(event){
			$(this.select).style.visibility = 'visible';
			new Effect.BlindUp(this.container,{
				duration: 0.3
			});
			Event.stop(event);
			return false;
		}.bindAsEventListener(select_multiple_two));		
	</script>
EOT;
return $ret;
}

?>