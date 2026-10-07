<?php
/*
 * Extend 2.0.0 « Lanceur » (chantier B ; direction A, validée le 2026-10-07) : le site comme le lanceur d'un jeu.
 *
 *   ┌──────────────────────────────────────────────────────────────────┐  La barre : la marque, les onglets (zone
 *   │ ⬢ LE NOM   ACCUEIL  ACTUALITÉS  FORUM  …          🔍 🔔 (◉ Moi)  │  « Onglets »), le compte. Puis la page :
 *   ├───────────────────────────────────────────────┬──────────────────┤  sur l'accueil, la vitrine (zone
 *   │ ┌───────────────────────────────────────────┐ │ EN LIGNE         │  « Vitrine » : le diaporama ; sans lui,
 *   │ │  LA VITRINE (diaporama ou nom du site)     │ │ ◉ Sable          │  le nom du site sur l'image du thème) ;
 *   │ └───────────────────────────────────────────┘ │ ◉ Doryan         │  ailleurs, le titre de la page sur cette
 *   │  le contenu (rendez-vous, actualités…)         │ DISCUSSION       │  image. Le panneau de droite (zone « En
 *   │                                                │ le salon         │  ligne ») reste ouvert sur toutes les
 *   ├───────────────────────────────────────────────┴──────────────────┤  pages. La barre d'état (zone du même
 *   │ ● serveur · ● vocal (zone « Barre d'état »)     🌐 FR · © le site │  nom), puis la langue et la mention.
 *   └──────────────────────────────────────────────────────────────────┘
 *
 * Au téléphone : une colonne ; les onglets passent en bas de l'écran (js/extend.js y ajoute « En ligne », qui ouvre le
 * panneau, et « Plus » au-delà de quatre rubriques). Les gabarits des modules restent ceux de tous les thèmes
 * (décision du chantier B) : Extend les habille par sa feuille, sans en réécrire un seul.
 */
$logo = '';

foreach ([(int) $this->config->extend_logo, (int) $this->config->nf_team_logo] as $fichier)
{
	if ($fichier && ($chemin = NeoFrag()->model2('file', $fichier)->path()))
	{
		$logo = $chemin;
		break;
	}
}

$nom     = html_entity_decode((string) $this->config->nf_name, ENT_QUOTES, 'UTF-8');
$devise  = html_entity_decode((string) $this->config->nf_description, ENT_QUOTES, 'UTF-8');
$accueil = trim((string) $this->url->request, '/') === '' || $this->url->request === 'index';
$discord = trim((string) $this->config->nf_social_discord);

// L'emblème, quand il n'y a pas de logo : les initiales du site (trois lettres au plus), dans un hexagone.
$mots     = preg_split('/[\s\-]+/u', trim($nom), -1, PREG_SPLIT_NO_EMPTY) ?: [$nom];
$initiales = mb_strtoupper(count($mots) > 1 ? implode('', array_map(static fn ($m) => mb_substr($m, 0, 1), array_slice($mots, 0, 3))) : mb_substr($nom, 0, 3));

// Le titre de la page, sur sa bande : celui que le module s'est donné (déjà codé pour le HTML, comme dans
// l'administration).
$titre = $accueil ? '' : trim(strip_tags((string) $this->output->data->get('module', 'title')));

$navigation = $this->output->region('navigation');
// La vitrine se montre là où l'on y a posé quelque chose (l'accueil, à l'installation) ; elle tient alors lieu de bande
// de titre. Sur l'accueil sans diaporama, le thème dessine la sienne.
$vitrine    = $this->output->region('vitrine');
$contenu    = $this->output->region('content');
$panneau    = $this->output->region('dock');
$etat       = $this->output->region('etat');
$reseaux    = trim((string) $this->view('socials'));
$inscription = !$this->user->id && $this->config->nf_registration_status;
?>
<a class="ex-evitement" href="#ex-contenu"><?php echo $this->lang('Aller au contenu') ?></a>
<header class="ex-barre">
	<a href="<?php echo url() ?>" class="ex-marque">
		<?php if ($logo): ?>
		<img class="ex-logo" src="<?php echo $logo ?>" alt="" />
		<?php else: ?>
		<span class="ex-embleme" aria-hidden="true"><?php echo nf_texte($initiales) ?></span>
		<?php endif ?>
		<span class="ex-nom"><?php echo nf_texte($nom) ?></span>
	</a>
	<?php if ($navigation): ?>
	<nav class="ex-onglets" aria-label="<?php echo $this->lang('Onglets') ?>"><?php echo $navigation ?></nav>
	<?php endif ?>
	<div class="ex-compte"><?php echo $this->view('compte') ?></div>
