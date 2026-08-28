import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import {
  Commande,
  CommandeResume,
  CreateCommandePayload,
  ProduitSousSeuil,
  ReceptionCommandePayload,
} from '../../core/models/commande.model';

@Injectable({ providedIn: 'root' })
export class CommandeService {
  constructor(private readonly http: HttpClient) {}

  produitsSousSeuil(idBoutique: number): Observable<ProduitSousSeuil[]> {
    return this.http.get<ProduitSousSeuil[]>(`${environment.apiUrl}/boutiques/${idBoutique}/produits-sous-seuil`);
  }

  list(idBoutique: number): Observable<CommandeResume[]> {
    const params = new HttpParams().set('idBoutique', String(idBoutique));

    return this.http.get<CommandeResume[]>(`${environment.apiUrl}/commandes`, { params });
  }

  get(idCommande: number): Observable<Commande> {
    return this.http.get<Commande>(`${environment.apiUrl}/commandes/${idCommande}`);
  }

  create(payload: CreateCommandePayload): Observable<Commande> {
    return this.http.post<Commande>(`${environment.apiUrl}/commandes`, payload);
  }

  receptionner(idCommande: number, payload: ReceptionCommandePayload): Observable<Commande> {
    return this.http.post<Commande>(`${environment.apiUrl}/commandes/${idCommande}/reception`, payload);
  }
}
