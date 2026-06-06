<?php
namespace NF\Widgets\Downloads\Controllers;
use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;
use NF\Modules\Downloads\Downloads;

class Index extends Controller_Widget
{
	public function popular($config = [])
	{
		$files = NeoFrag()->db	->select('id', 'title', 'file_size_bytes', 'downloads_count')
								->from('nf_downloads')
								->where('published', '1')
								->order_by('downloads_count DESC, title ASC')
								->limit(5)
								->get();

		$body = '';
		if (empty($files))
		{
			$body = '<div class="text-center text-muted py-2"><small>'.$this->lang('Aucun fichier').'</small></div>';
		}
		else
		{
			$body = '<ul class="list-unstyled mb-0">';
			foreach ($files as $f)
			{
				$body .= '<li class="py-1"><a href="'.url('downloads/go/'.$f['id']).'"><i class="fas fa-download mr-1"></i>'.htmlspecialchars($f['title']).'</a> <small class="text-muted">'.Downloads::format_size($f['file_size_bytes']).' • '.(int)$f['downloads_count'].' DL</small></li>';
			}
			$body .= '</ul>';
		}

		return $this->panel()
					->heading($this->lang('Téléchargements populaires'), 'fas fa-download')
					->body($body)
					->footer('<a href="'.url('downloads').'">'.icon('far fa-arrow-alt-circle-right').' '.$this->lang('Tous les fichiers').'</a>', 'right');
	}
}
