import { HttpClient } from '@angular/common/http';
import { Injectable, computed, signal } from '@angular/core';
import { Router } from '@angular/router';
import { Observable, tap } from 'rxjs';
import { environment } from '../../../environments/environment';
import { MeResponse, TokenResponse } from '../models/auth.model';

const TOKEN_KEY = 'stockpilot_token';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly currentUserSignal = signal<MeResponse | null>(null);
  readonly currentUser = this.currentUserSignal.asReadonly();
  readonly isGerant = computed(() => this.currentUserSignal()?.role === 'GERANT');

  // A "salesperson-only" account: an EMPLOYE whose every affectation is
  // VENDEUR (never RESPONSABLE) — VENDEUR isn't a global role, it's a
  // per-boutique poste on Affectation, so this has to be derived here.
  readonly isVendeurSeul = computed(() => {
    const user = this.currentUserSignal();

    return (
      !!user &&
      user.role === 'EMPLOYE' &&
      user.boutiques.length > 0 &&
      user.boutiques.every((b) => b.poste === 'VENDEUR')
    );
  });

  constructor(
    private readonly http: HttpClient,
    private readonly router: Router,
  ) {}

  login(email: string, motDePasse: string): Observable<TokenResponse> {
    return this.http.post<TokenResponse>(`${environment.apiUrl}/auth/login`, { email, motDePasse }).pipe(
      tap((res) => sessionStorage.setItem(TOKEN_KEY, res.token)),
    );
  }

  chargerProfil(): Observable<MeResponse> {
    return this.http.get<MeResponse>(`${environment.apiUrl}/auth/me`).pipe(
      tap((me) => this.currentUserSignal.set(me)),
    );
  }

  modifierCompte(payload: {
    motDePasseActuel: string;
    email?: string;
    nouveauMotDePasse?: string;
  }): Observable<MeResponse> {
    return this.http.post<MeResponse>(`${environment.apiUrl}/auth/me/modifier`, payload).pipe(
      tap((me) => this.currentUserSignal.set(me)),
    );
  }

  logout(): void {
    sessionStorage.removeItem(TOKEN_KEY);
    this.currentUserSignal.set(null);
    void this.router.navigate(['/login']);
  }

  getToken(): string | null {
    return sessionStorage.getItem(TOKEN_KEY);
  }

  isAuthenticated(): boolean {
    return this.getToken() !== null;
  }
}
