<?php
/**
 * https://neofr.ag
 * Régie publicitaire — tracking de clic + redirection vers la cible.
 */

namespace NF\Modules\Ads\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	public function _click($id)
	{
		$ad = $this->db->select('url', 'clicks')->from('nf_ads')->where('id', (int)$id)->row(FALSE);

		if (!$ad || !is_valid_url($ad['url']))
		{
			redirect('');
		}

		$this->db->where('id', (int)$id)->update('nf_ads', ['clicks' => (int)$ad['clicks'] + 1]);

		header('Location: '.$ad['url'], TRUE, 302);
		exit;
	}
}
