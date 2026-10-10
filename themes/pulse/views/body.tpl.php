<?php
/*
 * Pulse (chantier B, étape B3, maquette « Dallage » du 2026-10-06) : le site comme la maison commune.
 *
 *   ┌────────────────────────────────────────────────────────┐   Une barre claire, collée en haut : le logo (ou les
 *   │ [RG] Le nom   Accueil · Agenda · Forum…   compte Adhérer│   initiales du site) et le nom, les rubriques en
 *   ├───────────────────────────┬────────────────────────────┤   pastilles (zone « Barre de navigation »), le compte et
 *   │                           │ ▣ le prochain rendez-vous  │   l'appel « Adhérer ». L'accueil est une mosaïque : la
 *   │   la grande dalle         ├────────────────────────────┤   grande dalle d'accueil, posée par le thème, puis la
 *   │   d'accueil               │ ▣ le site en chiffres      │   zone « Mosaïque », dont chaque colonne est une dalle
 *   ├──────────────────┬────────┴─────┬──────────────────────┤   aussi large que sa taille (col-lg-N : N douzièmes).
 *   │ ▣ actualités     │ ▣ agenda     │ …                    │   Ailleurs : la zone « Contenu », et « À côté » si elle
 *   ╰──────────────────┴──────────────┴──────────────────────╯   porte quelque chose. Un pied sombre, où bat le point
 *   ▓ le nom · le plan · nous suivre ▓                            du plan.
 *
 * Au téléphone : une colonne ; les rubriques s'ouvrent par le bouton « Menu ». Les gabarits des modules restent ceux de
 * tous les thèmes (décision du chantier B) : Pulse les habille par sa feuille, sans en réécrire un seul.
 */
$logo = '';

foreach ([(int) $this->config->pulse_logo, (int) $this->config->nf_team_logo] as $fichier)
{
	if ($fichier && ($chemin = NeoFrag()->model2('file', $fichier)->path()))
	{
		$logo = $chemin;
		break;
	}
}

$nom    = html_entity_decode((string) $this->config->nf_name, ENT_QUOTES, 'UTF-8');
$devise = html_entity_decode((string) $this->config->nf_description, ENT_QUOTES, 'UTF-8');

// Les initiales du site, quand il n'a pas de logo : les premières lettres de ses deux premiers mots.
$initiales = '';

foreach (array_slice(preg_split('/[\s\-]+/u', trim($nom)) ?: [], 0, 2) as $mot)
{
	$initiales .= mb_strtoupper(mb_substr($mot, 0, 1));
}

$accueil = trim((string) $this->url->request, '/') === '' || $this->url->request === 'index';

// L'appel à adhérer, pour un visiteur : l'adresse réglée, sinon l'inscription du site quand elle est ouverte.
$appel = '';

if (!$this->user->id)
{
	if ($this->config->pulse_appel)
	{
		$appel = '<a class="pl-appel" href="'.nf_texte($this->config->pulse_appel).'">'.$this->lang('Adhérer').'</a>';
	}
	else if ($this->config->nf_registration_status)
	{
		$appel = '<a class="pl-appel" href="'.url('user/registration').'" data-modal-ajax="'.url('ajax/user/register').'">'.$this->lang('Adhérer').'</a>';
	}
}

// Le second chemin de la dalle d'accueil : l'agenda, sinon les actualités, sinon le forum.
$suite = NULL;

foreach ([['calendar', $this->lang('Voir l’agenda')], ['news', $this->lang('Les actualités')], ['forum', $this->lang('Le forum')]] as [$module, $libelle])
{
	if (($addon = @NeoFrag()->module($module)) && $addon->is_enabled())
	{
		$suite = [url($module), $libelle];
		break;
	}
}

