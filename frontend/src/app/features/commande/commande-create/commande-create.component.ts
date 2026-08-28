import { Component, OnInit, inject, signal } from '@angular/core';
import { FormArray, FormBuilder, FormGroup, FormsModule, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatAutocompleteModule } from '@angular/material/autocomplete';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatSelectModule } from '@angular/material/select';
import { Router, RouterLink } from '@angular/router';
import { BoutiqueContextService } from '../../../core/boutique/boutique-context.service';
import { Commande } from '../../../core/models/commande.model';
import { Fournisseur } from '../../../core/models/fournisseur.model';
import { Produit } from '../../../core/models/produit.model';
import { CatalogService } from '../../catalog/catalog.service';
import { CommandeService } from '../commande.service';
import { FournisseurFormComponent } from '../fournisseur-form/fournisseur-form.component';
import { FournisseurService } from '../fournisseur.service';

interface LigneFormValue {
  idProduit: number;
  nom: string;
  quantiteCommandee: number;
  prixUnitaire: string;
}

@Component({
  selector: 'sp-commande-create',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    FormsModule,
    RouterLink,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatAutocompleteModule,
    MatButtonModule,
    MatIconModule,
    MatDialogModule,
    MatProgressSpinnerModule,
  ],
  templateUrl: './commande-create.component.html',
  styleUrl: './commande-create.component.scss',
})
export class CommandeCreateComponent implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly commandeService = inject(CommandeService);
  private readonly fournisseurService = inject(FournisseurService);
  private readonly catalogService = inject(CatalogService);
  private readonly boutiqueContext = inject(BoutiqueContextService);
  private readonly router = inject(Router);
  private readonly dialog = inject(MatDialog);

  readonly idBoutique = this.boutiqueContext.selectedId();
  readonly fournisseurs = signal<Fournisseur[]>([]);
  readonly resultatsRecherche = signal<Produit[]>([]);
  readonly loadingSeuils = signal(false);
  readonly submitting = signal(false);
  readonly errorMessage = signal<string | null>(null);
  readonly searchTerm = signal('');

  readonly form = this.fb.group({
    idFournisseur: [null as number | null, Validators.required],
    datePrevue: [''],
    lignes: this.fb.array<FormGroup>([]),
  });

  get lignes(): FormArray<FormGroup> {
    return this.form.get('lignes') as FormArray<FormGroup>;
  }

  ngOnInit(): void {
    if (this.idBoutique === null) {
      this.router.navigate(['/commandes']);

      return;
    }

    this.fournisseurService.list().subscribe((fournisseurs) => this.fournisseurs.set(fournisseurs));
  }

  prefillDepuisSeuils(): void {
    if (this.idBoutique === null) {
      return;
    }

    this.loadingSeuils.set(true);
    this.commandeService.produitsSousSeuil(this.idBoutique).subscribe({
      next: (produits) => {
        for (const produit of produits) {
          this.ajouterLigne({
            idProduit: produit.idProduit,
            nom: produit.nom,
            quantiteCommandee: produit.quantiteRecommandee,
            prixUnitaire: produit.prixAchat,
          });
        }

        this.loadingSeuils.set(false);
      },
      error: () => this.loadingSeuils.set(false),
    });
  }

  rechercherProduit(): void {
    if (this.idBoutique === null || this.searchTerm().trim().length < 2) {
      this.resultatsRecherche.set([]);

      return;
    }

    this.catalogService.list({ nom: this.searchTerm(), idBoutique: this.idBoutique }).subscribe((res) => {
      this.resultatsRecherche.set(res.items);
    });
  }

  ajouterProduit(produit: Produit): void {
    this.ajouterLigne({
      idProduit: produit.idProduit,
      nom: produit.nom,
      quantiteCommandee: 1,
      prixUnitaire: produit.prixAchat,
    });
    this.searchTerm.set('');
    this.resultatsRecherche.set([]);
  }

  private ajouterLigne(valeur: LigneFormValue): void {
    const dejaPresente = this.lignes.controls.some((g) => g.value.idProduit === valeur.idProduit);

    if (dejaPresente) {
      return;
    }

    this.lignes.push(
      this.fb.group({
        idProduit: [valeur.idProduit, Validators.required],
        nom: [valeur.nom],
        quantiteCommandee: [valeur.quantiteCommandee, [Validators.required, Validators.min(1)]],
        prixUnitaire: [valeur.prixUnitaire, [Validators.required, Validators.pattern(/^\d+(\.\d{1,2})?$/)]],
      }),
    );
  }

  supprimerLigne(index: number): void {
    this.lignes.removeAt(index);
  }

  sousTotal(index: number): string {
    const raw = this.lignes.at(index).value as LigneFormValue;
    const total = Number(raw.quantiteCommandee || 0) * Number(raw.prixUnitaire || 0);

    return total.toFixed(2);
  }

  totalGeneral(): string {
    const total = this.lignes.controls.reduce((acc, _, index) => acc + Number(this.sousTotal(index)), 0);

    return total.toFixed(2);
  }

  ouvrirNouveauFournisseur(): void {
    const ref = this.dialog.open(FournisseurFormComponent, { width: '420px' });

    ref.afterClosed().subscribe((fournisseur: Fournisseur | undefined) => {
      if (fournisseur) {
        this.fournisseurs.update((liste) => [...liste, fournisseur]);
        this.form.patchValue({ idFournisseur: fournisseur.idFournisseur });
      }
    });
  }

  submit(): void {
    if (this.idBoutique === null || this.form.invalid || this.lignes.length === 0) {
      this.form.markAllAsTouched();

      return;
    }

    this.submitting.set(true);
    this.errorMessage.set(null);
    const raw = this.form.getRawValue();

    this.commandeService
      .create({
        idBoutique: this.idBoutique,
        idFournisseur: raw.idFournisseur!,
        datePrevue: raw.datePrevue || undefined,
        lignes: (raw.lignes as LigneFormValue[]).map((l) => ({
          idProduit: l.idProduit,
          quantiteCommandee: Number(l.quantiteCommandee),
          prixUnitaire: l.prixUnitaire,
        })),
      })
      .subscribe({
        next: (commande: Commande) => {
          this.submitting.set(false);
          this.router.navigate(['/commandes', commande.idCommande]);
        },
        error: () => {
          this.submitting.set(false);
          this.errorMessage.set("Impossible d'envoyer cette commande.");
        },
      });
  }
}