</header>

<div class="ex-cadre<?php if (!$panneau) echo ' ex-cadre-seul' ?>">
	<main class="ex-principal" id="ex-contenu" tabindex="-1" aria-label="<?php echo $this->lang('Contenu') ?>">
		<?php if ($vitrine): ?>
		<section class="ex-vitrine" aria-label="<?php echo $this->lang('Vitrine') ?>"><?php echo $vitrine ?></section>
		<?php elseif ($accueil): ?>
		<section class="ex-vitrine ex-vitrine-site">
			<div class="ex-art" aria-hidden="true"><i class="ex-ciel"></i><i class="ex-aurore"></i><i class="ex-monts"></i><i class="ex-monts ex-pres"></i></div>
			<div class="ex-vitrine-texte">
				<h1 class="ex-vitrine-titre"><?php echo nf_texte($nom) ?></h1>
				<?php if ($devise !== '' && $devise !== $nom): ?>
				<p class="ex-vitrine-devise"><?php echo nf_texte($devise) ?></p>
				<?php endif ?>
				<?php if ($inscription || $discord !== ''): ?>
				<div class="ex-vitrine-boutons">
					<?php if ($inscription): ?>
					<a class="ex-bouton" href="<?php echo url('user/registration') ?>" data-modal-ajax="<?php echo url('ajax/user/register') ?>"><?php echo $this->lang('Créer un compte') ?></a>
					<?php endif ?>
					<?php if ($discord !== ''): ?>
					<a class="ex-bouton ex-bouton-verre" href="<?php echo nf_texte($discord) ?>" target="_blank" rel="noopener"><?php echo icon('fab fa-discord') ?> <?php echo $this->lang('Rejoindre le Discord') ?></a>
					<?php endif ?>
				</div>
				<?php endif ?>
			</div>
		</section>
		<?php elseif ($titre !== ''): ?>
		<section class="ex-entete">
			<div class="ex-art" aria-hidden="true"><i class="ex-ciel"></i><i class="ex-aurore"></i><i class="ex-monts"></i><i class="ex-monts ex-pres"></i></div>
			<h1 class="ex-entete-titre"><?php echo $titre ?></h1>
		</section>
		<?php endif ?>
		<?php echo $contenu ?>
	</main>
	<?php if ($panneau): ?>
	<aside class="ex-panneau" id="ex-panneau" aria-label="<?php echo $this->lang('En ligne') ?>">
		<button type="button" class="ex-panneau-fermer" aria-controls="ex-panneau" aria-label="<?php echo $this->lang('Fermer le panneau') ?>"><i class="fas fa-xmark" aria-hidden="true"></i></button>
		<?php echo $panneau ?>
	</aside>
	<?php endif ?>
</div>

<footer class="ex-etat" aria-label="<?php echo $this->lang('Barre d’état') ?>">
	<?php if ($etat): ?>
	<div class="ex-etat-zone"><?php echo $etat ?></div>
	<?php endif ?>
	<div class="ex-etat-droite">
		<?php echo $reseaux ?>
		<?php echo nf_selecteur_theme() ?>
		<?php if (count($this->config->langs) > 1): $cur = $this->config->lang->info(); ?>
		<form method="post" action="<?php echo url('ajax/settings/languages') ?>" class="ex-langue dropup">
			<input type="hidden" name="url" value="<?php echo nf_texte($this->url->base.trim($cur->name.'/'.nf_chemin_public(), '/').$this->url->query) ?>" />
			<button class="btn btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-globe" aria-hidden="true"></i> <?php echo nf_texte($cur->title) ?></button>
			<div class="dropdown-menu dropdown-menu-end">
				<?php foreach ($this->config->langs as $l): $i = $l->info(); ?>
				<button type="submit" name="language" value="<?php echo $i->name ?>" class="dropdown-item<?php echo $i->name === $cur->name ? ' active' : '' ?>"><?php echo $i->icon ?> <?php echo nf_texte($i->title) ?></button>
				<?php endforeach ?>
			</div>
		</form>
		<?php endif ?>
		<span class="ex-copy"><?php echo $this->lang('Propulsé par') ?> <a href="https://neofr.ag" target="_blank" rel="noopener">NeoFrag Reborn</a> · © <?php echo date('Y') ?> <span class="ex-site-name"><?php echo nf_texte($nom) ?></span></span>
	</div>
</footer>
