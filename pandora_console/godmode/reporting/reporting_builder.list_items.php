<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
global $config;

// Login check
check_login ();

if (! check_acl ($config['id_user'], 0, "IW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	exit;
}

include_once($config['homedir'] . "/include/functions_agents.php");
enterprise_include_once ('include/functions_metaconsole.php');

if ($config ['metaconsole'] == 1) {
	$agents = array();
	$agents = metaconsole_get_report_agents($idReport);
	$modules = array();
	$modules = metaconsole_get_report_modules($idReport);
	$types = array ();
	$types = metaconsole_get_report_types($idReport);
}
else {
	//FORM FILTER
	switch ($config['dbtype']){
		case "mysql":
		case "postgresql":
			$rows = db_get_all_rows_sql('
				SELECT t5.nombre, t5.id_agente
				FROM
					(
					SELECT t1.*, id_agente
					FROM treport_content AS t1
						LEFT JOIN tagente_modulo AS t2
							ON t1.id_agent_module = id_agente_modulo
					) AS t4
					INNER JOIN tagente AS t5
						ON (t4.id_agent = t5.id_agente OR t4.id_agente = t5.id_agente)
				WHERE t4.id_report = ' . $idReport);
			break;
		case "oracle":
			$rows = db_get_all_rows_sql('
				SELECT t5.nombre, t5.id_agente
				FROM
					(
					SELECT t1.*, id_agente
					FROM treport_content t1
						LEFT JOIN tagente_modulo t2
							ON t1.id_agent_module = id_agente_modulo
					) t4
					INNER JOIN tagente t5
						ON (t4.id_agent = t5.id_agente OR t4.id_agente = t5.id_agente)
				WHERE t4.id_report = ' . $idReport);
			break;
	}

	if ($rows === false) {
		$rows = array();
	}

	$agents = array();
	foreach ($rows as $row) {
		$agents[$row['id_agente']] = $row['nombre'];
	}

	switch ($config['dbtype']){
		case "mysql":
		case "postgresql":
			$rows = db_get_all_rows_sql('
				SELECT t1.id_agent_module, t2.nombre
				FROM treport_content AS t1
					INNER JOIN tagente_modulo AS t2
						ON t1.id_agent_module = t2.id_agente_modulo
				WHERE t1.id_report = ' . $idReport);
			break;
		case "oracle":
			$rows = db_get_all_rows_sql('
				SELECT t1.id_agent_module, t2.nombre
				FROM treport_content t1
					INNER JOIN tagente_modulo t2
						ON t1.id_agent_module = t2.id_agente_modulo
				WHERE t1.id_report = ' . $idReport);
			break;
	}
	if ($rows === false) {
		$rows = array();
	}

	$modules = array();
	foreach ($rows as $row) {
		$modules[$row['id_agent_module']] = $row['nombre'];
	}

	$rows = db_get_all_rows_sql('
		SELECT DISTINCT(type)
		FROM treport_content
		WHERE id_report = ' . $idReport);
	if ($rows === false) {
		$rows = array();
	}

	$types = array();
	foreach ($rows as $row) {
		$types[$row['type']] = get_report_name($row['type']);
	}
}

$agentFilter = get_parameter('agent_filter', 0);
$moduleFilter = get_parameter('module_filter', 0);
$typeFilter = get_parameter('type_filter', 0);

$filterEnable = true;
$urlFilter = '';
if (($agentFilter == 0) && ($moduleFilter == 0) && ($typeFilter == 0)) {
	$filterEnable = false;
}

$urlFilter = '&agent_filter=' . $agentFilter . '&module_filter=' . $moduleFilter . '&type_filter=' . $typeFilter;

echo '<a href="javascript: toggleFormFilter();"><b>'.__('Items filter').'</b> ' . html_print_image("images/down.png", true, array("title" => __('Toggle filter(s)'), "id" => 'image_form_filter')) . '</a>';

$table = null;
$table->width = '80%';
$table->data[0][0] = __('Agents');
$table->data[0][1] = html_print_select($agents, 'agent_filter', $agentFilter, '', __('All'), 0, true);
$table->data[0][2] = __('Modules');
$table->data[0][3] = html_print_select($modules, 'module_filter', $moduleFilter, '', __('All'), 0, true);
$table->data[1][0] = __('Type');
$table->data[1][1] = html_print_select($types, 'type_filter', $typeFilter, '', __('All'), 0, true);

echo '<div id="form_filter" style="display: none;">';
echo '<form method="post" action ="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=filter&&id_report=' . $idReport . '">';

html_print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_submit_button(__('Filter'), 'filter', false, 'class="sub upd"');
html_print_input_hidden('action', 'filter');
echo '</div>';
echo '</form>';
echo '</div>';

$where = '1=1';
if ($typeFilter != '0') {
	$where .= ' AND type = "' . $typeFilter . '"';
}
if($agentFilter != 0) {
	$where .= ' AND id_agent = ' . $agentFilter;
}
if($moduleFilter != 0) {
	$where .= ' AND id_agent_module = ' . $moduleFilter;
}

switch ($config["dbtype"]) {
	case "mysql":
		$items = db_get_all_rows_sql('SELECT *
			FROM treport_content
			WHERE ' . $where . ' AND id_report = ' . $idReport . '
			ORDER BY `order`
			LIMIT ' . $offset . ', ' . $config["block_size"]);
		break;
	case "postgresql":
		$items = db_get_all_rows_sql('SELECT *
			FROM treport_content
			WHERE ' . $where . ' AND id_report = ' . $idReport . '
			ORDER BY "order"
			LIMIT ' . $config["block_size"] . ' OFFSET ' . $offset);
		break;
	case "oracle":
		$set = array();
		$set['limit'] = $config["block_size"];
		$set['offset'] = $offset;		
		$items = oracle_recode_query ('SELECT * FROM treport_content WHERE ' . $where . ' AND id_report = ' . $idReport . ' ORDER BY "order"', $set, 'AND', false);
		// Delete rnum row generated by oracle_recode_query() function
		if ($items !== false){		
			for ($i=0; $i < count($items); $i++) {
				unset($items[$i]['rnum']);		
			}
		}
		break;
}
$countItems = db_get_sql('SELECT COUNT(id_rc) FROM treport_content WHERE ' . $where . ' AND id_report = ' . $idReport);
$table = null;

if ($items){
	$table->width = '100%';
	$table->head[0] = '<span title="' . __('Sort') . '">' . __('S.') . '</span>';
	$table->head[1] = __('Type');
	if (!$filterEnable) {
		$table->head[1] .= ' <a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=order&dir=up&field=type&id_report=' . $idReport . $urlFilter . '">' . html_print_image("images/sort_up.png", true, array("title" => __('Ascendent'))) . '</a>' .
			'<a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=order&dir=down&field=type&id_report=' . $idReport . $urlFilter . '">' . html_print_image("images/sort_down.png", true, array("title" => __('Descent'))) . '</a>';
	}
	$table->head[2] = __('Agent');
	if (!$filterEnable) {
		$table->head[2] .= ' <a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=order&dir=up&field=agent&id_report=' . $idReport . $urlFilter . '">' . html_print_image("images/sort_up.png", true, array("title" => __('Ascendent'))) . '</a>' .
			'<a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=order&dir=down&field=agent&id_report=' . $idReport . $urlFilter . '">' . html_print_image("images/sort_down.png", true, array("title" => __('Descent'))) . '</a>';
	}
	$table->head[3] = __('Module');
	if (!$filterEnable) {
		$table->head[3] .= ' <a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=order&dir=up&field=module&id_report=' . $idReport . $urlFilter . '">' . html_print_image("images/sort_up.png", true, array("title" => __('Ascendent'))) . '</a>' .
			'<a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=order&dir=down&field=module&id_report=' . $idReport . $urlFilter . '">' . html_print_image("images/sort_down.png", true, array("title" => __('Descent'))) . '</a>';
	}
	$table->head[4] = __('Period');
	$table->head[5] = __('Description');
	$table->head[6] = '<span title="' . __('Options') . '">' . __('O.') . '</span>';

	$table->align[6] = 'center';
} else {
	echo '<br><br><div class="nf">'. __('No items') . '</div>';
}
	$lastPage = true;
	if (((($offset == 0) && ($config["block_size"] > $countItems)) ||
		($countItems >= ($config["block_size"] + $offset))) &&
		($countItems > $config["block_size"])) {
		$lastPage = false;
	}

	$count = 0;
	$rowPair = true;

if ($items === false) {
	$items = array();
}

foreach ($items as $item) {
	if ($rowPair)
		$table->rowclass[$count] = 'rowPair';
	else
		$table->rowclass[$count] = 'rowOdd';
	$rowPair = !$rowPair;
	
	$row = array();
	
	if ((reset($items) == $item) && ($offset == 0)) {
		$row[0] = '<span style="display: block; float: left; width: 16px;">&nbsp;</span>';
	}
	else {
		$row[0] = '<a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=order&dir=up&id_report=' . $idReport . '&id_item=' . $item['id_rc'] . $urlFilter . '">' . html_print_image("images/up.png", true, array("title" => __('Move to up'))) . '</a>';
	}
	
	if ((end($items) == $item) && $lastPage) {
		$row[0] .= '<span style="width: 16px;">&nbsp;</span>';
	}
	else {
		$row[0] .= '<a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=order&dir=down&id_report=' . $idReport . '&id_item=' . $item['id_rc'] . $urlFilter . '">' . html_print_image("images/down.png", true, array("title" => __('Move to down'))) . '</a>';
	}
	
	if ($filterEnable) {
		$row[0] = '';
	}
	
	$row[1] = get_report_name($item['type']);
	
	$server_name = $item ['server_name'];
	
	if (($config ['metaconsole'] == 1) && ($server_name != '')) {
		
		$connection = metaconsole_get_connection($server_name);
		if (!metaconsole_load_external_db($connection)) {
			//ui_print_error_message ("Error connecting to ".$server_name);
			continue;
		}
	}
	
	if ($item['id_agent'] == 0) {
		if ($item['id_agent_module'] == '') {
			$row[2] = '-';
			$row[3] = '-';
		}
		else {
			$row[2] = ui_print_truncate_text(agents_get_name(agents_get_module_id($item['id_agent_module'])), 20);
			$row[3] = ui_print_truncate_text(db_get_value_filter('nombre', 'tagente_modulo', array('id_agente_modulo' => $item['id_agent_module'])), 20);
		}
	}
	else {
		$row[2] = ui_print_truncate_text(agents_get_name($item['id_agent']), 20);
		
		if ($item['id_agent_module'] == '') {
			$row [3] = '-';
		}
		else {
			$row[3] = ui_print_truncate_text(db_get_value_filter('nombre', 'tagente_modulo', array('id_agente_modulo' => $item['id_agent_module'])),20);
		}
	}
	
	$row[4] = human_time_description_raw($item['period']);
	
	if ($item['description'] == '') {
		$row[5] = '-';
	}
	else {
		$row[5] = ui_print_truncate_text($item['description'], 25, true, true);
	}
	
	$row[6] = '<a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&tab=item_editor&action=edit&id_report=' . $idReport . '&id_item=' . $item['id_rc'] . '">' . html_print_image("images/wrench_orange.png", true, array("title" => __('Edit'))) . '</a>';
	$row[6] .= '&nbsp;';
	$row[6] .= '<a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=delete&id_report=' . $idReport . '&id_item=' . $item['id_rc'] . $urlFilter . '">' . html_print_image("images/cross.png", true, array("title" => __('Delete'))) .'</a>';
	
	$table->data[] = $row;
	$count++;
	//Restore db connection
	if (($config ['metaconsole'] == 1) && ($server_name != '') ) {
		metaconsole_restore_db();
	}
}
ui_pagination ($countItems, 'index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=edit&id_report=' . $idReport . $urlFilter);
html_print_table($table);
ui_pagination ($countItems, 'index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=edit&id_report=' . $idReport . $urlFilter);
?>
<script type="text/javascript">
function toggleFormFilter() {
	if ($("#form_filter").css('display') == 'none') {
		$("#image_form_filter").attr('src', 'images/up.png');
		$("#form_filter").css('display','');
	}
	else {
		$("#image_form_filter").attr('src', 'images/down.png');
		$("#form_filter").css('display','none');
	}
}
</script>
