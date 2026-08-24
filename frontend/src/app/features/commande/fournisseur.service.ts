import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { CreateFournisseurPayload, Fournisseur } from '../../core/models/fournisseur.model';

@Injectable({ providedIn: 'root' })
export class FournisseurService {
  constructor(private readonly http: HttpClient) {}

  list(): Observable<Fournisseur[]> {
    return this.http.get<Fournisseur[]>(`${environment.apiUrl}/fournisseurs`);
  }

  create(payload: CreateFournisseurPayload): Observable<Fournisseur> {
    return this.http.post<Fournisseur>(`${environment.apiUrl}/fournisseurs`, payload);
  }
}
