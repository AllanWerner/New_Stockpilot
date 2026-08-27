import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { of, throwError } from 'rxjs';
import { AuthService } from '../../../core/auth/auth.service';
import { TokenResponse } from '../../../core/models/auth.model';
import { LoginComponent } from './login.component';

describe('LoginComponent', () => {
  let fixture: ComponentFixture<LoginComponent>;
  let component: LoginComponent;
  let authService: AuthService;
  let router: Router;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [LoginComponent],
      providers: [provideHttpClient(), provideHttpClientTesting(), provideRouter([])],
    }).compileComponents();

    fixture = TestBed.createComponent(LoginComponent);
    component = fixture.componentInstance;
    authService = TestBed.inject(AuthService);
    router = TestBed.inject(Router);
  });

  it('does not call AuthService.login when the form is invalid', () => {
    spyOn(authService, 'login');

    component.submit();

    expect(authService.login).not.toHaveBeenCalled();
  });

  it('navigates to /dashboard on successful login', () => {
    const response: TokenResponse = { token: 't', idEmploye: 1, nom: 'Werner', prenom: 'Allan', role: 'GERANT' };
    spyOn(authService, 'login').and.returnValue(of(response));
    spyOn(router, 'navigate');

    component.form.setValue({ email: 'gerant@stockpilot.test', motDePasse: 'password123' });
    component.submit();

    expect(router.navigate).toHaveBeenCalledWith(['/dashboard']);
    expect(component.errorMessage()).toBeNull();
  });

  it('shows an error message on 401', () => {
    spyOn(authService, 'login').and.returnValue(throwError(() => ({ status: 401 })));

    component.form.setValue({ email: 'gerant@stockpilot.test', motDePasse: 'wrong' });
    component.submit();

    expect(component.errorMessage()).toBe('Email ou mot de passe incorrect.');
  });
});
