<?php
declare(strict_types=1);
namespace NF\Modules\Downloads\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Downloads\Downloads;

class Index extends Controller_Module
{
	public function index($groups)
	{
		$this	->title($this->lang('Téléchargements'))
				->icon('fas fa-download')
				->breadcrumb();

		if (empty($groups))
		{
			$body = '<div class="alert alert-info text-center">'.$this->lang('Aucun fichier disponible pour le moment.').'</div>';
		}
		else
		{
			$body = '';
			foreach ($groups as $g)
			{
				$body .= '<h2 class="h4 mt-3 mb-1"><i class="far fa-folder-open"></i> '.htmlspecialchars((string) ($g['cat']['title'])).'</h2>';
				if (!empty($g['cat']['description']))
				{
					$body .= '<p class="text-muted small">'.htmlspecialchars((string) ($g['cat']['description'])).'</p>';
				}
				$body .= '<div class="table-responsive mb-3"><table class="table table-sm mb-0"><thead><tr><th>'.$this->lang('Titre').'</th><th>'.$this->lang('Version').'</th><th>'.$this->lang('Taille').'</th><th>'.$this->lang('Type').'</th><th>'.$this->lang('Téléchargements').'</th><th></th></tr></thead><tbody>';

				foreach ($g['files'] as $f)
				{
					$body .= '<tr>'
						.'<td><strong>'.htmlspecialchars((string) ($f['title'])).'</strong>';
					if (!empty($f['description']))
					{
						$body .= '<br><small class="text-muted">'.htmlspecialchars((string) ($f['description'])).'</small>';
					}
					$body .= '</td>'
						.'<td>'.htmlspecialchars((string) ($f['version'] ?? '-')).'</td>'
						.'<td>'.Downloads::format_size($f['file_size_bytes']).'</td>'
						.'<td><small>'.htmlspecialchars((string) ($f['file_type'] ?? '-')).'</small></td>'
						.'<td>'.(int)$f['downloads_count'].'</td>'
						.'<td><a class="btn btn-sm btn-primary" href="'.url('downloads/go/'.$f['id']).'"><i class="fas fa-download"></i> '.$this->lang('Télécharger').'</a></td>'
						.'</tr>';
				}
				$body .= '</tbody></table></div>';
			}
		}

		return $this->panel()->title($this->lang('Bibliothèque de téléchargements'), 'fas fa-download')->body($body);
	}

	public function _go($file)
	{
		// URL vide → ne pas émettre header('Location: ') vide (= boucle, cf. bug forum). Retour à la liste.
		if (empty($file['file_url']))
		{
			redirect('downloads');
		}

		header('Location: '.$file['file_url'], TRUE, 302);
		exit;
	}
}
