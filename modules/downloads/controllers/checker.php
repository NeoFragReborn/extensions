<?php
namespace NF\Modules\Downloads\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function index()
	{
		$cats = NeoFrag()->db	->select('id', 'title', 'description')
								->from('nf_downloads_categories')
								->order_by('sort_order ASC, id ASC')
								->get();

		$groups = [];
		foreach ($cats as $c)
		{
			$files = NeoFrag()->db	->select('id', 'title', 'description', 'file_url', 'file_size_bytes', 'file_type', 'version', 'downloads_count')
									->from('nf_downloads')
									->where('category_id', $c['id'])
									->where('published', '1')
									->order_by('title ASC')
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
