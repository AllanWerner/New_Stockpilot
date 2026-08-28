import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Categorie, CreateCategoriePayload } from '../../core/models/produit.model';

@Injectable({ providedIn: 'root' })
export class CategorieService {
  constructor(private readonly http: HttpClient) {}

  list(): Observable<Categorie[]> {
    return this.http.get<Categorie[]>(`${environment.apiUrl}/categories`);
  }

  create(payload: CreateCategoriePayload): Observable<Categorie> {
    return this.http.post<Categorie>(`${environment.apiUrl}/categories`, payload);
  }
}
