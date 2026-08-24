import { TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { authGuard } from './auth.guard';
import { AuthService } from './auth.service';

describe('authGuard', () => {
  let authService: AuthService;
  let router: Router;

  beforeEach(() => {
    sessionStorage.clear();

    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting(), provideRouter([])],
    });

    authService = TestBed.inject(AuthService);
    router = TestBed.inject(Router);
  });

  afterEach(() => sessionStorage.clear());

  it('allows activation when a token is present', () => {
    sessionStorage.setItem('stockpilot_token', 'jwt-token');

    const result = TestBed.runInInjectionContext(() => authGuard({} as never, {} as never));

    expect(result).toBeTrue();
  });

  it('redirects to /login when no token is present', () => {
    const result = TestBed.runInInjectionContext(() => authGuard({} as never, {} as never));

    expect(result).toEqual(router.parseUrl('/login'));
  });

  it('does not call AuthService.logout just to check activation', () => {
    spyOn(authService, 'logout');

    TestBed.runInInjectionContext(() => authGuard({} as never, {} as never));

    expect(authService.logout).not.toHaveBeenCalled();
  });
});
