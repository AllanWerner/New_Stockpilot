export type StatutCommande = 'BROUILLON' | 'ENVOYEE' | 'RECUE_PARTIELLE' | 'RECUE';

export interface ProduitSousSeuil {
  idProduit: number;
  nom: string;
  quantiteActuelle: number;
  seuilReappro: number;
  quantiteRecommandee: number;
  prixAchat: string;
}

export interface LigneCommande {
  idLigneCommande: number;
  produit: { idProduit: number; nom: string };
  quantiteCommandee: number;
  quantiteRecue: number;
  prixUnitaire: string;
  sousTotal: string;
}

export interface CommandeResume {
  idCommande: number;
  statut: StatutCommande;
  dateCreation: string;
  datePrevue: string | null;
  fournisseur: { idFournisseur: number; nom: string };
  boutique: { idBoutique: number; nom: string };
}

export interface Commande extends CommandeResume {
  lignes: LigneCommande[];
}

export interface CreateCommandeLignePayload {
  idProduit: number;
  quantiteCommandee: number;
  prixUnitaire: string;
}

export interface CreateCommandePayload {
  idBoutique: number;
  idFournisseur: number;
  datePrevue?: string;
  lignes: CreateCommandeLignePayload[];
}

export interface ReceptionLignePayload {
  idLigneCommande: number;
  quantiteRecue: number;
}

export interface ReceptionCommandePayload {
  lignes: ReceptionLignePayload[];
}
