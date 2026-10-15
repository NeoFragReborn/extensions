<?php
declare(strict_types=1);
namespace NF\Widgets\Downloads\Controllers;
use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;
use NF\Modules\Downloads\Downloads;

class Index extends Controller_Widget
{
	public function index($config = [])
	{
		return $this->popular($config);
	}

	public function popular($config = [])
	{
		$files = NeoFrag()->db	->select('d.id', 'dl.title', 'd.file_size_bytes', 'd.downloads_count')
								->from('nf_downloads d')
								->join_lang('nf_downloads_lang dl', 'download_id', 'd.id', NULL, 'LEFT')
								->where('d.published', '1')
								->order_by('d.downloads_count DESC, dl.title ASC')
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
				$body .= '<li class="py-1"><a href="'.url('downloads/go/'.$f['id']).'"><i class="fas fa-download me-1"></i>'.nf_texte($f['title']).'</a> <small class="text-muted">'.Downloads::format_size($f['file_size_bytes']).' • '.(int)$f['downloads_count'].' DL</small></li>';
			}
			$body .= '</ul>';
		}

		return $this->panel()
					->heading($this->lang('Téléchargements populaires'), 'fas fa-download')
					->body($body)
					->footer('<a href="'.url('downloads').'">'.icon('far fa-arrow-alt-circle-right').' '.$this->lang('Tous les fichiers').'</a>', 'right');
	}
}
