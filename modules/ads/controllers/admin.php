<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Régie publicitaire — administration des annonces (CRUD).
 */

namespace NF\Modules\Ads\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index()
	{
		$this->subtitle($this->lang('Annonces'))->icon('fas fa-rectangle-ad');
		$this->add_action($this->button($this->lang('Nouvelle annonce'), 'fas fa-plus', 'primary')->url('admin/ads/new'));

		$ads = $this->db->select('*')->from('nf_ads')->order_by('placement', 'position', 'id')->get(FALSE);

		if (!$ads)
		{
			return $this->admin_card('fas fa-rectangle-ad', $this->lang('Régie publicitaire'), $this->admin_empty('fas fa-rectangle-ad', $this->lang('Aucune annonce.')));
		}

		$rows = '';
		foreach ($ads as $a)
		{
			$rows .= '<tr>'
				.'<td>'.htmlspecialchars((string) ($a['title'])).'</td>'
				.'<td><code>'.htmlspecialchars((string) ($a['placement'])).'</code></td>'
				.'<td>'.htmlspecialchars((string) ($a['format'])).'</td>'
				.'<td class="text-center">'.(!empty($a['active']) ? '<span class="badge text-bg-success">'.$this->lang('Active').'</span>' : '<span class="badge text-bg-secondary">'.$this->lang('Inactive').'</span>').'</td>'
				.'<td class="text-end">'.(int)$a['impressions'].' / '.(int)$a['clicks'].'</td>'
				.'<td class="text-end">'
					.'<a class="btn btn-sm btn-outline-primary" href="'.url('admin/ads/edit/'.(int)$a['id']).'"><i class="fas fa-pen"></i></a> '
					.'<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/ads/delete/'.(int)$a['id']).'" data-confirm="'.htmlspecialchars((string) ($this->lang('Supprimer cette annonce ?')), ENT_QUOTES).'"><i class="far fa-trash-alt"></i></a>'
				.'</td>'
				.'</tr>';
		}

		$body = '<div class="table-responsive"><table class="table table-hover"><thead><tr>'
			.'<th>'.$this->lang('Titre').'</th><th>'.$this->lang('Emplacement').'</th><th>'.$this->lang('Format').'</th>'
			.'<th class="text-center">'.$this->lang('Statut').'</th><th class="text-end">'.$this->lang('Vues / Clics').'</th><th></th>'
			.'</tr></thead><tbody>'.$rows.'</tbody></table></div>';

		return $this->admin_card('fas fa-rectangle-ad', $this->lang('Régie publicitaire'), $body, count($ads).' '.$this->lang('annonce|annonces', count($ads)));
	}

	public function _new()
	{
		return $this->_form(NULL);
	}

	public function _edit($id)
	{
		return $this->_form((int)$id);
	}

	private function _form($id)
	{
		$ad = $id ? $this->db->select('*')->from('nf_ads')->where('id', $id)->row(FALSE) : NULL;

		if ($id && !$ad)
		{
			$this->error(404);
			return;
		}

		$this->subtitle($id ? $this->lang('Modifier l\'annonce') : $this->lang('Nouvelle annonce'))->icon('fas fa-rectangle-ad');
		$this->breadcrumb($this->lang('Régie publicitaire'), 'admin/ads');

		$form = $this->form()
			->add_rules([
				'title'     => ['label' => $this->lang('Titre'),       'value' => $ad['title'] ?? '',           'rules' => 'required'],
				'placement' => ['label' => $this->lang('Emplacement (slot du widget)'), 'value' => $ad['placement'] ?? 'sidebar', 'rules' => 'required', 'description' => $this->lang('Doit correspondre au champ « Emplacement » du widget Publicité (ex. sidebar, footer, header).')],
				'format'    => ['label' => $this->lang('Format'),      'value' => $ad['format'] ?? 'image',     'type'  => 'select', 'values' => ['image' => $this->lang('Image (bannière)'), 'html' => $this->lang('HTML / AdSense')]],
				'image_url' => ['label' => $this->lang('URL de l\'image'), 'value' => $ad['image_url'] ?? '',   'description' => $this->lang('Format image : URL de la bannière.')],
				'url'       => ['label' => $this->lang('URL cible'),   'value' => $ad['url'] ?? '',             'description' => $this->lang('Format image : lien au clic.')],
				'html'      => ['label' => $this->lang('Code HTML'),   'value' => $ad['html'] ?? '',            'type'  => 'textarea', 'description' => $this->lang('Format HTML : code de la régie (AdSense…).')],
				'starts_at' => ['label' => $this->lang('Début (optionnel)'), 'value' => $ad['starts_at'] ?? '', 'type' => 'date', 'size' => 'col-4'],
				'ends_at'   => ['label' => $this->lang('Fin (optionnel)'),   'value' => $ad['ends_at'] ?? '',   'type' => 'date', 'size' => 'col-4'],
				'position'  => ['label' => $this->lang('Position'),    'value' => $ad['position'] ?? 0,         'type'  => 'number', 'size' => 'col-3'],
				'active'    => ['type' => 'checkbox', 'checked' => ['on' => $ad === NULL ? TRUE : !empty($ad['active'])], 'values' => ['on' => $this->lang('Active')]],
			])
			->add_submit($this->lang('Enregistrer'))
			->save();

		if ($form->is_valid($post))
		{
			$data = [
				'title'     => $post['title'],
				'placement' => preg_replace('/[^a-z0-9_-]/i', '', $post['placement']) ?: 'sidebar',
				'format'    => in_array($post['format'], ['image', 'html'], TRUE) ? $post['format'] : 'image',
				'image_url' => $post['image_url'],
				'url'       => $post['url'],
				'html'      => $post['html'],
				'starts_at' => $post['starts_at'] ?: NULL,
				'ends_at'   => $post['ends_at'] ?: NULL,
				'position'  => (int)$post['position'],
				'active'    => in_array('on', (array)$post['active']) ? 1 : 0,
			];

			if ($id)
			{
				$this->db->where('id', $id)->update('nf_ads', $data);
			}
			else
			{
				$this->db->insert('nf_ads', $data);
			}

			notify($this->lang($id ? 'Annonce modifiée' : 'Annonce créée'));
			redirect('admin/ads');
		}

		return $this->admin_card('fas fa-rectangle-ad', $id ? $this->lang('Modifier l\'annonce') : $this->lang('Nouvelle annonce'), $form->display());
	}

	public function _delete($id)
	{
		$this->check_csrf('admin/ads');

		$this->db->where('id', (int)$id)->delete('nf_ads');
		notify($this->lang('Annonce supprimée'));
		redirect('admin/ads');
	}
}
