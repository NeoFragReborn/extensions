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

		return in_array($commande, ['marche', 'pause', 'redemarrer', 'resynchroniser'], TRUE) ? [$commande] : NULL;
	}

	public function _fonctionnalites()
	{
		$this->_droit();

		return [];
	}

	public function _fonctionnalite($nom)
	{
		$this->_droit();

		return ($f = $this->_declaree($nom)) ? [$f] : NULL;
	}

	public function _basculer($nom)
	{
		$this->_droit();

		return ($f = $this->_declaree($nom)) ? [$f] : NULL;
	}

	public function _mise_en_place()
	{
		$this->_droit();

		return [];
	}

	public function _mise_en_place_apercu()
	{
		$this->_droit();

		return [];
	}

	public function _mise_en_place_appliquer()
	{
		$this->_droit();

		return [];
	}

	public function _mise_en_place_annuler()
	{
		$this->_droit();

		return [];
	}

	/** Une fonctionnalité que le bot a déclarée, ou NULL (404). */
	private function _declaree($nom): ?array
	{
		$modele = $this->model('discord');

		if (!$modele instanceof \NF\Modules\Discord\Models\Discord)
		{
			return NULL;
		}

		foreach ($modele->fonctionnalites() as $f)
		{
			if ($f['nom'] === $nom)
			{
				return $f;
			}
		}

		return NULL;
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

	public function _etiquettes($mapping_id)
	{
		$this->_droit();

		$salon = $this->db->select('mapping_id', 'channel_id', 'forum_id')->from('nf_discord_channels')->where('mapping_id', (int) $mapping_id)->row();

		return is_array($salon) && $salon ? [['mapping_id' => (int) $salon['mapping_id'], 'channel_id' => (string) $salon['channel_id'], 'forum_id' => (int) $salon['forum_id']]] : NULL;
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

	public function _roles_temporaires()
	{
		$this->_droit();

		return [];
	}

	public function _roles_temporaires_retirer($timed_id)
	{
		$this->_droit();

		$ligne = $this->db->select('timed_id')->from('nf_discord_timed_roles')->where('timed_id', (int) $timed_id)->row();

		return $ligne ? [['timed_id' => (int) $ligne]] : NULL;
	}

	private function _droit(): void
	{
		if (!$this->is_authorized('manage'))
		{
			$this->error->unauthorized();
		}
	}
}
