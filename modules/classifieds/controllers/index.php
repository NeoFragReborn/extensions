<?php
namespace NF\Modules\Classifieds\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Classifieds\Classifieds;

class Index extends Controller_Module
{
	public function index($categories, $ads)
	{
		$this->title($this->lang('Petites annonces'))->icon('fas fa-bullhorn')->breadcrumb();

		return $this->panel()->title($this->lang('Petites annonces'), 'fas fa-bullhorn')->body(
			$this->_toolbar(NULL).$this->_aside($categories, 0).$this->_grid($ads)
		);
	}

	public function _category($cat, $categories, $ads)
	{
		$this->title($cat['title'])->icon('fas fa-bullhorn')->breadcrumb();

		return $this->panel()->title($cat['title'], 'fas fa-folder-open')->body(
			$this->_toolbar(NULL).$this->_aside($categories, (int)$cat['id']).$this->_grid($ads)
		);
	}

	public function _show($ad)
	{
		$this->title($ad['title'])->icon('fas fa-bullhorn')->breadcrumb();

		$is_owner = $this->user() && (int)$this->user->id === (int)$ad['user_id'];
		$slug     = url_title($ad['title']);

		$body  = '<div class="nf-classified-detail">';

		if (!empty($ad['image']))
		{
			$body .= '<div class="mb-3 text-center"><img src="'.htmlspecialchars($ad['image']).'" alt="'.htmlspecialchars($ad['title'], ENT_QUOTES).'" class="img-fluid rounded" style="max-height:360px;"></div>';
		}

		$body .= '<div class="d-flex flex-wrap align-items-center mb-2" style="gap:8px;">';
		$body .= Classifieds::type_label($ad['ad_type']);
		$body .= '<span class="ml-auto" style="font-size:1.25rem;">'.Classifieds::format_price($ad['price'], $this).'</span>';
		$body .= '</div>';

		$body .= '<div class="text-muted small mb-3">';
		$author = $ad['author_id'] ? $this->user->link($ad['author_id'], $ad['author']) : '<i>'.$this->lang('Anonyme').'</i>';
		$body  .= $this->lang('Déposée par %s', $author).' · '.timetostr('d/m/Y', $ad['created_ts']);
		$body  .= ' · <i class="fas fa-folder"></i> '.htmlspecialchars($ad['cat_title']);
		$body  .= ' · <i class="fas fa-eye"></i> '.(int)$ad['views'];
		if ($ad['status'] !== 'published')
		{
			$body .= ' · '.Classifieds::status_label($ad['status']);
		}
		$body .= '</div>';

		$body .= '<div class="card mb-3"><div class="card-body">'.nl2br(htmlspecialchars($ad['description'])).'</div></div>';

		// Contact vendeur
		if (!empty($ad['contact']))
		{
			$body .= '<div class="alert alert-light"><i class="fas fa-address-card"></i> '.htmlspecialchars($ad['contact']).'</div>';
		}

		if ($is_owner)
		{
			$body .= '<div class="btn-group">';
			$body .= '<a class="btn btn-outline-primary" href="'.url('classifieds/'.$ad['id'].'/'.$slug.'/edit').'"><i class="fas fa-pen"></i> '.$this->lang('Modifier').'</a>';
			if ($ad['status'] === 'published')
			{
				$body .= '<a class="btn btn-outline-secondary" href="'.url('classifieds/'.$ad['id'].'/'.$slug.'/close').'" data-confirm="'.htmlspecialchars($this->lang('Clôturer cette annonce ?'), ENT_QUOTES).'"><i class="fas fa-check"></i> '.$this->lang('Clôturer').'</a>';
			}
			$body .= '</div>';
		}
		else if ($ad['status'] === 'published' && $ad['author_id'])
		{
			if ($this->user())
			{
				$body .= '<form method="post" action="'.url('classifieds/'.$ad['id'].'/'.$slug.'/contact').'">';
				$body .= '<div class="form-group"><label><i class="fas fa-envelope"></i> '.$this->lang('Contacter le vendeur').'</label>';
				$body .= '<textarea name="message" class="form-control" rows="3" required placeholder="'.htmlspecialchars($this->lang('Votre message au vendeur…'), ENT_QUOTES).'"></textarea></div>';
				$body .= '<button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> '.$this->lang('Envoyer').'</button>';
				$body .= '</form>';
			}
			else
			{
				$body .= '<div class="alert alert-info">'.$this->lang('Connecte-toi pour contacter le vendeur.').'</div>';
			}
		}

		$body .= '</div>';

		return $this->panel()->title($ad['title'], 'fas fa-bullhorn')->body($body);
	}

