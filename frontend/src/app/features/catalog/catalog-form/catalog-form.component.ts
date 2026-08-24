import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { Produit } from '../../../core/models/produit.model';
import { CatalogService } from '../catalog.service';

@Component({
  selector: 'sp-catalog-form',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatDialogModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatProgressSpinnerModule,
  ],
  templateUrl: './catalog-form.component.html',
  styleUrl: './catalog-form.component.scss',
})
export class CatalogFormComponent {
  private readonly fb = inject(FormBuilder);
  private readonly catalogService = inject(CatalogService);
  private readonly dialogRef = inject(MatDialogRef<CatalogFormComponent>);

  readonly loading = signal(false);
  readonly errorMessage = signal<string | null>(null);

  readonly form = this.fb.nonNullable.group({
    nom: ['', Validators.required],
    prixAchat: ['0.00', [Validators.required, Validators.pattern(/^\d+(\.\d{1,2})?$/)]],
    unite: ['piece', Validators.required],
    idCategorie: [1, [Validators.required, Validators.min(1)]],
    codeBarre: [''],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();

      return;
    }

    this.loading.set(true);
    this.errorMessage.set(null);
    const raw = this.form.getRawValue();

    this.catalogService
      .create({
        nom: raw.nom,
        prixAchat: raw.prixAchat,
        unite: raw.unite,
        idCategorie: raw.idCategorie,
        codeBarre: raw.codeBarre || undefined,
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
