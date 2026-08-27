export interface PointValorisation {
  date: string;
  valeur: string;
}

export interface ProduitSousSeuilCritique {
  idProduit: number;
  nom: string;
  idBoutique: number;
  nomBoutique: string;
  quantiteActuelle: number;
  seuilReappro: number;
  statut: 'rupture' | 'critique';
}

export interface Dashboard {
  valeurStock: string;
  referencesEnRupture: number;
  sousSeuilCritique: number;
  commandesEnCours: number;
  evolutionValorisation: PointValorisation[];
  produitsSousSeuilCritique: ProduitSousSeuilCritique[];
}
