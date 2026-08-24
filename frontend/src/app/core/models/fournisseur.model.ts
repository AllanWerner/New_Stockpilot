export interface Fournisseur {
  idFournisseur: number;
  nom: string;
  emailContact: string | null;
  telephone: string | null;
  delaiLivraisonJours: number | null;
}

export interface CreateFournisseurPayload {
  nom: string;
  emailContact?: string;
  telephone?: string;
  delaiLivraisonJours?: number;
}
