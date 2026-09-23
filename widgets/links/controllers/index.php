<?php
declare(strict_types=1);
namespace NF\Widgets\Links\Controllers;
use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	public function index($config = [])
	{
		return $this->popular($config);
	}

	public function popular($config = [])
	{
		$count = max(1, min(20, (int)($config['count'] ?? 5)));

		$links = NeoFrag()->db	->select('id', 'title', 'clicks')
								->from('nf_links')
								->where('published', '1')
								->order_by('clicks DESC, sort_order ASC')
								->limit($count)
								->get();

		$body = '';
		if (empty($links))
		{
			$body = '<div class="text-center text-muted py-2"><small>'.$this->lang('Aucun lien').'</small></div>';
		}
		else
		{
			$body = '<ul class="list-unstyled mb-0">';
			foreach ($links as $l)
			{
				$body .= '<li class="py-1"><a href="'.url('links/go/'.$l['id']).'" target="_blank" rel="noopener"><i class="fas fa-external-link-alt me-1"></i>'.htmlspecialchars((string) ($l['title'])).'</a> <small class="text-muted">('.(int)$l['clicks'].')</small></li>';
			}
			$body .= '</ul>';
		}

		if (($config['display_panel'] ?? 'oui') === 'non')
		{
			return $body;
		}

		return $this->panel()
					->heading($this->lang('Liens populaires'), 'fas fa-link')
					->body($body)
					->footer('<a href="'.url('links').'">'.icon('far fa-arrow-alt-circle-right').' '.$this->lang('Tous les liens').'</a>', 'right');
	}
}
