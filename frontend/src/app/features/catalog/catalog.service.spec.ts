import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { environment } from '../../../environments/environment';
import { CatalogService } from './catalog.service';

describe('CatalogService', () => {
  let service: CatalogService;
  let httpMock: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });

    service = TestBed.inject(CatalogService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => httpMock.verify());

  it('sends only the provided filters as query params', () => {
    service.list({ nom: 'Farine', idBoutique: 2 }).subscribe();

    const req = httpMock.expectOne(
      (r) => r.url === `${environment.apiUrl}/produits` && r.params.get('nom') === 'Farine' && r.params.get('idBoutique') === '2',
    );
    expect(req.request.method).toBe('GET');
    req.flush({ items: [], page: 1, limit: 20 });
  });

  it('omits undefined filters from the query string', () => {
    service.list({}).subscribe();

    const req = httpMock.expectOne(`${environment.apiUrl}/produits`);
    expect(req.request.params.keys().length).toBe(0);
    req.flush({ items: [], page: 1, limit: 20 });
  });

  it('posts to /produits/scan for a barcode scan', () => {
    service.scan({ codeBarre: '1234567890123' }).subscribe();

    const req = httpMock.expectOne(`${environment.apiUrl}/produits/scan`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual({ codeBarre: '1234567890123' });
    req.flush({});
  });
});
