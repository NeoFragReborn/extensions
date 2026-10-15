<?php
declare(strict_types=1);
namespace NF\Modules\Downloads\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function index()
	{
		$cats = NeoFrag()->db	->select('c.id', 'cl.title', 'cl.description')
								->from('nf_downloads_categories c')
								->join_lang('nf_downloads_categories_lang cl', 'category_id', 'c.id', NULL, 'LEFT')
								->order_by('c.sort_order ASC, c.id ASC')
								->get();

		$groups = [];
		foreach ($cats as $c)
		{
			$files = NeoFrag()->db	->select('d.id', 'dl.title', 'dl.description', 'd.file_url', 'd.file_size_bytes', 'd.file_type', 'd.version', 'd.downloads_count')
									->from('nf_downloads d')
									->join_lang('nf_downloads_lang dl', 'download_id', 'd.id', NULL, 'LEFT')
									->where('d.category_id', $c['id'])
									->where('d.published', '1')
									->order_by('dl.title ASC')
									->get();
			if (!empty($files))
			{
				$groups[] = ['cat' => $c, 'files' => $files];
			}
		}

		return [$groups];
	}

	public function _go($id)
	{
		$file = NeoFrag()->db	->select('id', 'file_url')
								->from('nf_downloads')
								->where('id', $id)
								->where('published', '1')
								->row();

		if (!empty($file))
		{
			NeoFrag()->db->execute('UPDATE nf_downloads SET downloads_count = downloads_count + 1 WHERE id = '.(int)$id);
			return [$file];
		}
	}
}
