<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Boutique — administration du catalogue (CRUD items).
 */

namespace NF\Modules\Shop\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Shop\Shop;

class Admin extends Controller_Module
{
	public function index()
	{
		$this->subtitle($this->lang('Catalogue'))->icon('fas fa-store')->css('shop');
		$this->add_action($this->button($this->lang('Nouvel item'), 'fas fa-plus', 'primary')->url('admin/shop/new'));

		$items = $this->db->select('*')->from('nf_shop_items')->order_by('position', 'id')->get(FALSE);

		if (!$items)
		{
			return $this->admin_card('fas fa-store', $this->lang('Boutique'), $this->admin_empty('fas fa-store', $this->lang('Aucun item. Crée le premier !')));
		}

		$libelles = $this->boutique()->type_labels();
		$rows = '';
		foreach ($items as $it)
		{
			$type = $libelles[$it['type']] ?? htmlspecialchars((string) ($it['type']));
			$rows .= '<tr>'
				.'<td><i class="'.htmlspecialchars((string) ($it['icon'] ?: 'fas fa-gift')).'"></i> '.htmlspecialchars((string) ($it['title'])).'</td>'
				.'<td>'.$type.'</td>'
				.'<td class="text-end"><i class="fas fa-coins"></i> '.(int)$it['price'].'</td>'
				.'<td class="text-center">'.((int)$it['stock'] < 0 ? '∞' : (int)$it['stock']).'</td>'
				.'<td class="text-center">'.(!empty($it['active']) ? '<span class="badge text-bg-success">'.$this->lang('Actif').'</span>' : '<span class="badge text-bg-secondary">'.$this->lang('Inactif').'</span>').'</td>'
				.'<td class="text-end">'
					.'<a class="btn btn-sm btn-outline-primary" href="'.url('admin/shop/edit/'.(int)$it['id']).'"><i class="fas fa-pen"></i></a> '
					.'<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/shop/delete/'.(int)$it['id']).'" data-confirm="'.htmlspecialchars((string) ($this->lang('Supprimer cet item ?')), ENT_QUOTES).'"><i class="far fa-trash-alt"></i></a>'
				.'</td>'
				.'</tr>';
		}

		$body = '<div class="table-responsive"><table class="table table-hover"><thead><tr>'
			.'<th>'.$this->lang('Item').'</th><th>'.$this->lang('Type').'</th><th class="text-end">'.$this->lang('Prix').'</th>'
			.'<th class="text-center">'.$this->lang('Stock').'</th><th class="text-center">'.$this->lang('Statut').'</th><th></th>'
			.'</tr></thead><tbody>'.$rows.'</tbody></table></div>';

		return $this->admin_card('fas fa-store', $this->lang('Boutique'), $body, count($items).' '.$this->lang('item|items', count($items)));
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
		$item = $id ? $this->db->select('*')->from('nf_shop_items')->where('id', $id)->row(FALSE) : NULL;

		if ($id && !$item)
		{
			$this->error(404);
			return;
		}

		$this->subtitle($id ? $this->lang('Modifier l\'item') : $this->lang('Nouvel item'))->icon('fas fa-store');
		$this->breadcrumb($this->lang('Boutique'), 'admin/shop');

		$types = $this->boutique()->type_labels();

		$form = $this->form()
			->add_rules([
				'title'       => ['label' => $this->lang('Titre'),            'value' => $item['title'] ?? '',             'rules' => 'required'],
				'description' => ['label' => $this->lang('Description'),      'value' => $item['description'] ?? '',       'type'  => 'textarea'],
				'icon'        => ['label' => $this->lang('Icône (classe FontAwesome)'), 'value' => $item['icon'] ?? 'fas fa-gift'],
				'price'       => ['label' => $this->lang('Prix (points)'),    'value' => $item['price'] ?? 0,             'type'  => 'number', 'rules' => 'required', 'size' => 'col-3'],
				'type'        => ['label' => $this->lang('Type'),            'value' => $item['type'] ?? 'perk',          'type'  => 'select', 'values' => $types],
				'payload'     => ['label' => $this->lang('Payload'),         'value' => $item['payload'] ?? '',           'description' => $this->lang('Grade : ID du groupe · VIP : nombre de jours · perk/cosmétique : clé libre')],
				'stock'       => ['label' => $this->lang('Stock (-1 = illimité)'), 'value' => $item['stock'] ?? -1,       'type'  => 'number', 'size' => 'col-3'],
				'position'    => ['label' => $this->lang('Position'),        'value' => $item['position'] ?? 0,           'type'  => 'number', 'size' => 'col-3'],
				'unique_per_user' => ['type' => 'checkbox', 'checked' => ['on' => $item === NULL ? TRUE : !empty($item['unique_per_user'])], 'values' => ['on' => $this->lang('Achat unique par membre')]],
				'active'      => ['type' => 'checkbox', 'checked' => ['on' => $item === NULL ? TRUE : !empty($item['active'])], 'values' => ['on' => $this->lang('Actif (visible en boutique)')]],
			])
			->add_submit($this->lang('Enregistrer'))
			->save();

		if ($form->is_valid($post))
		{
			$data = [
				'title'           => $post['title'],
				'description'     => $post['description'],
				'icon'            => $post['icon'] ?: 'fas fa-gift',
				'price'           => max(0, (int)$post['price']),
				'type'            => in_array($post['type'], Shop::TYPES, TRUE) ? $post['type'] : 'perk',
				'payload'         => $post['payload'],
				'stock'           => (int)$post['stock'],
				'position'        => (int)$post['position'],
				'unique_per_user' => in_array('on', (array)$post['unique_per_user']) ? 1 : 0,
				'active'          => in_array('on', (array)$post['active']) ? 1 : 0,
			];

			if ($id)
			{
				$this->db->where('id', $id)->update('nf_shop_items', $data);
			}
			else
			{
				$this->db->insert('nf_shop_items', $data);
			}

			notify($this->lang($id ? 'Item modifié' : 'Item créé'));
			redirect('admin/shop');
		}

		return $this->admin_card('fas fa-store', $id ? $this->lang('Modifier l\'item') : $this->lang('Nouvel item'), $form->display());
	}

	public function _delete($id)
	{
		$this->check_csrf('admin/shop');

		$this->db->where('id', (int)$id)->delete('nf_shop_items');
		notify($this->lang('Item supprimé'));
		redirect('admin/shop');
	}

	/** Le module, sous son vrai type : `module()` le rend comme un `Module` quelconque. */
	private function boutique(): \NF\Modules\Shop\Shop
	{
		/** @var \NF\Modules\Shop\Shop $shop */
		$shop = $this->module('shop');

		return $shop;
	}
}
