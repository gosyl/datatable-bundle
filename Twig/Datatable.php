<?php
namespace Gosyl\DataTableBundle\Twig;

/**
 * @package Gosyl
 * @subpackage DataTableBundle
 * @author alexandre.lippmann
 * @version 1.0
 * 
 * Helper de vue pour la génération de dataTable
 * 
 * Le paramètre aData doit contenir un tableau qui comporte 3 sous tableaux de la forme :
 * $aData = array(
 *		'cols' => array(), ---> tableau qui définie les colonnes
 *		'option' => array(), ---> Options du dataTable les options se trouvrent dans Psr2_Datatable_Option
 *		'results' => array(), ---> Tableau contenant les données à afficher
 *		);
 *
 * Les colonnes sont définies de la façon suivante :
 * $aCols = array(
 * 		'id' => array(
 * 				'title' => 'Identifiant',
 * 				'data' => 'id',
 * 				'visible' => false //par défaut true
 * 				),
 * 		'libelle' => array(
 * 				'title' => 'Libelle'
 * 				'data' => 'libelle'
 * 				),
 * 		// Ajout d'une colonne action
 * 		'actions' => array(
 * 				'title' => 'Actions'
 * 				'data' => null,
 * 				'render' => array(
 * 						//langage javascript
 * 						//par exemple ajout d'un lien
 * 						'render' => "
 * 									function(data, type, row, meta) {
 * 										return '<a href=\"/consulter/index/id/' + row.id + '\">' + row.libelle + '</a>';
 * 									}
 * 									";
 * 						),
 * 				
 * 				),
 * 		);
 *
 * Les résultats doivent sous la forme :
 * $aResults = array(
 * 		array('id' => 1, 'libelle' => 'CDTUE'),
 * 		array('id' => 2, 'libelle' => 'EXEUE'),
 * 		//....
 * 		);
 * 
 * Pour agir sur toute une rangée du tableau, rajouter aux options la clé 'createdRow'.
 * Par exemple pour appliquer une classe css :
 * 
 * // On lui rajoute la clé createdRow
 * $aTabOptions['createdRow'] = array(
 * 		'createdRow' => "
 * 			function(row, data, dataIndex) {
 * 				$(row).addClass('rouge');
 * 			}
 * 			",
 * 		);
 */
class Datatable extends \Twig_Extension {
	/**
	 * Tableau contenant les paramètres des colonnes du dataTable
	 * @var array
	 */
	private $aCols = array();
	
	/**
	 * Tableau contenant les options pour le dataTable
	 * @var array
	 */
	private $aTableOptions = array();
	
	/**
	 * Active ou non le plugin Colvis
	 * @var boolean
	 */
	private $bColVis = false;
	
	/**
	 * Active ou non le header du tableau fixe lors du scroll
	 * @var boolean
	 */
	private $bFixedHeader = false;
	
	/**
	 * Résultat d'une requete Ajax ou non
	 * @var boolean
	 */
	private $bResultatAjax = false;
	
	/**
	 * Résultat vide ou non
	 * @var boolean
	 */
	private $bResultatVide = false;
	
	/**
	 * Active ou non le plugin d'export
	 * @var boolean
	 */
	private $bButton = false;
	
	/**
	 * Colonne de référence en cas de modification
	 * @var integer
	 */
	private $nColRef = 1;
	
	/**
	 * Chaine contenant le javascript d'initialisation du dataTable
	 * @var string
	 */
	private $sJsDataTable = '';
	
	/**
	 * Nom de l'objet javascript contenant les datas en Json
	 * @var string
	 */
	private $sNameDatas;
	
	/**
	 * Nom de l'objet javascript pour éviter les doublons
	 * @var string
	 */
	private $sNameTable;
	
	/**
	 * Chaine de retour contenant le dataTable en html
	 * @var string
	 */
	private $sTable = '';
	
	/**
	 * Variable contenant soit une chaine au format Json ou un tableau de résultats
	 * @var mixed
	 */
	private $xResultat;
	