	public function _new($cats)
	{
		return $this->_form(NULL, $cats);
	}

	public function _edit($ad, $cats)
	{
		return $this->_form($ad, $cats);
	}

	protected function _form($ad, $cats)
	{
		$is_new = $ad === NULL;
		$this->title($is_new ? $this->lang('Déposer une annonce') : $this->lang('Modifier l’annonce'))->icon('fas fa-bullhorn')->breadcrumb();

		$this->form()
			 ->add_rules([
				'category_id' => ['label' => $this->lang('Catégorie'), 'type' => 'select', 'values' => $cats, 'value' => $is_new ? key($cats) : $ad['category_id'], 'rules' => 'required'],
				'ad_type'     => ['label' => $this->lang('Type'), 'type' => 'select', 'values' => ['offer' => $this->lang('Offre (je vends / propose)'), 'request' => $this->lang('Recherche (je cherche)')], 'value' => $is_new ? 'offer' : $ad['ad_type'], 'rules' => 'required'],
				'title'       => ['label' => $this->lang('Titre'), 'type' => 'text', 'value' => $is_new ? '' : $ad['title'], 'rules' => 'required'],
				'price'       => ['label' => $this->lang('Prix (€)'), 'type' => 'text', 'value' => ($is_new || $ad['price'] === NULL) ? '' : (float)$ad['price'], 'description' => $this->lang('Laisser vide pour « à débattre », 0 pour gratuit.')],
				'description' => ['label' => $this->lang('Description'), 'type' => 'textarea', 'value' => $is_new ? '' : $ad['description'], 'rules' => 'required'],
				'image'       => ['label' => $this->lang('Image (URL)'), 'type' => 'text', 'value' => $is_new ? '' : $ad['image'], 'description' => $this->lang('Lien direct vers une image (optionnel).')],
				'contact'     => ['label' => $this->lang('Contact (optionnel)'), 'type' => 'text', 'value' => $is_new ? '' : $ad['contact'], 'description' => $this->lang('Discord, email… À défaut, les membres pourront te contacter via le site.')]
			 ])
			 ->add_submit($is_new ? $this->lang('Publier') : $this->lang('Enregistrer'));

		if ($this->form()->is_valid($post))
		{
			$image = trim((string)($post['image'] ?? ''));
			if ($image !== '' && !preg_match('#^https?://#i', $image))
			{
				$image = '';
			}

			$price_raw = trim((string)($post['price'] ?? ''));
			$price     = $price_raw === '' ? NULL : max(0, (float)str_replace(',', '.', $price_raw));

			$data = [
				'category_id' => (int)$post['category_id'],
				'ad_type'     => in_array($post['ad_type'], ['offer', 'request'], TRUE) ? $post['ad_type'] : 'offer',
				'title'       => $post['title'],
				'description' => $post['description'],
				'price'       => $price,
				'image'       => $image,
				'contact'     => trim((string)($post['contact'] ?? ''))
			];

			if ($is_new)
			{
				$moderation       = !empty($this->config->classifieds_moderation);
				$data['user_id']  = (int)$this->user->id;
				$data['status']   = $moderation ? 'pending' : 'published';
				NeoFrag()->db->insert('nf_classifieds', $data);
				$id = (int)NeoFrag()->db->driver()->insert_id();
				notify($moderation ? $this->lang('Annonce envoyée, en attente de validation.') : $this->lang('Annonce publiée.'));
				redirect('classifieds/'.$id.'/'.url_title($post['title']));
			}

			NeoFrag()->db->where('id', $ad['id'])->update('nf_classifieds', $data);
			notify($this->lang('Annonce modifiée.'));
			redirect('classifieds/'.$ad['id'].'/'.url_title($post['title']));
		}

		return $this->row($this->col($this->panel()->heading()->body($this->form()->display()))->size('col-12'));
	}

