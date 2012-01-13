<?php
/*
** Zabbix
** Copyright (C) 2000-2012 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/
?>
<?php
require_once('include/config.inc.php');

$page['title'] = _('Configuration of Zabbix');
$page['file'] = 'adm.valuemapping.php';

require_once('include/page_header.php');
?>
<?php
$fields = array(
//							TYPE		OPTIONAL FLAGS			VALIDATION		EXCEPTION
	'valuemapid' => array(	T_ZBX_INT,	O_NO,	P_SYS,			DB_ID,			'(isset({form})&&({form}=="update"))||isset({delete})'),
	'mapname' => array(		T_ZBX_STR,	O_OPT,	null,			NOT_EMPTY, 		'isset({save})'),
	'mappings' => array(	T_ZBX_STR,	O_OPT,	null,			null,	null),

	'save' => array(		T_ZBX_STR,	O_OPT,	P_SYS|P_ACT,	null,	null),
	'delete' => array(		T_ZBX_STR,	O_OPT,	P_SYS|P_ACT,	null,	null),
	'form' => array(		T_ZBX_STR,	O_OPT,	P_SYS,			null,	null),
	'form_refresh' => array(T_ZBX_INT,	O_OPT,	null,			null,	null)
);
?>
<?php
check_fields($fields);

try{
	if (isset($_REQUEST['add_map'])) {
		if (!zbx_is_int($_REQUEST['add_value'])) {
			info(_('Value maps are used to create a mapping between numeric values and string representations.'));
			show_messages(false, null, _('Cannot add value map'));
		}
		else {
			$added = false;
			foreach ($_REQUEST['valuemap'] as $num => $valueMap) {
				if ($valueMap['value'] == $_REQUEST['add_value']) {
					$_REQUEST['valuemap'][$num]['newvalue'] = $_REQUEST['add_newvalue'];
					$added = true;
					break;
				}
			}

			if (!$added) {
				$_REQUEST['valuemap'][] = array(
					'value' => $_REQUEST['add_value'],
					'newvalue' => $_REQUEST['add_newvalue']
				);
			}

			unset($_REQUEST['add_value'], $_REQUEST['add_newvalue']);
		}
	}
	elseif (isset($_REQUEST['save'])) {
		$transaction = DBstart();

		$valueMap = array('name' => get_request('mapname'));
		$mappings = get_request('mappings', array());

		if (isset($_REQUEST['valuemapid'])) {
			$msg_ok = _('Value map updated');
			$msg_fail = _('Cannot update value map');
			$audit_action = AUDIT_ACTION_UPDATE;

			$valueMap['valuemapid'] = get_request('valuemapid');
			updateValueMap($valueMap, $mappings);
		}
		else{
			$msg_ok = _('Value map added');
			$msg_fail = _('Cannot add value map');
			$audit_action = AUDIT_ACTION_ADD;

			addValueMap($valueMap, $mappings);
		}

		add_audit($audit_action, AUDIT_RESOURCE_VALUE_MAP, _s('Value map [%1$s]', $valueMap['name']));

		show_messages(true, $msg_ok);

		unset($_REQUEST['form']);

		DBend(true);
	}
	elseif (isset($_REQUEST['delete']) && isset($_REQUEST['valuemapid'])) {
		$transaction = DBstart();

		$msg_ok = _('Value map deleted');
		$msg_fail = _('Cannot delete value map');

		$sql = 'SELECT m.name, m.valuemapid'.
				' FROM valuemaps m WHERE '.DBin_node('m.valuemapid').
				' AND m.valuemapid='.$_REQUEST['valuemapid'];
		if ($map_data = DBfetch(DBselect($sql))) {
			deleteValueMap($_REQUEST['valuemapid']);
		}

		add_audit(
			AUDIT_ACTION_DELETE,
			AUDIT_RESOURCE_VALUE_MAP,
			_s('Value map [%1$s] [%2$s]', $map_data['name'], $map_data['valuemapid'])
		);

		show_messages(true, $msg_ok);

		unset($_REQUEST['form']);

		DBend(true);
	}
}
catch (Exception $e) {
	if ($transaction) {
		DBend(false);
	}
	error($e->getMessage());
	show_messages(false, null, $msg_fail);
}


$form = new CForm();
$form->cleanItems();
$cmbConf = new CComboBox('configDropDown', 'adm.valuemapping.php', 'redirect(this.options[this.selectedIndex].value);');
$cmbConf->addItems(array(
	'adm.gui.php' => _('GUI'),
	'adm.housekeeper.php' => _('Housekeeper'),
	'adm.images.php' => _('Images'),
	'adm.iconmapping.php' => _('Icon mapping'),
	'adm.regexps.php' => _('Regular expressions'),
	'adm.macros.php' => _('Macros'),
	'adm.valuemapping.php' => _('Value mapping'),
	'adm.workingtime.php' => _('Working time'),
	'adm.triggerseverities.php' => _('Trigger severities'),
	'adm.triggerdisplayingoptions.php' => _('Trigger displaying options'),
	'adm.other.php' => _('Other')
));
$form->addItem($cmbConf);
if (!isset($_REQUEST['form'])) {
	$form->addItem(new CSubmit('form', _('Create value map')));
}


$cnf_wdgt = new CWidget();
$cnf_wdgt->addPageHeader(_('CONFIGURATION OF ZABBIX'), $form);

$data = array();
if (isset($_REQUEST['form'])) {
	$data['form'] = get_request('form', 1);
	$data['form_refresh'] = get_request('form_refresh', 0);
	$data['valuemapid'] = get_request('valuemapid');
	$data['valuemap'] = array();
	$data['mapname'] = '';
	$data['confirmMessage'] = null;
	$data['add_value'] = get_request('add_value');
	$data['add_newvalue'] = get_request('add_newvalue');

	if (!empty($data['valuemapid'])) {
		$db_valuemap = DBfetch(DBselect('SELECT v.name FROM valuemaps v WHERE v.valuemapid='.$data['valuemapid']));
		$data['mapname'] = $db_valuemap['name'];

		if (empty($data['form_refresh'])) {
			$data['valuemap'] = DBfetchArray(DBselect('SELECT m.mappingid,m.value,m.newvalue FROM mappings m WHERE m.valuemapid='.$data['valuemapid']));
		}
		else {
			$data['mapname'] = get_request('mapname', '');
			$data['valuemap'] = get_request('valuemap', array());
		}

		$valuemap_count = DBfetch(DBselect('SELECT COUNT(i.itemid) as cnt FROM items i WHERE i.valuemapid='.$data['valuemapid']));
		if ($valuemap_count['cnt']) {
			$data['confirmMessage'] = _n('Delete selected value mapping? It is used for %d item!', 'Delete selected value mapping? It is used for %d items!', $valuemap_count['cnt']);
		}
		else {
			$data['confirmMessage'] = _('Delete selected value mapping?');
		}
	}

	if (empty($data['valuemapid']) && !empty($data['form_refresh'])) {
		$data['mapname'] = get_request('mapname', '');
		$data['valuemap'] = get_request('valuemap', array());
	}

	order_result($data['valuemap'], 'value');

	$valueMappingForm = new CView('administration.general.valuemapping.edit', $data);
}
else {
	$cnf_wdgt->addHeader(_('Value mapping'));
	$cnf_wdgt->addItem(BR());

	$data['valuemaps'] = array();
	$db_valuemaps = DBselect('SELECT v.valuemapid, v.name FROM valuemaps v WHERE '.DBin_node('valuemapid'));
	while ($db_valuemap = DBfetch($db_valuemaps)) {
		$data['valuemaps'][$db_valuemap['valuemapid']] = $db_valuemap;
		$data['valuemaps'][$db_valuemap['valuemapid']]['maps'] = array();
	}
	order_result($data['valuemaps'], 'name');

	$db_maps = DBselect('SELECT m.valuemapid, m.value, m.newvalue FROM mappings m WHERE '.DBin_node('mappingid'));
	while ($db_map = DBfetch($db_maps)) {
		$data['valuemaps'][$db_map['valuemapid']]['maps'][] = array(
			'value' => $db_map['value'],
			'newvalue' => $db_map['newvalue']
		);
	}

	$valueMappingForm = new CView('administration.general.valuemapping.list', $data);
}

$cnf_wdgt->addItem($valueMappingForm->render());
$cnf_wdgt->show();

require_once('include/page_footer.php');
?>
