import { HttpClient, provideHttpClient, withInterceptors } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { AuthService } from '../auth/auth.service';
import { jwtInterceptor } from './jwt.interceptor';

describe('jwtInterceptor', () => {
  let http: HttpClient;
  let httpMock: HttpTestingController;
  let authService: AuthService;

  beforeEach(() => {
    sessionStorage.clear();

    TestBed.configureTestingModule({
      providers: [
        provideHttpClient(withInterceptors([jwtInterceptor])),
        provideHttpClientTesting(),
        provideRouter([]),
      ],
    });

    http = TestBed.inject(HttpClient);
    httpMock = TestBed.inject(HttpTestingController);
    authService = TestBed.inject(AuthService);
  });

  afterEach(() => {
    httpMock.verify();
    sessionStorage.clear();
  });

  it('attaches the bearer token when one is stored', () => {
    sessionStorage.setItem('stockpilot_token', 'jwt-token');

    http.get('/api/produits').subscribe();

    const req = httpMock.expectOne('/api/produits');
    expect(req.request.headers.get('Authorization')).toBe('Bearer jwt-token');
    req.flush({});
  });

  it('sends no Authorization header when no token is stored', () => {
    http.get('/api/produits').subscribe();

    const req = httpMock.expectOne('/api/produits');
    expect(req.request.headers.has('Authorization')).toBeFalse();
    req.flush({});
  });

  it('logs out on a 401 response', () => {
    sessionStorage.setItem('stockpilot_token', 'jwt-token');
    spyOn(authService, 'logout');

    http.get('/api/produits').subscribe({ error: () => undefined });

    const req = httpMock.expectOne('/api/produits');
    req.flush({ error: 'Authentification requise.' }, { status: 401, statusText: 'Unauthorized' });

    expect(authService.logout).toHaveBeenCalled();
  });
});
