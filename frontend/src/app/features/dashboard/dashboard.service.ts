import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Dashboard } from '../../core/models/dashboard.model';

@Injectable({ providedIn: 'root' })
export class DashboardService {
  constructor(private readonly http: HttpClient) {}

  get(idBoutique: number | null): Observable<Dashboard> {
    let params = new HttpParams();

    if (idBoutique !== null) {
      params = params.set('idBoutique', String(idBoutique));
    }

    return this.http.get<Dashboard>(`${environment.apiUrl}/dashboard`, { params });
  }
}
