<?php
	$permissions = Array(
        'add'       => array('send', 'page', 'mysend'),
		'insert'    => Array('post', 'posted'), 
		'addresses' => Array('addr_upd', 'address_add', 'address_delete', 'address_edit'),
		'forms'     => Array('form_add', 'form_delete', 'form_edit', 'form_group_add', 'form_group_edit', 'form_field_add', 'form_field_edit', 'getPages', 'getBindedPage', 'getAddresses'),
		'templates' => Array('template_add', 'template_delete', 'template_edit', 'getForms'),
		'messages'  => Array('message', 'message_delete')
	);
?>