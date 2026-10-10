<?php
/*
 * Blockcraft 2.0.0 (chantier B ; direction D « Spawn + Inventaire », validée le 2026-10-07) : le site d'un serveur de
 * jeu de blocs.
 *
 *   ┌────────────────────────────────────────────────────────────┐   Le ciel : le soleil (la lune et les étoiles la
 *   │ ▣ Le nom    [▣][▣][▣][▣][▣][▣][▣]   la barre d'objets  ☾ ▣ │   nuit), des nuages qui passent ; la marque, la
 *   │                                                            │   barre d'objets (zone « Barre d'objets » : chaque
 *   │                     LE NOM DU SERVEUR                      │   rubrique dans sa case), le compte. Sur l'accueil,
 *   │                  sa devise · [adresse][Copier]             │   le nom du serveur en grand, sa devise, son adresse
 *   │▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓ le sol : collines, arbres ▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓│   à copier et le Discord ; ailleurs, un bandeau
 *   ├──────────────────────────────────────┬─────────────────────┤   plus bas, au titre de la page. Puis le contenu
 *   │ ▣ le contenu (les nouvelles…)        │ ▣ la colonne        │   et la colonne, des blocs à coins carrés ; sur
 *   ├──────────────────────────────────────┴─────────────────────┤   l'accueil, une bande pleine largeur (les
 *   │ ▣ pleine largeur (les dernières constructions)             │   dernières constructions). Le pied en roche.
 *   ▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓ le pied en roche ▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓
 *
 * Au téléphone : une colonne, et la barre d'objets collée en bas de l'écran. Les gabarits des modules restent ceux de
 * tous les thèmes (décision du chantier B) : Blockcraft les habille par sa feuille, sans en réécrire un seul.
 */
$logo = '';

foreach ([(int) $this->config->blockcraft_logo, (int) $this->config->nf_team_logo] as $fichier)
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
$adresse = trim(html_entity_decode((string) $this->config->blockcraft_adresse, ENT_QUOTES, 'UTF-8'));
$discord = trim((string) $this->config->nf_social_discord);

// L'appel du ciel, pour un visiteur, quand le serveur n'a ni adresse ni Discord : l'inscription, si elle est ouverte.
$inscription = !$this->user->id && $this->config->nf_registration_status;

// Le titre de la page, dans le bandeau : celui que le module s'est donné (déjà codé pour le HTML, comme dans
// l'administration).
$titre = $accueil ? '' : trim(strip_tags((string) $this->output->data->get('module', 'title')));

