import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Mouvement } from '../../core/models/mouvement.model';

@Injectable({ providedIn: 'root' })
export class MouvementService {
  constructor(private readonly http: HttpClient) {}

  list(idBoutique?: number): Observable<Mouvement[]> {
    let params = new HttpParams();

    if (idBoutique !== undefined) {
      params = params.set('idBoutique', String(idBoutique));
    }

    return this.http.get<Mouvement[]>(`${environment.apiUrl}/mouvements`, { params });
  }
}
