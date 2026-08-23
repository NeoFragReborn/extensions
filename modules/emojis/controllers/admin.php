<?php
/**
 * https://neofr.ag
 */

namespace NF\Modules\Emojis\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index($emojis)
	{
		$this->title($this->lang('Emojis'))->icon('far fa-smile');

		if (empty($emojis))
		{
			$body = $this->admin_empty('far fa-smile', $this->lang('Aucun emoji. Ajoutes-en un : il sera utilisable partout via <code>:nom:</code>.'));
		}
		else
		{
			$body = '<table class="table table-hover" style="margin:0;"><thead><tr><th style="width:1%;"></th><th>'.$this->lang('Code').'</th><th class="text-end"></th></tr></thead><tbody>';
			foreach ($emojis as $e)
			{
				$url   = NeoFrag()->model2('file', $e['image_id'])->path();
				$body .= '<tr>'
					.'<td>'.($url ? '<img src="'.$url.'" alt="" style="height:24px;width:auto;">' : '').'</td>'
					.'<td><code>:'.htmlspecialchars($e['name']).':</code></td>'
					.'<td class="text-end"><a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/emojis/delete/'.$e['id'].'/'.url_title($e['name'])).'" data-confirm="'.htmlspecialchars($this->lang('Supprimer cet emoji ?'), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a></td>'
					.'</tr>';
			}
			$body .= '</tbody></table>';
		}

		$actions = '<a class="btn btn-primary btn-sm" href="'.url('admin/emojis/add').'"><i class="fas fa-plus"></i> '.$this->lang('Nouvel emoji').'</a>';

		return $this->admin_card('far fa-smile', $this->lang('Emojis personnalisés'), $body, count($emojis).' '.$this->lang('emoji|emojis', count($emojis)), $actions);
	}

	public function _add()
	{
		$this->title($this->lang('Nouvel emoji'))->icon('far fa-smile')->breadcrumb();

		$this->form()
			 ->add_rules([
				'name' => [
					'label'       => $this->lang('Nom'),
					'type'        => 'text',
					'rules'       => 'required',
					'description' => $this->lang('Lettres minuscules, chiffres et _ uniquement. Utilisable via :nom:')
				],
				'image' => [
					'label'  => $this->lang('Image'),
					'type'   => 'file',
					'upload' => 'emojis',
					'info'   => $this->lang(' d\'image (max. %d Mo)', file_upload_max_size() / 1024 / 1024),
					'check'  => function($filename, $ext){
						if (!in_array($ext, ['gif', 'jpeg', 'jpg', 'png', 'webp']))
						{
							return $this->lang('Veuillez choisir un fichier d\'image');
						}
					}
				]
			 ])
			 ->add_submit($this->lang('Créer'));

		if ($this->form()->is_valid($post))
		{
			$name = strtolower(trim($post['name']));

			if (!preg_match('/^[a-z0-9_]+$/', $name))
			{
				notify($this->lang('Nom invalide : lettres minuscules, chiffres et _ uniquement.'), 'danger');
				redirect('admin/emojis/add');
			}

			if (empty($post['image']))
			{
				notify($this->lang('Image requise.'), 'danger');
				redirect('admin/emojis/add');
			}

			if (NeoFrag()->db->from('nf_custom_emojis')->where('name', $name)->count())
			{
				notify($this->lang('Un emoji porte déjà ce nom.'), 'danger');
				redirect('admin/emojis/add');
			}

			NeoFrag()->db->insert('nf_custom_emojis', ['name' => $name, 'image_id' => (int)$post['image']]);
			notify($this->lang('Emoji ajouté.'));
			redirect('admin/emojis');
		}

		return $this->admin_back('admin/emojis', $this->lang('Emojis')).$this->admin_card('fas fa-plus', $this->lang('Nouvel emoji'), $this->form()->display());
	}

	public function _delete($e)
	{
		$this->check_csrf('admin/emojis');

		if (!empty($e['image_id']))
		{
			NeoFrag()->model2('file', (int)$e['image_id'])->delete();
		}

		NeoFrag()->db->where('id', $e['id'])->delete('nf_custom_emojis');
		notify($this->lang('Emoji supprimé.'));
		redirect('admin/emojis');
	}
}
