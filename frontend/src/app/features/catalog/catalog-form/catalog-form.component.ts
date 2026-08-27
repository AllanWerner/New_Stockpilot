import { Component, OnInit, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MAT_DIALOG_DATA, MatDialog, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatSelectModule } from '@angular/material/select';
import { AuthService } from '../../../core/auth/auth.service';
import { Categorie, Produit } from '../../../core/models/produit.model';
import { CategorieFormComponent } from '../categorie-form/categorie-form.component';
import { CategorieService } from '../categorie.service';
import { CatalogService } from '../catalog.service';

export interface CatalogFormDialogData {
  idBoutique: number | null;
}

@Component({
  selector: 'sp-catalog-form',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatDialogModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatIconModule,
    MatButtonModule,
    MatProgressSpinnerModule,
  ],
  templateUrl: './catalog-form.component.html',
  styleUrl: './catalog-form.component.scss',
})
export class CatalogFormComponent implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly catalogService = inject(CatalogService);
  private readonly categorieService = inject(CategorieService);
  private readonly authService = inject(AuthService);
  private readonly dialog = inject(MatDialog);
  private readonly dialogRef = inject(MatDialogRef<CatalogFormComponent>);
  private readonly data = inject<CatalogFormDialogData>(MAT_DIALOG_DATA, { optional: true });

  readonly loading = signal(false);
  readonly errorMessage = signal<string | null>(null);
  readonly idBoutique = this.data?.idBoutique ?? null;
  readonly categories = signal<Categorie[]>([]);
  readonly isGerant = this.authService.isGerant;

  // Only a gérant can pick between the current boutique / no boutique / all
  // boutiques — a RESPONSABLE always assigns to their own boutique context,
  // matching the previous (pre-choice) behavior, so the mode is fixed for
  // them and the picker itself stays hidden.
  readonly modeAssignation = signal<'aucune' | 'boutique' | 'toutes'>(
    this.idBoutique !== null ? 'boutique' : 'aucune',
  );

  readonly form = this.fb.nonNullable.group({
    nom: ['', Validators.required],
    prixAchat: ['0.00', [Validators.required, Validators.pattern(/^\d+(\.\d{1,2})?$/)]],
    unite: ['piece', Validators.required],
    idCategorie: [null as number | null, Validators.required],
    codeBarre: [''],
    seuilReappro: [0, [Validators.required, Validators.min(0)]],
  });

  ngOnInit(): void {
    this.categorieService.list().subscribe((categories) => this.categories.set(categories));
  }

  ouvrirNouvelleCategorie(): void {
    const ref = this.dialog.open(CategorieFormComponent, { width: '420px' });

    ref.afterClosed().subscribe((categorie: Categorie | undefined) => {
      if (categorie) {
        this.categories.update((liste) => [...liste, categorie]);
        this.form.patchValue({ idCategorie: categorie.idCategorie });
      }
    });
  }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();

      return;
    }

    this.loading.set(true);
    this.errorMessage.set(null);
    const raw = this.form.getRawValue();
    const mode = this.modeAssignation();

    this.catalogService
      .create({
        nom: raw.nom,
        prixAchat: raw.prixAchat,
        unite: raw.unite,
        idCategorie: raw.idCategorie!,
        codeBarre: raw.codeBarre || undefined,
        idBoutique: mode === 'boutique' ? (this.idBoutique ?? undefined) : undefined,
        toutesBoutiques: mode === 'toutes',
        seuilReappro: mode !== 'aucune' ? raw.seuilReappro : undefined,
      })
      .subscribe({
        next: (produit: Produit) => {
          this.loading.set(false);
          this.dialogRef.close(produit);
        },
        error: (err: unknown) => {
          this.loading.set(false);
          const status = err instanceof Object && 'status' in err ? (err as { status: number }).status : 0;
          this.errorMessage.set(
            status === 409
              ? 'Un produit avec ce code-barres existe déjà.'
              : status === 404
                ? 'Catégorie introuvable.'
                : 'Impossible de créer ce produit.',
          );
        },
      });
  }
}
