export type RoleEmploye = 'GERANT' | 'EMPLOYE';
export type PosteEmploye = 'RESPONSABLE' | 'VENDEUR';

export interface TokenResponse {
  token: string;
  idEmploye: number;
  nom: string;
  prenom: string;
  role: RoleEmploye;
}

export interface BoutiqueAffectation {
  idBoutique: number;
  nomBoutique: string;
  poste: PosteEmploye;
}

export interface MeResponse {
  idEmploye: number;
  nom: string;
  prenom: string;
  email: string;
  role: RoleEmploye;
  boutiques: BoutiqueAffectation[];
}
