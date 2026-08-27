export interface Boutique {
  idBoutique: number;
  nom: string;
  adresse: string;
  ville: string;
}

export interface CreateBoutiquePayload {
  nom: string;
  adresse: string;
  ville: string;
}
