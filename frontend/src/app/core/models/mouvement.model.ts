export interface Mouvement {
  idMouvement: number;
  type: 'RECEPTION' | 'TRANSFERT' | 'AJUSTEMENT';
  quantite: number;
  dateMouvement: string;
  produit: { idProduit: number; nom: string };
  boutique: { idBoutique: number; nom: string };
  employe: { idEmploye: number; nom: string; prenom: string };
  idCommande: number | null;
}
