<?php
namespace Gosyl\DataTableBundle\Utility;

/**
 * @package Gosyl
 * @subpackage DataTableBundle
 * @author alexandre.lippmann
 * @version 1.0
 * 
 * Classe contenant les différentes constantes du bundle DataTable *
 */
class Constantes {
	
	/**
	 * Options pour appliquer la langue française dans les différents dataTables
	 * 
	 * @var array $aDataTableLanguage
	 */
	public static $aDataTableLanguage = array(
		'language' => array(
				'processing' => 'Traitement des données...',
				'lengthMenu' => 'Afficher _MENU_ résultats',
				'zeroRecords' => 'Aucun résultat à afficher',
				"emptyTable" =>	 "Pas de résultat pour les critères demandés.",
				"info" =>         "Affichage des résultats _START_ à _END_ sur _TOTAL_ éléments",
				"infoEmpty" =>  "Pas de résultat pour les critères demandés.",
				"infoFiltered" => "(filtré de _MAX_ résultats au total)",
				"infoPostFix" => "",
				"search" => "Rechercher :",
				"url" => "",
				"paginate" => array(
					"first"=>  "Début",
					"previous" => "Précédent",
					"next" => "Suivant",
					"last" => "Fin"
				),
		),
	);
	
	public static $base = array(
	    "jQueryUI" => true,
	    "sort" => true,
	    'info' => false,
	    'filter' => false,
	    'searching' => false,
	    "paging" => false,
	    "autoWith" => true,
	    "stateSave" => true,
	    "retrieve" => true,
	    "pageLength" => 10,
	    "pagingType" => "full_numbers",
	    "dom" => "<\"H\"RCTlf>t<\"F\"rpi>",
	    "processing" => false,
	    "serverSide" => false
	);
	
	/**
	 * options simple pour un dataTable
	 */
	public static $simple = array(
	    "jQueryUI" => true,
	    "sort" => true,
	    'info' => true,
	    'filter' => false,
	    'searching' => true,
	    "paging" => true,
	    "autoWith" => true,
	    "stateSave" => true,
	    "retrieve" => true,
	    "pageLength" => 25,
	    "pagingType" => "full_numbers",
	    "processing" => false,
	    "serverSide" => false
	);
	
	/**
	 * renvoi le tableau d'options associé à $sName
	 * @param string $sName
	 * @return array
	 */
	public static function getOptions($sName) {
	    if (isset(self::${$sName})) {
	        return self::${$sName};
	    }
	    return array();
	}
}