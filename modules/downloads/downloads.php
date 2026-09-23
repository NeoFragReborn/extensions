<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Module Downloads — bibliothèque de fichiers téléchargeables.
 */

namespace NF\Modules\Downloads;

use NF\NeoFrag\Addons\Module;

class Downloads extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Téléchargements'),
			'description' => $this->lang('Bibliothèque de fichiers à télécharger avec catégories, version et compteur.'),
			'icon'        => 'fas fa-download',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => [],
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => ['neofrag' => '0.2.0'],
			'routes'      => [
				''                                  => 'index',
				'go/{id}'                           => '_go',
				'admin{pages}'                      => 'index',
				'admin/file/add'                    => '_file_add',
				'admin/file/{id}/{url_title}'       => '_file_edit',
				'admin/file/delete/{id}/{url_title}' => '_file_delete',
				'admin/cat/add'                     => '_cat_add',
				'admin/cat/{id}/{url_title}'        => '_cat_edit',
				'admin/cat/delete/{id}/{url_title}' => '_cat_delete'
			]
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access' => [
					[
						'title'  => $this->lang('Téléchargements'),
						'icon'   => 'fas fa-download',
						'access' => [
							'manage' => ['title' => $this->lang('Gérer fichiers et catégories'), 'icon' => 'fas fa-edit', 'admin' => TRUE]
						]
					]
				]
			]
		];
	}

	/**
	 * Format bytes en taille humaine (KB, MB, GB).
	 */
	public static function format_size($bytes)
	{
		if ($bytes === NULL || $bytes <= 0) return '-';
		$units = ['B', 'KB', 'MB', 'GB', 'TB'];
		$i = 0;
		while ($bytes >= 1024 && $i < count($units) - 1)
		{
			$bytes /= 1024;
			$i++;
		}
		return round($bytes, 2).' '.$units[$i];
	}
}
