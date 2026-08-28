import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import {
  AjustementStockPayload,
  CreateProduitPayload,
  Produit,
  ProduitFilters,
  ProduitListResponse,
  ScanProduitPayload,
} from '../../core/models/produit.model';

@Injectable({ providedIn: 'root' })
export class CatalogService {
  constructor(private readonly http: HttpClient) {}

  list(filters: ProduitFilters): Observable<ProduitListResponse> {
    let params = new HttpParams();

    for (const [key, value] of Object.entries(filters)) {
      if (value !== undefined && value !== null && value !== '') {
        params = params.set(key, String(value));
      }
    }

    return this.http.get<ProduitListResponse>(`${environment.apiUrl}/produits`, { params });
  }

  create(payload: CreateProduitPayload): Observable<Produit> {
    return this.http.post<Produit>(`${environment.apiUrl}/produits`, payload);
  }

  scan(payload: ScanProduitPayload): Observable<Produit> {
    return this.http.post<Produit>(`${environment.apiUrl}/produits/scan`, payload);
  }

  ajuster(idProduit: number, payload: AjustementStockPayload): Observable<Produit> {
    return this.http.post<Produit>(`${environment.apiUrl}/produits/${idProduit}/ajustement`, payload);
  }
}
