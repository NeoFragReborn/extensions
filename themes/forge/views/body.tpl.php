<?php
/*
 * Forge 2.0 « Coulée » (chantier B, étape B2, maquette retenue le 2026-10-06) : le site comme le poste de
 * commandement d'un clan.
 *
 *   ┌──────┬──────────────────────────────┐   Ordinateur : le RAIL d'acier à gauche — le blason, la zone « Rail de
 *   │ rail │ foyer (haut de page, lave)    │   navigation », le compte —, collé pendant qu'on défile ; à droite, le
 *   │      ├──────────────────────────────┤   foyer de lave (la zone « Haut de page », l'accueil seulement), le
 *   │      │ contenu                       │   contenu, puis le pied riveté.
 *   │      │ pied riveté                   │
 *   └──────┴──────────────────────────────┘   Téléphone et tablette (forge.css, § Téléphone) : le blason et le compte
 *                                              en barre du haut, la navigation en barre d'onglets en bas.
 *
 * Les gabarits des modules restent ceux de tous les thèmes (décision du chantier B) : Forge les habille par sa
 * feuille, sans en réécrire un seul.
 */
$logo = '';

foreach ([(int) $this->config->forge_logo, (int) $this->config->nf_team_logo] as $fichier)
{
	if ($fichier && ($chemin = NeoFrag()->model2('file', $fichier)->path()))
	{
		$logo = $chemin;
		break;
	}
}

// Sans logo, un sigle : les initiales des deux premiers mots du nom du site (« NeoFrag Reborn » → « NR »).
$nom   = html_entity_decode((string) $this->config->nf_name, ENT_QUOTES, 'UTF-8');
$mots  = preg_split('/[\s\-_.]+/u', trim($nom), -1, PREG_SPLIT_NO_EMPTY) ?: ['NF'];
$sigle = count($mots) > 1 ? mb_substr($mots[0], 0, 1).mb_substr($mots[1], 0, 1) : mb_substr($mots[0], 0, 2);

// Les braises du foyer (réglage) : aucune, sept douces, seize vives. Jamais enregistré, le réglage se lit FALSE.
$braises = [0, 7, 16][$this->config->forge_braises === FALSE ? 1 : max(0, min(2, (int) $this->config->forge_braises))];
?>
<a class="fg-evitement" href="#fg-contenu"><?php echo $this->lang('Aller au contenu') ?></a>
<div class="fg-coulee">
	<aside class="fg-rail" data-bs-theme="dark" aria-label="<?php echo $this->lang('Navigation du site') ?>">
		<a class="fg-blason" href="<?php echo url() ?>" title="<?php echo nf_texte($nom) ?>">
			<?php if ($logo): ?>
				<img class="fg-blason-logo" src="<?php echo $logo ?>" alt="" />
			<?php else: ?>
				<span class="fg-blason-sigle" aria-hidden="true"><?php echo nf_texte(mb_strtoupper($sigle)) ?></span>
			<?php endif ?>
			<span class="fg-blason-nom"><?php echo nf_texte($nom) ?></span>
		</a>
		<?php if ($zone = $this->output->region('rail')): ?>
			<div class="fg-rail-zone"><?php echo $zone ?></div>
		<?php endif ?>
		<?php echo $this->view('compte') ?>
	</aside>

	<div class="fg-page">
		<?php if ($zone = $this->output->region('foyer')): ?>
			<header class="fg-foyer" data-bs-theme="dark">
				<?php echo str_repeat('<i class="fg-braise" aria-hidden="true"></i>', $braises) ?>
				<div class="fg-cadre"><?php echo $zone ?></div>
			</header>
		<?php endif ?>

		<main class="fg-contenu" id="fg-contenu" tabindex="-1">
			<div class="fg-cadre"><?php echo $this->output->region('content') ?></div>
		</main>

		<?php if ($zone = $this->output->region('after_content')): ?>
			<section class="fg-apres">
				<div class="fg-cadre"><?php echo $zone ?></div>
			</section>
		<?php endif ?>

		<footer class="fg-pied">
			<div class="fg-cadre">
				<?php if ($zone = $this->output->region('footer')): ?>
					<div class="fg-pied-zone"><?php echo $zone ?></div>
				<?php endif ?>
				<div class="fg-pied-barre">
					<div class="fg-copy">
						<?php echo $this->lang('Propulsé par') ?> <a href="https://neofrag-reborn.xyz" target="_blank" rel="noopener">NeoFrag Reborn</a>
						· © <?php echo date('Y') ?> <span class="fg-site-name"><?php echo nf_texte($nom) ?></span>
						· <?php echo nf_liens_legaux() ?>
					</div>
					<?php echo $this->view('socials') ?>
					<?php echo nf_selecteur_theme() ?>
					<?php if (count($this->config->langs) > 1): $cur = $this->config->lang->info(); ?>
					<form method="post" action="<?php echo url('ajax/settings/languages') ?>" class="fg-lang dropup">
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
			</div>
		</footer>
	</div>
</div>
