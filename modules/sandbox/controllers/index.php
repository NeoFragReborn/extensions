<?php
declare(strict_types=1);
namespace NF\Modules\Sandbox\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Sandbox\Lib\Diff;

/**
 * La page du bac à sable : on écrit à gauche, on voit le résultat à droite.
 *
 * Le rendu passe par `bbcode()`, la même fonction que le forum — un bac à sable qui rendrait
 * autrement mentirait. En dessous, on montre CE QUI A ÉTÉ RETIRÉ par l'assainissement : c'est la
 * seule chose que le produit n'explique nulle part ailleurs, et la raison d'être du module.
 */
class Index extends Controller_Module
{
	public function index($brouillon, $emojis)
	{
		$this	->title($this->lang('Bac à sable'))
				->icon('fas fa-flask')
				->breadcrumb();

		$this->form()
			 ->add_rules([
				'content' => [
					'label' => $this->lang('Votre essai'),
					'type'  => 'editor',
					'value' => $brouillon,
				]
			 ])
			 ->add_submit($this->lang('Voir le résultat'), 'fas fa-eye');

		$rendu = $brouillon;

		// Ce que le membre a RÉELLEMENT envoyé, avant que le formulaire ne l'assainisse. C'est la
		// seule valeur qui permette de dire ce qui a été retiré : `$post['content']` est déjà propre,
		// et le comparer au rendu revenait à annoncer « rien n'a été retiré » quoi qu'il arrive.
		$brut = '';

		if ($this->form()->is_valid($post))
		{
			$brut  = Diff::contenu((string) ($_POST[$this->form()->token()]['content'] ?? ''));
			$rendu = Diff::contenu($post['content']);

			// Le brouillon est conservé : on revient au bac à sable pour reprendre son essai, pas
			// pour le retaper. Une ligne par membre, remplacée à chaque envoi — `replace()` est
			// l'écriture du produit pour « insérer ou mettre à jour », employée par le cœur.
			NeoFrag()->db->replace('nf_sandbox_drafts', [
				'user_id' => (int) $this->user->id,
				'content' => $rendu,
			]);

			notify($this->lang('Essai enregistré.'));
		}

		return $this	->css('sandbox')
						->panel()
						->title($this->lang('Bac à sable'), 'fas fa-flask')
						->body($this->_aide($emojis).$this->form()->display().$this->_apercu($rendu, $brut));
	}

	/**
	 * L'aperçu, et ce que l'assainissement a retiré.
	 *
	 * `$stocke` est la valeur retenue — assainie par le formulaire, et c'est elle qu'on affiche.
	 * `$brut` est ce que le membre a envoyé, vide quand la page est simplement ouverte : c'est lui
	 * qui sert de point de comparaison, faute de quoi le module annoncerait toujours que rien n'a
	 * été retiré.
	 */
	protected function _apercu(string $stocke, string $brut = ''): string
	{
		if (trim($stocke) === '')
		{
			return '';
		}

		$rendu  = bbcode($stocke);
		$retire = Diff::retire($brut !== '' ? $brut : $stocke, $rendu);

		$html  = '<h2 class="h5 mt-4">'.$this->lang('Le résultat').'</h2>';
		$html .= '<div class="nf-sandbox-preview">'.$rendu.'</div>';

		if ($retire['tags'] || $retire['attributes'])
		{
			$html .= '<div class="alert alert-warning nf-sandbox-removed">';
			$html .= '<strong><i class="fas fa-filter"></i> '.$this->lang('Ce que le site a retiré').'</strong>';
			$html .= '<p>'.$this->lang('Tout n\'est pas autorisé dans un message : ce qui suit a été écarté pour la sécurité de tous.').'</p>';

			if ($retire['tags'])
			{
				$html .= '<div>'.$this->lang('Balises').' : ';
				$html .= implode(' ', array_map(static fn ($t) => '<code>&lt;'.nf_texte($t).'&gt;</code>', $retire['tags']));
				$html .= '</div>';
			}

			if ($retire['attributes'])
			{
				$html .= '<div>'.$this->lang('Attributs').' : ';
				$html .= implode(' ', array_map(static fn ($a) => '<code>'.nf_texte($a).'</code>', $retire['attributes']));
				$html .= '</div>';
			}

			if ($retire['truncated'])
			{
				$html .= '<div class="text-muted">'.$this->lang('… et d\'autres encore.').'</div>';
			}

			$html .= '</div>';
		}
		else if ($brut !== '')
		{
			// On ne l'annonce que si l'on a VRAIMENT comparé à ce qui a été envoyé. À la simple
			// ouverture de la page, le brouillon relu est déjà propre : dire « rien n'a été retiré »
			// serait exact et pourtant trompeur, puisque la comparaison n'a pas eu lieu.
			$html .= '<div class="alert alert-success"><i class="fas fa-check"></i> '
				.$this->lang('Rien n\'a été retiré : tout ce que vous avez écrit passe tel quel.').'</div>';
		}

		return $html;
	}

	/** L'aide-mémoire, construit du RÉEL : les émojis viennent de la base, jamais d'une liste recopiée. */
	protected function _aide($emojis): string
	{
		$html  = '<div class="nf-sandbox-help">';
		$html .= '<p>'.$this->lang('Écrivez ce que vous voulez, envoyez, et regardez ce que le site en fait. Rien n\'est publié : cette page n\'est visible que de vous.').'</p>';
		$html .= '<ul>';
		$html .= '<li>'.$this->lang('Une adresse web devient un lien toute seule — essayez <code>https://neofr.ag</code>.').'</li>';
		$html .= '<li>'.$this->lang('Un <code>@pseudo</code> devient un lien vers le profil du membre, s\'il existe.').'</li>';

		if ($emojis)
		{
			$exemples = array_slice($emojis, 0, 8);
			$html .= '<li>'.$this->lang('Les émojis du site s\'écrivent entre deux-points : %s.',
				implode(' ', array_map(static fn ($n) => '<code>:'.nf_texte($n).':</code>', $exemples))).'</li>';
		}
		else
		{
			$html .= '<li>'.$this->lang('Aucun émoji personnalisé n\'est configuré sur ce site pour le moment.').'</li>';
		}

		$html .= '<li>'.$this->lang('La mise en forme riche passe par la barre d\'outils ci-dessous ; ce qui n\'est pas autorisé est retiré, et le site vous dira quoi.').'</li>';
		$html .= '</ul></div>';

		return $html;
	}
}