$navigation = $this->output->region('navigation');
$reseaux    = trim((string) $this->view('socials'));
$contenu    = $this->output->region('content');
$cote       = $accueil ? '' : $this->output->region('cote');
?>
<a class="pl-evitement" href="#pl-contenu"><?php echo $this->lang('Aller au contenu') ?></a>
<header class="pl-barre" data-nf-entete>
	<div class="pl-barre-ligne">
		<a href="<?php echo url() ?>" class="pl-marque">
			<?php if ($logo): ?>
			<img class="pl-logo" src="<?php echo $logo ?>" alt="" />
			<?php else: ?>
			<i class="pl-tuile" aria-hidden="true"><?php echo nf_texte($initiales ?: 'N') ?></i>
			<?php endif ?>
			<span class="pl-nom"><?php echo nf_texte($nom) ?></span>
		</a>
		<?php if ($navigation): ?>
		<nav class="pl-nav" id="pl-nav" aria-label="<?php echo $this->lang('Rubriques') ?>"><?php echo $navigation ?></nav>
		<?php endif ?>
		<div class="pl-barre-droite">
			<?php echo $this->view('compte') ?>
			<?php echo $appel ?>
			<?php if ($navigation): ?>
			<button type="button" class="pl-menu" aria-expanded="false" aria-controls="pl-nav"><i aria-hidden="true"></i><span><?php echo $this->lang('Menu') ?></span></button>
			<?php endif ?>
		</div>
	</div>
</header>

<main class="pl-page" id="pl-contenu" tabindex="-1">
	<?php if ($accueil): ?>
	<section class="pl-mosaique" aria-label="<?php echo $this->lang('Accueil') ?>">
		<div class="pl-accueil">
			<div class="pl-accueil-texte">
				<h1 class="pl-titre"><?php echo nf_texte($nom) ?></h1>
				<?php if ($devise !== '' && $devise !== $nom): ?>
				<p class="pl-devise"><?php echo nf_texte($devise) ?></p>
				<?php endif ?>
				<?php if ($appel || $suite): ?>
				<div class="pl-accueil-boutons">
					<?php echo $appel ?>
					<?php if ($suite): ?><a class="pl-bouton-creux" href="<?php echo $suite[0] ?>"><?php echo $suite[1] ?></a><?php endif ?>
				</div>
				<?php endif ?>
			</div>
		</div>
		<?php echo $this->output->region('mosaique') ?>
	</section>
	<?php endif ?>

	<?php if (!$accueil || $contenu): ?>
	<div class="pl-corps<?php if (!$cote) echo ' pl-corps-seul' ?>">
		<div class="pl-contenu"><?php echo $contenu ?></div>
		<?php if ($cote): ?>
		<aside class="pl-cote" data-nf-colle aria-label="<?php echo $this->lang('À côté') ?>"><?php echo $cote ?></aside>
		<?php endif ?>
	</div>
	<?php endif ?>
</main>

<footer class="pl-pied">
	<div class="pl-pied-dalle<?php if (!$reseaux) echo ' pl-pied-sans-reseaux' ?>">
		<div class="pl-pied-col">
			<p class="pl-pied-nom"><?php echo nf_texte($nom) ?></p>
			<?php if ($devise !== '' && $devise !== $nom): ?>
			<p class="pl-pied-devise"><?php echo nf_texte($devise) ?></p>
			<?php endif ?>
			<?php if ($zone = $this->output->region('footer')): ?>
			<div class="pl-pied-zone"><?php echo $zone ?></div>
			<?php endif ?>
		</div>
		<div class="pl-plan" aria-hidden="true"><i></i></div>
		<?php if ($reseaux): ?>
		<div class="pl-pied-col">
			<p class="pl-pied-titre"><?php echo $this->lang('Nous suivre') ?></p>
			<?php echo $reseaux ?>
		</div>
		<?php endif ?>
		<div class="pl-pied-barre">
			<div class="pl-copy">
				<?php echo $this->lang('Propulsé par') ?> <a href="https://neofrag-reborn.xyz" target="_blank" rel="noopener">NeoFrag Reborn</a>
				· © <?php echo date('Y') ?> <span class="pl-site-name"><?php echo nf_texte($nom) ?></span>
				· <?php echo nf_liens_legaux() ?>
			</div>
			<?php echo nf_selecteur_theme() ?>
			<?php if (count($this->config->langs) > 1): $cur = $this->config->lang->info(); ?>
			<form method="post" action="<?php echo url('ajax/settings/languages') ?>" class="pl-lang dropup">
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
