<?php
/**
 * Effet saisonnier : un seul canvas, piloté par `js/seasonal.js`.
 *
 * Les paramètres passent par des attributs `data-`, jamais par un `<script>` en ligne : la politique
 * de sécurité du site est stricte et n'autorise pas le script inline. C'est aussi ce qui permet au
 * même fichier JS de servir plusieurs configurations sans être régénéré.
 *
 * `aria-hidden` et `role="presentation"` : c'est une décoration, elle n'a rien à dire à un lecteur
 * d'écran. `pointer-events: none` (dans la feuille) garantit qu'elle n'intercepte aucun clic.
 */
?>
<canvas class="nf-seasonal" role="presentation" aria-hidden="true"
        data-seasonal-effect="<?php echo nf_texte($effet) ?>"
        data-seasonal-count="<?php echo (int) $particules ?>"></canvas>
