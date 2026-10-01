<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Articles\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Admin extends Controller
{
	/*
	 * Les réglages des widgets du Blog : le nombre de billets pour les listes, et pour
	 * tous l'affichage dans un panneau ou nu.
	 */

	public function index($settings = [])
	{
		return $this->view('admin', array_merge(['count' => 5, 'display_panel' => 'oui', 'nombre' => TRUE], $settings));
	}

	public function populaires($settings = [])
	{
		return $this->index($settings);
	}

	public function une($settings = [])
	{
		return $this->view('admin', array_merge(['count' => 5, 'display_panel' => 'oui', 'nombre' => FALSE], $settings));
	}

	public function categories($settings = [])
	{
		return $this->une($settings);
	}

	public function tags($settings = [])
	{
		return $this->une($settings);
	}

	public function archives($settings = [])
	{
		return $this->une($settings);
	}
}
