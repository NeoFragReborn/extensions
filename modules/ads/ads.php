<?php
/**
 * https://neofr.ag
 * Module Régie publicitaire — bannières/HTML par emplacement. Masquées pour les
 * membres ayant le perk 'no_ads' (boutique) ou le statut VIP (gamification).
 * Rendu via le widget « ads » (placement configurable) qui appelle render().
 */

namespace NF\Modules\Ads;

use NF\NeoFrag\Addons\Module;

class Ads extends Module
{
	const PERK_NO_ADS = 'no_ads';

	protected function __info()
	{
		return [
			'title'       => $this->lang('Régie publicitaire'),
			'description' => $this->lang('Bannières et blocs HTML par emplacement, masqués pour les membres sans-pub / VIP.'),
			'icon'        => 'fas fa-rectangle-ad',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'version'     => '1.0',
			'admin'       => TRUE,
			'depends'     => ['neofrag' => '1.0.0'],
			'routes'      => [
				'click/{id}'        => '_click',
				'admin'             => 'index',
				'admin/new'         => '_new',
				'admin/edit/{id}'   => '_edit',
				'admin/delete/{id}' => '_delete',
			]
		];
	}

	/** Le membre doit-il être épargné de publicité (perk no_ads ou VIP) ? */
	public function suppressed($user_id)
	{
		$user_id = (int)$user_id;

		if (!$user_id)
		{
			return FALSE;
		}

		if (($gam = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['gamification'])) && $gam->is_vip($user_id))
		{
			return TRUE;
		}

		if (($shop = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['shop'])) && $shop->has_perk($user_id, self::PERK_NO_ADS))
		{
			return TRUE;
		}

		return FALSE;
	}

	/** Rend une publicité pour un emplacement (vide si membre sans-pub/VIP ou aucune pub). */
	public function render($placement)
	{
		$placement = preg_replace('/[^a-z0-9_-]/i', '', (string)$placement) ?: 'sidebar';
		$user_id   = $this->user() ? (int)$this->user->id : 0;

		if ($this->suppressed($user_id))
		{
			return '';
		}

		$ads = $this->db	->select('*')
							->from('nf_ads')
							->where('placement', $placement)
							->where('active', 1)
							->get(FALSE);

		$now = time();
		$ads = array_values(array_filter($ads, function($a) use ($now){
			return (empty($a['starts_at']) || strtotime($a['starts_at']) <= $now)
				&& (empty($a['ends_at'])   || strtotime($a['ends_at'])   >= $now);
		}));

		if (!$ads)
		{
			return '';
		}

		$ad = $ads[array_rand($ads)];

		$this->db->where('id', (int)$ad['id'])->update('nf_ads', ['impressions' => (int)$ad['impressions'] + 1]);

		if ($ad['format'] === 'html')
		{
			return '<div class="nf-ad nf-ad-html">'.$ad['html'].'</div>';
		}

		return '<a class="nf-ad nf-ad-image" href="'.url('ads/click/'.(int)$ad['id']).'" target="_blank" rel="noopener sponsored" title="'.htmlspecialchars($ad['title']).'">'
			.'<img src="'.htmlspecialchars($ad['image_url']).'" alt="'.htmlspecialchars($ad['title']).'" style="max-width:100%;height:auto;" />'
			.'</a>';
	}
}