	public function _close($ad)
	{
		NeoFrag()->db->where('id', $ad['id'])->update('nf_classifieds', ['status' => 'closed']);
		notify($this->lang('Annonce clôturée.'));
		redirect('classifieds/'.$ad['id'].'/'.url_title($ad['title']));
	}

	public function _contact($ad)
	{
		$message = trim((string)($_POST['message'] ?? ''));
		$slug    = url_title($ad['title']);

		if ($message === '')
		{
			notify($this->lang('Message vide.'), 'error');
			redirect('classifieds/'.$ad['id'].'/'.$slug);
		}

		if ($notifications = $this->module('notifications'))
		{
			$notifications->push(
				(int)$ad['user_id'],
				'classifieds-contact',
				$this->lang('%s s’intéresse à ton annonce « %s »', $this->user->username, $ad['title']),
				'classifieds/'.$ad['id'].'/'.$slug,
				(int)$this->user->id
			);
		}

		notify($this->lang('Message envoyé au vendeur.'));
		redirect('classifieds/'.$ad['id'].'/'.$slug);
	}

	protected function _toolbar($active)
	{
		$btn = '';
		if ($this->user())
		{
			$btn = '<a class="btn btn-primary mb-3" href="'.url('classifieds/new').'"><i class="fas fa-plus"></i> '.$this->lang('Déposer une annonce').'</a>';
		}
		return $btn;
	}

	protected function _aside($categories, $active)
	{
		$out  = '<div class="nf-classified-cats mb-3"><div class="list-group list-group-horizontal flex-wrap" style="gap:6px;">';
		$out .= '<a class="list-group-item list-group-item-action'.($active === 0 ? ' active' : '').'" href="'.url('classifieds').'">'.$this->lang('Toutes').'</a>';
		foreach ($categories as $c)
		{
			$out .= '<a class="list-group-item list-group-item-action'.($active === (int)$c['id'] ? ' active' : '').'" href="'.url('classifieds/category/'.$c['id'].'/'.url_title($c['title'])).'">'
				.htmlspecialchars($c['title']).' <span class="badge badge-light">'.(int)$c['nb'].'</span></a>';
		}
		$out .= '</div></div>';
		return $out;
	}

	protected function _grid($ads)
	{
		if (empty($ads))
		{
			return '<div class="alert alert-info text-center">'.$this->lang('Aucune annonce pour le moment.').'</div>';
		}

		$out = '<div class="nf-card-grid">';
		foreach ($ads as $a)
		{
			$slug = url_title($a['title']);
			$out .= '<div class="nf-content-card">';
			if (!empty($a['image']))
			{
				$out .= '<a href="'.url('classifieds/'.$a['id'].'/'.$slug).'"><div style="height:140px;background:#0001 center/cover no-repeat url(\''.htmlspecialchars($a['image'], ENT_QUOTES).'\');border-radius:6px;margin-bottom:8px;"></div></a>';
			}
			$out .= '<div class="nf-content-card-head">';
			$out .= '<div class="nf-content-card-title"><a href="'.url('classifieds/'.$a['id'].'/'.$slug).'">'.htmlspecialchars($a['title']).'</a></div>';
			$out .= Classifieds::type_label($a['ad_type']);
			$out .= '</div>';
			$out .= '<div class="nf-content-card-meta">';
			$out .= '<span>'.Classifieds::format_price($a['price'], $this).'</span>';
			$out .= '<span><i class="fas fa-folder"></i> '.htmlspecialchars($a['cat_title']).'</span>';
			$out .= '</div>';
			$out .= '<div class="nf-content-card-foot">';
			$author = $a['author_id'] ? $this->user->link($a['author_id'], $a['author']) : '<i>'.$this->lang('Anonyme').'</i>';
			$out .= '<small class="text-muted">'.$author.' · '.timetostr('d/m', $a['created_ts']).'</small>';
			$out .= '<span class="nf-content-card-spacer"></span>';
			$out .= '<small class="text-muted"><i class="fas fa-eye"></i> '.(int)$a['views'].'</small>';
			$out .= '</div>';
			$out .= '</div>';
		}
		$out .= '</div>';
		return $out;
	}
}