	protected function _ajouteColonne() {
		$sColumns = '"columns": [';
		
		$bFirst = true;
		
		foreach ($this->aCols as $aValue) {
		    $nbCols = count($this->aCols);
			$bFirst2 = true;
			
			if($bFirst) {
				$bFirst = false;
			} else {
				$sColumns .= ', ';
			}
			
			$sColumns .= '{';

            $aKeys = array_keys($aValue);

			foreach ($aValue as $sKey => $xValue) {
				if($bFirst2) {
					$bFirst2 = false;
				} else {
					$sColumns .= ', ';
				}
				
				if(is_string($xValue) && $xValue != 'null') {
					if($sKey == 'render') {
						$sColumns .= '"' . $sKey . '": ' . $xValue . '';
					} else {
						$sColumns .= '"' . $sKey . '": "' . $xValue . '"';
					}
				} elseif(is_string($xValue) && $xValue == 'null' && $sKey == 'data') {
					$sColumns .= '"' . $sKey . '": ' . $xValue;
				} elseif(is_bool($xValue)) {
					if($xValue) {
						$sColumns .= '"' . $sKey . '": true';
					} else {
						$sColumns .= '"' . $sKey . '": false';
					}
				} elseif(is_int($xValue)) {
					$sColumns .= '"' . $sKey . '": ' . $xValue;
				} elseif(is_array($xValue)) { // le type tableau permet d'ajouter une colonne action (édition, suppression)
					foreach($xValue as $sSubKey => $sValue) {
						$sColumns .= '"' . $sSubKey . '": ' . $sValue;
					}
				}


			}

			if(!in_array('width', $aKeys)) {
                $sColumns .= ', "width": "' . (100 / $nbCols) . '%"';
            }
			$sColumns .= '}';
		}
		
		$sColumns .= ']';
		return $sColumns;
	}
	