$navigation = $this->output->region('navigation');
$contenu    = $this->output->region('content');
$colonne    = $this->output->region('colonne');
$largeur    = $this->output->region('largeur');
$reseaux    = trim((string) $this->view('socials'));
?>
<a class="bc-evitement" href="#bc-contenu"><?php echo $this->lang('Aller au contenu') ?></a>
<header class="bc-ciel<?php echo $accueil ? ' bc-ciel-accueil' : ' bc-bandeau' ?>">
	<?php /* Le décor a sa propre couche, qui coupe ce qui déborde (les nuages traversent l'écran) : couper le ciel
	         entier aurait coupé aussi le menu du compte, qui s'ouvre sous la barre. */ ?>
	<div class="bc-decor" aria-hidden="true">
		<div class="bc-etoiles"></div>
		<div class="bc-astre"></div>
		<div class="bc-nuage bc-n1"></div>
		<div class="bc-nuage bc-n2"></div>
		<div class="bc-nuage bc-n3"></div>
		<div class="bc-sol"></div>
	</div>
	<div class="bc-barre">
		<a href="<?php echo url() ?>" class="bc-marque">
			<?php if ($logo): ?>
			<img class="bc-logo" src="<?php echo $logo ?>" alt="" />
			<?php else: ?>
			<i class="bc-bloc-marque" aria-hidden="true"></i>
			<?php endif ?>
			<span class="bc-nom"><?php echo nf_texte($nom) ?></span>
		</a>
		<?php if ($navigation): ?>
		<nav class="bc-objets" aria-label="<?php echo $this->lang('Rubriques') ?>"><?php echo $navigation ?></nav>
		<?php endif ?>
		<div class="bc-droite"><?php echo $this->view('compte') ?></div>
	</div>
	<?php if ($accueil): ?>
	<div class="bc-heros">
		<h1 class="bc-titre"><?php echo nf_texte($nom) ?></h1>
		<?php if ($devise !== '' && $devise !== $nom): ?>
		<p class="bc-devise"><?php echo nf_texte($devise) ?></p>
		<?php endif ?>
		<?php if ($adresse !== ''): ?>
		<div class="bc-adresse">
			<code><?php echo nf_texte($adresse) ?></code>
			<button type="button" class="bc-bouton bc-copier" data-adresse="<?php echo nf_texte($adresse) ?>" data-copie="<?php echo $this->lang('Adresse copiée !') ?>"><?php echo $this->lang('Copier l’adresse') ?></button>
		</div>
		<?php endif ?>
		<?php if ($discord !== '' || ($adresse === '' && $inscription)): ?>
		<div class="bc-heros-boutons">
			<?php if ($discord !== ''): ?>
			<a class="bc-bouton bc-bouton-clair" href="<?php echo nf_texte($discord) ?>" target="_blank" rel="noopener"><?php echo icon('fab fa-discord') ?> <?php echo $this->lang('Rejoindre le Discord') ?></a>
			<?php endif ?>
			<?php if ($adresse === '' && $inscription): ?>
			<a class="bc-bouton" href="<?php echo url('user/registration') ?>" data-modal-ajax="<?php echo url('ajax/user/register') ?>"><?php echo $this->lang('Créer un compte') ?></a>
			<?php endif ?>
		</div>
		<?php endif ?>
		<p class="bc-annonce" role="status" aria-live="polite"></p>
	</div>
	<?php elseif ($titre !== ''): ?>
	<p class="bc-page-titre"><?php echo $titre ?></p>
	<?php endif ?>
</header>

<main class="bc-page" id="bc-contenu" tabindex="-1">
	<?php if ($contenu || $colonne): ?>
	<div class="bc-corps<?php if (!$colonne) echo ' bc-corps-seul' ?>">
		<div class="bc-contenu"><?php echo $contenu ?></div>
		<?php if ($colonne): ?>
		<aside class="bc-colonne" aria-label="<?php echo $this->lang('Colonne') ?>"><?php echo $colonne ?></aside>
		<?php endif ?>
	</div>
	<?php endif ?>
	<?php if ($largeur): ?>
	<section class="bc-largeur" aria-label="<?php echo $this->lang('Pleine largeur') ?>"><?php echo $largeur ?></section>
	<?php endif ?>
</main>

<footer class="bc-pied">
	<div class="bc-pied-voile">
		<div class="bc-pied-haut">
			<div class="bc-pied-col">
				<p class="bc-pied-nom"><?php echo nf_texte($nom) ?></p>
				<?php if ($devise !== '' && $devise !== $nom): ?>
				<p class="bc-pied-devise"><?php echo nf_texte($devise) ?></p>
				<?php endif ?>
			</div>
			<?php if ($zone = $this->output->region('footer')): ?>
			<div class="bc-pied-zone"><?php echo $zone ?></div>
			<?php endif ?>
			<?php if ($reseaux): ?>
			<div class="bc-pied-col">
				<p class="bc-pied-titre"><?php echo $this->lang('Nous suivre') ?></p>
				<?php echo $reseaux ?>
			</div>
			<?php endif ?>
		</div>
		<div class="bc-pied-barre">
			<div class="bc-copy">
				<?php echo $this->lang('Propulsé par') ?> <a href="https://neofrag-reborn.xyz" target="_blank" rel="noopener">NeoFrag Reborn</a>
				· © <?php echo date('Y') ?> <span class="bc-site-name"><?php echo nf_texte($nom) ?></span>
				· <?php echo nf_liens_legaux() ?>
			</div>
			<?php echo nf_selecteur_theme() ?>
			<?php if (count($this->config->langs) > 1): $cur = $this->config->lang->info(); ?>
			<form method="post" action="<?php echo url('ajax/settings/languages') ?>" class="bc-lang dropup">
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
