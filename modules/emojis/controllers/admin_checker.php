<?php
/**
 * https://neofr.ag
 */

namespace NF\Modules\Emojis\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	public function index($page = '')
	{
		return [NeoFrag()->db->select('*')->from('nf_custom_emojis')->order_by('name ASC')->get()];
	}

	public function _add() { return [NULL]; }

	public function _delete($id, $name)
	{
		$e = NeoFrag()->db->select('id', 'image_id')->from('nf_custom_emojis')->where('id', $id)->row();
		return $e ? [$e] : NULL;
	}
}
