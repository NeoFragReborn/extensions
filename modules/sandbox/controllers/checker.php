<?php
declare(strict_types=1);
namespace NF\Modules\Sandbox\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function index()
	{
		// Le bac à sable garde un brouillon par membre : il n'a de sens que connecté. Un visiteur
		// anonyme reçoit la page d'accès refusé du produit, pas un formulaire qui ne mènerait nulle
		// part.
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}

		$brouillon = NeoFrag()->db	->select('content')
									->from('nf_sandbox_drafts')
									->where('user_id', (int) $this->user->id)
									->row();

		// Les émojis viennent de `custom_emojis_map()`, le helper du produit, et NON d'une lecture
		// directe de `nf_custom_emojis` : celle-ci créait un couplage à un autre module que
		// `check-addon-coupling` a refusé — à juste titre. Le helper se protège déjà lui-même quand
		// la table n'existe pas, et l'aide-mémoire reste construit du réel plutôt que recopié.
		$emojis = [];

		foreach (array_keys(custom_emojis_map()) as $jeton)
		{
			// Les clés sont de la forme « :nom: » ; l'aide-mémoire réécrit les deux-points lui-même.
			$emojis[] = trim((string) $jeton, ':');
		}

		sort($emojis);

		return [(string) (is_array($brouillon) ? ($brouillon['content'] ?? '') : (string) $brouillon), array_slice($emojis, 0, 50)];
	}
}
