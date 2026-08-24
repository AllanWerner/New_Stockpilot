import { HttpClient } from '@angular/common/http';
import { Injectable, inject, signal } from '@angular/core';
import { Observable, tap } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Notification } from '../models/notification.model';

@Injectable({ providedIn: 'root' })
export class NotificationService {
  private readonly http = inject(HttpClient);
  private readonly compteNonLuesSignal = signal(0);
  readonly compteNonLues = this.compteNonLuesSignal.asReadonly();

  rafraichirCompte(): void {
    this.http
      .get<{ compte: number }>(`${environment.apiUrl}/notifications/non-lues/compte`)
      .subscribe((res) => this.compteNonLuesSignal.set(res.compte));
  }

  list(): Observable<Notification[]> {
    return this.http.get<Notification[]>(`${environment.apiUrl}/notifications`);
  }

  marquerLue(idNotification: number): Observable<Notification> {
    return this.http.post<Notification>(`${environment.apiUrl}/notifications/${idNotification}/lue`, {}).pipe(
      tap(() => this.rafraichirCompte()),
    );
  }

  toutMarquerLu(): Observable<{ compte: number }> {
    return this.http.post<{ compte: number }>(`${environment.apiUrl}/notifications/tout-marquer-lu`, {}).pipe(
      tap((res) => this.compteNonLuesSignal.set(res.compte)),
    );
  }
}
