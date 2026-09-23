<?php
declare(strict_types=1);
namespace NF\Modules\Webradio\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Webradio\Lib\Schedule;

/**
 * La page publique : le lecteur, ce qui passe maintenant, et la grille de la semaine.
 *
 * Le lecteur est un `<audio>` du navigateur, sans aucune bibliothèque : lire un flux est la chose
 * même que cet élément sait faire, et lui ajouter un habillage JavaScript ne rendrait pas le son
 * meilleur. La conséquence est qu'il fonctionne **sans notre JavaScript** — celui-ci ne sert qu'à
 * rafraîchir l'émission en cours sans recharger la page.
 *
 * La grille, elle, est du HTML pur : elle reste lisible quand le flux est injoignable, ce qui est le
 * cas le plus fréquent d'une webradio amateur.
 */
class Index extends Controller_Module
{
	/** Les jours de la grille, du lundi au dimanche — leurs NOMS sont rendus par `nom_jour()`. */
	const JOURS = [1, 2, 3, 4, 5, 6, 7];

	/**
	 * Le nom localisé d'un jour de la semaine (1 = lundi), rendu par le produit lui-même.
	 *
	 * `timetostr('l', …)` applique la table de l'addon de langue actif : le produit sait déjà dire
	 * « Montag » et « lunedì ». Les retraduire ici, c'eût été sept mots de plus dans six fichiers,
	 * et une seconde source de vérité pour rien.
	 *
	 * Le 1er janvier 2024 était un lundi : c'est le point de départ du décalage.
	 */
	public static function nom_jour(int $jour): string
	{
		return timetostr('l', strtotime('2024-01-01 +'.(max(1, min(7, $jour)) - 1).' days'));
	}

	public function index($station, $creneaux, $en_cours)
	{
		$this	->title($station['name'] !== '' ? $station['name'] : $this->lang('Webradio'))
				->icon('fas fa-broadcast-tower')
				->breadcrumb();

		$corps = $this->_lecteur($station, $en_cours).$this->_grille($creneaux, $en_cours);

		return $this	->css('webradio')
						->js('webradio')
						->panel()
						->title($station['name'] !== '' ? $station['name'] : $this->lang('Webradio'), 'fas fa-broadcast-tower')
						->body($corps);
	}

	protected function _lecteur($station, $en_cours): string
	{
		if ($station['stream'] === '')
		{
			// Aucun flux configuré : on le dit, plutôt que d'afficher un lecteur muet dont personne
			// ne saura s'il est cassé ou si la station est simplement à l'arrêt.
			return '<div class="alert alert-info">'.$this->lang('Aucun flux n\'est configuré pour le moment.').'</div>';
		}

		// L'adresse du fragment est posée par le gabarit : le script ne construit aucune URL.
		$html = '<div class="nf-webradio" data-webradio-refresh="60" data-webradio-endpoint="'.htmlspecialchars((string) (url('ajax/webradio/now')), ENT_QUOTES).'">';
		$html .= '<div class="nf-webradio-player">';
		$html .= '<audio controls preload="none" src="'.htmlspecialchars((string) ($station['stream']), ENT_QUOTES).'">';
		// Le repli du `<audio>` : un navigateur qui ne sait pas lire le flux propose au moins le lien.
		$html .= '<a href="'.htmlspecialchars((string) ($station['stream']), ENT_QUOTES).'" rel="noopener">'.$this->lang('Écouter le flux').'</a>';
		$html .= '</audio>';
		$html .= '</div>';

		$html .= '<div class="nf-webradio-now" data-webradio-now>';
		$html .= $this->_a_l_antenne($en_cours);
		$html .= '</div>';

		if ($station['site'] !== '')
		{
			$html .= '<div class="nf-webradio-site"><a href="'.htmlspecialchars((string) ($station['site']), ENT_QUOTES).'" target="_blank" rel="noopener">'
				.'<i class="fas fa-external-link-alt"></i> '.$this->lang('Site de la station').'</a></div>';
		}

		if (Schedule::contenu_mixte($station['stream']))
		{
			// Un flux `http` sur un site `https` est refusé par le navigateur lui-même. On le dit,
			// sans quoi le visiteur voit un lecteur qui ne démarre jamais, sans explication.
			$html .= '<div class="alert alert-warning">'.$this->lang('Ce flux est diffusé en http : votre navigateur peut le refuser sur une page sécurisée.').'</div>';
		}

		return $html.'</div>';
	}

	/** Ce qui passe maintenant — remplacé tel quel par le script quand l'émission change. */
	protected function _a_l_antenne($en_cours): string
	{
		if (!$en_cours)
		{
			return '<span class="nf-webradio-off"><i class="fas fa-music"></i> '.$this->lang('Programmation libre').'</span>';
		}

		$html = '<span class="nf-webradio-live"><i class="fas fa-circle"></i> '.$this->lang('À l\'antenne').'</span> ';
		$html .= '<strong>'.htmlspecialchars((string) ($en_cours['title'])).'</strong>';

		if (trim((string) $en_cours['host']) !== '')
		{
			$html .= ' <span class="nf-webradio-host">'.$this->lang('avec %s', htmlspecialchars((string) ($en_cours['host']))).'</span>';
		}

		return $html.' <span class="nf-webradio-hours">'.htmlspecialchars((string) ($en_cours['start_time'].' – '.$en_cours['end_time'])).'</span>';
	}

	protected function _grille($creneaux, $en_cours): string
	{
		if (!$creneaux)
		{
			return '<div class="alert alert-info">'.$this->lang('Aucune émission au programme.').'</div>';
		}

		$par_jour = array_fill_keys(self::JOURS, []);

		foreach ($creneaux as $creneau)
		{
			$par_jour[Schedule::jour($creneau['day'])][] = $creneau;
		}

		$aujourdhui = (int) date('N');
		$html = '<h2 class="h5 mt-4">'.$this->lang('La grille de la semaine').'</h2><div class="nf-webradio-grid">';

		foreach (self::JOURS as $numero)
		{
			$html .= '<div class="nf-webradio-day'.($numero === $aujourdhui ? ' is-today' : '').'">';
			$html .= '<h3 class="nf-webradio-day-title">'.htmlspecialchars((string) (self::nom_jour($numero))).'</h3>';

			if (!$par_jour[$numero])
			{
				$html .= '<p class="nf-webradio-empty">'.$this->lang('Programmation libre').'</p>';
			}
			else
			{
				$html .= '<ul>';

				foreach ($par_jour[$numero] as $creneau)
				{
					$direct = $en_cours && (int) $en_cours['id'] === (int) $creneau['id'];

					$html .= '<li'.($direct ? ' class="is-live"' : '').'>';
					$html .= '<span class="nf-webradio-time">'.htmlspecialchars((string) ($creneau['start_time'].' – '.$creneau['end_time'])).'</span> ';
					$html .= '<strong>'.htmlspecialchars((string) ($creneau['title'])).'</strong>';

					if (trim((string) $creneau['host']) !== '')
					{
						$html .= ' <span class="nf-webradio-host">'.$this->lang('avec %s', htmlspecialchars((string) ($creneau['host']))).'</span>';
					}

					if (trim((string) $creneau['description']) !== '')
					{
						$html .= '<p class="nf-webradio-desc">'.nl2br(htmlspecialchars((string) ($creneau['description']))).'</p>';
					}

					$html .= '</li>';
				}

				$html .= '</ul>';
			}

			$html .= '</div>';
		}

		return $html.'</div>';
	}
}
