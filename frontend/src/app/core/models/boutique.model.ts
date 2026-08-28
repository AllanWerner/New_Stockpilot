export interface Boutique {
  idBoutique: number;
  nom: string;
  adresse: string;
  ville: string;
  actif: boolean;
}

export interface CreateBoutiquePayload {
  nom: string;
  adresse: string;
  ville: string;
}
