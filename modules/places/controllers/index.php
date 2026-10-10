<?php
declare(strict_types=1);
namespace NF\Modules\Places\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Places\Lib\Place;

/**
 * La page publique : une carte, puis la liste des lieux.
 *
 * Rien n'est écrit en JavaScript INLINE — la politique de sécurité ne l'autorise pas. Les
 * coordonnées voyagent par attributs `data-` sur les éléments de la liste, que le script relit.
 * Conséquence heureuse : **sans JavaScript, la page reste utile** — la liste, les adresses et les
 * liens vers OpenStreetMap sont dans le HTML.
 */
class Index extends Controller_Module
{
	public function index($lieux, $cadrage)
	{
		$this	->title($this->lang('Carte des lieux'))
				->icon('fas fa-map-marked-alt')
				->breadcrumb();

		if (!$lieux)
		{
			return $this->panel()
						->title($this->lang('Carte des lieux'), 'fas fa-map-marked-alt')
						->body('<div class="alert alert-info text-center">'.$this->lang('Aucun lieu pour le moment.').'</div>');
		}

		$corps = '<div class="nf-places"'
			.' data-places-lat="'.Place::nombre((float) $cadrage['lat']).'"'
			.' data-places-lon="'.Place::nombre((float) $cadrage['lon']).'"'
			.' data-places-zoom="'.(int) $cadrage['zoom'].'"'
			// Les tuiles passent par le site (User\Controllers\Ajax::_tuile()) : OpenStreetMap ne voit pas les visiteurs.
			.' data-places-tuiles="'.nf_texte(url('ajax/user/tuile').'/{z}/{x}/{y}').'"'
			.' data-places-attribution="'.nf_texte($this->lang('Fond de carte : &copy; les contributeurs d\'OpenStreetMap')).'">';

		// Le conteneur de la carte est vide et MASQUÉ tant que le script ne l'a pas pris en charge :
		// un cadre gris vide serait pire que pas de carte du tout.
		$corps .= '<div class="nf-places-map" hidden></div>';
		$corps .= '<ul class="nf-places-list">';

		foreach ($lieux as $lieu)
		{
			$corps .= $this->_lieu($lieu);
		}

		$corps .= '</ul></div>';

		return $this	->css('leaflet')
						->css('places')
						->js('leaflet.min')
						->js('places')
						->panel()
						->title($this->lang('Carte des lieux'), 'fas fa-map-marked-alt')
						->body($corps);
	}

	protected function _lieu($lieu): string
	{
		$lat  = Place::nombre((float) $lieu['lat']);
		$lon  = Place::nombre((float) $lieu['lon']);
		$icone = trim((string) $lieu['cat_icon']) !== '' ? $lieu['cat_icon'] : 'fas fa-map-marker-alt';

		$html  = '<li class="nf-places-item"'
			.' data-place-lat="'.$lat.'"'
			.' data-place-lon="'.$lon.'"'
			.' data-place-title="'.nf_texte($lieu['title']).'"'
			.' data-place-icon="'.nf_texte($icone).'"'
			.' data-place-color="'.nf_texte(Place::couleur($lieu['cat_color'])).'">';

		$html .= '<h3 class="nf-places-title">'.icon($icone).' '.nf_texte($lieu['title']).'</h3>';
		$html .= '<div class="nf-places-meta"><span><i class="fas fa-folder"></i> '.nf_texte($lieu['cat_title']).'</span>';

		if (trim((string) $lieu['address']) !== '')
		{
			$html .= '<span><i class="fas fa-location-arrow"></i> '.nf_texte($lieu['address']).'</span>';
		}

		$html .= '</div>';

		if (trim((string) $lieu['description']) !== '')
		{
			$html .= '<p class="nf-places-desc">'.nl2br(nf_texte($lieu['description'])).'</p>';
		}

		$html .= '<div class="nf-places-links">';
		$html .= '<a href="'.nf_texte(Place::lien_osm((float) $lieu['lat'], (float) $lieu['lon'])).'" target="_blank" rel="noopener">'
			.'<i class="fas fa-map"></i> '.$this->lang('Voir sur OpenStreetMap').'</a>';

		// Le lien libre du lieu a été ramené à http/https à l'enregistrement ; on le revalide, une
		// ligne pouvant venir d'un import ou d'une base modifiée à la main.
		if (($lien = Place::lien_sur($lieu['link'])) !== '')
		{
			$html .= ' <a href="'.nf_texte($lien).'" target="_blank" rel="noopener nofollow">'
				.'<i class="fas fa-external-link-alt"></i> '.$this->lang('Site du lieu').'</a>';
		}

		return $html.'</div></li>';
	}
}
