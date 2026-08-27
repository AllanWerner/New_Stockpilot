import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Boutique, CreateBoutiquePayload } from '../../core/models/boutique.model';
import { AffecterEmployePayload, CreateEmployePayload, Employe } from '../../core/models/employe.model';

@Injectable({ providedIn: 'root' })
export class OrganisationService {
  constructor(private readonly http: HttpClient) {}

  listEmployes(): Observable<Employe[]> {
    return this.http.get<Employe[]>(`${environment.apiUrl}/employes`);
  }

  createEmploye(payload: CreateEmployePayload): Observable<Employe> {
    return this.http.post<Employe>(`${environment.apiUrl}/employes`, payload);
  }

  activerEmploye(idEmploye: number): Observable<Employe> {
    return this.http.post<Employe>(`${environment.apiUrl}/employes/${idEmploye}/activer`, {});
  }

  desactiverEmploye(idEmploye: number): Observable<Employe> {
    return this.http.post<Employe>(`${environment.apiUrl}/employes/${idEmploye}/desactiver`, {});
  }

  supprimerEmploye(idEmploye: number): Observable<void> {
    return this.http.delete<void>(`${environment.apiUrl}/employes/${idEmploye}`);
  }

  listBoutiques(): Observable<Boutique[]> {
    return this.http.get<Boutique[]>(`${environment.apiUrl}/boutiques`);
  }

  createBoutique(payload: CreateBoutiquePayload): Observable<Boutique> {
    return this.http.post<Boutique>(`${environment.apiUrl}/boutiques`, payload);
  }

  affecterEmploye(idBoutique: number, payload: AffecterEmployePayload): Observable<unknown> {
    return this.http.post(`${environment.apiUrl}/boutiques/${idBoutique}/affecter`, payload);
  }

  activerBoutique(idBoutique: number): Observable<Boutique> {
    return this.http.post<Boutique>(`${environment.apiUrl}/boutiques/${idBoutique}/activer`, {});
  }

  desactiverBoutique(idBoutique: number): Observable<Boutique> {
    return this.http.post<Boutique>(`${environment.apiUrl}/boutiques/${idBoutique}/desactiver`, {});
  }

  supprimerBoutique(idBoutique: number): Observable<void> {
    return this.http.delete<void>(`${environment.apiUrl}/boutiques/${idBoutique}`);
  }
}
