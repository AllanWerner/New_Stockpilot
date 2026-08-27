export interface Categorie {
  idCategorie: number;
  nom: string;
}

export interface CreateCategoriePayload {
  nom: string;
}

export interface StockInfo {
  quantiteActuelle: number;
  seuilReappro: number;
  quantiteCommandeReco: number;
  sousSeuil: boolean;
}

export interface Produit {
  idProduit: number;
  nom: string;
  codeBarre: string | null;
  description: string | null;
  prixAchat: string;
  unite: string;
  categorie: Categorie;
  sourceCompletion: 'AUTOMATIQUE' | 'MANUELLE' | null;
  stock?: StockInfo | null;
}

export interface ProduitListResponse {
  items: Produit[];
  page: number;
  limit: number;
}

export interface ProduitFilters {
  nom?: string;
  idCategorie?: number;
  idFournisseur?: number;
  idBoutique?: number;
  statutStock?: 'rupture' | 'critique' | 'ok';
  page?: number;
  limit?: number;
}

export interface CreateProduitPayload {
  nom: string;
  codeBarre?: string;
  description?: string;
  prixAchat: string;
  unite: string;
  idCategorie: number;
  idBoutique?: number;
  toutesBoutiques?: boolean;
  seuilReappro?: number;
  quantiteCommandeReco?: number;
}

export interface ScanProduitPayload {
  codeBarre: string;
  idBoutique?: number;
  seuilReappro?: number;
  quantiteCommandeReco?: number;
}

export interface AjustementStockPayload {
  idBoutique: number;
  quantite: number;
}
