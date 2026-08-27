import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { environment } from '../../../environments/environment';
import { Boutique } from '../models/boutique.model';
import { BoutiqueContextService } from './boutique-context.service';

describe('BoutiqueContextService', () => {
  let service: BoutiqueContextService;
  let httpMock: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });

    service = TestBed.inject(BoutiqueContextService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => httpMock.verify());

  it('starts with no boutiques and a null (consolidated) selection', () => {
    expect(service.boutiques()).toEqual([]);
    expect(service.selectedId()).toBeNull();
  });

  it('auto-selects the single boutique when the employee has only one', () => {
    const boutiques: Boutique[] = [
      { idBoutique: 5, nom: 'Centre-ville', adresse: '1 rue Test', ville: 'Lyon', actif: true },
    ];

    service.charger().subscribe();
    httpMock.expectOne(`${environment.apiUrl}/boutiques`).flush(boutiques);

    expect(service.selectedId()).toBe(5);
    expect(service.selectedBoutique()?.nom).toBe('Centre-ville');
  });

  it('leaves the selection at null (consolidated) when there are several boutiques', () => {
    const boutiques: Boutique[] = [
      { idBoutique: 1, nom: 'Centre-ville', adresse: '1 rue Test', ville: 'Lyon', actif: true },
      { idBoutique: 2, nom: 'Rive gauche', adresse: '2 rue Test', ville: 'Lyon', actif: true },
    ];

    service.charger().subscribe();
    httpMock.expectOne(`${environment.apiUrl}/boutiques`).flush(boutiques);

    expect(service.selectedId()).toBeNull();
  });

  it('filters out inactive boutiques and auto-selects the remaining single active one', () => {
    const boutiques: Boutique[] = [
      { idBoutique: 1, nom: 'Centre-ville', adresse: '1 rue Test', ville: 'Lyon', actif: true },
      { idBoutique: 2, nom: 'Marché Couvert (fermé)', adresse: '2 rue Test', ville: 'Lyon', actif: false },
    ];

    service.charger().subscribe();
    httpMock.expectOne(`${environment.apiUrl}/boutiques`).flush(boutiques);

    expect(service.boutiques()).toEqual([boutiques[0]]);
    expect(service.selectedId()).toBe(1);
  });

  it('updates the selection when selectionner() is called', () => {
    service.selectionner(7);

    expect(service.selectedId()).toBe(7);
  });
});
