import { BoutiqueAffectation, PosteEmploye, RoleEmploye } from './auth.model';

export interface Employe {
  idEmploye: number;
  nom: string;
  prenom: string;
  email: string;
  role: RoleEmploye;
  actif: boolean;
  boutiques: BoutiqueAffectation[];
}

export interface CreateEmployePayload {
  nom: string;
  prenom: string;
  email: string;
  motDePasse: string;
  role: RoleEmploye;
}

export interface AffecterEmployePayload {
  idEmploye: number;
  posteEmploye: PosteEmploye;
}
