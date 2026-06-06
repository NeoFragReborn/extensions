<?php
/**
 * https://neofr.ag
 * Flux RSS — page d'index + flux news/articles (RSS 2.0).
 */

namespace NF\Modules\Feeds\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	const LIMIT = 20;

	public function index()
	{
		return $this->panel()
					->heading($this->lang('Flux RSS'), 'fas fa-rss')
					->body(
						'<p>'.$this->lang('Abonne-toi au contenu du site dans ton lecteur de flux :').'</p>'
						.'<ul class="list-unstyled">'
						.'<li class="mb-2"><a href="'.url('feeds/news').'">'.icon('fas fa-rss').' '.$this->lang('Actualités').'</a></li>'
						.'<li><a href="'.url('feeds/articles').'">'.icon('fas fa-rss').' '.$this->lang('Articles').'</a></li>'
						.'</ul>'
					);
	}

	public function _news()
	{
		$lang = $this->config->lang->info()->name;

		$rows = $this->db	->select('n.news_id AS id', 'n.date', 'n.deleted_at', 'l.title', 'l.introduction AS summary')
							->from('nf_news n')
							->join('nf_news_lang l', 'l.news_id = n.news_id')
							->where('l.lang', $lang)
							->where('n.published', '1')
							->order_by('n.date DESC')
							->limit(40)
							->get(FALSE);

		$this->_emit($this->lang('Actualités'), 'feeds/news', $this->_items($rows, 'news'));
	}

	public function _articles()
	{
		$lang = $this->config->lang->info()->name;

		$rows = $this->db	->select('a.article_id AS id', 'a.date', 'a.deleted_at', 'l.title', 'l.excerpt AS summary')
							->from('nf_articles a')
							->join('nf_articles_lang l', 'l.article_id = a.article_id')
							->where('l.lang', $lang)
							->where('a.published', '1')
							->order_by('a.date DESC')
							->limit(40)
							->get(FALSE);

		$this->_emit($this->lang('Articles'), 'feeds/articles', $this->_items($rows, 'articles'));
	}

	/** Filtre (non supprimé, non programmé futur) + construit les items du flux. */
	private function _items($rows, $module)
	{
		$now   = time();
		$items = [];

		foreach ($rows as $r)
		{
			if (!empty($r['deleted_at']))
			{
				continue;
			}

			$ts = strtotime((string)$r['date']);

			if (!$ts || $ts > $now)
			{
				continue;
			}

			$items[] = [
				'title'   => $r['title'],
				'url'     => $module.'/'.(int)$r['id'].'/'.url_title($r['title']),
				'ts'      => $ts,
				'summary' => (string)$r['summary'],
			];

			if (count($items) >= self::LIMIT)
			{
				break;
			}
		}

		return $items;
	}

	private function _emit($channel_title, $self_path, $items)
	{
		$base = ($this->url->https ? 'https' : 'http').'://'.$this->url->host.$this->url->base;

		header('Content-Type: application/rss+xml; charset=UTF-8');

		$xml  = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
		$xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel>';
		$xml .= '<title>'.$this->_x($this->config->nf_name.' — '.$channel_title).'</title>';
		$xml .= '<link>'.$this->_x($base).'</link>';
		$xml .= '<description>'.$this->_x($channel_title.' — '.$this->config->nf_name).'</description>';
		$xml .= '<language>'.$this->_x($this->config->lang->info()->name).'</language>';
		$xml .= '<atom:link href="'.$this->_x($base.$self_path).'" rel="self" type="application/rss+xml" />';

		foreach ($items as $it)
		{
			$link = $base.$it['url'];
			$xml .= '<item>';
			$xml .= '<title>'.$this->_x($it['title']).'</title>';
			$xml .= '<link>'.$this->_x($link).'</link>';
			$xml .= '<guid isPermaLink="true">'.$this->_x($link).'</guid>';
			$xml .= '<pubDate>'.date('r', $it['ts']).'</pubDate>';
			$xml .= '<description><![CDATA['.str_replace(']]>', ']]]]><![CDATA[>', $it['summary']).']]></description>';
			$xml .= '</item>';
		}

		$xml .= '</channel></rss>';

		echo $xml;
		exit;
	}

	private function _x($s)
	{
		return htmlspecialchars((string)$s, ENT_QUOTES | ENT_XML1, 'UTF-8');
	}
}
