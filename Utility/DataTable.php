<?php

namespace Gosyl\DataTableBundle\Utility;

use Doctrine\ORM\QueryBuilder;

class DataTable {

	/**
	 * @var QueryBuilder
	 */
	protected $oSelect;

	/**
	 * @var array
	 */
	protected $aParams;

	/**
	 * @var array
	 */
	protected $aChamps;

	/**
	 * @var int $nChamps
	 */
	protected $nChamps;
	
	// Publics
	
	/* -- Getters -- */
	public function getSelect() {
		return $this->oSelect;
	}

	public function getParams() {
		return $this->aParams;
	}

	public function getaChamps() {
		return $this->aChamps;
	}

	public function getnChamps() {
		return $this->nChamps;
	}

	/* -- Setters -- */
	public function setSelect(QueryBuilder $oSelect) {
		$this->oSelect = $oSelect;
		return $this;
	}

	public function setParams($aParams) {
		$this->aParams = $aParams;
		return $this;
	}

	public function setaChamps($aChamps) {
		$this->aChamps = $aChamps;
		return $this;
	}

	public function setnChamps($nChamps) {
		$this->nChamps = $nChamps;
		return $this;
	}

	public function getExtraParams(QueryBuilder $oSelect, $aParams = array(), $aChamps = array(), $bNoLimit = false) {
		// Initialisation des variables
		$this->setParams($aParams);
		$this->setSelect($oSelect);
		$this->setaChamps($aChamps);
		$this->setnChamps(count($aChamps));
		
		// Application des méthodes d'altération de la requête originale
		if($bNoLimit) {
			$this->getLimit();
		}
		
		$this->getOrder();
		$this->getSearch();
		
		return $this->getSelect();
	}

	public function getLimit() {
		$aParams = $this->getParams();
		if(isset($aParams['start']) && $aParams['length'] != - 1) {
			$this->getSelect()->setFirstResult($aParams['start'])->setMaxResults($aParams['length']);
		}
	}

	public function getOrder() {
		$aParams = $this->getParams();
		$aChamps = $this->getaChamps();
		
		if(isset($aParams['order']) && is_array($aParams['order'])) {
			for($i = 0; $i < count($aParams['order']); $i++) {
				// On récupère le nom de la colonne
				$sColonne = $aChamps[$aParams['order'][$i]['column']];
				if($i == 0) {
					$this->getSelect()->orderBy($sColonne, $aParams['order'][$i]['dir']);
				} else {
					$this->getSelect()->addOrderBy($sColonne, $aParams['order'][$i]['dir']);
				}
			}
		}
	}

	public function getSearch() {
		// Initialisation du marqueur de recherche sur plusieurs colonnes. (multi-critères)
		$bMultiCrit = false;
		$aSearchMulti = array();
		$aParams = $this->getParams();
		$aChamps = $this->getaChamps();
		
		if(! is_array($this->getaChamps()) || count($this->getaChamps()) <= 0) {
			throw new \Exception('Erreur : Les champs de la recherche ne sont pas spécifié dans la requete de DataTable.');
		} elseif(isset($aParams['search']['value']) && $aParams['search']['value'] != '') {
			// Cas d'une recherche multi-critères, on explose la recherche en tableau
			if(preg_match('/\+/', $aParams['search']['value'])) {
				$aSearchMulti = explode('+', $aParams['search']['value']);
				$bMultiCrit = true;
				
				// Filtre de la recherche
				$aSearchMulti = array_filter($aSearchMulti);
			}
			
			for($i = 0; $i < $this->getnChamps(); $i++) {
				if(isset($aChamps[$i]) && is_string($aChamps[$i]) && ! empty($aChamps[$i])) {
					if($bMultiCrit) {
						foreach($aSearchMulti as $xValue) {
							$sValSearch = trim($xValue);
							if($i == 0) {
								$this->getSelect()->where($this->getSelect()->expr()->like($aChamps[$i], $this->getSelect()->expr()->literal('%' . $sValSearch . '%')));
							} else {
								$this->getSelect()->orWhere($this->getSelect()->expr()->like($aChamps[$i], $this->getSelect()->expr()->literal('%' . $sValSearch . '%')));
							}
						}
					} else {
						$sValSearch = trim($aParams['search']['value']);
						if($i == 0) {
							$this->getSelect()->where($this->getSelect()->expr()->like($aChamps[$i], $this->getSelect()->expr()->literal('%' . $sValSearch . '%')));
						} else {
							$this->getSelect()->orWhere($this->getSelect()->expr()->like($aChamps[$i], $this->getSelect()->expr()->literal('%' . $sValSearch . '%')));
						}
					}
				}
			}
		}
	}
}