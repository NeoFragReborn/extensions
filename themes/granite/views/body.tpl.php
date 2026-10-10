<?php
/*
 * Granite 2.0 « Gazette » (chantier B, étape B2, maquette A retenue le 2026-10-06) : le site comme le journal du club.
 *
 *   ┌───────────────────────────────────────────┐   La date du jour et le compte, sur une ligne ; le titre imprimé
 *   │ mardi 6 octobre 2026          compte      │   (le logo réglé, sinon le nom du site) et sa devise ; un double
 *   │            LE NOM DU SITE                 │   filet ; les rubriques (zone « Rubriques ») ; la ligne « En bref »
 *   │            sa devise                      │   (zone du même nom), qui défile ; la une (zone « Contenu ») ; puis
 *   │ ═════════════════════════════════════════ │   « Après le contenu » ; le pied en « ours » (zone « Pied de page »,
 *   │   Rubriques · entre · deux · filets       │   puis la ligne de qui publie).
 *   │ EN BREF ▸ ce qui défile…                  │
 *   │ la une à colonnes                          │   Au téléphone : le titre se resserre, les rubriques défilent de
 *   │ ═════════ l'ours ═════════                │   côté, tout passe en une colonne.
 *   └───────────────────────────────────────────┘
 *
 * Les gabarits des modules restent ceux de tous les thèmes (décision du chantier B) : la Gazette les habille par sa
 * feuille, sans en réécrire un seul.
 */
$logo = '';

foreach ([(int) $this->config->granite_logo, (int) $this->config->nf_team_logo] as $fichier)
{
	if ($fichier && ($chemin = NeoFrag()->model2('file', $fichier)->path()))
	{
		$logo = $chemin;
		break;
	}
}

$nom    = html_entity_decode((string) $this->config->nf_name, ENT_QUOTES, 'UTF-8');
$devise = html_entity_decode((string) $this->config->nf_description, ENT_QUOTES, 'UTF-8');
?>
<a class="gz-evitement" href="#gz-contenu"><?php echo $this->lang('Aller au contenu') ?></a>
<div class="gz-journal">
	<div class="gz-bandeau-date">
		<span class="gz-date"><?php echo nf_texte(timetostr($this->lang('l j F Y'))) ?></span>
		<?php echo $this->view('compte') ?>
	</div>

	<header class="gz-titre">
		<a href="<?php echo url() ?>" class="gz-titre-lien">
			<?php if ($logo): ?><img class="gz-logo" src="<?php echo $logo ?>" alt="" /><?php endif ?>
			<span class="gz-nom"><?php echo nf_texte($nom) ?></span>
		</a>
		<?php if ($devise !== '' && $devise !== $nom): ?>
			<p class="gz-devise"><?php echo nf_texte($devise) ?></p>
		<?php endif ?>
	</header>

	<div class="gz-filet-double" aria-hidden="true"></div>

	<?php if ($zone = $this->output->region('rubriques')): ?>
		<nav class="gz-rubriques" aria-label="<?php echo $this->lang('Rubriques') ?>"><?php echo $zone ?></nav>
	<?php endif ?>

	<?php if ($zone = $this->output->region('breves')): ?>
		<div class="gz-breves">
			<b class="gz-breves-titre"><?php echo $this->lang('En bref') ?></b>
			<div class="gz-breves-fil"><?php echo $zone ?></div>
		</div>
	<?php endif ?>

	<main class="gz-contenu" id="gz-contenu" tabindex="-1">
		<?php echo $this->output->region('content') ?>
	</main>

	<?php if ($zone = $this->output->region('after_content')): ?>
		<section class="gz-apres"><?php echo $zone ?></section>
	<?php endif ?>

	<footer class="gz-ours">
		<?php if ($zone = $this->output->region('footer')): ?>
			<div class="gz-ours-zone"><?php echo $zone ?></div>
		<?php endif ?>
		<div class="gz-ours-barre">
			<div class="gz-copy">
				<?php echo $this->lang('Propulsé par') ?> <a href="https://neofrag-reborn.xyz" target="_blank" rel="noopener">NeoFrag Reborn</a>
				· © <?php echo date('Y') ?> <span class="gz-site-name"><?php echo nf_texte($nom) ?></span>
				· <?php echo nf_liens_legaux() ?>
			</div>
			<?php echo $this->view('socials') ?>
			<?php echo nf_selecteur_theme() ?>
			<?php if (count($this->config->langs) > 1): $cur = $this->config->lang->info(); ?>
			<form method="post" action="<?php echo url('ajax/settings/languages') ?>" class="gz-lang dropup">
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
</div>
