<?php
declare(strict_types=1);
namespace NF\Modules\Links\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	public function index($groups)
	{
		$this	->title($this->lang('Liens'))
				->icon('fas fa-link')
				->breadcrumb();

		if (empty($groups))
		{
			$body = '<div class="alert alert-info text-center">'.$this->lang('Aucun lien pour le moment.').'</div>';
		}
		else
		{
			$body = '';
			foreach ($groups as $g)
			{
				$body .= '<h2 class="h4 mt-3 mb-2"><i class="far fa-folder-open"></i> '.htmlspecialchars((string) ($g['cat']['title'])).'</h2>';
				$body .= '<div class="list-group mb-3">';
				foreach ($g['links'] as $l)
				{
					$clicks = (int)$l['clicks'];
					$body .= '<a href="'.url('links/go/'.$l['id']).'" target="_blank" rel="noopener" class="list-group-item list-group-item-action">';
					$body .= '<div class="d-flex justify-content-between">';
					$body .= '<strong>'.htmlspecialchars((string) ($l['title'])).'</strong>';
					$body .= '<small class="text-muted">'.$this->lang('%d clic|%d clics', $clicks, $clicks).'</small>';
					$body .= '</div>';
					if (!empty($l['description']))
					{
						$body .= '<small class="text-muted">'.htmlspecialchars((string) ($l['description'])).'</small>';
					}
					$body .= '</a>';
				}
				$body .= '</div>';
			}
		}

		return $this->panel()->title($this->lang('Annuaire de liens'), 'fas fa-link')->body($body);
	}

	public function _go($link)
	{
		// Redirect direct vers l'URL externe (le compteur a été incrémenté dans le checker)
		// URL vide → ne pas émettre header('Location: ') vide (= boucle, cf. bug forum). Retour à la liste.
		if (empty($link['url']))
		{
			redirect('links');
		}

		header('Location: '.$link['url'], TRUE, 302);
		exit;
	}
}