	protected function _ajouteJs() {
		$sJs = '';
		// Inclusion des extensions
		if($this->bColVis) {
			$sJs .= '<script src="/bundles/gosyldatatable/js/library/Datatables/ColVis/js/dataTables.colVis.js"></script>';
			$sJs .= '<link rel="stylesheet" href="/bundles/gosyldatatable/js/library/Datatables/ColVis/css/dataTables.colvis.jqueryui.css" />';
		}
		
		if($this->bFixedHeader) {
			$sJs .= '<script src="/bundles/gosyldatatable/js/library/Datatables/FixedHeader-3.1.1/js/dataTables.fixedHeader.js"></script>';
			$sJs .= '<link rel="stylesheet" href="/bundles/gosyldatatable/js/library/Datatables/FixedHeader-3.1.1/css/fixedHeader.jqueryui.min.css" />';
		}
		
		if($this->bButton) {
			$sJs .= '<script src="/bundles/gosyldatatable/js/library/Datatables/Buttons-1.1.2/js/dataTables.buttons.min.js"></script>';
			$sJs .= '<script src="/bundles/gosyldatatable/js/library/Datatables/Buttons-1.1.2/js/jszip.min.js"></script>';
			$sJs .= '<script src="/bundles/gosyldatatable/js/library/Datatables/Buttons-1.1.2/js/pdfmake.min.js"></script>';
			$sJs .= '<script src="/bundles/gosyldatatable/js/library/Datatables/Buttons-1.1.2/js/vfs_fonts.js"></script>';
			$sJs .= '<script src="/bundles/gosyldatatable/js/library/Datatables/Buttons-1.1.2/js/buttons.html5.min.js"></script>';
			$sJs .= '<link rel="stylesheet" href="/bundles/gosyldatatable/js/library/Datatables/Buttons-1.1.2/css/buttons.dataTables.min.css" />';
			$sJs .= '<link rel="stylesheet" href="/bundles/gosyldatatable/js/library/Datatables/Buttons-1.1.2/css/buttons.jqueryui.min.css" />';
		}
		
		$sJs .= '<script type="text/javascript">//<!--
					$(document).ready(function() {
						var ' . $this->sNameTable . ' = $("#' . $this->sNameTable . '").dataTable({
							' . $this->_getOptions() . ',
							"lengthMenu": [[5 ,10, 25, 50, -1], [5, 10, 25, 50, "All"]],';
							if(count($this->aCols) > 15) {
								$sJs .= '
										"scrollX": "100%",
										"scrollY": "520",
										"scrollCollapse": true,
										';
							}
							
							if(!is_null($this->aCols) && !$this->bResultatAjax) {
								$sJs .= '
										"data": ' . $this->xResultat . ', 
										' . $this->_ajouteColonne() . ',
										';
							}
							
							if($this->bResultatAjax) {
								$sJs .= '
										"processing": true,
										"serverSide": true,
										"ajax": {
											"type": "POST",
											"url": "' . $this->xResultat . '",
										},
										' . $this->_ajouteColonne() . ',
										';
							}
							
							//Gestion des plugins
							if($this->bColVis) {
								$sJs .= '
										"colVis": {
											"buttonText": "Ajouter/Supprimer des colonnes",
											"align": "left",
											"restore": true
										},
										';
							}
							
							if($this->bButton) {
								$sJs .= '
										dom: \'<"H"RClf>tB<"F"rpi>\',
										buttons: [
											{
												extend: \'copy\',
												text: "Copier"
											},
											"csvHtml5",
											"pdfHtml5"
										]
										';
							}
							
						$sJs .= '});
						';
						
						// Initialisation des plugins
						if($this->bColVis) {
							$sJs .= '
									var colvis' . $this->sNameTable . ' = new $.fn.dataTable.ColVis(' . $this->sNameTable . ');
									$(colvis'.$this->sNameTable . '.button()).insertAfter("div.info");
									';
						}
						
						//$sJs .= '//var colReorder'. $this->sNameTable . ' = new $.fn.dataTable.ColReorder(' . $this->sNameTable . ');';
						
						if($this->bFixedHeader && count($this->aCols) <= 15) {
							$sJs .= '
								new $.fn.dataTable.FixedHeader('.$this->sNameTable.');';
						}
					$sJs .= '});
				//--></script>';
		
		return $sJs;
	}
	
	protected function _ajouteResultats() {
		$xResultats = $this->xResultat;
		
		$sReturn = '<script type="text/javascript">
						$(document).ready(function() {
							var '.$this->sNameDatas.' = "'.$xResultats.'";
						});
					</script>';
		
		return $sReturn;
	}
	
	protected function _createBody() {
		$xResultats = $this->xResultat;
	
		$sTable = '<tbody>';
	
		if(is_array($xResultats)) {
			foreach ($xResultats as $aValue) {
				$sTable .= '<tr>';
				foreach ($aValue as $sValue) {
					$sTable .= '<td>' . $sValue . '</td>';
				}
				$sTable .= '</tr>';
			}
		} elseif($this->bResultatAjax) {
			$sTable .= '<tr><td>Chargement des données en cours...</td></tr>';
		} else {
			$sTable .= '<tr><td>' . $xResultats . '</td></tr>';
		}
	
		$sTable .= '</tbody>';
	
		return $sTable;
	}
	
	protected function _creerTFoot() {
		$sTable = '<tfoot></tfoot>';
		
		return $sTable;
	}
	
	protected function _creerTHead() {
		$xResultats = $this->xResultat;
		
		$sTable = '<thead>';
		
		if(is_array($xResultats)) {
			$sTable .= '<tr>';
			foreach (array_keys($xResultats) as $sKey) {
				$sTable .= '<th>' . $sKey . '</th>';
			}
			$sTable .= '</tr>';
		} else {
			$sTable .= '<tr><th>-</th></tr>';
		}
		
		$sTable .= '</thead>';
		
		return $sTable;
	}
	
	protected function _getOptions($aTabOptions = null) {
		$sOptions = '';
		$bFirst = true;
		
		if(is_null($aTabOptions)) {
			$aTabOptions = $this->aTableOptions;
		}
		
		foreach ($aTabOptions as $sKey => $xValue) {
			if($bFirst) {
				$bFirst = false;
			} else {
				$sOptions .= ', ';
			}
			
			if(is_string($xValue)) {
				$sOptions .= '"' . $sKey . '": "' . addslashes($xValue) . '"';
			} elseif(is_int($xValue)) {
				$sOptions .= '"' . $sKey . '": ' . $xValue;
			} elseif(is_bool($xValue)) {
				if($xValue) {
					$sOptions .= '"' . $sKey . '": true';
				} else {
					$sOptions .= '"' . $sKey . '": false';
				}
			} elseif (is_array($xValue) && !array_key_exists('function', $xValue)) {
				$sOptions .= '"' . $sKey . '": { ' . $this->_getOptions($xValue) . ' }';
			} elseif(is_array($xValue) && (array_key_exists('function', $xValue) || array_key_exists('render', $xValue))) {
				$sOptions .= '"' .$sKey  . '": ';
				$sOptions .= $xValue[array_keys($xValue)[0]];
			}
		}
		
		return $sOptions;
	}
	
	protected function _setData($aData) {
		if(isset($aData['results']) && is_array($aData['results']) && count($aData['results']) > 0) { // Si les données sont présentes sous forme d'un tableau
			$this->xResultat = json_encode($aData['results']['data']);
		} elseif(!isset($aData['cols']) && is_array($aData) && count($aData) > 0) {
			$this->xResultat = $aData;
		} elseif(is_string($aData['results']) && !empty($aData['results']) && !is_null($aData['results'])) {
			$this->xResultat = $aData['results'];
			$this->bResultatAjax = true;
		} else {
			$this->xResultat = 'Aucun résultat.';
			$this->bResultatVide = true;
		}
		
		if(is_array($aData) && isset($aData['cols'])) {
			$this->aCols = $aData['cols'];
		}
		
		if(isset($aData['options']) && is_array($aData['options'])) {
			$this->aTableOptions = $aData['options'];
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see Twig_Extension::getFunctions()
	 */
	public function getFunctions() {
		return array(
				new \Twig_SimpleFunction('datatable', array($this, 'datatableFunction'), array('is_safe' => array('html')))
		);
	}

    /**
     * @param array $aData Données du dataTable our url des données à télécharger en Ajax
     * @param bool|string $bColVis Activation de l'extension Colvis
     * @param bool|string $bButton Activation de l'extension permettant l'exportation
     * @param bool|string $bFixedHeader Activation de l'extension FixedHeader
     * @param string $sClassTable Class css à appliquer au tableau
     * @param int|number $nColReference Colonne de référence
     * @return string
     */
	public function datatableFunction($aData = array(), $sClassTable = 'table table-striped table-bordered dataTable no-footer dt-responsive', $bColVis = false, $bButton = false, $bFixedHeader = false, $nColReference = 0) {
		$this->nColRef = $nColReference;
		$this->bColVis = $bColVis;
		$this->bButton = $bButton;
		$this->bFixedHeader = $bFixedHeader;
		
		/**
		 * Génération d'un id aléatoire pour le dataTable et pour les données Json
		 */
		$this->sNameTable = 'oTable' . rand(0, 10000);
		$this->sNameDatas = 'jsonData' . rand(0, 10000);
		
		/**
		 * Récupération et mise en forme des données
		 */
		$this->_setData($aData);
		$sTable = $this->_ajouteJS();
		
		/**
		 * Début de création du tableau
		 */
		if(!is_null($this->xResultat) && is_string($this->xResultat) && !$this->bResultatVide) {
			$sTable .= $this->_ajouteResultats();
			$sTable .= '<table id="'.$this->sNameTable.'" class="'.$sClassTable.'">';
		} elseif(is_array($this->xResultat) && count($this->xResultat > 0)) {
			$sTable .= '<table id="'.$this->sNameTable.'" class="'.$sClassTable.'">';
			$sTable .= $this->_creerTHead();
			$sTable .= $this->_createBody();
			$sTable .= $this->_creerTFoot();
		} elseif(!$this->bResultatAjax) {
			$sTable = '<table class="table center thlarge">';
			$sTable .= $this->_createBody();
			$sTable .= $this->_creerTFoot();
		}
		
		$sTable .= '</table>';
		
		
		
		return $sTable;
	}
	
	public function getName() {
		return 'twig.extension.datatable';
	}
}