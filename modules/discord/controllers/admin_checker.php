<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Discord\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	public function index()
	{
		$this->_droit();

		return [];
	}

	public function _connexion()
	{
		$this->_droit();

		return [];
	}

	public function _cle()
	{
		$this->_droit();

		return [];
	}

	public function _commande($commande)
	{
		$this->_droit();

		return in_array($commande, ['marche', 'pause', 'redemarrer'], TRUE) ? [$commande] : NULL;
	}

	public function _salons()
	{
		$this->_droit();

		return [];
	}

	public function _salons_supprimer($mapping_id)
	{
		$this->_droit();

		$ligne = $this->db->select('mapping_id')->from('nf_discord_channels')->where('mapping_id', (int) $mapping_id)->row();

		return $ligne ? [['mapping_id' => (int) $ligne]] : NULL;
	}

	public function _roles()
	{
		$this->_droit();

		return [];
	}

	public function _roles_supprimer($mapping_id)
	{
		$this->_droit();

		$ligne = $this->db->select('mapping_id')->from('nf_discord_roles')->where('mapping_id', (int) $mapping_id)->row();

		return $ligne ? [['mapping_id' => (int) $ligne]] : NULL;
	}

	private function _droit(): void
	{
		if (!$this->is_authorized('manage'))
		{
			$this->error->unauthorized();
		}
	}
}
