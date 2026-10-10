<?php
/*
 * Chronique (chantier B, étape B3, maquette C retenue le 2026-10-06) : le site comme le carnet de la saison.
 *
 *   ┌────────────────────────────────────────────────┐   Un en-tête discret, qui reste en haut : le nom du site (ou son
 *   │ Le nom du site     compte · ☰ Sommaire · Adhérer│   logo), le compte, le bouton « Sommaire » qui ouvre tout le site
 *   │ ─────────────── trait de progression ───────── │   (zone « Sommaire ») et l'appel à adhérer ; un fin trait suit la
 *   │ SAISON 2026 – 2027                              │   lecture. L'accueil s'ouvre sur une grande phrase (le nom, la
 *   │ Le nom du site           ┌ la semaine ┐         │   devise) et la zone « Ouverture » (la semaine en cours). Puis le
 *   │ sa devise                 └───────────┘         │   corps : la zone « Contenu » (la frise de la saison sur
 *   │ ◆ OCTOBRE 2026                    │ À côté      │   l'accueil) et la zone « À côté », collée. Le pied ferme le carnet
 *   │ ● une entrée                      │             │   (zone « Pied de page », puis qui publie).
 *   │ ◇ — fin du carnet —                             │
 *   └────────────────────────────────────────────────┘   Au téléphone : une seule colonne, le sommaire en plein écran.
 *
 * Les gabarits des modules restent ceux de tous les thèmes (décision du chantier B) : Chronique les habille par sa
 * feuille, sans en réécrire un seul.
 */
$logo = '';

foreach ([(int) $this->config->chronique_logo, (int) $this->config->nf_team_logo] as $fichier)
{
	if ($fichier && ($chemin = NeoFrag()->model2('file', $fichier)->path()))
	{
		$logo = $chemin;
		break;
	}
}

$nom    = html_entity_decode((string) $this->config->nf_name, ENT_QUOTES, 'UTF-8');
$devise = html_entity_decode((string) $this->config->nf_description, ENT_QUOTES, 'UTF-8');
$accueil = trim((string) $this->url->request, '/') === '' || $this->url->request === 'index';

// La saison d'un club commence en septembre : « Saison 2026 – 2027 » de septembre à août, dans le fuseau de celui
// qui regarde.
$aujourdhui = new \DateTimeImmutable('now', nf_fuseau());
$annee      = (int) $aujourdhui->format('Y');
$saison     = (int) $aujourdhui->format('n') >= 9 ? [$annee, $annee + 1] : [$annee - 1, $annee];

$cote = $this->output->region('cote');
?>
<a class="ch-evitement" href="#ch-contenu"><?php echo $this->lang('Aller au contenu') ?></a>
<header class="ch-tete">
	<div class="ch-tete-ligne">
		<a href="<?php echo url() ?>" class="ch-marque">
			<?php if ($logo): ?><img class="ch-logo" src="<?php echo $logo ?>" alt="" /><?php endif ?>
			<span class="ch-nom"><?php echo nf_texte($nom) ?></span>
		</a>
		<div class="ch-tete-droite">
			<?php echo $this->view('compte') ?>
			<details class="ch-sommaire">
				<summary class="ch-bouton-sommaire"><i aria-hidden="true"></i><span><?php echo $this->lang('Sommaire') ?></span></summary>
				<div class="ch-sommaire-cadre">
					<div class="ch-sommaire-tete">
						<p class="ch-sommaire-titre"><?php echo $this->lang('Sommaire') ?></p>
						<button type="button" class="ch-sommaire-fermer"><?php echo $this->lang('Fermer') ?> <i class="fas fa-xmark" aria-hidden="true"></i></button>
					</div>
					<nav class="ch-sommaire-zone" aria-label="<?php echo $this->lang('Sommaire') ?>"><?php echo $this->output->region('sommaire') ?></nav>
				</div>
			</details>
			<?php if (!$this->user->id): ?>
				<?php if ($this->config->chronique_appel): ?>
					<a class="ch-appel" href="<?php echo nf_texte($this->config->chronique_appel) ?>"><?php echo $this->lang('Adhérer') ?></a>
				<?php elseif ($this->config->nf_registration_status): ?>
					<a class="ch-appel" href="<?php echo url('user/registration') ?>" data-modal-ajax="<?php echo url('ajax/user/register') ?>"><?php echo $this->lang('Adhérer') ?></a>
				<?php endif ?>
			<?php endif ?>
		</div>
	</div>
	<div class="ch-progression" aria-hidden="true"></div>
</header>

<main class="ch-page" id="ch-contenu" tabindex="-1">
	<?php if ($accueil): ?>
	<section class="ch-ouverture<?php if (!($ouverture = $this->output->region('ouverture'))) echo ' ch-ouverture-seule' ?>">
		<div class="ch-ouverture-texte">
			<small class="ch-saison"><?php echo $this->lang('Saison %d – %d', $saison[0], $saison[1]) ?></small>
			<h1 class="ch-titre"><?php echo nf_texte($nom) ?></h1>
			<?php if ($devise !== '' && $devise !== $nom): ?>
			<p class="ch-devise"><?php echo nf_texte($devise) ?></p>
			<?php endif ?>
		</div>
		<?php if ($ouverture): ?>
		<div class="ch-ouverture-zone"><?php echo $ouverture ?></div>
		<?php endif ?>
	</section>
	<?php endif ?>

	<div class="ch-corps<?php if (!$cote) echo ' ch-corps-seul' ?>">
		<div class="ch-contenu"><?php echo $this->output->region('content') ?></div>
		<?php if ($cote): ?>
		<aside class="ch-cote" aria-label="<?php echo $this->lang('À côté') ?>"><?php echo $cote ?></aside>
		<?php endif ?>
	</div>
</main>

<footer class="ch-pied">
	<div class="ch-ornement" aria-hidden="true"></div>
	<p class="ch-pied-nom"><?php echo nf_texte($nom) ?></p>
	<?php if ($zone = $this->output->region('footer')): ?>
		<div class="ch-pied-zone"><?php echo $zone ?></div>
	<?php endif ?>
	<div class="ch-pied-barre">
		<div class="ch-copy">
			<?php echo $this->lang('Propulsé par') ?> <a href="https://neofrag-reborn.xyz" target="_blank" rel="noopener">NeoFrag Reborn</a>
			· © <?php echo date('Y') ?> <span class="ch-site-name"><?php echo nf_texte($nom) ?></span>
			· <?php echo nf_liens_legaux() ?>
		</div>
		<?php echo $this->view('socials') ?>
		<?php echo nf_selecteur_theme() ?>
		<?php if (count($this->config->langs) > 1): $cur = $this->config->lang->info(); ?>
		<form method="post" action="<?php echo url('ajax/settings/languages') ?>" class="ch-lang dropup">
			<input type="hidden" name="url" value="<?php echo nf_texte($this->url->base.trim($cur->name.'/'.nf_chemin_public(), '/').$this->url->query) ?>" />
			<button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?php echo $cur->icon ?> <?php echo strtoupper($cur->name) ?></button>
			<div class="dropdown-menu dropdown-menu-end">
				<?php foreach ($this->config->langs as $l): $i = $l->info(); ?>
				<button type="submit" name="language" value="<?php echo $i->name ?>" class="dropdown-item<?php echo $i->name === $cur->name ? ' active' : '' ?>"><?php echo $i->icon ?> <?php echo nf_texte($i->title) ?></button>
				<?php endforeach ?>
			</div>
		</form>
		<?php endif ?>
	</div>
</footer>
