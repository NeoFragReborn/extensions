<?php
namespace NF\Widgets\Articles\Controllers;
use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	public function index($config = [])
	{
		$count = max(1, min(20, (int)($config['count'] ?? 5)));

		$articles = NeoFrag()->db	->select('a.article_id', 'al.title', 'UNIX_TIMESTAMP(a.date) AS ts')
									->from('nf_articles a')
									->join('nf_articles_lang al', 'a.article_id = al.article_id')
									->where('a.published', '1')
									->where('al.lang', $this->config->lang->info()->name)
									->order_by('a.date DESC')
									->limit($count)
									->get();

		$body = '';
		if (empty($articles))
		{
			$body = '<div class="text-center text-muted py-2"><small>'.$this->lang('Aucun article').'</small></div>';
		}
		else
		{
			$body = '<ul class="list-unstyled mb-0">';
			foreach ($articles as $a)
			{
				$body .= '<li class="py-1 border-bottom">';
				$body .= '<a href="'.url('articles/'.$a['article_id'].'/'.url_title($a['title'])).'"><i class="far fa-file-alt mr-1"></i>'.htmlspecialchars($a['title']).'</a>';
				$body .= '<br><small class="text-muted">'.timetostr('j M Y', $a['ts']).'</small>';
				$body .= '</li>';
			}
			$body .= '</ul>';
		}

		if (($config['display_panel'] ?? 'oui') === 'non')
		{
			return $body;
		}

		return $this->panel()
					->heading($this->lang('Articles récents'), 'far fa-newspaper')
					->body($body)
					->footer('<a href="'.url('articles').'">'.icon('far fa-arrow-alt-circle-right').' '.$this->lang('Tous les articles').'</a>', 'right');
	}
}
